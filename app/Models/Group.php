<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['name', 'loan_officer_id', 'penalty_balance'];

    protected $casts = [
        'penalty_balance' => 'float',
    ];

    public function loanOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }
}
