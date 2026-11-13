<?php

namespace App\PageBuilder;

use App\Cms\BlockRegistry;
use App\Support\Settings;
use Illuminate\Support\Str;

/**
 * Page builder: the block types a page can be made of, their fields and
 * default content, plus cleaning of what the admin editor sends.
 *
 * A page's blocks are stored as JSON:
 *   [{ "id": "b1x2", "type": "hero", "hidden": false, "data": { ...fields } }, ...]
 *
 * Field types: text, textarea, markdown, url, number, select, toggle,
 * list (array of strings), repeater (array of rows), icon, image.
 */
class PageBuilder
{
    public const MAX_BLOCKS = 60;

    /** URL shortcuts that follow Settings → General → Links. */
    public const URL_TOKENS = ['@github', '@marketplace'];

    public function __construct(private Settings $settings) {}

    /** @return array<string, array{label: string, description: string, fields: array, defaults: array}> */
    public static function definitions(): array
    {
        $button = fn (string $prefix, string $label) => [
            ['name' => "{$prefix}_label", 'label' => "{$label} text", 'type' => 'text'],
            ['name' => "{$prefix}_url", 'label' => "{$label} link", 'type' => 'url'],
        ];

        $heading = [
            ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
            ['name' => 'text', 'label' => 'Intro text', 'type' => 'textarea'],
        ];

        $definitions = [
            'hero' => [
                'label' => 'Hero',
                'description' => 'Big headline, intro, buttons and key numbers.',
                'fields' => [
                    ['name' => 'badge', 'label' => 'Badge', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Headline', 'type' => 'textarea', 'help' => 'Each line starts a new line on the page.'],
                    ['name' => 'highlight', 'label' => 'Highlighted last line', 'type' => 'text'],
                    ['name' => 'text', 'label' => 'Intro text', 'type' => 'textarea'],
                    ...$button('primary', 'Main button'),
                    ...$button('secondary', 'Second button'),
                    ['name' => 'facts', 'label' => 'Key numbers', 'type' => 'repeater', 'max' => 4, 'fields' => [
                        ['name' => 'value', 'label' => 'Value', 'type' => 'text'],
                        ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
                    ]],
                    ['name' => 'show_mockup', 'label' => 'Show the admin dashboard illustration', 'type' => 'toggle'],
                ],
                'defaults' => [
                    'badge' => 'Free & open source · MIT licensed',
                    'title' => "Your store.\nYour server.",
                    'highlight' => 'Zero platform fees.',
                    'text' => 'Modrix Commerce is a complete e-commerce platform built on Laravel 13. Install it on your own hosting, own every order and every byte of data, and add the features you need from a marketplace of free themes and modules.',
                    'primary_label' => 'Download free', 'primary_url' => '@github',
                    'secondary_label' => 'Browse the marketplace →', 'secondary_url' => '#marketplace',
                    'facts' => [
                        ['value' => '$0', 'label' => 'license'],
                        ['value' => '0%', 'label' => 'transaction fees'],
                        ['value' => '100%', 'label' => 'your data'],
                    ],
                    'show_mockup' => true,
                ],
            ],

            'page_header' => [
                'label' => 'Page header',
                'description' => 'Page title with an optional subtitle.',
                'fields' => [
                    ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea'],
                ],
                'defaults' => ['eyebrow' => '', 'title' => 'Page title', 'subtitle' => ''],
            ],

            'text' => [
                'label' => 'Text',
                'description' => 'Rich text written in Markdown.',
                'fields' => [
                    ['name' => 'content', 'label' => 'Content', 'type' => 'markdown', 'help' => '## Heading, **bold**, *italic*, [link](https://…), - list, > quote'],
                    ['name' => 'width', 'label' => 'Width', 'type' => 'select', 'options' => ['narrow' => 'Narrow (reading)', 'wide' => 'Wide']],
                ],
                'defaults' => ['content' => "## A heading\n\nWrite your text here.", 'width' => 'narrow'],
            ],

            'image' => [
                'label' => 'Image',
                'description' => 'A single image with a caption.',
                'fields' => [
                    ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                    ['name' => 'alt', 'label' => 'Description for screen readers', 'type' => 'text'],
                    ['name' => 'caption', 'label' => 'Caption', 'type' => 'text'],
                    ['name' => 'width', 'label' => 'Width', 'type' => 'select', 'options' => ['narrow' => 'Narrow', 'wide' => 'Wide']],
                ],
                'defaults' => ['image' => '', 'alt' => '', 'caption' => '', 'width' => 'wide'],
            ],

            'stack' => [
                'label' => 'Logo strip',
                'description' => 'A row of names, e.g. the tech stack or customers.',
                'fields' => [
                    ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
                    ['name' => 'items', 'label' => 'Names', 'type' => 'list'],
                ],
                'defaults' => [
                    'label' => 'Built on a stack you already trust',
                    'items' => ['Laravel 13', 'PHP 8.3+', 'MySQL 8', 'React 19', 'Inertia', 'Tailwind CSS 4'],
                ],
            ],

            'features' => [
                'label' => 'Feature grid',
                'description' => 'Cards with an icon, title and text.',
                'fields' => [
                    ...$heading,
                    ['name' => 'items', 'label' => 'Cards', 'type' => 'repeater', 'max' => 24, 'fields' => [
                        ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'badge', 'label' => 'Badge', 'type' => 'text'],
                        ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                        ['name' => 'chips', 'label' => 'Tags', 'type' => 'list'],
                        ['name' => 'size', 'label' => 'Size', 'type' => 'select', 'options' => ['normal' => 'Normal', 'wide' => 'Wide (2 columns)']],
                        ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['plain' => 'White', 'dark' => 'Dark', 'gradient' => 'Gradient']],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'features',
                    'eyebrow' => 'Everything in the core',
                    'title' => 'A full store out of the box, not a starter kit.',
                    'text' => 'Catalog, checkout, content, design and settings ship in the free core. No paid tier, no locked features.',
                    'items' => [
                        self::card('layout', 'Page builder', 'Design your homepage and pages visually with theme sections and blocks. Reorder, show or hide, and edit content live in the theme customizer — no code required.', ['Sections', 'Blocks', 'Live preview'], 'wide', 'dark'),
                        self::card('page', 'Page management', 'Create About, FAQ, policy and landing pages with a rich-text editor, SEO fields and menu placement.'),
                        self::card('lines', 'Blog management', 'Posts, categories and authors to bring search traffic to your store.', [], 'normal', 'plain', 'Coming soon'),
                        self::card('sparkle', 'AI integrated', 'Connect your own AI provider key in Admin → AI and let it write product descriptions, page copy and SEO text. Your key, your bill — no middleman markup.', ['OpenAI', 'Bring your own key'], 'wide', 'gradient'),
                        self::card('settings', 'General settings', 'Store details, social links, maintenance mode, registration, password rules, two-factor and session limits — all in one place.'),
                        self::card('mail', 'SMTP settings', 'Send order and account emails through your own mail server: host, port, TLS/SSL, sender name and reply-to.'),
                        self::card('image', 'Custom logo & branding', 'Upload a logo, a dark-mode logo, a login-screen logo and favicon, and name your admin panel.'),
                        self::card('cart', 'Catalog, cart & checkout', 'Products, variants, attributes, brands, inventory, coupons, taxes, shipping and guest checkout.'),
                        self::card('receipt', 'Orders & invoices', 'Order management, bulk actions, PDF invoices, digital downloads and sales reports.'),
                        self::card('globe', 'Multi-language & SEO', 'Translate the storefront and your content, with an automatic sitemap for search engines.'),
                        self::card('shield', 'Staff roles & audit log', 'Give each team member exactly the permissions they need, and see who changed what.'),
                        self::card('refresh', 'Backups & one-click updates', 'Scheduled backups and core updates with automatic rollback if anything goes wrong.'),
                    ],
                ],
            ],

            'marketplace' => [
                'label' => 'Marketplace showcase',
                'description' => 'Dark panel with checkpoints, module tags and theme tiles.',
                'fields' => [
                    ...$heading,
                    ['name' => 'points', 'label' => 'Checkpoints', 'type' => 'list'],
                    ...$button('button', 'Button'),
                    ['name' => 'modules', 'label' => 'Module names', 'type' => 'list'],
                    ['name' => 'themes', 'label' => 'Theme names', 'type' => 'list'],
                ],
                'defaults' => [
                    'anchor' => 'marketplace',
                    'eyebrow' => 'Modrix Cloud marketplace',
                    'title' => 'Free themes and modules, one click away.',
                    'text' => 'The core stays lean. Everything else is a module or theme you install from **Admin → Extensions** with the built-in Modrix Cloud Connector — or upload as a ZIP. Packages are signed and verified before they touch your store.',
                    'points' => ['Install, update and roll back from your admin', 'No rebuild on the server — screens ship prebuilt', 'Build and publish your own modules and themes'],
                    'button_label' => 'Explore Modrix Cloud', 'button_url' => '@marketplace',
                    'modules' => ['PayPal', 'Razorpay', 'Product Reviews', 'Wishlist', 'Newsletter', 'Advanced SEO', 'Google Analytics', 'Flash Sales', 'Cart Recovery', 'Multi-Vendor', 'SMS', 'Store Pickup', 'Order Returns', 'GDPR Consent', 'AI Assistant'],
                    'themes' => ['Circuit', 'Atelier', 'Nord', 'Linden', 'Sable', 'Tempo'],
                ],
            ],

            'steps' => [
                'label' => 'Steps + code',
                'description' => 'Numbered columns with an optional code box.',
                'fields' => [
                    ...$heading,
                    ['name' => 'items', 'label' => 'Steps', 'type' => 'repeater', 'max' => 6, 'fields' => [
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'help' => '`code`, **bold** and [links](https://…) work.'],
                    ]],
                    ['name' => 'code_title', 'label' => 'Code box title', 'type' => 'text'],
                    ['name' => 'code', 'label' => 'Code', 'type' => 'markdown', 'help' => 'Lines starting with # show as comments. Leave empty to hide the box.'],
                ],
                'defaults' => [
                    'anchor' => 'deploy',
                    'eyebrow' => 'Deploy anywhere',
                    'title' => 'Runs on the hosting you already have.',
                    'text' => 'No vendor lock-in. If it runs PHP and MySQL, it runs Modrix Commerce.',
                    'items' => [
                        ['title' => 'Shared hosting', 'text' => 'cPanel, Plesk or DirectAdmin. Upload, open `/install`, and the web installer sets up the database and your store. No SSH needed.'],
                        ['title' => 'VPS or cloud server', 'text' => 'Full control on any Linux server with Nginx or Apache, PHP 8.3+ and MySQL 8 or MariaDB.'],
                        ['title' => 'Git platforms', 'text' => 'Push to deploy. The platform provides the database; the installer only creates your owner account.'],
                    ],
                    'code_title' => 'Quick start',
                    'code' => "# clone and set up\ngit clone https://github.com/Modrix-site/Modrix-Commerce.git\ncd Modrix-Commerce\ncomposer setup\ncomposer dev\n# then open /install to create your store",
                ],
            ],

            'comparison' => [
                'label' => 'Comparison',
                'description' => 'Text beside a “us vs. them” table.',
                'fields' => [
                    ...$heading,
                    ['name' => 'rows', 'label' => 'Rows', 'type' => 'repeater', 'max' => 10, 'fields' => [
                        ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
                        ['name' => 'us', 'label' => 'Ours', 'type' => 'text'],
                        ['name' => 'them', 'label' => 'Others (struck through)', 'type' => 'text'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'open-source',
                    'eyebrow' => 'Open source',
                    'title' => 'Free forever. Read every line.',
                    'text' => 'Modrix Commerce is MIT licensed. Use it for your own shop or your clients’ shops, fork it, extend it. No license keys for the core, no revenue share, no phoning home.',
                    'rows' => [
                        ['label' => 'License cost', 'us' => 'Free', 'them' => '$29–$299 / month'],
                        ['label' => 'Transaction fees', 'us' => '0%', 'them' => '0.5–2%'],
                        ['label' => 'Where your data lives', 'us' => 'Your server', 'them' => 'Their cloud'],
                        ['label' => 'Source code', 'us' => 'Open, MIT', 'them' => 'Closed'],
                    ],
                ],
            ],

            'latest_posts' => [
                'label' => 'Latest blog posts',
                'description' => 'The newest published posts. Hidden when there are none.',
                'fields' => [
                    ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                    ['name' => 'count', 'label' => 'Number of posts', 'type' => 'number', 'min' => 1, 'max' => 12],
                ],
                'defaults' => ['anchor' => 'blog', 'eyebrow' => 'From the blog', 'title' => 'News and guides.', 'count' => 3],
            ],

            'cta' => [
                'label' => 'Call to action',
                'description' => 'Coloured banner with a heading and buttons.',
                'fields' => [
                    ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                    ...$button('primary', 'Main button'),
                    ...$button('secondary', 'Second button'),
                ],
                'defaults' => [
                    'title' => 'Launch your store this afternoon.',
                    'text' => 'Download Modrix Commerce, install it on your server, and add free themes and modules as you grow.',
                    'primary_label' => 'Download free', 'primary_url' => '@github',
                    'secondary_label' => 'Visit the marketplace', 'secondary_url' => '@marketplace',
                ],
            ],
        ];

        // Blocks added by enabled plugins (types look like "vendor/package:block").
        $definitions += app(BlockRegistry::class)->all();

        // Every block can carry an anchor (#features) for menu and button links.
        foreach ($definitions as &$definition) {
            $definition['fields'][] = ['name' => 'anchor', 'label' => 'Section ID', 'type' => 'text', 'help' => 'Optional. Lets links point here, e.g. #features.'];
            $definition['defaults'] += ['anchor' => ''];
        }

        return $definitions;
    }

    /** Block definitions for the admin editor (JSON). */
    public static function forEditor(): array
    {
        return [
            'blocks' => self::definitions(),
            'icons' => Icons::ALL,
        ];
    }

    /** The landing page, as blocks. Used to seed the Home page and as the fallback home. */
    public static function landingBlocks(): array
    {
        return array_map(
            fn (string $type) => self::make($type),
            ['hero', 'stack', 'features', 'marketplace', 'steps', 'comparison', 'latest_posts', 'cta'],
        );
    }

    /** A new block of a type, filled with its default content. */
    public static function make(string $type, array $data = []): array
    {
        return [
            'id' => Str::lower(Str::random(8)),
            'type' => $type,
            'hidden' => false,
            'data' => array_replace(self::definitions()[$type]['defaults'], $data),
        ];
    }

    /**
     * Cleans blocks sent by the editor: unknown types and fields are dropped,
     * values are cut to size and URLs limited to safe schemes.
     */
    public static function sanitize(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        $definitions = self::definitions();
        $clean = [];

        foreach (array_slice(array_values($blocks), 0, self::MAX_BLOCKS) as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;

            if (! is_string($type) || ! isset($definitions[$type])) {
                continue;
            }

            $id = is_string($block['id'] ?? null) && preg_match('/^[a-z0-9]{1,16}$/', $block['id'])
                ? $block['id']
                : Str::lower(Str::random(8));

            $clean[] = [
                'id' => $id,
                'type' => $type,
                'hidden' => (bool) ($block['hidden'] ?? false),
                'data' => self::cleanFields($definitions[$type]['fields'], (array) ($block['data'] ?? [])),
            ];
        }

        return $clean;
    }

    private static function cleanFields(array $fields, array $input): array
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field['name']] = self::cleanValue($field, $input[$field['name']] ?? null);
        }

        return $data;
    }

