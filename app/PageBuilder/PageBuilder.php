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
                'category' => 'hero',
                'icon' => 'rocket',
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
                    'text' => 'Modrix CMS is a complete e-commerce platform built on Laravel 13. Install it on your own hosting, own every order and every byte of data, and add the features you need from a marketplace of free themes and modules.',
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
                'category' => 'hero',
                'icon' => 'page',
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
                'category' => 'content',
                'icon' => 'lines',
                'description' => 'Rich text written in Markdown.',
                'fields' => [
                    ['name' => 'content', 'label' => 'Content', 'type' => 'markdown', 'help' => '## Heading, **bold**, *italic*, [link](https://…), - list, > quote'],
                    ['name' => 'width', 'label' => 'Width', 'type' => 'select', 'options' => ['narrow' => 'Narrow (reading)', 'wide' => 'Wide']],
                ],
                'defaults' => ['content' => "## A heading\n\nWrite your text here.", 'width' => 'narrow'],
            ],

            'image' => [
                'label' => 'Image',
                'category' => 'media',
                'icon' => 'image',
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
                'category' => 'social',
                'icon' => 'award',
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
                'category' => 'content',
                'icon' => 'layout',
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

            'banner_split' => [
                'label' => 'Split content + showcase',
                'category' => 'content',
                'icon' => 'layers',
                'description' => 'Two-column feature section with checklist points, image or highlight card, and dual buttons.',
                'fields' => [
                    ...$heading,
                    ['name' => 'position', 'label' => 'Visual placement', 'type' => 'select', 'options' => ['right' => 'Visual on the right', 'left' => 'Visual on the left']],
                    ['name' => 'theme', 'label' => 'Section style', 'type' => 'select', 'options' => ['plain' => 'Warm paper', 'white' => 'White card', 'dark' => 'Dark contrast']],
                    ['name' => 'points', 'label' => 'Checklist points', 'type' => 'list'],
                    ...$button('primary', 'Main button'),
                    ...$button('secondary', 'Second button'),
                    ['name' => 'image', 'label' => 'Showcase image (optional)', 'type' => 'image'],
                    ['name' => 'alt', 'label' => 'Image alt text', 'type' => 'text'],
                    ['name' => 'stat_value', 'label' => 'Highlight metric value', 'type' => 'text'],
                    ['name' => 'stat_label', 'label' => 'Highlight metric label', 'type' => 'text'],
                ],
                'defaults' => [
                    'eyebrow' => 'Own your stack',
                    'title' => 'Designed for speed, built for complete ownership.',
                    'text' => 'Run your storefront with zero recurring SaaS lock-in, full access to every template and database table, and instant theme customization.',
                    'position' => 'right',
                    'theme' => 'white',
                    'points' => [
                        'Full source code access with zero transaction fees',
                        'Sub-50ms server response time with built-in caching',
                        'One-click switch between block editor and visual canvas',
                    ],
                    'primary_label' => 'Explore features',
                    'primary_url' => '#features',
                    'secondary_label' => 'Read documentation →',
                    'secondary_url' => '@github',
                    'image' => '',
                    'alt' => 'Platform architecture preview',
                    'stat_value' => '99.98%',
                    'stat_label' => 'Uptime across self-hosted stores',
                ],
            ],

            'stats' => [
                'label' => 'Stats & metrics band',
                'category' => 'social',
                'icon' => 'chart',
                'description' => 'High-impact numbers and proof metrics in 3 or 4 columns.',
                'fields' => [
                    ...$heading,
                    ['name' => 'theme', 'label' => 'Theme style', 'type' => 'select', 'options' => ['dark' => 'Dark band', 'light' => 'Light cards', 'brand' => 'Brand gradient']],
                    ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'options' => ['4' => '4 columns', '3' => '3 columns']],
                    ['name' => 'items', 'label' => 'Metrics', 'type' => 'repeater', 'max' => 8, 'fields' => [
                        ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
                        ['name' => 'value', 'label' => 'Number / Value', 'type' => 'text'],
                        ['name' => 'label', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'detail', 'label' => 'Supporting note', 'type' => 'text'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'metrics',
                    'eyebrow' => 'Proven performance',
                    'title' => 'Numbers that speak for themselves.',
                    'text' => 'Engineered to stay fast under flash-sale traffic while keeping hosting costs low.',
                    'theme' => 'dark',
                    'columns' => '4',
                    'items' => [
                        ['icon' => 'bolt', 'value' => '38ms', 'label' => 'Median response', 'detail' => 'Cached edge-ready pages'],
                        ['icon' => 'shield', 'value' => '$0', 'label' => 'Platform cut', 'detail' => 'Keep 100% of every sale'],
                        ['icon' => 'globe', 'value' => '40+', 'label' => 'Extensions & themes', 'detail' => 'Signed & verified packages'],
                        ['icon' => 'star', 'value' => '4.9/5', 'label' => 'Developer rating', 'detail' => 'Clean Laravel 13 codebase'],
                    ],
                ],
            ],

            'marketplace' => [
                'label' => 'Marketplace showcase',
                'category' => 'commerce',
                'icon' => 'cart',
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

            'pricing' => [
                'label' => 'Pricing plans',
                'category' => 'commerce',
                'icon' => 'tag',
                'description' => 'Tiered pricing table with feature checklists, badges and highlighted plan.',
                'fields' => [
                    ...$heading,
                    ['name' => 'note', 'label' => 'Bottom guarantee or billing note', 'type' => 'text'],
                    ['name' => 'plans', 'label' => 'Plans', 'type' => 'repeater', 'max' => 4, 'fields' => [
                        ['name' => 'name', 'label' => 'Plan name', 'type' => 'text'],
                        ['name' => 'badge', 'label' => 'Badge (e.g. Most popular)', 'type' => 'text'],
                        ['name' => 'price', 'label' => 'Price', 'type' => 'text'],
                        ['name' => 'period', 'label' => 'Billing period', 'type' => 'text'],
                        ['name' => 'description', 'label' => 'Short description', 'type' => 'textarea'],
                        ['name' => 'features', 'label' => 'Included features', 'type' => 'list'],
                        ['name' => 'button_label', 'label' => 'Button text', 'type' => 'text'],
                        ['name' => 'button_url', 'label' => 'Button link', 'type' => 'url'],
                        ['name' => 'featured', 'label' => 'Highlight this plan', 'type' => 'toggle'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'pricing',
                    'eyebrow' => 'Transparent pricing',
                    'title' => 'Start free, scale without limits.',
                    'text' => 'Self-host the open-source core for free forever, or pick a managed cloud plan when you want zero server maintenance.',
                    'note' => 'All plans include SSL certificates, unlimited staff accounts and 30-day money-back guarantee.',
                    'plans' => [
                        [
                            'name' => 'Community Self-Hosted',
                            'badge' => 'MIT Open Source',
                            'price' => '$0',
                            'period' => 'forever',
                            'description' => 'Full platform source code to run on your own VPS or shared hosting.',
                            'features' => ['Unlimited products & orders', 'Visual & block page builders', 'All free marketplace themes', 'Community forum support'],
                            'button_label' => 'Download free',
                            'button_url' => '@github',
                            'featured' => false,
                        ],
                        [
                            'name' => 'Growth Cloud',
                            'badge' => 'Most popular',
                            'price' => '$29',
                            'period' => '/ month',
                            'description' => 'Managed high-speed cloud hosting with automated daily backups and updates.',
                            'features' => ['Everything in Community', 'Managed global CDN & SSL', 'Automated daily backups', 'Priority email support'],
                            'button_label' => 'Start 14-day trial',
                            'button_url' => '@marketplace',
                            'featured' => true,
                        ],
                        [
                            'name' => 'Scale Enterprise',
                            'badge' => 'High volume',
                            'price' => '$99',
                            'period' => '/ month',
                            'description' => 'Dedicated compute resources, staging environments and SLA guarantee.',
                            'features' => ['Everything in Growth Cloud', 'Dedicated Redis & database', 'Staging & Git deploy pipeline', '99.99% uptime SLA & Slack support'],
                            'button_label' => 'Talk to sales',
                            'button_url' => '#contact',
                            'featured' => false,
                        ],
                    ],
                ],
            ],

            'testimonials' => [
                'label' => 'Customer testimonials',
                'category' => 'social',
                'icon' => 'message',
                'description' => 'Customer quotes with star ratings, author roles and layout variations.',
                'fields' => [
                    ...$heading,
                    ['name' => 'layout', 'label' => 'Layout style', 'type' => 'select', 'options' => ['grid' => '3-column cards', 'spotlight' => 'Featured spotlight + grid', 'minimal' => 'Minimal editorial']],
                    ['name' => 'theme', 'label' => 'Color theme', 'type' => 'select', 'options' => ['plain' => 'Light cards', 'dark' => 'Dark contrast']],
                    ['name' => 'items', 'label' => 'Testimonials', 'type' => 'repeater', 'max' => 12, 'fields' => [
                        ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea'],
                        ['name' => 'author', 'label' => 'Author name', 'type' => 'text'],
                        ['name' => 'role', 'label' => 'Role / Company', 'type' => 'text'],
                        ['name' => 'badge', 'label' => 'Result badge (optional)', 'type' => 'text'],
                        ['name' => 'rating', 'label' => 'Star rating (1–5)', 'type' => 'number', 'min' => 1, 'max' => 5],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'testimonials',
                    'eyebrow' => 'Trusted by merchants',
                    'title' => 'Why store owners switch to Modrix.',
                    'text' => 'From independent studios to multi-warehouse brands, teams love owning their storefront.',
                    'layout' => 'grid',
                    'theme' => 'plain',
                    'items' => [
                        [
                            'quote' => 'We migrated from a $299/mo hosted platform in one weekend. Our pages load twice as fast and our checkout fees dropped to zero.',
                            'author' => 'Elena Rostova',
                            'role' => 'Founder, Studio Nord',
                            'badge' => '+42% conversion',
                            'rating' => 5,
                        ],
                        [
                            'quote' => 'Having both a structured block editor and a full visual canvas means our marketing team ships landing pages without waiting on engineering.',
                            'author' => 'Marcus Vance',
                            'role' => 'Head of Digital, Kinetix Gear',
                            'badge' => '3x faster launches',
                            'rating' => 5,
                        ],
                        [
                            'quote' => 'The codebase is clean Laravel and React. Writing a custom ERP sync plugin took less than an afternoon.',
                            'author' => 'Priya Nair',
                            'role' => 'Lead Developer, Atelier Supply',
                            'badge' => '100% extensible',
                            'rating' => 5,
                        ],
                    ],
                ],
            ],

            'faq' => [
                'label' => 'FAQ accordion',
                'category' => 'social',
                'icon' => 'check',
                'description' => 'Collapsible questions and answers in split, centered or two-column layout.',
                'fields' => [
                    ...$heading,
                    ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['split' => 'Heading beside questions', 'centered' => 'Centered single column', 'two_col' => 'Two-column grid']],
                    ...$button('contact', 'Support button (optional)'),
                    ['name' => 'items', 'label' => 'Questions', 'type' => 'repeater', 'max' => 20, 'fields' => [
                        ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'help' => 'Supports **bold**, `code` and [links](https://…).'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'faq',
                    'eyebrow' => 'Common questions',
                    'title' => 'Everything you need to know.',
                    'text' => 'Have another question? Reach out to our community or support team anytime.',
                    'layout' => 'split',
                    'contact_label' => 'Ask in community →',
                    'contact_url' => '@github',
                    'items' => [
                        [
                            'question' => 'Is Modrix CMS really free for commercial stores?',
                            'answer' => 'Yes. Modrix CMS is released under the **MIT license**. You can use it for unlimited commercial stores, client projects and custom deployments without royalties or license fees.',
                        ],
                        [
                            'question' => 'Can I switch between the Block Editor and Visual Builder?',
                            'answer' => 'Yes. Any block page can be converted into a visual-builder document in one click while keeping your original blocks intact so you can switch back whenever you like.',
                        ],
                        [
                            'question' => 'What server requirements do I need?',
                            'answer' => 'Any host running **PHP 8.3+** and **MySQL 8 / MariaDB / SQLite** works out of the box — from shared cPanel hosting to dedicated cloud servers.',
                        ],
                        [
                            'question' => 'Can I build custom themes and plugins?',
                            'answer' => 'Absolutely. Themes and plugins live in `extensions/` and can register custom blocks, admin pages, hooks and storefront styles.',
                        ],
                    ],
                ],
            ],

            'gallery' => [
                'label' => 'Bento media gallery',
                'category' => 'media',
                'icon' => 'image',
                'description' => 'Visual showcase grid for screenshots, lookbooks or portfolio items.',
                'fields' => [
                    ...$heading,
                    ['name' => 'columns', 'label' => 'Grid columns', 'type' => 'select', 'options' => ['3' => '3 columns', '2' => '2 columns', '4' => '4 columns']],
                    ['name' => 'items', 'label' => 'Gallery items', 'type' => 'repeater', 'max' => 12, 'fields' => [
                        ['name' => 'image', 'label' => 'Image (optional)', 'type' => 'image'],
                        ['name' => 'tag', 'label' => 'Category tag', 'type' => 'text'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'caption', 'label' => 'Caption', 'type' => 'textarea'],
                        ['name' => 'accent', 'label' => 'Placeholder / card accent', 'type' => 'select', 'options' => ['violet' => 'Violet glow', 'lime' => 'Lime studio', 'coral' => 'Warm coral', 'slate' => 'Midnight slate']],
                        ['name' => 'url', 'label' => 'Optional link', 'type' => 'url'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'showcase',
                    'eyebrow' => 'Storefront showcase',
                    'title' => 'Crafted for modern commerce.',
                    'text' => 'Every screen is tuned for readability, fast interaction and mobile conversion.',
                    'columns' => '3',
                    'items' => [
                        ['image' => '', 'tag' => 'Storefront', 'title' => 'Minimal product discovery', 'caption' => 'Clean typography and instant filtering keep shoppers focused on your catalog.', 'accent' => 'violet', 'url' => ''],
                        ['image' => '', 'tag' => 'Admin UI', 'title' => 'Polaris-inspired workspace', 'caption' => 'Dark sidebar, command palette and customizable density for daily operations.', 'accent' => 'slate', 'url' => ''],
                        ['image' => '', 'tag' => 'Visual Builder', 'title' => 'Live responsive canvas', 'caption' => 'Preview desktop, tablet and mobile breakpoints side by side as you edit.', 'accent' => 'lime', 'url' => ''],
                    ],
                ],
            ],

            'video_showcase' => [
                'label' => 'Video & demo showcase',
                'category' => 'media',
                'icon' => 'play',
                'description' => 'Embedded video player or interactive demo frame with key takeaways.',
                'fields' => [
                    ...$heading,
                    ['name' => 'video_url', 'label' => 'Video URL (YouTube, Vimeo or MP4)', 'type' => 'url', 'help' => 'Leave empty to display an interactive product preview frame.'],
                    ['name' => 'caption', 'label' => 'Video caption / duration', 'type' => 'text'],
                    ['name' => 'theme', 'label' => 'Section style', 'type' => 'select', 'options' => ['dark' => 'Dark theater', 'light' => 'Light surface']],
                    ['name' => 'highlights', 'label' => 'Key takeaways', 'type' => 'list'],
                    ...$button('primary', 'Call to action button'),
                ],
                'defaults' => [
                    'anchor' => 'demo',
                    'eyebrow' => 'Product walkthrough',
                    'title' => 'See how a store comes together in minutes.',
                    'text' => 'Watch the full workflow from installing Modrix CMS to customizing your theme and publishing your first landing page.',
                    'video_url' => '',
                    'caption' => '4 min walkthrough · No plugins required',
                    'theme' => 'dark',
                    'highlights' => [
                        'Zero-config web installer',
                        'Drag-and-drop section & block editing',
                        'Instant theme color & typography tokens',
                    ],
                    'primary_label' => 'Try it yourself',
                    'primary_url' => '@github',
                ],
            ],

            'timeline' => [
                'label' => 'Roadmap & timeline',
                'category' => 'content',
                'icon' => 'clock',
                'description' => 'Vertical or horizontal milestones for company history, process or product roadmap.',
                'fields' => [
                    ...$heading,
                    ['name' => 'items', 'label' => 'Milestones', 'type' => 'repeater', 'max' => 10, 'fields' => [
                        ['name' => 'phase', 'label' => 'Date / Phase', 'type' => 'text'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'status', 'label' => 'Status badge', 'type' => 'select', 'options' => ['done' => 'Completed', 'active' => 'In progress', 'upcoming' => 'Planned']],
                        ['name' => 'text', 'label' => 'Description', 'type' => 'textarea'],
                    ]],
                ],
                'defaults' => [
                    'anchor' => 'roadmap',
                    'eyebrow' => 'Product roadmap',
                    'title' => 'Continuous improvements, shipped openly.',
                    'text' => 'Every release is tracked in the open with community feedback driving what we build next.',
                    'items' => [
                        ['phase' => 'Phase 01', 'title' => 'Core CMS & Block Builder', 'status' => 'done', 'text' => 'Pages, blog posts, categories, markdown editor and extensible block architecture.'],
                        ['phase' => 'Phase 02', 'title' => 'Visual Canvas & Theme Tokens', 'status' => 'done', 'text' => 'Live drag-and-drop visual builder, responsive device preview and design library.'],
                        ['phase' => 'Phase 03', 'title' => 'Commerce & Marketplace Sync', 'status' => 'active', 'text' => 'One-click extension installer, payment gateways and automated cloud backups.'],
                        ['phase' => 'Phase 04', 'title' => 'Multi-store & Headless API', 'status' => 'upcoming', 'text' => 'GraphQL & REST storefront APIs for custom mobile apps and multi-region catalogs.'],
                    ],
                ],
            ],

            'steps' => [
                'label' => 'Steps + code',
                'category' => 'content',
                'icon' => 'code',
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
                    'text' => 'No vendor lock-in. If it runs PHP and MySQL, it runs Modrix CMS.',
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
                'category' => 'commerce',
                'icon' => 'shield',
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
                    'text' => 'Modrix CMS is MIT licensed. Use it for your own shop or your clients’ shops, fork it, extend it. No license keys for the core, no revenue share, no phoning home.',
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
                'category' => 'content',
                'icon' => 'page',
                'description' => 'The newest published posts. Hidden when there are none.',
                'fields' => [
                    ['name' => 'eyebrow', 'label' => 'Small label', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                    ['name' => 'count', 'label' => 'Number of posts', 'type' => 'number', 'min' => 1, 'max' => 12],
                ],
                'defaults' => ['anchor' => 'blog', 'eyebrow' => 'From the blog', 'title' => 'News and guides.', 'count' => 3],
            ],

            'newsletter_cta' => [
                'label' => 'Newsletter & promo bar',
                'category' => 'conversion',
                'icon' => 'mail',
                'description' => 'Email capture or promotional callout bar with perks list and theme styles.',
                'fields' => [
                    ['name' => 'badge', 'label' => 'Small badge', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                    ['name' => 'text', 'label' => 'Supporting text', 'type' => 'textarea'],
                    ['name' => 'placeholder', 'label' => 'Email input placeholder', 'type' => 'text'],
                    ...$button('button', 'Submit / Action button'),
                    ['name' => 'perks', 'label' => 'Reassurance perks', 'type' => 'list'],
                    ['name' => 'theme', 'label' => 'Banner style', 'type' => 'select', 'options' => ['dark' => 'Dark card', 'brand' => 'Brand gradient', 'lime' => 'High-contrast lime']],
                ],
                'defaults' => [
                    'anchor' => 'newsletter',
                    'badge' => 'Release notes & updates',
                    'title' => 'Get new themes, modules and tips in your inbox.',
                    'text' => 'One concise email when we ship major features or new free extensions. Never shared, unsubscribe in one click.',
                    'placeholder' => 'you@example.com',
                    'button_label' => 'Subscribe',
                    'button_url' => '#newsletter',
                    'perks' => ['Monthly digest', 'Free starter themes', 'One-click unsubscribe'],
                    'theme' => 'dark',
                ],
            ],

            'cta' => [
                'label' => 'Call to action',
                'category' => 'conversion',
                'icon' => 'bolt',
                'description' => 'Coloured banner with a heading and buttons.',
                'fields' => [
                    ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                    ...$button('primary', 'Main button'),
                    ...$button('secondary', 'Second button'),
                ],
                'defaults' => [
                    'title' => 'Launch your store this afternoon.',
                    'text' => 'Download Modrix CMS, install it on your server, and add free themes and modules as you grow.',
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
            'categories' => [
                'all' => 'All blocks',
                'hero' => 'Hero & Headers',
                'content' => 'Content & Layout',
                'commerce' => 'Commerce & Pricing',
                'social' => 'Social Proof & FAQ',
                'media' => 'Media & Video',
                'conversion' => 'Conversion & CTA',
                'plugin' => 'Extensions',
            ],
            'presets' => self::presets(),
        ];
    }

    /**
     * Multi-block page presets for starting or appending full section flows in one click.
     *
     * @return list<array{key: string, label: string, description: string, types: list<string>}>
     */
    public static function presets(): array
    {
        return [
            [
                'key' => 'full_landing',
                'label' => 'Complete Landing Page',
                'description' => 'Hero, logo strip, feature bento grid, marketplace, steps, comparison, blog and CTA.',
                'types' => ['hero', 'stack', 'features', 'marketplace', 'steps', 'comparison', 'latest_posts', 'cta'],
            ],
            [
                'key' => 'saas_pricing',
                'label' => 'Pricing & Comparison',
                'description' => 'Page header, 3-tier pricing plans, us-vs-them comparison, FAQ accordion and final CTA.',
                'types' => ['page_header', 'pricing', 'comparison', 'faq', 'cta'],
            ],
            [
                'key' => 'product_showcase',
                'label' => 'Product & Demo Showcase',
                'description' => 'Hero, stats band, split content showcase, video walkthrough, customer testimonials and newsletter.',
                'types' => ['hero', 'stats', 'banner_split', 'video_showcase', 'testimonials', 'newsletter_cta'],
            ],
            [
                'key' => 'company_story',
                'label' => 'About, Roadmap & Gallery',
                'description' => 'Page header, split story section, stats band, milestone timeline, bento gallery and CTA.',
                'types' => ['page_header', 'banner_split', 'stats', 'timeline', 'gallery', 'cta'],
            ],
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
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', trim($value)) ? strtolower(trim($value)) : (string) ($field['default'] ?? ''),
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
