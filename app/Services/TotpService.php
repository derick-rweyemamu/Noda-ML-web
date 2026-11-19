<?php

namespace App\Services;

use Illuminate\Support\Str;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, 31)];
        }
        return $secret;
    }

    public function getOtpAuthUri(string $email, string $secret, string $issuer = 'NODA ML'): string
    {
        $label = rawurlencode("{$issuer}:{$email}");
        $issuerParam = rawurlencode($issuer);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerParam}&algorithm=SHA1&digits=6&period=30";
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $clean = preg_replace('/\s+/', '', $code ?? '');
        if (! preg_match('/^\d{6}$/', $clean)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->getCode($secret, $timeSlice + $offset), $clean)) {
                return true;
            }
        }
        return false;
    }

    public function getCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= (int) floor(time() / 30);
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = substr($hash, $offset, 4);
        $value = unpack('N', $truncated)[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $binaryString = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }
}
