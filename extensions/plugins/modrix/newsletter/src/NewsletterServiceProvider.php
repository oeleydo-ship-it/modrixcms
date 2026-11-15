<?php

namespace Modrix\Newsletter;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modrix\Newsletter\Models\Subscriber;
use Throwable;

class NewsletterServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $this->block('signup', [
            'label' => 'Newsletter Signup',
            'description' => 'Email subscription card or banner with optional name, consent checkbox, and tag assignment.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'button_label', 'label' => 'Button label', 'type' => 'text'],
                ['name' => 'tag', 'label' => 'Auto-tag subscribers (optional)', 'type' => 'text', 'help' => 'e.g. blog-readers, waitlist'],
                ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => [
                    'card' => 'Light card',
                    'dark' => 'Dark spotlight',
                    'banner' => 'Compact horizontal banner',
                ]],
                ['name' => 'show_name', 'label' => 'Ask for subscriber name', 'type' => 'toggle'],
                ['name' => 'require_consent', 'label' => 'Show privacy consent checkbox', 'type' => 'toggle'],
                ['name' => 'consent_label', 'label' => 'Consent text', 'type' => 'text'],
                ['name' => 'show_count', 'label' => 'Show subscriber count badge', 'type' => 'toggle'],
            ],
            'defaults' => [
                'anchor' => 'newsletter',
                'eyebrow' => 'Newsletter',
                'title' => 'Get updates straight to your inbox.',
                'description' => 'Product releases, guides, and practical tips. No spam, unsubscribe any time.',
                'button_label' => 'Subscribe',
                'tag' => 'website',
                'style' => 'card',
                'show_name' => true,
                'require_consent' => false,
                'consent_label' => 'I agree to receive email updates and can unsubscribe at any time.',
                'show_count' => true,
            ],
        ]);

        Inertia::share('newsletter', fn (): array => [
            'subscribeUrl' => route('plugin.modrix.newsletter.subscribe'),
            'status' => session('newsletter.status'),
            'message' => session('newsletter.message'),
        ]);

        $this->filter('block.data', function (array $data, string $type): array {
            if ($type !== $this->name().':signup') {
                return $data;
            }

            $count = 0;

            try {
                if (Schema::hasTable('nl_subscribers')) {
                    $count = Subscriber::query()->subscribed()->count();
                }
            } catch (Throwable) {
                $count = 0;
            }

            $data['subscriber_count'] = $count;

            return $data;
        });

        $this->hook('head', fn (): string => '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'newsletter.css')).'">');
    }
}
