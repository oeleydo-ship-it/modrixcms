<?php

namespace Modrix\PromoBars\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\PromoBars\Models\PromoCampaign;

class CampaignsController
{
    public function index(): Response
    {
        $campaigns = PromoCampaign::query()
            ->orderByDesc('is_active')
            ->latest('id')
            ->get()
            ->map(fn (PromoCampaign $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'title' => $c->title,
                'body' => $c->body,
                'badge_text' => $c->badge_text,
                'cta_label' => $c->cta_label,
                'cta_url' => $c->cta_url,
                'coupon_code' => $c->coupon_code,
                'bg_color' => $c->bg_color,
                'text_color' => $c->text_color,
                'accent_color' => $c->accent_color,
                'trigger_type' => $c->trigger_type,
                'trigger_value' => $c->trigger_value,
                'ends_at' => $c->ends_at?->format('Y-m-d\TH:i'),
                'ends_at_human' => $c->ends_at?->toDayDateTimeString(),
                'is_active' => $c->is_active,
                'impressions' => $c->impressions,
                'clicks' => $c->clicks,
                'ctr' => $c->impressions > 0 ? round(($c->clicks / $c->impressions) * 100, 1) : 0.0,
            ])
            ->all();

        $totals = [
            'active' => PromoCampaign::query()->active()->count(),
            'impressions' => (int) PromoCampaign::query()->sum('impressions'),
            'clicks' => (int) PromoCampaign::query()->sum('clicks'),
        ];

        return Inertia::render('plugin:modrix/promo-bars/CampaignsIndex', [
            'campaigns' => $campaigns,
            'totals' => $totals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PromoCampaign::query()->create($this->validated($request));

        return back()->with('status', 'Promotion created.');
    }

    public function update(Request $request, PromoCampaign $campaign): RedirectResponse
    {
        $campaign->update($this->validated($request));

        return back()->with('status', 'Promotion updated.');
    }

    public function toggle(PromoCampaign $campaign): RedirectResponse
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        return back()->with('status', $campaign->is_active ? 'Promotion activated.' : 'Promotion paused.');
    }

    public function destroy(PromoCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('status', 'Promotion deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'type' => ['required', 'in:bar,popup'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:1000'],
            'badge_text' => ['nullable', 'string', 'max:60'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|#)/i'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'bg_color' => ['required', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'trigger_type' => ['required', 'in:immediate,delay,scroll'],
            'trigger_value' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'ends_at' => ['nullable', 'date'],
        ]);

        return [
            ...$data,
            'trigger_value' => (int) ($data['trigger_value'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
