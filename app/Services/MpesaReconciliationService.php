<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MpesaReconciliationService
{
    public function __construct(
        private LoanCalculatorService $calculator,
        private NotificationService $notifications
    ) {}

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+255' . substr($digits, 1);
        }
        if (str_starts_with($digits, '255')) {
            return '+' . $digits;
        }
        return $phone;
    }

    public function processCallback(array $payload, ?User $actor = null): MpesaTransaction
    {
        $receipt = strtoupper(trim((string) ($payload['receipt_number'] ?? '')));
        $existing = MpesaTransaction::where('receipt_number', $receipt)->first();
        if ($existing) {
            return $existing; // Idempotent: never double-credit same M-Pesa receipt
        }

        $settings = AdminSetting::current();
        $msisdn = $this->normalizePhone((string) ($payload['msisdn'] ?? ''));
        $amount = $this->calculator->round2((float) ($payload['amount'] ?? 0));
        $lipaNamba = (string) ($payload['lipa_namba'] ?? $settings->voda_lipa_namba);
        $ref = isset($payload['account_reference']) ? trim((string) $payload['account_reference']) : null;

        // Match loan by account_reference (loan ID) or by member phone
        $loan = null;
        $member = null;

        if ($ref && is_numeric($ref)) {
            $loan = Loan::with('member')->where('id', (int) $ref)->where('status', '!=', 'completed')->first();
            $member = $loan?->member;
        }

        if (! $member && $msisdn !== '') {
            $member = Member::where('phone', $msisdn)->first();
            if ($member) {
                $loan = Loan::where('member_id', $member->id)->where('status', '!=', 'completed')->first();
            }
        }

        return DB::transaction(function () use ($receipt, $msisdn, $lipaNamba, $amount, $ref, $member, $loan, $payload, $settings, $actor) {
            $tx = MpesaTransaction::create([
                'receipt_number' => $receipt,
                'msisdn' => $msisdn,
                'lipa_namba' => $lipaNamba,
                'amount' => $amount,
                'account_reference' => $ref,
                'member_id' => $member?->id,
                'loan_id' => $loan?->id,
                'status' => 'unmatched',
                'raw_payload' => $payload,
                'transacted_at' => now(),
            ]);

            if ($settings->mpesa_auto_reconcile && $loan && $amount > 0) {
                $this->reconcileToLoan($tx, $loan, $actor);
            }

            return $tx->fresh(['member', 'loan', 'payment']);
        });
    }

    public function reconcileToLoan(MpesaTransaction $tx, Loan $loan, ?User $actor = null): MpesaTransaction
    {
        $metrics = $this->calculator->computeMetrics($loan);
        $weekNumber = min($loan->term_weeks, $metrics['weeks_paid'] + 1);

        $payment = Payment::create([
            'loan_id' => $loan->id,
            'amount' => $tx->amount,
            'paid_at' => $tx->transacted_at ?? now(),
            'week_number' => $weekNumber,
            'recorded_by' => $actor?->id,
        ]);

        $newStatus = $this->calculator->resolveStatus($loan->fresh('payments'));
        $loan->update(['status' => $newStatus]);

        $tx->update([
            'member_id' => $loan->member_id,
            'loan_id' => $loan->id,
            'payment_id' => $payment->id,
            'status' => 'reconciled',
        ]);

        $member = $loan->member;
        if ($member) {
            $msg = "Vodacom M-Pesa Receipt {$tx->receipt_number}: TZS " . number_format($tx->amount, 0) .
                " received for Loan #{$loan->id} (Wk {$weekNumber}). Thank you! — NODA ML";
            $rem = Reminder::create([
                'loan_id' => $loan->id,
                'member_id' => $member->id,
                'channel' => 'sms',
                'scheduled_date' => now(),
                'status' => 'pending',
                'message' => $msg,
            ]);
            $this->notifications->dispatchReminder($rem);
        }

        AuditService::log(
            $actor,
            'MPESA_AUTO_RECONCILED',
            "Receipt {$tx->receipt_number}",
            "Lipa Namba {$tx->lipa_namba}: TZS " . number_format($tx->amount, 0) . " -> Loan #{$loan->id} ({$member?->full_name})"
        );

        return $tx->fresh();
    }
}
