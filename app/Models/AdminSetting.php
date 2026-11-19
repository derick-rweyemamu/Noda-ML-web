<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'reminder_lead_days',
        'sms_enabled_by_offset',
        'email_enabled_by_offset',
        'penalty_amount_per_member',
        'penalty_rule',
        'penalty_cutoff_hour',
        'penalty_cutoff_minute',
        'min_loan_amount',
        'max_loan_amount',
        'max_weeks',
        'load_rate',
        'voda_lipa_namba',
        'voda_business_name',
        'mpesa_auto_reconcile',
        'twilio_enabled',
        'twilio_account_sid',
        'twilio_auth_token',
        'twilio_from_number',
    ];

    protected $hidden = [
        'twilio_auth_token',
    ];

    protected $casts = [
        'reminder_lead_days' => 'array',
        'sms_enabled_by_offset' => 'array',
        'email_enabled_by_offset' => 'array',
        'penalty_amount_per_member' => 'float',
        'min_loan_amount' => 'float',
        'max_loan_amount' => 'float',
        'load_rate' => 'float',
        'mpesa_auto_reconcile' => 'boolean',
        'twilio_enabled' => 'boolean',
        'twilio_auth_token' => 'encrypted',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'reminder_lead_days' => [3, 1, 0],
            'sms_enabled_by_offset' => ['3' => true, '1' => true, '0' => true],
            'email_enabled_by_offset' => ['3' => false, '1' => true, '0' => true],
            'penalty_amount_per_member' => 10000,
            'penalty_rule' => 'one_time_per_missed_due_date',
            'penalty_cutoff_hour' => 10,
            'penalty_cutoff_minute' => 30,
            'min_loan_amount' => 100000,
            'max_loan_amount' => 3000000,
            'max_weeks' => 26,
            'load_rate' => 0.17,
            'voda_lipa_namba' => '55443322',
            'voda_business_name' => 'NODA ML MICROFINANCE',
            'mpesa_auto_reconcile' => true,
        ]);
    }
}
