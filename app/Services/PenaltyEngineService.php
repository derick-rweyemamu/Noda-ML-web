<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Penalty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PenaltyEngineService
{
    public function __construct(
        private LoanCalculatorService $calculator,
        private NotificationService $notifications
    ) {}

    public function runDailySweep(?Carbon $now = null): int
    {
        $now ??= now();
        $settings = AdminSetting::current();
        $existingKeys = Penalty::pluck('due_date_key')->flip();
        $appliedCount = 0;

        $loans = Loan::with(['member', 'group.members', 'payments'])
            ->where('status', '!=', 'completed')
            ->get();

        foreach ($loans as $loan) {
            $metrics = $this->calculator->computeMetrics($loan, $now, $settings);
            if (! $metrics['is_overdue']) {
                continue;
            }

            $missedWeek = $metrics['weeks_paid'] + 1;
            $dueDateKey = match ($settings->penalty_rule) {
                'per_day_late' => "{$loan->id}-day-{$now->format('Y-m-d')}",
                'escalating' => "{$loan->id}-esc-week-{$missedWeek}",
                default => "{$loan->id}-week-{$missedWeek}",
            };

            if ($existingKeys->has($dueDateKey)) {
                continue;
            }
            $existingKeys->put($dueDateKey, true);

            $group = $loan->group;
            if (! $group || $group->members->isEmpty()) {
                continue;
            }

            $baseAmount = (float) $settings->penalty_amount_per_member;
            $perMember = $settings->penalty_rule === 'escalating'
                ? $this->calculator->round2($baseAmount * pow(1.25, Penalty::where('triggered_by_loan_id', $loan->id)->count()))
                : $this->calculator->round2($baseAmount);

            $totalPenalty = $this->calculator->round2($perMember * $group->members->count());

            DB::transaction(function () use ($group, $loan, $perMember, $totalPenalty, $now, $dueDateKey, $missedWeek, $settings, &$appliedCount) {
                $penalty = Penalty::create([
                    'group_id' => $group->id,
                    'triggered_by_loan_id' => $loan->id,
                    'member_id' => $loan->member_id,
                    'amount_per_member' => $perMember,
                    'total_penalty' => $totalPenalty,
                    'applied_at' => $now,
                    'due_date_key' => $dueDateKey,
                    'reason' => "Late payment on Week {$missedWeek} by {$loan->member->full_name} ({$settings->penalty_rule})",
                ]);

                $group->increment('penalty_balance', $totalPenalty);
                foreach ($group->members as $gm) {
                    $gm->increment('penalty_balance', $perMember);
                }

                $loan->update(['status' => 'overdue']);
                $this->notifications->notifyGroupPenalty($group->fresh('members'), $penalty, $loan->member);
                $appliedCount++;
            });
        }

        return $appliedCount;
    }

    public function applyManualPenalty(
        User $actor,
        Group $group,
        ?Member $lateMember,
        ?Loan $loan,
        string $reason,
        ?Carbon $now = null
    ): Penalty {
        $now ??= now();
        $settings = AdminSetting::current();
        $group->load(['members', 'loans.payments']);

        $unpaidLoans = $group->loans->filter(function (Loan $l) use ($now, $settings) {
            if ($l->status === 'completed') {
                return false;
            }
            return $this->calculator->computeMetrics($l, $now, $settings)['remaining_loan_balance'] > 0.0;
        });

        if ($unpaidLoans->isEmpty()) {
            throw new \DomainException("Cannot apply penalty: all members in {$group->name} are paid.");
        }

        $overdueLoans = $unpaidLoans->filter(
            fn (Loan $l) => $this->calculator->computeMetrics($l, $now, $settings)['is_overdue']
        );

        if ($overdueLoans->isEmpty()) {
            $cutoff = sprintf('%02d:%02d', $settings->penalty_cutoff_hour, $settings->penalty_cutoff_minute);
            throw new \DomainException("Cannot apply penalty yet: no member in {$group->name} has passed the {$cutoff} cutoff on their return date.");
        }

        $perMember = $this->calculator->round2((float) $settings->penalty_amount_per_member);
        $totalPenalty = $this->calculator->round2($perMember * $group->members->count());

        return DB::transaction(function () use ($actor, $group, $lateMember, $loan, $overdueLoans, $perMember, $totalPenalty, $reason, $now) {
            $targetLoan = $loan ?? $overdueLoans->first();
            $targetMember = $lateMember ?? $targetLoan?->member ?? $group->members->first();

            $penalty = Penalty::create([
                'group_id' => $group->id,
                'triggered_by_loan_id' => $targetLoan?->id,
                'member_id' => $targetMember?->id,
                'amount_per_member' => $perMember,
                'total_penalty' => $totalPenalty,
                'applied_at' => $now,
                'due_date_key' => 'manual-' . uniqid(),
                'reason' => trim($reason) !== '' ? $reason : "Manual late penalty for {$group->name}",
            ]);

            $group->increment('penalty_balance', $totalPenalty);
            foreach ($group->members as $gm) {
                $gm->increment('penalty_balance', $perMember);
            }

            $this->notifications->notifyGroupPenalty($group->fresh('members'), $penalty, $targetMember);
            AuditService::log($actor, 'APPLY_PENALTY', $group->name, "TZS {$perMember}/member (TZS {$totalPenalty} total) — {$penalty->reason}");

            return $penalty;
        });
    }
}
