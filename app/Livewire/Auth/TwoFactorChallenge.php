<?php

namespace App\Livewire\Auth;

use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\TotpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class TwoFactorChallenge extends Component
{
    public string $code = '';
    public string $setupSecret = '';
    public string $otpAuthUri = '';
    public array $generatedRecoveryCodes = [];
    public bool $showRecoveryCodes = false;
    public bool $showSetupQr = false;
    public string $errorMessage = '';
    public string $statusMessage = '';

    public function mount(TotpService $totp): void
    {
        $user = Auth::user()?->fresh();
        if (! $user || ! $user->isStaff()) {
            $this->redirect(route('login'));
            return;
        }
        if (session('two_factor_passed')) {
            $this->redirect(route('staff.groups'));
            return;
        }

        if (! $user->hasTwoFactorEnabled()) {
            $this->setupSecret = session('two_factor_pending_secret') ?: $totp->generateSecret();
            session(['two_factor_pending_secret' => $this->setupSecret]);
            $this->otpAuthUri = $totp->getOtpAuthUri($user->email, $this->setupSecret);
        }
    }

    public function openSetup(): void
    {
        $this->errorMessage = '';
        $this->showSetupQr = true;
    }

    public function closeSetup(): void
    {
        $this->errorMessage = '';
        $this->showSetupQr = false;
    }

    public function confirmSetup(TotpService $totp): void
    {
        $this->errorMessage = '';
        $user = Auth::user();
        $secret = session('two_factor_pending_secret') ?: $this->setupSecret;

        if (! $totp->verifyCode($secret, $this->code)) {
            $this->errorMessage = 'Invalid 6-digit authenticator code. Please try again.';
            return;
        }

        $plainCodes = $totp->generateRecoveryCodes(8);
        $hashedCodes = array_map(fn ($c) => Hash::make($c), $plainCodes);

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($hashedCodes)),
            'two_factor_confirmed_at' => now(),
            'two_factor_email_token' => null,
            'two_factor_email_expires_at' => null,
        ]);

        session()->forget('two_factor_pending_secret');
        session(['two_factor_passed' => true]);
        AuditService::log($user, '2FA_ENROLLED', $user->email, 'Enrolled TOTP 2FA authenticator');

        $this->generatedRecoveryCodes = $plainCodes;
        $this->showRecoveryCodes = true;
    }

    public function verify(TotpService $totp): void
    {
        $this->errorMessage = '';
        $user = Auth::user();
        $input = trim($this->code);

        if (! $user->hasTwoFactorEnabled()) {
            $this->errorMessage = '2FA is not set up yet. Click "Set up 2FA" below first.';
            return;
        }

        if ($input === '') {
            $this->errorMessage = 'Enter your 6-digit code or a recovery code.';
            return;
        }

        $secret = decrypt($user->two_factor_secret);
        if ($totp->verifyCode($secret, $input)) {
            session(['two_factor_passed' => true]);
            $this->redirect(route('staff.groups'));
            return;
        }

        // Check one-time recovery codes
        $hashedCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
        foreach ($hashedCodes as $idx => $hashed) {
            if (Hash::check(strtoupper($input), $hashed)) {
                unset($hashedCodes[$idx]);
                $user->update([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($hashedCodes))),
                ]);
                session(['two_factor_passed' => true]);
                AuditService::log($user, '2FA_RECOVERY_CODE_USED', $user->email, 'Signed in using one-time recovery code');
                $this->redirect(route('staff.groups'));
                return;
            }
        }

        $this->errorMessage = 'Invalid 2FA code or recovery code.';
    }

    public function sendRecoveryEmail(NotificationService $notifications): void
    {
        $this->errorMessage = '';
        $user = Auth::user();
        $plainToken = Str::random(48);

        $user->update([
            'two_factor_email_token' => hash('sha256', $plainToken),
            'two_factor_email_expires_at' => now()->addMinutes(15),
        ]);

        $recoveryUrl = route('two-factor.recover', ['token' => $plainToken, 'email' => $user->email]);
        $notifications->sendTwoFactorRecoveryEmail($user, $recoveryUrl);

        AuditService::log($user, '2FA_EMAIL_RECOVERY_REQUESTED', $user->email, 'Sent 2FA reset link to registered email');
        $this->statusMessage = __('messages.recovery_email_sent') . " (Dev link: {$recoveryUrl})";
    }

    public function continueToDashboard(): void
    {
        $this->redirect(route('staff.groups'));
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge', [
            'user' => Auth::user(),
        ]);
    }
}
