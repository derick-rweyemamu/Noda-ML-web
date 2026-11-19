<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'iso2', 'phone_code'];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function getFlagAttribute(): string
    {
        $iso = strtoupper($this->iso2 ?? '');
        if (strlen($iso) !== 2) {
            return '';
        }

        return mb_chr(ord($iso[0]) - ord('A') + 0x1F1E6, 'UTF-8')
            . mb_chr(ord($iso[1]) - ord('A') + 0x1F1E6, 'UTF-8');
    }

    public function getFlagUrlAttribute(): string
    {
        return asset('assets/images/flags/' . strtolower($this->iso2) . '.png');
    }
}
