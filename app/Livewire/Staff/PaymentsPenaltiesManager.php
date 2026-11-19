<?php

namespace App\Livewire\Staff;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\Penalty;
use App\Services\AuditService;
use App\Services\MpesaReconciliationService;
use App\Services\PenaltyEngineService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PaymentsPenaltiesManager extends Component
{
    public bool $showPenaltyModal = false;
    public string $groupSearch = '';
    public ?int $penaltyGroupId = null;
    public ?int $penaltyMemberId = null;
    public string $penaltyReason = 'Missed weekly due date';
    public string $flashMessage = '';
    public string $errorMessage = '';

    // Simulate Vodacom Lipa Namba payment modal
    public bool $showMpesaModal = false;
    public string $mpesaPhone = '+255712100001';
    public float $mpesaAmount = 117000;
    public string $mpesaReceipt = '';

    public function openMpesaModal(): void
    {
        $this->mpesaReceipt = 'VODA' . strtoupper(substr(uniqid(), -6));
        $this->showMpesaModal = true;
    }

    public function simulateMpesa(MpesaReconciliationService $mpesa): void
    {
        abort_unless(Auth::user()?->can('mpesa.simulate'), 403);
        $this->validate([
            'mpesaPhone' => 'required|string|min:9',
            'mpesaAmount' => 'required|numeric|min:1',
            'mpesaReceipt' => 'required|string|min:4',
        ]);

        $tx = $mpesa->processCallback([
            'receipt_number' => $this->mpesaReceipt,
            'msisdn' => $this->mpesaPhone,
            'amount' => $this->mpesaAmount,
        ], Auth::user());

        $this->showMpesaModal = false;
        $this->flashMessage = $tx->status === 'reconciled'
            ? "Vodacom Lipa Namba {$tx->receipt_number} auto-reconciled to {$tx->member?->full_name} (Loan #{$tx->loan_id})."
            : "Vodacom Lipa Namba {$tx->receipt_number} logged as UNMATCHED (no active loan for {$tx->msisdn}).";
    }

    public function openPenaltyModal(): void
    {
        $first = Group::with('members')->first();
        $this->penaltyGroupId = $first?->id;
        $this->penaltyMemberId = $first?->members->first()?->id;
        $this->groupSearch = '';
        $this->penaltyReason = 'Missed weekly due date';
        $this->showPenaltyModal = true;
    }

    public function updatedPenaltyGroupId($val): void
    {
        $this->penaltyMemberId = Member::where('group_id', $val)->value('id');
    }

    public function runSweep(PenaltyEngineService $engine): void
    {
        abort_unless(Auth::user()?->can('penalties.sweep'), 403);
        $count = $engine->runDailySweep();
        AuditService::log(Auth::user(), 'PENALTY_SWEEP', "{$count} penalty(ies)", 'Triggered daily penalty sweep from web admin');
        $this->flashMessage = $count > 0
            ? "Penalty sweep applied {$count} group penalty(ies) and notified members."
            : 'Penalty sweep complete: no new overdue installments past cutoff.';
    }

    public function applyManualPenalty(PenaltyEngineService $engine): void
    {
        abort_unless(Auth::user()?->can('penalties.apply'), 403);
        $this->errorMessage = '';
        $this->validate([
            'penaltyGroupId' => 'required|exists:groups,id',
            'penaltyReason' => 'required|string|min:2',
        ]);

        $group = Group::findOrFail($this->penaltyGroupId);
        $member = $this->penaltyMemberId ? Member::find($this->penaltyMemberId) : null;
        $loan = $member ? Loan::where('member_id', $member->id)->where('status', '!=', 'completed')->first() : null;

        try {
            $engine->applyManualPenalty(Auth::user(), $group, $member, $loan, $this->penaltyReason);
            $this->showPenaltyModal = false;
            $this->flashMessage = "Group penalty applied to {$group->name} & all members notified.";
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $groups = Group::orderBy('name')->get();
        $filteredGroups = $this->groupSearch
            ? $groups->filter(fn ($g) => str_contains(strtolower($g->name), strtolower($this->groupSearch)))
            : $groups;
        $groupMembers = $this->penaltyGroupId ? Member::where('group_id', $this->penaltyGroupId)->orderBy('full_name')->get() : collect();

        return view('livewire.staff.payments-penalties-manager', [
            'penalties' => Penalty::with(['group', 'member'])->latest('applied_at')->get(),
            'payments' => Payment::with(['loan.member', 'recorder'])->latest('paid_at')->take(15)->get(),
            'mpesaTransactions' => MpesaTransaction::with(['member', 'loan'])->latest('transacted_at')->take(15)->get(),
            'auditLogs' => AuditLog::latest()->take(30)->get(),
            'filteredGroups' => $filteredGroups,
            'groupMembers' => $groupMembers,
        ]);
    }
}
