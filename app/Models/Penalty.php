<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalty extends Model
{
    protected $fillable = [
        'group_id',
        'triggered_by_loan_id',
        'member_id',
        'amount_per_member',
        'total_penalty',
        'applied_at',
        'due_date_key',
        'reason',
    ];

    protected $casts = [
        'amount_per_member' => 'float',
        'total_penalty' => 'float',
        'applied_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
