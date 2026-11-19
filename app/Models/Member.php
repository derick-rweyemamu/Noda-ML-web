<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    public const ID_TYPES = ['NIDA', 'Voter ID Card', 'Driving License', 'Passport'];
    public const GENDERS = ['female', 'male'];

    protected $fillable = [
        'group_id',
        'full_name',
        'phone',
        'email',
        'gender',
        'date_of_birth',
        'id_type',
        'id_number',
        'id_image_path',
        'photo_path',
        'penalty_balance',
    ];

    protected $casts = [
        'penalty_balance' => 'float',
        'date_of_birth' => 'date',
    ];

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function referees(): HasMany
    {
        return $this->hasMany(MemberReferee::class);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(MemberCollateral::class);
    }
}
