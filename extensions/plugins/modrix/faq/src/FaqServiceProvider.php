<?php

namespace Modrix\Faq;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use App\Support\Markdown;
use Illuminate\Support\Facades\Schema;
use Modrix\Faq\Models\FaqItem;
use Throwable;

class FaqServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $this->block('accordion', [
            'label' => 'FAQ Accordion',
            'description' => 'Searchable, categorized FAQ accordion with helpfulness voting.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'category', 'label' => 'Filter category (optional)', 'type' => 'text', 'help' => 'Leave empty to show all categories.'],
                ['name' => 'searchable', 'label' => 'Show search input & category tabs', 'type' => 'toggle'],
                ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => [
                    'single' => 'Centered reading column',
                    'split' => 'Heading beside questions',
                ]],
            ],
            'defaults' => [
                'anchor' => 'faq',
                'eyebrow' => 'Questions & answers',
                'title' => 'Frequently asked questions',
                'subtitle' => 'Everything you need to know before getting started.',
                'category' => '',
                'searchable' => true,
                'layout' => 'single',
            ],
        ]);

        $this->filter('block.data', function (array $data, string $type): array {
            if ($type !== $this->name().':accordion') {
                return $data;
            }

            $data['allow_voting'] = (bool) Cms::setting($this->name(), 'allow_voting', true);
            $data['allow_multiple_open'] = (bool) Cms::setting($this->name(), 'allow_multiple_open', false);
            $data['items'] = [];

            try {
                if (! Schema::hasTable('faq_items')) {
                    return $data;
                }

                $category = trim((string) ($data['category'] ?? ''));

                $data['items'] = FaqItem::query()
                    ->published()
                    ->when($category !== '', fn ($q) => $q->where('category', $category))
                    ->get()
                    ->map(fn (FaqItem $item): array => [
                        'id' => $item->id,
                        'category' => $item->category,
                        'question' => $item->question,
                        'answer_html' => Markdown::toHtml($item->answer_markdown),
                        'helpful_yes' => $item->helpful_yes,
                        'helpful_no' => $item->helpful_no,
                        'vote_url' => route('plugin.modrix.faq.vote', $item->id),
                    ])
                    ->all();
            } catch (Throwable) {
                $data['items'] = [];
            }

            return $data;
        });

        $this->hook('head', function (): string {
            $out = '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'faq.css')).'">';

            if (! Cms::setting($this->name(), 'enable_schema', true)) {
                return $out;
            }

            try {
                if (! Schema::hasTable('faq_items')) {
                    return $out;
                }

                $items = FaqItem::query()->published()->take(50)->get();

                if ($items->isEmpty()) {
                    return $out;
                }

                $schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $items->map(fn (FaqItem $item): array => [
                        '@type' => 'Question',
                        'name' => $item->question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => trim(strip_tags(Markdown::toHtml($item->answer_markdown))),
                        ],
                    ])->all(),
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
