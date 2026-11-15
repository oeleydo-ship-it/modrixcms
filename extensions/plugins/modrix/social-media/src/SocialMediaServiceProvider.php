<?php

namespace Modrix\SocialMedia;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modrix\SocialMedia\Models\SocialProfile;
use Throwable;

class SocialMediaServiceProvider extends PluginServiceProvider
{
    public const PLATFORMS = [
        'x' => 'X (Twitter)',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'github' => 'GitHub',
        'discord' => 'Discord',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'pinterest' => 'Pinterest',
        'threads' => 'Threads',
        'email' => 'Email',
        'website' => 'Website',
    ];

    protected function bootPlugin(): void
    {
        $this->block('links', [
            'label' => 'Social Profiles',
            'description' => 'Display your connected social profiles from Admin → Social Links.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => [
                    'cards' => 'Profile cards',
                    'pills' => 'Compact pills',
                    'minimal' => 'Icon row',
                ]],
                ['name' => 'show_followers', 'label' => 'Show follower counts', 'type' => 'toggle'],
            ],
            'defaults' => [
                'anchor' => 'social',
                'eyebrow' => 'Connect',
                'title' => 'Follow us everywhere',
                'subtitle' => 'Updates, releases, and behind-the-scenes stories.',
                'style' => 'cards',
                'show_followers' => true,
            ],
        ]);

        $this->block('share', [
            'label' => 'Social Share Bar',
            'description' => 'Interactive share buttons for the current page with click tracking.',
            'fields' => [
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Subtext', 'type' => 'text'],
                ['name' => 'style', 'label' => 'Appearance', 'type' => 'select', 'options' => [
                    'buttons' => 'Solid buttons',
                    'compact' => 'Compact strip',
                    'dark' => 'Dark banner',
                ]],
            ],
            'defaults' => [
                'anchor' => 'share',
                'title' => 'Share this page',
                'subtitle' => 'Spread the word with your network.',
                'style' => 'buttons',
            ],
        ]);

        $this->block('feed', [
            'label' => 'Social Showcase Feed',
            'description' => 'Curated social posts, highlights, and community shoutouts.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'items', 'label' => 'Posts', 'type' => 'repeater', 'max' => 12, 'fields' => [
                    ['name' => 'platform', 'label' => 'Platform', 'type' => 'select', 'options' => self::PLATFORMS],
                    ['name' => 'author', 'label' => 'Author name', 'type' => 'text'],
                    ['name' => 'handle', 'label' => 'Handle', 'type' => 'text'],
                    ['name' => 'content', 'label' => 'Post text', 'type' => 'textarea'],
                    ['name' => 'image', 'label' => 'Image (optional)', 'type' => 'image'],
                    ['name' => 'post_url', 'label' => 'Post URL', 'type' => 'url'],
                    ['name' => 'date', 'label' => 'Date label', 'type' => 'text'],
                    ['name' => 'likes', 'label' => 'Likes / reactions', 'type' => 'text'],
                ]],
            ],
            'defaults' => [
                'anchor' => 'social-feed',
                'eyebrow' => 'Community',
                'title' => 'Latest from our socials',
                'subtitle' => 'Highlights from X, Instagram, and LinkedIn.',
                'items' => [
                    [
                        'platform' => 'x',
                        'author' => 'Modrix',
                        'handle' => '@modrixcommerce',
                        'content' => 'Shipped our new plugin bundle with Form Builder, Social Suite, Newsletter, and SEO tools.',
                        'image' => '',
                        'post_url' => 'https://x.com',
                        'date' => 'Today',
                        'likes' => '142',
                    ],
                ],
            ],
        ]);

        Inertia::share('socialMedia', fn (): array => [
            'sharePlatforms' => $this->sharePlatforms(),
            'trackUrl' => route('plugin.modrix.social-media.track'),
        ]);

        $this->filter('block.data', function (array $data, string $type): array {
            if ($type !== $this->name().':links') {
                return $data;
            }

            $data['profiles'] = $this->activeProfiles();

            return $data;
        });

        $this->hook('head', function (): string {
            $tags = [
                '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'social-media.css')).'">',
                '<meta name="twitter:card" content="summary_large_image">',
            ];

            $handle = trim((string) Cms::setting($this->name(), 'twitter_handle', ''));

            if ($handle !== '') {
                $normalized = str_starts_with($handle, '@') ? $handle : '@'.$handle;
                $tags[] = '<meta name="twitter:site" content="'.e($normalized).'">';
            }

            $ogImage = trim((string) Cms::setting($this->name(), 'default_og_image', ''));

            if ($ogImage !== '') {
                $tags[] = '<meta property="og:image" content="'.e($ogImage).'">';
            }

            return implode("\n", $tags);
        });

        $this->hook('body.end', fn (): string => $this->renderFloatingBar());
    }

    /** @return array<int, string> */
    private function sharePlatforms(): array
    {
        $raw = (string) Cms::setting($this->name(), 'share_platforms', 'x,facebook,linkedin,whatsapp,telegram,email,copy');
        $allowed = ['x', 'facebook', 'linkedin', 'whatsapp', 'telegram', 'reddit', 'email', 'copy'];
        $items = array_values(array_intersect(
            array_filter(array_map(fn ($v) => strtolower(trim($v)), explode(',', $raw))),
            $allowed,
        ));

        return $items !== [] ? $items : ['x', 'facebook', 'linkedin', 'copy'];
    }

    /** @return array<int, array<string, mixed>> */
    private function activeProfiles(): array
    {
        try {
            if (! Schema::hasTable('social_profiles')) {
                return [];
            }

            return SocialProfile::query()
                ->active()
                ->get()
                ->map(fn (SocialProfile $p): array => [
                    'id' => $p->id,
                    'platform' => $p->platform,
                    'label' => $p->label,
                    'handle' => $p->handle,
                    'url' => route('plugin.modrix.social-media.go', $p->id),
                    'raw_url' => $p->url,
                    'followers' => $p->followers,
                    'open_new_tab' => $p->open_new_tab,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function renderFloatingBar(): string
    {
        // Not inside a framed form: the page around it already has the bar.
        if (request()->boolean('embed') || ! Cms::setting($this->name(), 'floating_bar', true)) {
            return '';
        }

        $position = Cms::setting($this->name(), 'floating_position', 'left') === 'right' ? 'right' : 'left';
        $trackUrl = e(route('plugin.modrix.social-media.track'));
        $labels = [
            'x' => 'X',
            'facebook' => 'FB',
            'linkedin' => 'IN',
            'whatsapp' => 'WA',
            'telegram' => 'TG',
            'reddit' => 'RD',
            'email' => 'Mail',
            'copy' => 'Copy',
        ];

        $buttons = '';

        foreach ($this->sharePlatforms() as $platform) {
            $label = e($labels[$platform] ?? strtoupper($platform));
            $plat = e($platform);
            $buttons .= '<button type="button" class="msm-float-btn" data-msm-share="'.$plat.'" title="Share on '.$label.'" aria-label="Share on '.$label.'">'.$label.'</button>';
        }

        return '<aside class="msm-float is-'.$position.'" id="msm-float-bar" data-track-url="'.$trackUrl.'" aria-label="Share this page">'
            .$buttons
            .'</aside>';
    }
}
