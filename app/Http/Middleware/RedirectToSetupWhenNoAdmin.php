<?php

namespace App\Http\Middleware;

use App\Support\FirstRunSetup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Until the site has an admin account, every page leads to the first-run setup screen.
 */
class RedirectToSetupWhenNoAdmin
{
    public function __construct(private FirstRunSetup $setup) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('setup', 'setup/*') || ! $this->setup->isPending()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'This site has not been set up yet. Open it in a browser to create the admin account.'], 503);
        }

        return redirect()->route('setup');
    }
}
