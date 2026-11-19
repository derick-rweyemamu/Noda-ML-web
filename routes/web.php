<?php

use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Member\MemberPortal;
use App\Livewire\Staff\ContactsManager;
use App\Livewire\Staff\GroupsManager;
use App\Livewire\Staff\LoansManager;
use App\Livewire\Staff\NotificationsManager;
use App\Livewire\Staff\PaymentsPenaltiesManager;
use App\Livewire\Staff\ProfileManager;
use App\Livewire\Staff\ReportsManager;
use App\Livewire\Staff\SettingsManager;
use App\Livewire\Staff\TemplatesManager;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', LoginPage::class)->name('login');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'sw'], true)) {
        session(['locale' => $locale]);
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }
    }
    return back();
})->name('locale.switch');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/two-factor/challenge', TwoFactorChallenge::class)->name('two-factor.challenge');
    Route::get('/member', MemberPortal::class)->name('member.portal');
});

Route::get('/two-factor/recover/{token}', function (Request $request, string $token) {
    $email = strtolower(trim((string) $request->query('email', '')));
    $user = User::where('email', $email)->where('role', 'staff')->first();

    if (
        ! $user ||
        ! $user->two_factor_email_token ||
        ! $user->two_factor_email_expires_at ||
        now()->gt($user->two_factor_email_expires_at) ||
        ! hash_equals($user->two_factor_email_token, hash('sha256', $token))
    ) {
        abort(403, 'Invalid or expired 2FA recovery link.');
    }

    // Reset old 2FA device so staff can enroll a new one
    $user->update([
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_email_token' => null,
        'two_factor_email_expires_at' => null,
    ]);

    Auth::login($user);
    session()->regenerate();
    session(['two_factor_passed' => false]);
    AuditService::log($user, '2FA_DEVICE_RESET_VIA_EMAIL', $user->email, 'Reset 2FA device using email recovery link');

    return redirect()->route('two-factor.challenge');
})->name('two-factor.recover');

Route::prefix('staff')->middleware(['auth', 'staff', '2fa'])->name('staff.')->group(function () {
    Route::get('/groups', GroupsManager::class)->middleware('permission:groups.view')->name('groups');
    Route::get('/contacts', ContactsManager::class)->middleware('permission:contacts.view')->name('contacts');
    Route::get('/loans', LoansManager::class)->middleware('permission:loans.view')->name('loans');
    Route::get('/payments', PaymentsPenaltiesManager::class)->middleware('permission:payments.view')->name('payments');
    Route::get('/notifications', NotificationsManager::class)->middleware('permission:notifications.view')->name('notifications');
    Route::get('/templates', TemplatesManager::class)->middleware('permission:templates.view')->name('templates');
    Route::get('/reports', ReportsManager::class)->middleware('permission:reports.view')->name('reports');
    Route::get('/settings', SettingsManager::class)->middleware('permission:settings.view')->name('settings');
    Route::get('/profile', ProfileManager::class)->name('profile');
    Route::get('/members/{member}', MemberPortal::class)->middleware('permission:groups.view')->name('member.show');
});
