<?php

namespace Modrix\SocialMedia\Http\Admin;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\SocialMedia\Models\SocialEvent;
use Modrix\SocialMedia\Models\SocialProfile;

class AnalyticsController
{
    public function index(): Response
    {
        $since = now()->subDays(30);

        $byPlatform = SocialEvent::query()
            ->select('platform', 'event_type', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy('platform', 'event_type')
            ->orderByDesc('total')
            ->get();

        $topPages = SocialEvent::query()
            ->select('page_url', DB::raw('MAX(page_title) as page_title'), DB::raw('COUNT(*) as total'))
            ->where('event_type', 'share')
            ->where('created_at', '>=', $since)
            ->groupBy('page_url')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recent = SocialEvent::query()->latest('created_at')->limit(25)->get();

        return Inertia::render('plugin:modrix/social-media/AnalyticsIndex', [
            'summary' => [
                'shares_30d' => SocialEvent::query()->where('event_type', 'share')->where('created_at', '>=', $since)->count(),
                'clicks_30d' => SocialEvent::query()->where('event_type', 'click')->where('created_at', '>=', $since)->count(),
                'active_profiles' => SocialProfile::query()->where('is_active', true)->count(),
                'total_profile_clicks' => (int) SocialProfile::query()->sum('clicks_count'),
            ],
            'byPlatform' => $byPlatform,
            'topPages' => $topPages,
            'recent' => $recent,
        ]);
    }
}
