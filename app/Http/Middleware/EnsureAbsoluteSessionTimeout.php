<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbsoluteSessionTimeout
{
    public const SESSION_LOGIN_AT = 'login_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $absoluteLifetime = (int) config('session.absolute_lifetime', 0);

        if ($absoluteLifetime <= 0) {
            return $next($request);
        }

        $loginAt = $request->session()->get(self::SESSION_LOGIN_AT);

        if ($loginAt === null) {
            $request->session()->put(self::SESSION_LOGIN_AT, now()->getTimestamp());

            return $next($request);
        }

        if ((now()->getTimestamp() - (int) $loginAt) >= ($absoluteLifetime * 60)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', __('auth.session_expired'));
        }

        return $next($request);
    }
}
