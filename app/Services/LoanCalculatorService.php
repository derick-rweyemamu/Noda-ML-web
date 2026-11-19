<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Loan;
use Carbon\Carbon;

class LoanCalculatorService
{
    public function round2(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }

    public function calculateTotalRepayable(float $principal, float $loadRate = 0.17): float
    {
        return $this->round2($principal + ($principal * $loadRate));
    }

    public function calculateWeeklyAmount(float $totalRepayable, int $termWeeks): float
    {
        return $this->round2($totalRepayable / max(1, $termWeeks));
    }

    public function buildSchedule(
        float $principal,
        float $loadRate,
        int $termWeeks,
        Carbon $firstReturnDate
    ): array {
        $total = $this->calculateTotalRepayable($principal, $loadRate);
        $weekly = $this->calculateWeeklyAmount($total, $termWeeks);
        $schedule = [];

        for ($week = 1; $week <= $termWeeks; $week++) {
            $amount = $week < $termWeeks
                ? $weekly
                : $this->round2($total - ($weekly * ($termWeeks - 1)));

            $schedule[] = [
                'week_number' => $week,
                'due_date' => $firstReturnDate->copy()->addWeeks($week - 1),
                'amount' => $amount,
            ];
        }

        return $schedule;
    }

    public function computeCutoff(Carbon $dueDate, int $hour = 10, int $minute = 30): Carbon
    {
        return $dueDate->copy()->setTime($hour, $minute, 0);
    }

    public function computeMetrics(
        Loan $loan,
        ?Carbon $now = null,
        ?AdminSetting $settings = null
    ): array {
        $now ??= now();
        $settings ??= AdminSetting::current();

        $loan->loadMissing(['payments', 'member']);
        $paid = $this->round2((float) $loan->payments->sum('amount'));
        $remainingLoan = $this->round2(max(0.0, $loan->total_repayable - $paid));
        $memberPenalty = $this->round2((float) ($loan->member?->penalty_balance ?? 0.0));
        $totalRemaining = $this->round2($remainingLoan + $memberPenalty);

        $firstReturn = Carbon::parse($loan->first_return_date);
        $schedule = $this->buildSchedule(
            (float) $loan->principal,
            (float) $loan->load_rate,
            (int) $loan->term_weeks,
            $firstReturn
        );

        $cumulative = 0.0;
        $weeksPaid = 0;
        foreach ($schedule as $inst) {
            $cumulative = $this->round2($cumulative + $inst['amount']);
            if ($paid + 0.001 >= $cumulative) {
                $weeksPaid++;
            } else {
                break;
            }
        }
        $weeksPaid = min($weeksPaid, (int) $loan->term_weeks);

        $weeksElapsed = $now->lt($firstReturn)
            ? 0
            : ((int) floor($firstReturn->diffInDays($now) / 7)) + 1;

        $isCompleted = $remainingLoan <= 0.0;
        $nextDueDate = ($isCompleted || $weeksPaid >= $loan->term_weeks)
            ? null
            : $firstReturn->copy()->addWeeks($weeksPaid);

        $amountDueThisWeek = 0.0;
        if (! $isCompleted) {
            $dueWeeksCount = min(max($weeksElapsed, $weeksPaid + 1), (int) $loan->term_weeks);
            $expected = 0.0;
            for ($i = 0; $i < $dueWeeksCount; $i++) {
                $expected = $this->round2($expected + $schedule[$i]['amount']);
            }
            $amountDueThisWeek = $this->round2(max(0.0, $expected - $paid));
        }

        $weeksRemaining = max(0, (int) $loan->term_weeks - $weeksPaid);
        $isOverdue = $nextDueDate !== null
            && $now->gt($this->computeCutoff($nextDueDate, $settings->penalty_cutoff_hour, $settings->penalty_cutoff_minute))
            && $remainingLoan > 0.0;

        $progress = $loan->total_repayable > 0
            ? min(1.0, max(0.0, $paid / $loan->total_repayable))
            : 1.0;

        return [
            'amount_paid' => $paid,
            'remaining_loan_balance' => $remainingLoan,
            'penalty_balance' => $memberPenalty,
            'total_remaining_balance' => $totalRemaining,
            'weeks_paid' => $weeksPaid,
            'weeks_elapsed' => $weeksElapsed,
            'next_payment_due_date' => $nextDueDate,
            'amount_due_this_week' => $amountDueThisWeek,
            'weeks_remaining' => $weeksRemaining,
            'is_overdue' => $isOverdue,
            'progress' => $progress,
            'schedule' => $schedule,
        ];
    }

    public function resolveStatus(Loan $loan, ?Carbon $now = null, ?AdminSetting $settings = null): string
    {
        $metrics = $this->computeMetrics($loan, $now, $settings);
        if ($metrics['remaining_loan_balance'] <= 0.0) {
            return 'completed';
        }
        return $metrics['is_overdue'] ? 'overdue' : 'active';
    }
}
