<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => 'Welcome Message',
                'category' => 'welcome',
                'email_subject' => 'Welcome to {company} — {group}',
                'email_subject_sw' => 'Karibu {company} — {group}',
                'email_body' => "Hello {name},\n\nWelcome to {company}! Your membership under {group} has been registered.\n\nThank you for choosing {company}.",
                'email_body_sw' => "Habari {name},\n\nKaribu {company}! Uanachama wako chini ya kikundi cha {group} umesajiliwa.\n\nAsante kwa kuchagua {company}.",
                'sms_body' => 'Welcome {name} to {company}! You are now registered under {group}.',
                'sms_body_sw' => 'Karibu {name} katika {company}! Umesajiliwa chini ya kikundi cha {group}.',
                'is_active' => true,
            ],
            [
                'key' => 'next_payment',
                'name' => 'Next Payment Reminder',
                'category' => 'next_payment',
                'email_subject' => 'Upcoming Loan Payment Reminder — Loan {loan_id}',
                'email_subject_sw' => 'Ukumbusho wa Malipo ya Mkopo — Mkopo {loan_id}',
                'email_body' => "Hello {name},\n\nThis is a reminder that your next installment of {amount} for Loan {loan_id} ({group}) is due on {due_date}.\nRemaining balance: {balance}.\n\nThank you,\n{company}",
                'email_body_sw' => "Habari {name},\n\nHuu ni ukumbusho kuwa rejesho lako la {amount} kwa Mkopo {loan_id} ({group}) linatakiwa kulipwa tarehe {due_date}.\nSalio lililobaki: {balance}.\n\nAsante,\n{company}",
                'sms_body' => 'Hello {name}, reminder: {amount} for Loan {loan_id} is due on {due_date}. Balance: {balance}. — {company}',
                'sms_body_sw' => 'Habari {name}, ukumbusho: {amount} ya Mkopo {loan_id} inatakiwa tarehe {due_date}. Salio: {balance}. — {company}',
                'is_active' => true,
            ],
            [
                'key' => 'overdue',
                'name' => 'Overdue Payment Notice',
                'category' => 'overdue',
                'email_subject' => 'URGENT: Overdue Loan Payment — Loan {loan_id}',
                'email_subject_sw' => 'MUHIMU: Malipo ya Mkopo Yamechelewa — Mkopo {loan_id}',
                'email_body' => "Hello {name},\n\nYour loan installment of {amount} for Loan {loan_id} ({group}) was due on {due_date} and is currently OVERDUE.\nPlease settle your payment immediately to avoid additional group penalties.\nRemaining balance: {balance}.\n\n{company}",
                'email_body_sw' => "Habari {name},\n\nRejesho lako la {amount} kwa Mkopo {loan_id} ({group}) lilipaswa kulipwa tarehe {due_date} na limechelewa.\nTafadhali lipa mara moja ili kuepuka faini ya kikundi.\nSalio lililobaki: {balance}.\n\n{company}",
                'sms_body' => 'URGENT {name}: Your Loan {loan_id} payment of {amount} (due {due_date}) is OVERDUE. Please pay now to avoid penalties. — {company}',
                'sms_body_sw' => 'MUHIMU {name}: Malipo ya Mkopo {loan_id} ya {amount} (tarehe {due_date}) yamechelewa. Lipa sasa kuepuka faini. — {company}',
                'is_active' => true,
            ],
            [
                'key' => 'payment',
                'name' => 'Payment Received',
                'category' => 'payment',
                'email_subject' => 'Payment Confirmation — Loan {loan_id}',
                'email_subject_sw' => 'Uthibitisho wa Malipo — Mkopo {loan_id}',
                'email_body' => "Hello {name},\n\nWe have received your payment of {amount} for Loan {loan_id} ({group}).\nYour remaining balance is {balance}.\n\nThank you,\n{company}",
                'email_body_sw' => "Habari {name},\n\nTumepokea malipo yako ya {amount} kwa Mkopo {loan_id} ({group}).\nSalio lako lililobaki ni {balance}.\n\nAsante,\n{company}",
                'sms_body' => 'Thank you {name}! We received {amount} for Loan {loan_id}. Remaining balance: {balance}. — {company}',
                'sms_body_sw' => 'Asante {name}! Tumepokea {amount} kwa Mkopo {loan_id}. Salio lililobaki: {balance}. — {company}',
                'is_active' => true,
            ],
            [
                'key' => 'full_payment',
                'name' => 'Full Payment / Loan Settled',
                'category' => 'full_payment',
                'email_subject' => 'Congratulations! Loan {loan_id} Fully Settled',
                'email_subject_sw' => 'Hongera! Mkopo {loan_id} Umelipwa Kikamilifu',
                'email_body' => "Hello {name},\n\nCongratulations! Your Loan {loan_id} under {group} has been paid in full (final payment: {amount}).\nRemaining balance: TZS 0.\n\nThank you for banking with {company}.",
                'email_body_sw' => "Habari {name},\n\nHongera! Mkopo wako {loan_id} chini ya {group} umelipwa kikamilifu (malipo ya mwisho: {amount}).\nSalio lililobaki: TZS 0.\n\nAsante kwa kuwa nasi {company}.",
                'sms_body' => 'Congratulations {name}! Your Loan {loan_id} is now FULLY PAID (final payment {amount}). Thank you for choosing {company}!',
                'sms_body_sw' => 'Hongera {name}! Mkopo wako {loan_id} UMELIPWA WOTE (malipo ya mwisho {amount}). Asante kwa kuchagua {company}!',
                'is_active' => true,
            ],
            [
                'key' => 'contact_marketing',
                'name' => 'Contact / Marketing Outreach',
                'category' => 'marketing',
                'email_subject' => 'Special Offer & Update from {company}',
                'email_subject_sw' => 'Taarifa Maalum na Ofa kutoka {company}',
                'email_body' => "Hello {name},\n\nWe value your membership with {company} ({group}). Reach out to your loan officer or visit our branch to learn about our latest loan products and flexible terms.\n\nWarm regards,\n{company}",
                'email_body_sw' => "Habari {name},\n\nTunathamini uanachama wako na {company} ({group}). Wasiliana na afisa wetu wa mikopo au tembelea tawi letu kufahamu huduma na ofa zetu mpya za mikopo.\n\nAsante,\n{company}",
                'sms_body' => 'Hello {name}, {company} has new loan offers & flexible terms for {group} members! Contact your loan officer today.',
                'sms_body_sw' => 'Habari {name}, {company} ina ofa mpya za mikopo kwa wanachama wa {group}! Wasiliana na afisa wako wa mikopo leo.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            $existing = CommunicationTemplate::where('key', $tpl['key'])->first();
            if (! $existing) {
                CommunicationTemplate::create($tpl);
            } else {
                $existing->update([
                    'email_subject_sw' => $existing->email_subject_sw ?: $tpl['email_subject_sw'],
                    'email_body_sw' => $existing->email_body_sw ?: $tpl['email_body_sw'],
                    'sms_body_sw' => $existing->sms_body_sw ?: $tpl['sms_body_sw'],
                ]);
            }
        }
    }
}
