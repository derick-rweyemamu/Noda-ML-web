<?php

namespace App\Services;

use App\Models\Country;

class PhoneHelper
{
    public static function formatE164(string $dialCode, string $localNumber): string
    {
        $cleanInput = trim($localNumber);
        if (str_starts_with($cleanInput, '+')) {
            return '+' . preg_replace('/\D+/', '', $cleanInput);
        }

        $codeDigits = preg_replace('/\D+/', '', $dialCode);
        $localDigits = ltrim(preg_replace('/\D+/', '', $cleanInput), '0');

        if ($localDigits === '') {
            return '';
        }

        return '+' . $codeDigits . $localDigits;
    }

    public static function isValidE164(string $phone): bool
    {
        return (bool) preg_match('/^\+[1-9]\d{6,14}$/', $phone);
    }

    public static function splitE164(?string $fullPhone, string $defaultDialCode = '+255'): array
    {
        $phone = trim((string) $fullPhone);
        if ($phone === '') {
            return [$defaultDialCode, ''];
        }

        if (str_starts_with($phone, '+')) {
            $codes = Country::whereNotNull('phone_code')
                ->pluck('phone_code')
                ->map(fn ($c) => '+' . preg_replace('/\D+/', '', $c))
                ->unique()
                ->sortByDesc(fn ($c) => strlen($c));

            foreach ($codes as $code) {
                if (str_starts_with($phone, $code)) {
                    return [$code, substr($phone, strlen($code))];
                }
            }
        }

        return [$defaultDialCode, ltrim(preg_replace('/\D+/', '', $phone), '0')];
    }
}
