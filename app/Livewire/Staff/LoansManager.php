<?php

namespace App\Livewire\Staff;

use App\Models\AdminSetting;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\LoanCalculatorService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LoansManager extends Component
{
    public string $statusFilter = 'all';

    // Request Loan modal
    public bool $showLoanModal = false;
    public string $loanGroupSearch = '';
    public ?int $loanGroupId = null;
    public ?int $loanMemberId = null;
    public float $loanPrincipal = 500000;
    public int $loanWeeks = 10;
    public int $loanRestDays = 7;

    // Record Payment modal (locked to selected group, searchable member dropdown)
    public ?int $paymentGroupId = null;
    public string $paymentGroupName = '';
    public string $paymentMemberSearch = '';
    public ?int $paymentMemberId = null;
    public ?int $paymentLoanId = null;
    public float $paymentAmount = 0;
    public int $paymentWeek = 1;

    public string $flashMessage = '';
    public string $errorMessage = '';

    public function openLoanModal(): void
    {
        $firstGroup = Group::with('members')->first();
        $this->loanGroupId = $firstGroup?->id;
        $this->loanMemberId = $firstGroup?->members->first()?->id;
        $this->loanGroupSearch = '';
        $this->loanPrincipal = 500000;
        $this->loanWeeks = 10;
        $this->loanRestDays = 7;
        $this->errorMessage = '';
        $this->showLoanModal = true;
    }

    public function updatedLoanGroupId($val): void
    {
        $this->loanMemberId = Member::where('group_id', $val)->value('id');
    }

    public function createLoan(LoanCalculatorService $calc, NotificationService $notifications): void
    {
        abort_unless(Auth::user()?->can('loans.create'), 403);
        $settings = AdminSetting::current();
        $this->validate([
            'loanMemberId' => 'required|exists:members,id',
            'loanPrincipal' => "required|numeric|min:{$settings->min_loan_amount}|max:{$settings->max_loan_amount}",
            'loanWeeks' => "required|integer|min:1|max:{$settings->max_weeks}",
            'loanRestDays' => 'required|integer|min:1',
        ]);

        $member = Member::findOrFail($this->loanMemberId);
        $total = $calc->calculateTotalRepayable($this->loanPrincipal, $settings->load_rate);
        $weekly = $calc->calculateWeeklyAmount($total, $this->loanWeeks);
        $firstReturn = now()->addDays($this->loanRestDays);

        $loan = Loan::create([
            'member_id' => $member->id,
            'group_id' => $member->group_id,
            'principal' => $calc->round2($this->loanPrincipal),
            'term_weeks' => $this->loanWeeks,
            'load_rate' => $settings->load_rate,
            'total_repayable' => $total,
            'weekly_amount' => $weekly,
            'start_date' => now(),
            'first_return_date' => $firstReturn,
            'status' => 'active',
        ]);

        $metrics = $calc->computeMetrics($loan, now(), $settings);
        $notifications->scheduleRemindersForLoan($loan, $member, $metrics, $settings);
        AuditService::log(Auth::user(), 'CREATE_LOAN', "Loan #{$loan->id} ({$member->full_name})", "Principal TZS " . number_format($this->loanPrincipal) . ", {$this->loanWeeks} wks, 1st return {$firstReturn->format('d M Y')}");

        $this->showLoanModal = false;
        $this->flashMessage = "Loan approved for {$member->full_name} & reminders scheduled.";
    }

    public function openGroupPayment(int $groupId, LoanCalculatorService $calc): void
    {
        $group = Group::findOrFail($groupId);
        $this->paymentGroupId = $group->id;
        $this->paymentGroupName = $group->name;
        $this->paymentMemberSearch = '';

        $firstMember = Member::where('group_id', $groupId)
            ->whereHas('loans', fn ($q) => $q->where('status', '!=', 'completed'))
            ->first();

        $this->selectPaymentMember($firstMember?->id, $calc);
    }

    public function selectPaymentMember(?int $memberId, ?LoanCalculatorService $calc = null): void
    {
        $calc ??= app(LoanCalculatorService::class);
        $this->paymentMemberId = $memberId;
        if (! $memberId) {
            $this->paymentLoanId = null;
            $this->paymentAmount = 0;
            $this->paymentWeek = 1;
            return;
        }

        $loan = Loan::where('member_id', $memberId)->where('status', '!=', 'completed')->first();
        $this->paymentLoanId = $loan?->id;
        if ($loan) {
            $metrics = $calc->computeMetrics($loan);
            $this->paymentAmount = $metrics['amount_due_this_week'] > 0 ? $metrics['amount_due_this_week'] : $loan->weekly_amount;
            $this->paymentWeek = min($loan->term_weeks, $metrics['weeks_paid'] + 1);
        }
    }

    public function savePayment(LoanCalculatorService $calc): void
    {
        abort_unless(Auth::user()?->can('payments.record'), 403);
        $loan = Loan::with('member')->findOrFail($this->paymentLoanId);
        $this->validate([
            'paymentLoanId' => 'required|exists:loans,id',
            'paymentAmount' => 'required|numeric|min:1',
            'paymentWeek' => "required|integer|min:1|max:{$loan->term_weeks}",
        ]);

        Payment::create([
            'loan_id' => $loan->id,
            'amount' => $calc->round2($this->paymentAmount),
            'paid_at' => now(),
            'week_number' => $this->paymentWeek,
            'recorded_by' => Auth::id(),
        ]);

        $newStatus = $calc->resolveStatus($loan->fresh('payments'));
        $loan->update(['status' => $newStatus]);
        AuditService::log(Auth::user(), 'RECORD_PAYMENT', "{$loan->member->full_name} (Loan #{$loan->id})", "Paid TZS " . number_format($this->paymentAmount) . " for Week {$this->paymentWeek}");

        $this->paymentGroupId = null;
        $this->flashMessage = 'Payment recorded successfully.';
    }

    public function render(LoanCalculatorService $calc)
    {
        $settings = AdminSetting::current();
        $query = Loan::with(['member', 'group', 'payments']);
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $loansByGroup = $query->get()->groupBy('group_id')->map(function ($groupLoans) use ($calc) {
            $group = $groupLoans->first()->group;
            $totalRemaining = $groupLoans->where('status', '!=', 'completed')->sum(fn ($l) => $calc->computeMetrics($l)['remaining_loan_balance']);
            return [
                'group' => $group,
                'has_unpaid' => $groupLoans->where('status', '!=', 'completed')->isNotEmpty(),
                'total_remaining' => $totalRemaining,
                'loans' => $groupLoans->map(fn ($l) => [
                    'loan' => $l,
                    'metrics' => $calc->computeMetrics($l),
                ]),
            ];
        });

        $allGroups = Group::orderBy('name')->get();
        $filteredLoanGroups = $this->loanGroupSearch
            ? $allGroups->filter(fn ($g) => str_contains(strtolower($g->name), strtolower($this->loanGroupSearch)))
            : $allGroups;
        $loanGroupMembers = $this->loanGroupId ? Member::where('group_id', $this->loanGroupId)->orderBy('full_name')->get() : collect();

        $paymentMembers = $this->paymentGroupId
            ? Member::where('group_id', $this->paymentGroupId)
                ->whereHas('loans', fn ($q) => $q->where('status', '!=', 'completed'))
                ->when($this->paymentMemberSearch, fn ($q) => $q->where(function ($sub) {
                    $sub->where('full_name', 'like', "%{$this->paymentMemberSearch}%")
                        ->orWhere('phone', 'like', "%{$this->paymentMemberSearch}%");
                }))
                ->orderBy('full_name')
                ->get()
            : collect();

        $previewSchedule = ($this->loanPrincipal >= $settings->min_loan_amount && $this->loanWeeks >= 1)
            ? $calc->buildSchedule($this->loanPrincipal, $settings->load_rate, $this->loanWeeks, now()->addDays(max(1, $this->loanRestDays)))
            : [];

        return view('livewire.staff.loans-manager', [
            'loansByGroup' => $loansByGroup,
            'settings' => $settings,
            'filteredLoanGroups' => $filteredLoanGroups,
            'loanGroupMembers' => $loanGroupMembers,
            'paymentMembers' => $paymentMembers,
            'previewSchedule' => $previewSchedule,
        ]);
    }
}
