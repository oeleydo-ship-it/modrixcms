<?php

namespace Modrix\SeoAnalytics\Http;

use App\Cms\Cms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modrix\SeoAnalytics\Models\SeoPageView;
use Throwable;

class HitController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Cms::setting('modrix/seo-analytics', 'track_views', true)) {
            return response()->json(['tracked' => false]);
        }

        if (Cms::setting('modrix/seo-analytics', 'exclude_admins', true) && $request->user()?->is_admin) {
            return response()->json(['tracked' => false]);
        }

        $ua = strtolower((string) $request->userAgent());

        if (preg_match('/bot|crawl|spider|slurp|lighthouse|headless/', $ua)) {
            return response()->json(['tracked' => false]);
        }

        $data = $request->validate([
            'path' => ['required', 'string', 'max:300'],
            'title' => ['nullable', 'string', 'max:200'],
            'referrer' => ['nullable', 'string', 'max:500'],
        ]);

        $path = '/'.ltrim(parse_url($data['path'], PHP_URL_PATH) ?: '/', '/');

        if (str_starts_with($path, '/admin')) {
            return response()->json(['tracked' => false]);
        }

        $refHost = '';

        if (! empty($data['referrer'])) {
            $parsedHost = parse_url($data['referrer'], PHP_URL_HOST);
            $ownHost = $request->getHost();

            if (is_string($parsedHost) && $parsedHost !== '' && strcasecmp($parsedHost, $ownHost) !== 0) {
                $refHost = Str::limit(strtolower($parsedHost), 160, '');
            }
        }

        $device = 'desktop';

        if (preg_match('/ipad|tablet/', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobi|iphone|android/', $ua)) {
            $device = 'mobile';
        }

        $today = now()->toDateString();

        try {
            $row = SeoPageView::query()->firstOrCreate(
                [
                    'path' => $path,
                    'referrer_host' => $refHost,
                    'device' => $device,
                    'viewed_date' => $today,
                ],
                [
                    'page_title' => $data['title'] ?? null,
                    'views' => 0,
                ],
            );

            $row->increment('views');

            if (! empty($data['title']) && $row->page_title !== $data['title']) {
                $row->update(['page_title' => $data['title']]);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['tracked' => true]);
    }
}
