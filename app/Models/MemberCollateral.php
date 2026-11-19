<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCollateral extends Model
{
    protected $fillable = [
        'member_id',
        'item_name',
        'estimated_value',
        'serial_number',
        'description',
        'image_path',
    ];

    protected $casts = [
        'estimated_value' => 'float',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
