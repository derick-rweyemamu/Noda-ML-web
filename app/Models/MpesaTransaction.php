<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'receipt_number',
        'msisdn',
        'lipa_namba',
        'amount',
        'account_reference',
        'member_id',
        'loan_id',
        'payment_id',
        'status',
        'raw_payload',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'raw_payload' => 'array',
        'transacted_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
