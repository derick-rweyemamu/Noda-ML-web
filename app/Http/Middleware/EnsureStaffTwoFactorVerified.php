<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isStaff() && ! session('two_factor_passed')) {
            return redirect()->route('two-factor.challenge');
        }
        return $next($request);
    }
}
