<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'member_id',
        'group_id',
        'principal',
        'term_weeks',
        'load_rate',
        'total_repayable',
        'weekly_amount',
        'start_date',
        'first_return_date',
        'status',
    ];

    protected $casts = [
        'principal' => 'float',
        'load_rate' => 'float',
        'total_repayable' => 'float',
        'weekly_amount' => 'float',
        'start_date' => 'datetime',
        'first_return_date' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
