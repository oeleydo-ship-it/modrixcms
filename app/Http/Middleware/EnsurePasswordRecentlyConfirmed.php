<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installing, enabling or deleting code needs the admin's password from the last 15 minutes.
 * GET requests come back to the same page after confirming; other requests go back to the page they came from.
 */
class EnsurePasswordRecentlyConfirmed
{
    public const TIMEOUT_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt <= self::TIMEOUT_SECONDS) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->isMethod('GET') ? $request->fullUrl() : url()->previous());

        return redirect()->route('admin.password.confirm');
    }
}
