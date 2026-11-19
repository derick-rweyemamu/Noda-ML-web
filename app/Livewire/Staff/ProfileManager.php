<?php

namespace App\Livewire\Staff;

use App\Models\Country;
use App\Models\StaffProfile;
use App\Services\AuditService;
use App\Services\PhoneHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfileManager extends Component
{
    public string $name = '';
    public string $email = '';
    public string $locale = 'en';

    public string $phoneCode = '+255';
    public string $phone = '';
    public string $national_id = '';
    public string $address = '';
    public string $emergency_contact_name = '';
    public string $emergencyContactPhoneCode = '+255';
    public string $emergency_contact_phone = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public string $flashMessage = '';
    public string $passwordMessage = '';

    public function mount(): void
    {
        $user = Auth::user()->load('staffProfile');
        $this->name = $user->name;
        $this->email = $user->email;
        $this->locale = $user->locale ?? 'en';

        $p = $user->staffProfile;
        [$code, $local] = PhoneHelper::splitE164($p?->phone);
        $this->phoneCode = $code;
        $this->phone = $local;
        $this->national_id = $p?->national_id ?? '';
        $this->address = $p?->address ?? '';
        $this->emergency_contact_name = $p?->emergency_contact_name ?? '';
        [$emergencyCode, $emergencyLocal] = PhoneHelper::splitE164($p?->emergency_contact_phone);
        $this->emergencyContactPhoneCode = $emergencyCode;
        $this->emergency_contact_phone = $emergencyLocal;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        $profileId = $user->staffProfile?->id;
        $fullPhone = trim($this->phone) !== '' ? PhoneHelper::formatE164($this->phoneCode, $this->phone) : '';
        $this->phone = $fullPhone !== '' ? ltrim(substr($fullPhone, strlen($this->phoneCode))) : '';
        $fullEmergencyPhone = trim($this->emergency_contact_phone) !== ''
            ? PhoneHelper::formatE164($this->emergencyContactPhoneCode, $this->emergency_contact_phone)
            : '';
        $this->emergency_contact_phone = $fullEmergencyPhone !== ''
            ? ltrim(substr($fullEmergencyPhone, strlen($this->emergencyContactPhoneCode)))
            : '';

        $this->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'locale' => 'required|in:en,sw',
        ]);
        if ($fullPhone !== '') {
            validator(['phone' => $fullPhone], [
                'phone' => 'string|min:8|unique:staff_profiles,phone,' . ($profileId ?? 'NULL'),
            ])->validate();
        }
        if ($fullEmergencyPhone !== '') {
            validator(['emergency_contact_phone' => $fullEmergencyPhone], [
                'emergency_contact_phone' => 'string|min:8',
            ])->validate();
        }

        $user->update([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'locale' => $this->locale,
        ]);

        StaffProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'staff_code' => $user->staffProfile?->staff_code ?? sprintf('NODA-STF-%03d', $user->id),
                'phone' => $fullPhone ?: null,
                'national_id' => trim($this->national_id) ?: null,
                'address' => trim($this->address) ?: null,
                'emergency_contact_name' => trim($this->emergency_contact_name) ?: null,
                'emergency_contact_phone' => $fullEmergencyPhone ?: null,
            ]
        );

        session(['locale' => $this->locale]);
        AuditService::log($user, 'UPDATE_PROFILE', $user->email, 'Updated staff profile details');
        $this->flashMessage = 'Profile updated successfully.';
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        AuditService::log($user, 'UPDATE_PASSWORD', $user->email, 'Changed staff password');
        $this->passwordMessage = 'Password updated successfully.';
    }

    public function resetTwoFactor(): void
    {
        $user = Auth::user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
        session(['two_factor_passed' => false]);
        AuditService::log($user, '2FA_RESET_BY_USER', $user->email, 'Initiated 2FA re-enrollment from profile');
        $this->redirect(route('two-factor.challenge'));
    }

    public function render()
    {
        return view('livewire.staff.profile-manager', [
            'user' => Auth::user()->load(['staffProfile.branch', 'staffProfile.department', 'staffProfile.jobTitle']),
            'countries' => Country::orderByRaw("CASE WHEN iso2 = 'TZ' THEN 0 ELSE 1 END, name ASC")->get(),
        ]);
    }
}
