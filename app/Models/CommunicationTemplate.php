<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    public const CATEGORIES = [
        'welcome' => 'Welcome',
        'next_payment' => 'Next Payment',
        'overdue' => 'Overdue',
        'payment' => 'Payment Received',
        'full_payment' => 'Full Payment',
        'marketing' => 'Contact / Marketing',
        'custom' => 'Custom',
    ];

    public const PLACEHOLDERS = [
        '{name}',
        '{group}',
        '{amount}',
        '{due_date}',
        '{balance}',
        '{loan_id}',
        '{company}',
    ];

    protected $fillable = [
        'key',
        'name',
        'category',
        'email_subject',
        'email_subject_sw',
        'email_body',
        'email_body_sw',
        'sms_body',
        'sms_body_sw',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function forLocale(?string $locale = null): array
    {
        $loc = $locale ?? app()->getLocale();
        $useSw = $loc === 'sw';

        return [
            'email_subject' => ($useSw && ! empty($this->email_subject_sw)) ? $this->email_subject_sw : $this->email_subject,
            'email_body' => ($useSw && ! empty($this->email_body_sw)) ? $this->email_body_sw : $this->email_body,
            'sms_body' => ($useSw && ! empty($this->sms_body_sw)) ? $this->sms_body_sw : $this->sms_body,
        ];
    }

    public static function renderText(?string $template, array $vars = []): string
    {
        if (empty($template)) {
            return '';
        }

        $defaults = [
            '{name}' => 'Amina Hassan',
            '{group}' => 'Umoja Women Group',
            '{amount}' => 'TZS 50,000',
            '{due_date}' => now()->addDays(3)->format('d M Y'),
            '{balance}' => 'TZS 250,000',
            '{loan_id}' => '#12',
            '{company}' => 'NODA ML',
        ];

        $replacements = array_merge($defaults, $vars);

        return strtr($template, $replacements);
    }
}
