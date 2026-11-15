<?php

namespace Modrix\SeoAnalytics;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use App\Support\Settings;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Modrix\SeoAnalytics\Http\RedirectMiddleware;

class SeoAnalyticsServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $this->app->make(Router::class)->prependMiddlewareToGroup('web', RedirectMiddleware::class);
        Inertia::share('seoAnalytics', function (): ?array {
            if (request()->is('admin', 'admin/*')) {
                return null;
            }

            if (! Cms::setting($this->name(), 'track_views', true)) {
                return null;
            }

            return [
                'hitUrl' => route('plugin.modrix.seo-analytics.hit'),
            ];
        });

        $this->hook('head', function (): string {
            $tags = [];

            $base = rtrim((string) Cms::setting($this->name(), 'canonical_base', ''), '/');
            $canonical = $base !== '' ? $base.request()->getRequestUri() : url()->current();
            // A page can set its own canonical address (a blog post's SEO field); the page then prints it itself.
            if (request()->attributes->get('canonical_url') === null) {
                $tags[] = '<link rel="canonical" href="'.e($canonical).'">';
            }

            if ($google = trim((string) Cms::setting($this->name(), 'google_verification', ''))) {
                $tags[] = '<meta name="google-site-verification" content="'.e($google).'">';
            }

            if ($bing = trim((string) Cms::setting($this->name(), 'bing_verification', ''))) {
                $tags[] = '<meta name="msvalidate.01" content="'.e($bing).'">';
            }

            $settings = app(Settings::class);
            $orgName = trim((string) Cms::setting($this->name(), 'org_name', '')) ?: (string) $settings->get('site_name', 'Modrix');
            $orgType = (string) Cms::setting($this->name(), 'org_type', 'Organization');
            $orgLogo = trim((string) Cms::setting($this->name(), 'org_logo_url', '')) ?: $settings->fileUrl('logo');

            $schema = [
                '@context' => 'https://schema.org',
                '@type' => $orgType,
                'name' => $orgName,
                'url' => url('/'),
            ];

            if ($orgLogo) {
                $schema['logo'] = url($orgLogo);
            }

            $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
            $tags[] = '<script type="application/ld+json">'.$json.'</script>';

            return implode("\n", $tags);
        });
    }
}
