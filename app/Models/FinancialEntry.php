<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialEntry extends Model
{
    public const TYPES = [
        'expense' => 'Operating Expense',
        'other_income' => 'Other Income',
        'asset' => 'Asset (Cash / Bank / Fixed)',
        'liability' => 'Liability / Borrowing',
        'equity' => 'Equity / Capital',
    ];

    protected $fillable = [
        'type',
        'category',
        'amount',
        'entry_date',
        'description',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'entry_date' => 'date',
    ];

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