    private static function cleanValue(array $field, mixed $value): mixed
    {
        $string = fn (int $max) => is_scalar($value) ? Str::limit(trim((string) $value), $max, '') : '';

        return match ($field['type']) {
            'text' => $string(300),
            'textarea' => $string(3000),
            'markdown' => $string(100000),
            'url' => self::cleanUrl($string(500)),
            'number' => max($field['min'] ?? 0, min($field['max'] ?? 1000, (int) (is_numeric($value) ? $value : ($field['min'] ?? 0)))),
            'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'select' => is_string($value) && isset($field['options'][$value]) ? $value : array_key_first($field['options']),
            'icon' => is_string($value) && isset(Icons::ALL[$value]) ? $value : 'sparkle',
            'image' => is_string($value) && preg_match('#^pages/[A-Za-z0-9_\-]+\.(jpe?g|png|webp|gif)$#', $value) ? $value : '',
            'list' => array_values(array_filter(array_map(
                fn ($item) => is_scalar($item) ? Str::limit(trim((string) $item), 200, '') : '',
                array_slice(is_array($value) ? $value : [], 0, 60),
            ), fn ($item) => $item !== '')),
            'repeater' => array_map(
                fn ($row) => self::cleanFields($field['fields'], is_array($row) ? $row : []),
                array_slice(is_array($value) ? array_values($value) : [], 0, $field['max'] ?? 20),
            ),
            default => null,
        };
    }

    private static function cleanUrl(string $url): string
    {
        if ($url === '' || in_array($url, self::URL_TOKENS, true)) {
            return $url;
        }

        return preg_match('~^(https?://|/(?!/)|#|mailto:)~i', $url) ? $url : '';
    }

    /** Resolves @github / @marketplace to the links in Settings. */
    public function url(?string $url): string
    {
        return match ($url) {
            '@github' => $this->settings->link('github'),
            '@marketplace' => $this->settings->link('marketplace'),
            null, '' => '#',
            default => $url,
        };
    }

    private static function card(string $icon, string $title, string $text, array $chips = [], string $size = 'normal', string $style = 'plain', string $badge = ''): array
    {
        return compact('icon', 'title', 'badge', 'text', 'chips', 'size', 'style');
    }
}
