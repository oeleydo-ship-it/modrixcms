<?php

namespace App\Http\Middleware;

use App\Cms\Themes\ThemeManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Draws the public site with the active (or previewed) theme. */
class ApplyTheme
{
    public function __construct(private ThemeManager $themes) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->themes->apply($request);

        return $next($request);
    }
}
