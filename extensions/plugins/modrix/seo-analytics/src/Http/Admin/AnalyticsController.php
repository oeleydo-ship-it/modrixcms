<?php

namespace Modrix\SeoAnalytics\Http\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\SeoAnalytics\Models\SeoPageView;

class AnalyticsController
{
    public function index(Request $request): Response
    {
        $days = in_array((int) $request->query('days', 30), [7, 30, 90], true) ? (int) $request->query('days', 30) : 30;
        $since = now()->subDays($days - 1)->toDateString();
        $today = now()->toDateString();

        $totalViews = (int) SeoPageView::query()->where('viewed_date', '>=', $since)->sum('views');
        $todayViews = (int) SeoPageView::query()->where('viewed_date', $today)->sum('views');
        $uniquePages = SeoPageView::query()->where('viewed_date', '>=', $since)->distinct('path')->count('path');

        $dailyRows = SeoPageView::query()
            ->select('viewed_date', DB::raw('SUM(views) as total'))
            ->where('viewed_date', '>=', $since)
            ->groupBy('viewed_date')
            ->orderBy('viewed_date')
            ->get()
            ->keyBy(fn ($row) => $row->viewed_date->toDateString());

        $chart = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chart[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'views' => (int) ($dailyRows->get($date)->total ?? 0),
            ];
        }

        $topPages = SeoPageView::query()
            ->select('path', DB::raw('MAX(page_title) as page_title'), DB::raw('SUM(views) as total'))
            ->where('viewed_date', '>=', $since)
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $topReferrers = SeoPageView::query()
            ->select('referrer_host', DB::raw('SUM(views) as total'))
            ->where('viewed_date', '>=', $since)
            ->where('referrer_host', '!=', '')
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $devices = SeoPageView::query()
            ->select('device', DB::raw('SUM(views) as total'))
            ->where('viewed_date', '>=', $since)
            ->groupBy('device')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('plugin:modrix/seo-analytics/AnalyticsIndex', [
            'days' => $days,
            'summary' => [
                'total_views' => $totalViews,
                'today_views' => $todayViews,
                'unique_pages' => $uniquePages,
                'top_referrer' => $topReferrers->first()?->referrer_host ?: 'Direct',
            ],
            'chart' => $chart,
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
            'devices' => $devices,
        ]);
    }
}
