<?php

namespace App\Livewire\Member;

use App\Models\AdminSetting;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\LoanCalculatorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MemberPortal extends Component
{
    public ?int $memberId = null;

    // Record Payment modal (staff only, for overdue loans)
    public ?int $paymentLoanId = null;
    public float $paymentAmount = 0;
    public int $paymentWeek = 1;
    public string $flashMessage = '';

    public function mount(?Member $member = null): void
    {
        $user = Auth::user();
        if ($user->isMember()) {
            $this->memberId = $user->member_id;
        } else {
            $this->memberId = $member?->id ?? Member::value('id');
        }
    }

    public function openRecordPayment(int $loanId, LoanCalculatorService $calc): void
    {
        abort_unless(Auth::user()?->isStaff() && Auth::user()?->can('payments.record'), 403);
        $loan = Loan::with('payments')->where('member_id', $this->memberId)->findOrFail($loanId);
        $metrics = $calc->computeMetrics($loan);

        $this->paymentLoanId = $loan->id;
        $this->paymentAmount = $metrics['amount_due_this_week'] > 0 ? $metrics['amount_due_this_week'] : $loan->weekly_amount;
        $this->paymentWeek = min($loan->term_weeks, $metrics['weeks_paid'] + 1);
    }

    public function savePayment(LoanCalculatorService $calc): void
    {
        abort_unless(Auth::user()?->isStaff() && Auth::user()?->can('payments.record'), 403);
        $loan = Loan::with('member')->where('member_id', $this->memberId)->findOrFail($this->paymentLoanId);
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
        AuditService::log(
            Auth::user(),
            'RECORD_PAYMENT',
            "{$loan->member->full_name} (Loan #{$loan->id})",
            'Paid TZS ' . number_format($this->paymentAmount) . " for Week {$this->paymentWeek}"
        );

        $this->paymentLoanId = null;
        $this->flashMessage = 'Payment recorded successfully.';
    }

    public function render(LoanCalculatorService $calc)
    {
        $member = Member::with(['group.penalties', 'loans.payments', 'referees', 'collaterals'])->findOrFail($this->memberId);
        $activeLoans = $member->loans->where('status', '!=', 'completed')->map(fn ($l) => [
            'loan' => $l,
            'metrics' => $calc->computeMetrics($l),
        ]);
        $completedLoans = $member->loans->where('status', 'completed');

        return view('livewire.member.member-portal', [
            'member' => $member,
            'activeLoans' => $activeLoans,
            'completedLoans' => $completedLoans,
            'isStaff' => Auth::user()->isStaff(),
            'settings' => AdminSetting::current(),
        ]);
    }
}
