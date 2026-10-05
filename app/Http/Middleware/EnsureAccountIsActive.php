<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out users whose account, or whose facility, has been deactivated.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $message = null;

        if (! $user->isActive()) {
            $message = 'Your account has been deactivated. Please contact your administrator.';
        } elseif ($user->facility_id && ! $user->facility?->isActive()) {
            $message = 'Your facility has been deactivated. Please contact the System Administrator.';
        }

        if ($message) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
