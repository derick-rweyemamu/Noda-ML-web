<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberReferee extends Model
{
    protected $fillable = [
        'member_id',
        'full_name',
        'phone',
        'relationship',
        'id_number',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
