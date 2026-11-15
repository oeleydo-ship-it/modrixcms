<?php

namespace Modrix\PromoBars;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use App\Support\Markdown;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modrix\PromoBars\Models\PromoCampaign;
use Throwable;

class PromoBarsServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $this->hook('head', fn (): string => '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'promo-bars.css')).'">');
        $this->hook('admin.head', fn (): string => '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'promo-bars.css')).'">');

        $this->hook('body.end', function (): string {
            if (request()->is('admin', 'admin/*')) {
                return '';
            }

            return '<div id="modrix-promo-root"></div>';
        });

        Inertia::share('promoBars', function (): ?array {
            if (request()->is('admin', 'admin/*')) {
                return null;
            }

            if (! Cms::setting($this->name(), 'enable_global', true)) {
                return null;
            }

            try {
                if (! Schema::hasTable('promo_campaigns')) {
                    return null;
                }

                $campaigns = PromoCampaign::query()
                    ->active()
                    ->orderByRaw("CASE WHEN type = 'bar' THEN 0 ELSE 1 END")
                    ->latest('id')
                    ->get()
                    ->map(fn (PromoCampaign $c): array => $c->toPublicPayload())
                    ->all();
            } catch (Throwable) {
                return null;
            }

            return [
                'dismissDays' => max(1, (int) Cms::setting($this->name(), 'dismiss_days', 7)),
                'campaigns' => $campaigns,
            ];
        });

        $this->block('banner', [
            'label' => 'Promo Banner',
            'description' => 'Conversion banner with optional coupon copy, countdown timer, and CTA button.',
            'icon' => 'bolt',
            'fields' => [
                ['name' => 'badge_text', 'label' => 'Badge text', 'type' => 'text'],
                ['name' => 'heading', 'label' => 'Headline', 'type' => 'text', 'required' => true],
                ['name' => 'body', 'label' => 'Details', 'type' => 'markdown'],
                ['name' => 'coupon_code', 'label' => 'Coupon code (optional)', 'type' => 'text'],
                ['name' => 'cta_label', 'label' => 'Button label', 'type' => 'text'],
                ['name' => 'cta_url', 'label' => 'Button URL', 'type' => 'url'],
                ['name' => 'ends_at', 'label' => 'Countdown target (ISO / YYYY-MM-DD HH:MM, optional)', 'type' => 'text'],
                ['name' => 'theme', 'label' => 'Color theme', 'type' => 'select', 'options' => [
                    'dark' => 'Dark slate',
                    'accent' => 'Warm amber',
                    'light' => 'Light bordered',
                ]],
            ],
            'defaults' => [
                'badge_text' => 'Limited Offer',
                'heading' => 'Save 20% on your first order',
                'body' => 'Use your code at checkout before the timer runs out.',
                'coupon_code' => 'SAVE20',
                'cta_label' => 'Shop Now',
                'cta_url' => '/',
                'ends_at' => '',
                'theme' => 'dark',
            ],
            'render' => function (array $data): array {
                return [
                    ...$data,
                    'body_html' => filled($data['body'] ?? null) ? Markdown::toHtml((string) $data['body']) : '',
                ];
            },
        ]);
    }
}
