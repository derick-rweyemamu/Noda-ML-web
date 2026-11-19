<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'loan_id',
        'member_id',
        'channel',
        'scheduled_date',
        'sent_at',
        'status',
        'message',
        'offset_days',
        'is_penalty_notice',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'sent_at' => 'datetime',
        'is_penalty_notice' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
