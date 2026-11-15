<?php

namespace Modrix\SeoAnalytics\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modrix\SeoAnalytics\Models\SeoRedirect;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return call_user_func($next, $request);
        }

        if ($request->is('admin', 'admin/*', 'sitemap.xml', 'seo-analytics/*')) {
            return call_user_func($next, $request);
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');

        try {
            if (Schema::hasTable('seo_redirects')) {
                $redirect = SeoRedirect::query()->active()->where('from_path', $path)->first();

                if ($redirect !== null) {
                    $redirect->increment('hits');
                    $redirect->update(['last_hit_at' => now()]);

                    $status = in_array($redirect->status_code, [301, 302], true) ? $redirect->status_code : 301;

                    return str_starts_with($redirect->to_url, '/')
                        ? redirect()->to($redirect->to_url, $status)
                        : redirect()->away($redirect->to_url, $status);
                }
            }
        } catch (Throwable) {
            // Ignore errors if migrations have not run yet.
        }

        return call_user_func($next, $request);
    }
}
