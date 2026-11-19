<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Penalty;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendSms(string $phone, string $message): bool
    {
        $settings = AdminSetting::current();
        $sid = $settings->twilio_enabled ? ($settings->twilio_account_sid ?: env('TWILIO_ACCOUNT_SID')) : env('TWILIO_ACCOUNT_SID');
        $token = $settings->twilio_enabled ? ($settings->twilio_auth_token ?: env('TWILIO_AUTH_TOKEN')) : env('TWILIO_AUTH_TOKEN');
        $from = $settings->twilio_enabled ? ($settings->twilio_from_number ?: env('TWILIO_FROM_NUMBER')) : env('TWILIO_FROM_NUMBER');

        if (($settings->twilio_enabled || ! empty(env('TWILIO_ACCOUNT_SID'))) && ! empty($sid) && ! empty($token) && ! empty($from)) {
            try {
                $response = Http::withBasicAuth($sid, $token)
                    ->asForm()
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'From' => $from,
                        'To' => $phone,
                        'Body' => $message,
                    ]);
                return $response->successful();
            } catch (\Throwable $e) {
                Log::error('Twilio SMS failed: ' . $e->getMessage());
                return false;
            }
        }

        // Dev/Log adapter
        Log::info("[NODA ML SMS -> {$phone}] {$message}");
        return true;
    }

    public function sendEmail(string $email, string $subject, string $body): bool
    {
        try {
            Mail::raw($body, function ($msg) use ($email, $subject) {
                $msg->to($email)->subject($subject);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('Email send failed: ' . $e->getMessage());
            return false;
        }
    }

    public function dispatchReminder(Reminder $reminder): Reminder
    {
        $member = $reminder->member;
        if (! $member) {
            $reminder->update(['status' => 'failed']);
            return $reminder;
        }

        $ok = match ($reminder->channel) {
            'sms' => $this->sendSms($member->phone, $reminder->message),
            'email' => ! empty($member->email) && $this->sendEmail($member->email, 'NODA ML Payment Notice', $reminder->message),
            default => false,
        };

        $reminder->update([
            'status' => $ok ? 'sent' : 'failed',
            'sent_at' => $ok ? now() : null,
        ]);

        return $reminder->fresh();
    }

    public function scheduleRemindersForLoan(Loan $loan, Member $member, array $metrics, \App\Models\AdminSetting $settings): void
    {
        $dueDate = $metrics['next_payment_due_date'];
        if (! $dueDate) {
            return;
        }

        $amountToPay = $metrics['amount_due_this_week'] > 0 ? $metrics['amount_due_this_week'] : $loan->weekly_amount;
        $message = "Hello {$member->full_name}, reminder: TZS " . number_format($amountToPay, 0) .
            " is due on {$dueDate->format('d M Y')}. Remaining balance: TZS " .
            number_format($metrics['total_remaining_balance'], 0) . ". — NODA ML";

        foreach ($settings->reminder_lead_days as $offset) {
            $scheduled = $dueDate->copy()->subDays((int) $offset);
            if (! empty($settings->sms_enabled_by_offset[(string) $offset])) {
                Reminder::create([
                    'loan_id' => $loan->id,
                    'member_id' => $member->id,
                    'channel' => 'sms',
                    'scheduled_date' => $scheduled,
                    'status' => 'pending',
                    'message' => $message,
                    'offset_days' => (int) $offset,
                ]);
            }
            if (! empty($settings->email_enabled_by_offset[(string) $offset]) && ! empty($member->email)) {
                Reminder::create([
                    'loan_id' => $loan->id,
                    'member_id' => $member->id,
                    'channel' => 'email',
                    'scheduled_date' => $scheduled,
                    'status' => 'pending',
                    'message' => $message,
                    'offset_days' => (int) $offset,
                ]);
            }
        }
    }

    public function notifyGroupPenalty(Group $group, Penalty $penalty, ?Member $lateMember): void
    {
        $lateName = $lateMember?->full_name ?? $group->name;
        foreach ($group->members as $member) {
            $msg = "Notice ({$group->name}): {$lateName} missed a loan due date. " .
                "A group late fee of TZS " . number_format($penalty->amount_per_member, 0) .
                " has been applied to each member, including {$member->full_name}. — NODA ML";

            $smsReminder = Reminder::create([
                'loan_id' => $penalty->triggered_by_loan_id,
                'member_id' => $member->id,
                'channel' => 'sms',
                'scheduled_date' => now(),
                'status' => 'pending',
                'message' => $msg,
                'is_penalty_notice' => true,
            ]);
            $this->dispatchReminder($smsReminder);

            if (! empty($member->email)) {
                $emailReminder = Reminder::create([
                    'loan_id' => $penalty->triggered_by_loan_id,
                    'member_id' => $member->id,
                    'channel' => 'email',
                    'scheduled_date' => now(),
                    'status' => 'pending',
                    'message' => $msg,
                    'is_penalty_notice' => true,
                ]);
                $this->dispatchReminder($emailReminder);
            }
        }
    }

    public function sendTwoFactorRecoveryEmail(User $user, string $recoveryUrl): bool
    {
        $body = "Hello {$user->name},\n\n" .
            "You requested 2FA device recovery for your NODA ML staff account.\n" .
            "Click the link below within 15 minutes to reset your 2FA device and enroll a new authenticator:\n\n" .
            "{$recoveryUrl}\n\n" .
            "If you did not request this, please ignore this email.\n— NODA ML Security";

        return $this->sendEmail($user->email, 'NODA ML — 2FA Device Recovery', $body);
    }
}
