<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class LoginPage extends Component
{
    public string $email = '';
    public string $password = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isStaff()) {
                $this->redirect(session('two_factor_passed') ? route('staff.groups') : route('two-factor.challenge'));
            } else {
                $this->redirect(route('member.portal'));
            }
        }
    }

    public function login(): void
    {
        $this->errorMessage = '';
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt(['email' => strtolower(trim($this->email)), 'password' => $this->password])) {
            $this->errorMessage = 'Invalid email or password.';
            return;
        }

        session()->regenerate();
        $user = Auth::user();
        if ($user->isStaff() && $user->staffProfile && $user->staffProfile->status !== 'active') {
            Auth::logout();
            $this->errorMessage = "Your staff account is {$user->staffProfile->status}. Contact Super Admin.";
            return;
        }
        session(['locale' => $user->locale ?? session('locale', 'en')]);

        if ($user->isStaff()) {
            session(['two_factor_passed' => false]);
            $this->redirect(route('two-factor.challenge'));
            return;
        }

        $this->redirect(route('member.portal'));
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
