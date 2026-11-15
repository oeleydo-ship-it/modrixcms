<?php

namespace Modrix\Testimonials;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use App\Support\Settings;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modrix\Testimonials\Models\Testimonial;
use Throwable;

class TestimonialsServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $this->block('showcase', [
            'label' => 'Testimonials Showcase',
            'description' => 'Display approved customer testimonials with star ratings and summary score.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => [
                    'grid' => '3-column grid',
                    'spotlight' => 'Featured spotlight + grid',
                    'dark' => 'Dark theme section',
                ]],
                ['name' => 'count', 'label' => 'Max testimonials', 'type' => 'number', 'min' => 1, 'max' => 24],
                ['name' => 'featured_only', 'label' => 'Show featured only', 'type' => 'toggle'],
                ['name' => 'show_rating_summary', 'label' => 'Show average rating summary', 'type' => 'toggle'],
            ],
            'defaults' => [
                'anchor' => 'testimonials',
                'eyebrow' => 'Customer stories',
                'title' => 'Loved by teams shipping faster.',
                'subtitle' => 'Verified feedback from store owners and agencies.',
                'layout' => 'grid',
                'count' => 6,
                'featured_only' => false,
                'show_rating_summary' => true,
            ],
        ]);

        $this->block('submit', [
            'label' => 'Review Submission Form',
            'description' => 'Let visitors submit a star rating and testimonial for moderation.',
            'fields' => [
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'button_label', 'label' => 'Button label', 'type' => 'text'],
                ['name' => 'show_company', 'label' => 'Include role & company fields', 'type' => 'toggle'],
            ],
            'defaults' => [
                'anchor' => 'leave-review',
                'title' => 'Share your experience',
                'subtitle' => 'How has Modrix helped your business?',
                'button_label' => 'Submit review',
                'show_company' => true,
            ],
        ]);

        Inertia::share('testimonials', fn (): array => [
            'submitUrl' => route('plugin.modrix.testimonials.submit'),
            'submitted' => (bool) session('testimonials.submitted'),
            'successMessage' => Cms::setting($this->name(), 'success_message', 'Thank you for sharing your review!'),
        ]);

        $this->filter('block.data', function (array $data, string $type): array {
            if ($type !== $this->name().':showcase') {
                return $data;
            }

            try {
                if (! Schema::hasTable('testimonials')) {
                    $data['items'] = [];
                    $data['summary'] = ['average' => 0, 'count' => 0];

                    return $data;
                }

                $query = Testimonial::query()->approved();
                $totalCount = (clone $query)->count();
                $avg = $totalCount > 0 ? round((float) (clone $query)->avg('rating'), 1) : 0;

                if (! empty($data['featured_only'])) {
                    $query->where('is_featured', true);
                }

                $items = $query
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->latest()
                    ->take((int) ($data['count'] ?? 6))
                    ->get()
                    ->map(fn (Testimonial $t): array => [
                        'id' => $t->id,
                        'author_name' => $t->author_name,
                        'author_role' => $t->author_role,
                        'company' => $t->company,
                        'rating' => $t->rating,
                        'quote' => $t->quote,
                        'is_featured' => $t->is_featured,
                        'verified' => $t->verified,
                    ])
                    ->all();

                $data['items'] = $items;
                $data['summary'] = ['average' => $avg, 'count' => $totalCount];
            } catch (Throwable) {
                $data['items'] = [];
                $data['summary'] = ['average' => 0, 'count' => 0];
            }

            return $data;
        });

        $this->hook('head', function (): string {
            $out = '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'testimonials.css')).'">';

            if (! Cms::setting($this->name(), 'enable_schema', true)) {
                return $out;
            }

            try {
                if (! Schema::hasTable('testimonials')) {
                    return $out;
                }

                $count = Testimonial::query()->approved()->count();

                if ($count === 0) {
                    return $out;
                }

                $avg = round((float) Testimonial::query()->approved()->avg('rating'), 2);
                $siteName = (string) app(Settings::class)->get('site_name', 'Modrix');

                $schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $siteName,
                    'aggregateRating' => [
                        '@type' => 'AggregateRating',
                        'ratingValue' => $avg,
                        'reviewCount' => $count,
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ],
                ];

                $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
                $out .= "\n".'<script type="application/ld+json">'.$json.'</script>';
            } catch (Throwable) {
                // Ignore schema errors before migration.
            }

            return $out;
        });
    }
}
