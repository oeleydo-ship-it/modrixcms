<?php

namespace Modrix\PromoBars\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modrix\PromoBars\Models\PromoCampaign;

class TrackController
{
    public function __invoke(Request $request, PromoCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'event' => ['required', 'in:impression,click'],
        ]);

        if ($data['event'] === 'impression') {
            $campaign->increment('impressions');
        } else {
            $campaign->increment('clicks');
        }

        return response()->json([
            'ok' => true,
            'impressions' => $campaign->impressions,
            'clicks' => $campaign->clicks,
        ]);
    }
}
