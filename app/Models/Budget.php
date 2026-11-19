<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    public const CATEGORIES = [
        'Disbursements',
        'Collections',
        'Interest & Fee Income',
        'Operating Expenses',
    ];

    protected $fillable = [
        'period',
        'category',
        'budgeted_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'budgeted_amount' => 'float',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
