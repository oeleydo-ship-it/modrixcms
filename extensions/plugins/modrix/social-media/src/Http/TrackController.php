<?php

namespace Modrix\SocialMedia\Http;

use App\Cms\Cms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modrix\SocialMedia\Models\SocialEvent;
use Modrix\SocialMedia\Models\SocialProfile;

class TrackController
{
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'in:share,click'],
            'platform' => ['required', 'string', 'max:40'],
            'page_url' => ['required', 'string', 'max:500'],
            'page_title' => ['nullable', 'string', 'max:200'],
        ]);

        if (Cms::setting('modrix/social-media', 'track_events', true)) {
            SocialEvent::query()->create([
                'event_type' => $data['event_type'],
                'platform' => Str::lower($data['platform']),
                'page_url' => $data['page_url'],
                'page_title' => $data['page_title'] ?? null,
                'created_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function go(Request $request, SocialProfile $profile): RedirectResponse
    {
        abort_unless($profile->is_active, 404);

        if (Cms::setting('modrix/social-media', 'track_events', true)) {
            $profile->increment('clicks_count');
            SocialEvent::query()->create([
                'event_type' => 'click',
                'platform' => $profile->platform,
                'page_url' => mb_substr(url()->previous() ?: '/', 0, 500),
                'page_title' => $profile->label,
                'created_at' => now(),
            ]);
        }

        return redirect()->away($profile->url);
    }
}
