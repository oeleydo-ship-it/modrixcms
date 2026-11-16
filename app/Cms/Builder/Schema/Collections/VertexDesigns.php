<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\TemplatePresentation;
use App\Cms\Themes\DesignTokens;

/**
 * Reusable design blocks and multi-page templates for the Vertex theme (`modrix/vertex`).
 *
 * Presents Modrix Commerce, a free, MIT-licensed, self-hosted e-commerce platform built on Laravel.
 * The wording is deliberately plain: it names the features the project documents (store and
 * checkout, orders, pages, SEO, roles, backups, themes and modules) and states no counts,
 * versions or benchmarks that cannot be checked.
 *
 * Every section reads Theme Settings (`--site-*`) and Customize options (`--vertex-*`) live.
 */
final class VertexDesigns
{
    public const COLLECTION = 'vertex';

    private const ASSETS = '/templates/vertex/';

    private const CARD_SHADOW = '0 4px 18px -6px rgba(12, 35, 64, 0.08)';

    private int $idCounter = 0;

    /**
     * Reusable design blocks surfaced inside the Visual Page Builder.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->design(
                'vertex-header',
                'Vertex · Header with Drop-Down Menus',
                'structure',
                'White header with a small link bar on top, the Modrix logo, four drop-down menus, a search button and a Try demo button.',
                [$this->headerSection()],
            ),
            $this->design(
                'vertex-footer',
                'Vertex · Four-Column Footer',
                'structure',
                'White footer with a colored stripe on top, a short description, two link columns, a help column and a few quick links.',
                [$this->footerSection()],
            ),
            $this->design(
                'vertex-hero',
                'Vertex · Navy Hero',
                'hero',
                'Navy two-column hero with a short headline, one paragraph, two outlined buttons and an illustration.',
                [$this->heroSection()],
            ),
            $this->design(
                'vertex-announcement',
                'Vertex · Announcement Strip',
                'banner',
                'Blue strip with a small image, a short message and one outlined button.',
                [$this->announcementSection()],
            ),
            $this->design(
                'vertex-spotlight',
                'Vertex · Three Highlight Cards',
                'content',
                'Light grey band with three white cards, each with an illustration and a linked heading.',
                [$this->spotlightSection()],
            ),
            $this->design(
                'vertex-solutions',
                'Vertex · Three-Column Overview',
                'content',
                'Three columns on white, each with an illustration, a heading and two sentences of text.',
                [$this->solveSection()],
            ),
            $this->design(
                'vertex-trending',
                'Vertex · Link Cards',
                'content',
                'Two cards side by side and one centered card below, each with an image, a short text and a link.',
                [$this->trendingSection()],
            ),
            $this->design(
                'vertex-cta-band',
                'Vertex · Call-to-Action Band',
                'conversion',
                'Centered navy band with a heading, one line of text and two outlined buttons.',
                [$this->ctaBandSection()],
            ),
            $this->design(
                'vertex-page-hero',
                'Vertex · Page Header',
                'hero',
                'Compact navy band for inner pages with a small label, a title and a short introduction.',
                [$this->innerHeroSection('FEATURES', 'What is included in Modrix Commerce', 'The free core covers the everyday work of running an online store: products, checkout, orders, pages and settings.')],
            ),
            $this->design(
                'vertex-domains',
                'Vertex · Feature Grid',
                'content',
                'A heading and twelve small cards listing the features of the free core in plain words.',
                [$this->domainsSection()],
            ),
            $this->design(
                'vertex-modules',
                'Vertex · Module List with Filter',
                'content',
                'Filter buttons (All, Payments, Orders and delivery, Marketing and sales, Privacy, SEO and tools) above cards that name example modules.',
                [$this->modulesCatalogSection()],
            ),
            $this->design(
                'vertex-solutions-tracks',
                'Vertex · Use Case Cards',
                'content',
                'Three cards that describe who the platform is for: small shops, agencies and developers.',
                [$this->solutionsTracksSection()],
            ),
            $this->design(
                'vertex-cli-quickstart',
                'Vertex · Install Commands',
                'content',
                'Copyable install commands next to a short list of hosting needs and license facts.',
                [$this->developersQuickstartSection()],
            ),
            $this->design(
                'vertex-demo-import',
                'Vertex · Demo Import Panel',
                'conversion',
                'Tabs that explain how to import the demo from the admin or the command line, plus a list of the demo pages.',
                [$this->demoImportSection()],
            ),
        ];
    }

    /**
     * Complete page templates (including site header and footer) available in the Visual Builder.
     *
     * @return list<array<string, mixed>>
     */
    public function pages(): array
    {
        return array_map(fn (array $definition): array => [
            'key' => $definition['key'],
            'name' => $definition['name'],
            'category' => $definition['category'],
            'description' => $definition['description'],
            'document' => $this->document([
                $this->headerSection(),
                ...$definition['sections'](),
                $this->footerSection(),
            ]),
        ], $this->pageDefinitions());
    }

    /**
     * Body-only documents imported by `theme.json` `demo` (site header & footer wrap them).
     *
     * @return list<array<string, mixed>>
     */
    public function demoPages(): array
    {
        return array_map(fn (array $definition): array => [
            'key' => $definition['key'],
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'category' => $definition['category'],
            'description' => $definition['description'],
            'document' => $this->document($definition['sections']()),
        ], $this->pageDefinitions());
    }

    /** @return array<string, mixed> */
    public function headerDocument(): array
    {
        return $this->document([$this->headerSection()]);
    }

    /** @return array<string, mixed> */
    public function footerDocument(): array
    {
        return $this->document([$this->footerSection()]);
    }

    /**
     * @return list<array{key: string, name: string, slug: string, category: string, description: string, sections: callable(): list<array<string, mixed>>}>
     */
    private function pageDefinitions(): array
    {
        return [
            [
                'key' => 'vertex-home',
                'name' => 'Vertex · Home',
                'slug' => 'home',
                'category' => 'enterprise',
                'description' => 'Home page for Modrix Commerce: hero, announcement strip, three highlight cards, a short overview and links to where to start.',
                'sections' => fn (): array => [
                    $this->heroSection(),
                    $this->announcementSection(),
                    $this->spotlightSection(),
                    $this->solveSection(),
                    $this->trendingSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'vertex-platform',
                'name' => 'Vertex · Features',
                'slug' => 'platform',
                'category' => 'enterprise',
                'description' => 'What the free core includes: the technology it runs on and twelve features in plain words.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'FEATURES',
                        'What is included in Modrix Commerce',
                        'The free core covers the everyday work of running an online store: products, checkout, orders, pages and settings. Here is what it does.',
                    ),
                    $this->techPillarsSection(),
                    $this->domainsSection(),
                    $this->ctaBandSection(
                        'Want to look at the code?',
                        'Read the install guide, or import the demo pages into your own site.',
                    ),
                ],
            ],
            [
                'key' => 'vertex-modules',
                'name' => 'Vertex · Themes & Modules',
                'slug' => 'modules',
                'category' => 'enterprise',
                'description' => 'How themes and modules work, with a filterable list of example modules such as payment providers and marketing tools.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'THEMES & MODULES',
                        'Add what you need, when you need it',
                        'The core stays small. Themes and modules are separate packages that you install from the admin or upload as a ZIP file.',
                    ),
                    $this->modulesCatalogSection(),
                    $this->ctaBandSection(
                        'Want to build your own?',
                        'Themes and modules are plain packages in the extensions folder, so you can write your own.',
                    ),
                ],
            ],
            [
                'key' => 'vertex-solutions',
                'name' => 'Vertex · Use Cases',
                'slug' => 'solutions',
                'category' => 'enterprise',
                'description' => 'Who Modrix Commerce is for: small shops, agencies working for clients and developers.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'USE CASES',
                        'Who Modrix Commerce is for',
                        'Small shops, agencies that build stores for clients, and developers who want an e-commerce base they can read and change.',
                    ),
                    $this->solutionsTracksSection(),
                    $this->solveSection(),
                    $this->ctaBandSection(
                        'Take a look for yourself',
                        'Import the demo pages or follow the install guide.',
                    ),
                ],
            ],
            [
                'key' => 'vertex-developers',
                'name' => 'Vertex · Get Started',
                'slug' => 'developers',
                'category' => 'enterprise',
                'description' => 'Install guide with copyable commands, hosting requirements and license facts.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'GET STARTED',
                        'Install Modrix Commerce',
                        'You need PHP 8.3 or newer and a MySQL, MariaDB or SQLite database. Then run a few commands.',
                    ),
                    $this->developersQuickstartSection(),
                    $this->spotlightSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'vertex-demo',
                'name' => 'Vertex · Demo',
                'slug' => 'demo',
                'category' => 'enterprise',
                'description' => 'How to import the Vertex demo from the admin or the command line, and the list of pages it adds.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'DEMO',
                        'Try the Vertex demo for Modrix Commerce',
                        'Import the Vertex header, footer and six example pages, then change them in the page builder.',
                    ),
                    $this->demoImportSection(),
                    $this->trendingSection(),
                    $this->ctaBandSection(
                        'Start with the open-source core',
                        'Free under the MIT license. It runs wherever PHP 8.3 and a supported database run.',
                    ),
                ],
            ],
        ];
    }

    /**
     * Header matching the reference top utility bar, crisp white menu bar, multi-column dropdowns,
     * inline search icon trigger and sharp rectangular TRY DEMO CTA button.
     *
     * @return array<string, mixed>
     */
    private function headerSection(): array
    {
        $utilityBar = [
            ...$this->container('row', '20px', 'utility-bar', [
                'justifyContent' => $this->responsive('flex-end'),
                'alignItems' => $this->responsive('center'),
                'width' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->utilityLink('Free and open source · MIT license', '/platform'),
                $this->utilityLink('Themes & modules', '/modules'),
                $this->utilityLink('Install guide', '/developers'),
                $this->utilityLink('Source code', '/developers#cli'),
                $this->utilityLink('Demo', '/demo'),
            ],
        ];

        $logo = $this->blockNode(BlockType::Logo, [
            'src' => self::ASSETS.'logo.svg',
            'alt' => 'Modrix Commerce · Laravel 13',
            'text' => 'modrix',
            'link' => '/',
            'width' => '184px',
            'height' => 56,
        ], [
            'maxWidth' => $this->responsive('184px'),
        ]);

        $navigation = $this->blockNode(BlockType::Navigation, [
            'items' => [
                [
                    'label' => 'Features',
                    'url' => '/platform',
                    'children' => [
                        [
                            'label' => 'Products & checkout',
                            'url' => '/platform#domains',
                            'description' => 'Products, variants, coupons, taxes, shipping and guest checkout.',
                            'icon' => 'package',
                        ],
                        [
                            'label' => 'Orders & invoices',
                            'url' => '/platform#domains',
                            'description' => 'Manage orders, create PDF invoices and sell digital downloads.',
                            'icon' => 'layers',
                        ],
                        [
                            'label' => 'Pages & SEO',
                            'url' => '/platform#domains',
                            'description' => 'Write pages, design your home page and get an automatic sitemap.',
                            'icon' => 'panels-top-left',
                        ],
                        [
                            'label' => 'Admin & security',
                            'url' => '/platform#domains',
                            'description' => 'Staff roles, an audit log, two-factor sign-in and backups.',
                            'icon' => 'shield-check',
                        ],
                    ],
                ],
                [
                    'label' => 'Themes & modules',
                    'url' => '/modules',
                    'children' => [
                        [
                            'label' => 'How it works',
                            'url' => '/modules',
                            'description' => 'Install from the admin, or upload a ZIP file.',
                            'icon' => 'boxes',
                        ],
                        [
                            'label' => 'Payment modules',
                            'url' => '/modules#catalog',
                            'description' => 'Add payment providers such as PayPal and Razorpay.',
                            'icon' => 'globe',
                        ],
                        [
                            'label' => 'Marketing modules',
                            'url' => '/modules#catalog',
                            'description' => 'Reviews, wishlists, newsletters, flash sales and cart reminders.',
                            'icon' => 'target',
                        ],
                        [
                            'label' => 'Build your own',
                            'url' => '/developers',
                            'description' => 'Themes and modules are plain packages in the extensions folder.',
                            'icon' => 'sparkle',
                        ],
                    ],
                ],
                [
                    'label' => 'Use cases',
                    'url' => '/solutions',
                    'children' => [
                        [
                            'label' => 'Small shops',
                            'url' => '/solutions#tracks',
                            'description' => 'Run your own store on regular hosting.',
                            'icon' => 'users',
                        ],
                        [
                            'label' => 'Agency and client work',
                            'url' => '/solutions#tracks',
                            'description' => 'Use one free core for every client project.',
                            'icon' => 'building-2',
                        ],
                        [
                            'label' => 'Developers',
                            'url' => '/solutions#tracks',
                            'description' => 'A standard Laravel application you can read and change.',
                            'icon' => 'compass',
                        ],
                    ],
                ],
                [
                    'label' => 'Get started',
                    'url' => '/developers',
                    'children' => [
                        [
                            'label' => 'Install guide',
                            'url' => '/developers#cli',
                            'description' => 'Install with Composer and Artisan.',
                            'icon' => 'book-open',
                        ],
                        [
                            'label' => 'Try the demo',
                            'url' => '/demo',
                            'description' => 'Import the example pages of this theme.',
                            'icon' => 'play',
                        ],
                        [
                            'label' => 'License and hosting',
                            'url' => '/developers#security',
                            'description' => 'MIT license and plain hosting requirements.',
                            'icon' => 'workflow',
                        ],
                    ],
                ],
            ],
            'orientation' => 'horizontal',
            'alignment' => 'left',
        ], [
            'fontSize' => $this->responsive('14.5px'),
            'fontWeight' => $this->responsive('500'),
            'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
            'gap' => $this->responsive('26px'),
        ]);

        $searchBtn = $this->blockNode(BlockType::Button, [
            'label' => 'Search',
            'url' => '#search',
            'variant' => 'outline',
            'size' => 'sm',
            'vertexRole' => 'header-search',
        ]);

        $demoCta = $this->sharpPrimaryButton('TRY DEMO', '/demo');

        $headerActions = [
            ...$this->container('row', '12px', 'header-actions', [
                'alignItems' => $this->responsive('center'),
                'justifyContent' => $this->responsive('flex-end'),
            ]),
            'children' => [$searchBtn, $demoCta],
        ];

        $headerBar = [
            ...$this->container('columns', '24px', 'header-bar', [
                'columns' => [3, 6, 3],
                'stackBelow' => 'mobile',
            ]),
            'children' => [
                [
                    ...$this->container('row', '0px', 'header-logo-col', ['alignItems' => $this->responsive('center')]),
                    'children' => [$logo],
                ],
                [
                    ...$this->container('row', '0px', 'header-nav-col', ['alignItems' => $this->responsive('center')]),
                    'children' => [$navigation],
                ],
                $headerActions,
            ],
        ];

        return $this->tagSection('header', $this->section('Header · Vertex Navigation', [
            $this->row([$this->column(12, [$utilityBar, $headerBar])], $this->wideRow([
                'gap' => $this->responsive('0px'),
            ])),
        ], [
            'sticky' => true,
            'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg', '#ffffff')),
            'padding' => ['desktop' => ['top' => '2px', 'bottom' => '2px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '4px', 'bottom' => '4px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Deep navy 2-column hero mirroring the VMware screenshot.
     *
     * @return array<string, mixed>
     */
    private function heroSection(): array
    {
        $leftCol = [
            ...$this->container('stack', '18px', 'hero-copy', [
                'justifyContent' => $this->responsive('center'),
            ]),
            'children' => [
                $this->h1('Free, open-source e-commerce you host yourself', [
                    'fontSize' => ['desktop' => '44px', 'tablet' => '36px', 'mobile' => '30px'],
                    'fontWeight' => $this->responsive('300'),
                    'lineHeight' => $this->responsive('1.18'),
                    'color' => $this->responsive('#ffffff'),
                    'letterSpacing' => $this->responsive('-0.015em'),
                    'maxWidth' => $this->responsive('560px'),
                ]),
                $this->paragraph('Modrix Commerce is an online store platform built on Laravel. Install it on your own hosting, keep your orders and customer data, and add themes and modules when you need them.', [
                    'fontSize' => $this->responsive('15.5px'),
                    'lineHeight' => $this->responsive('1.65'),
                    'color' => $this->responsive('#d8e7f4'),
                    'maxWidth' => $this->responsive('540px'),
                ]),
                [
                    ...$this->container('row', '14px', null, ['margin' => $this->responsive(['top' => '6px']), 'alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->outlineCtaButton('See the features', '/platform'),
                        $this->outlineCtaButton('Try the demo', '/demo'),
                    ],
                ],
            ],
        ];

        $rightCol = [
            ...$this->container('stack', '0px', 'hero-art', [
                'alignItems' => $this->responsive('center'),
                'justifyContent' => $this->responsive('center'),
            ]),
            'children' => [
                $this->image(self::ASSETS.'hero-factory.svg', 'Illustration of an online store and its admin', [
                    'width' => $this->responsive('100%'),
                    'maxWidth' => $this->responsive('480px'),
                ]),
            ],
        ];

        $split = [
            ...$this->container('columns', '40px', 'hero-split', [
                'columns' => [7, 5],
                'stackBelow' => 'tablet',
            ]),
            'children' => [$leftCol, $rightCol],
        ];

        return $this->tagSection('hero', $this->section('Hero · Modrix Commerce', [
            $this->row([$this->column(12, [$split])], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#0c2340')),
            'color' => $this->responsive('#ffffff'),
            'padding' => ['desktop' => ['top' => '76px', 'bottom' => '76px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '48px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Cerulean announcement banner directly under the hero.
     *
     * @return array<string, mixed>
     */
    private function announcementSection(): array
    {
        $thumbCol = [
            ...$this->container('stack', '0px', null, ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->image(self::ASSETS.'banner-release.svg', 'Modrix Commerce demo badge', [
                    'width' => $this->responsive('100%'),
                    'maxWidth' => $this->responsive('190px'),
                    'borderRadius' => $this->responsive('4px'),
                ]),
            ],
        ];

        $textCol = [
            ...$this->container('stack', '6px', null, ['justifyContent' => $this->responsive('center')]),
            'children' => [
                $this->h3('Try the Vertex demo', [
                    'fontSize' => $this->responsive('20px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive('#ffffff'),
                ]),
                $this->paragraph('Import the header, the footer and six example pages from Appearance → Themes, then edit them in the page builder.', [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.55'),
                    'color' => $this->responsive('#e0f3fb'),
                ]),
            ],
        ];

        $ctaCol = [
            ...$this->container('row', '0px', null, [
                'justifyContent' => $this->responsive('flex-end'),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                $this->outlineCtaButton('Open the demo', '/demo'),
            ],
        ];

        $bannerRow = [
            ...$this->container('columns', '24px', 'banner-row', [
                'columns' => [2, 7, 3],
                'stackBelow' => 'tablet',
            ]),
            'children' => [$thumbCol, $textCol, $ctaCol],
        ];

        return $this->tagSection('announcement', $this->section('Announcement Strip', [
            $this->row([$this->column(12, [$bannerRow])], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive('#005f87'),
            'color' => $this->responsive('#ffffff'),
            'padding' => ['desktop' => ['top' => '22px', 'bottom' => '22px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '20px', 'bottom' => '20px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Three equal spotlight cards over cool pale-gray background.
     *
     * @return array<string, mixed>
     */
    private function spotlightSection(): array
    {
        $cards = [
            [
                'img' => 'spotlight-core.svg',
                'alt' => 'Illustration of a store admin',
                'title' => 'A complete store, free to use',
                'url' => '/platform',
            ],
            [
                'img' => 'spotlight-security.svg',
                'alt' => 'Illustration of roles and backups',
                'title' => 'Roles, an audit log and backups are included',
                'url' => '/platform',
            ],
            [
                'img' => 'spotlight-modules.svg',
                'alt' => 'Illustration of themes and modules',
                'title' => 'Add themes and modules when you need them',
                'url' => '/modules',
            ],
        ];

        $cols = array_map(fn (array $card): array => $this->column(4, [[
            ...$this->container('stack', '18px', 'spotlight-card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '28px', 'bottom' => '30px', 'left' => '24px', 'right' => '24px']),
                'alignItems' => $this->responsive('center'),
                'textAlign' => $this->responsive('center'),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->image(self::ASSETS.$card['img'], $card['alt'], [
                    'width' => $this->responsive('100%'),
                    'maxWidth' => $this->responsive('260px'),
                ], $card['url']),
                $this->h3($card['title'], [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('400'),
                    'lineHeight' => $this->responsive('1.4'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                    'textAlign' => $this->responsive('center'),
                ], $card['url']),
            ],
        ]]), $cards);

        return $this->tagSection('spotlight', $this->section('Platform Spotlight Cards', [
            $this->row($cols, $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '56px', 'bottom' => '56px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '40px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * White editorial 3-column section ("What Can We Help You Solve?").
     *
     * @return array<string, mixed>
     */
    private function solveSection(): array
    {
        $items = [
            [
                'img' => 'solve-stack.svg',
                'alt' => 'Illustration of a self-hosted store',
                'title' => 'Run your own store',
                'url' => '/platform',
                'body' => 'Install Modrix Commerce on your own hosting. Your products, orders and customers stay in your own database.',
            ],
            [
                'img' => 'solve-storefront.svg',
                'alt' => 'Illustration of themes and modules',
                'title' => 'Add what you need',
                'url' => '/modules',
                'body' => 'Start with the core, then add themes and modules such as payment providers, reviews, newsletters or flash sales.',
            ],
            [
                'img' => 'solve-security.svg',
                'alt' => 'Illustration of the admin area',
                'title' => 'Manage it from one admin',
                'url' => '/platform',
                'body' => 'Handle orders, pages and settings in one place. Staff roles, an audit log and scheduled backups are included.',
            ],
        ];

        $headingRow = $this->row([$this->column(12, [
            $this->h2('What you get', [
                'fontSize' => ['desktop' => '32px', 'mobile' => '26px'],
                'fontWeight' => $this->responsive('300'),
                'textAlign' => $this->responsive('center'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                'margin' => $this->responsive(['bottom' => '12px']),
            ]),
        ])], $this->wideRow());

        $cols = array_map(fn (array $item): array => $this->column(4, [[
            ...$this->container('stack', '14px', 'solve-card', [
                'alignItems' => $this->responsive('center'),
                'textAlign' => $this->responsive('center'),
                'padding' => $this->responsive(['top' => '8px', 'bottom' => '8px', 'left' => '10px', 'right' => '10px']),
            ]),
            'children' => [
                $this->image(self::ASSETS.$item['img'], $item['alt'], [
                    'width' => $this->responsive('100%'),
                    'maxWidth' => $this->responsive('290px'),
                ], $item['url']),
                $this->h3($item['title'], [
                    'fontSize' => $this->responsive('20px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                    'textAlign' => $this->responsive('center'),
                ], $item['url']),
                $this->paragraph($item['body'], [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.65'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                    'textAlign' => $this->responsive('center'),
                    'maxWidth' => $this->responsive('340px'),
                ]),
            ],
        ]]), $items);

        return $this->tagSection('solutions', $this->section('Overview', [
            $headingRow,
            $this->row($cols, $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
            'padding' => ['desktop' => ['top' => '68px', 'bottom' => '68px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '48px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Trending Topics: 2 wide horizontal cards on top + 1 centered horizontal card below.
     *
     * @return array<string, mixed>
     */
    private function trendingSection(): array
    {
        $topCards = [
            $this->horizontalTopicCard(
                'topic-cloud.svg',
                'Illustration of themes and modules',
                'Themes and modules',
                'Install free themes and modules from Admin → Extensions, or upload a ZIP file. Each one can be updated or rolled back from the same screen.',
                'SEE THEMES & MODULES >',
                '/modules',
            ),
            $this->horizontalTopicCard(
                'topic-demo.svg',
                'Illustration of the demo import',
                'Try the Vertex demo',
                'Import the header, the footer and six example pages, then edit them in the page builder.',
                'TRY THE DEMO >',
                '/demo',
            ),
        ];

        $bottomCard = $this->horizontalTopicCard(
            'topic-gateways.svg',
            'Illustration of payment methods',
            'Payments are added as modules',
            'Payment providers such as PayPal and Razorpay are available as modules, so you only add the ones you use.',
            'SEE PAYMENT MODULES >',
            '/modules',
        );

        $centeredBottomWrapper = [
            ...$this->container('stack', '0px', 'topic-centered-wrap', [
                'maxWidth' => $this->responsive('620px'),
                'width' => $this->responsive('100%'),
                'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
            ]),
            'children' => [$bottomCard],
        ];

        return $this->tagSection('trending', $this->section('Where to start', [
            $this->row([$this->column(12, [
                $this->h2('Good places to start', [
                    'fontSize' => ['desktop' => '32px', 'mobile' => '26px'],
                    'fontWeight' => $this->responsive('300'),
                    'textAlign' => $this->responsive('center'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                    'margin' => $this->responsive(['bottom' => '16px']),
                ]),
            ])], $this->wideRow()),
            $this->row([
                $this->column(6, [$topCards[0]]),
                $this->column(6, [$topCards[1]]),
            ], $this->wideRow()),
            $this->row([
                $this->column(12, [$centeredBottomWrapper]),
            ], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '64px', 'bottom' => '68px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '48px', 'bottom' => '52px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Dark midnight conversion CTA strip with dual white-outlined rectangular actions.
     *
     * @return array<string, mixed>
     */
    private function ctaBandSection(
        string $title = 'Want to try it?',
        string $subtitle = '',
    ): array {
        $blocks = [
            $this->h2($title, [
                'fontSize' => ['desktop' => '30px', 'mobile' => '24px'],
                'fontWeight' => $this->responsive('300'),
                'textAlign' => $this->responsive('center'),
                'color' => $this->responsive('#ffffff'),
            ]),
        ];

        if ($subtitle !== '') {
            $blocks[] = $this->paragraph($subtitle, [
                'fontSize' => $this->responsive('14.5px'),
                'color' => $this->responsive('#cbdcf0'),
                'textAlign' => $this->responsive('center'),
                'maxWidth' => $this->responsive('620px'),
                'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
            ]);
        }

        $blocks[] = [
            ...$this->container('row', '16px', null, [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => $this->responsive('center'),
                'margin' => $this->responsive(['top' => '10px']),
            ]),
            'children' => [
                $this->outlineCtaButton('GET STARTED', '/developers'),
                $this->outlineCtaButton('TRY THE DEMO', '/demo'),
            ],
        ];

        return $this->tagSection('cta-band', $this->section('Call-to-Action Band', [
            $this->row([$this->column(12, $blocks)], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#0c2340')),
            'color' => $this->responsive('#ffffff'),
            'padding' => ['desktop' => ['top' => '64px', 'bottom' => '64px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '48px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * White 4-column enterprise footer crowned by the CSS green→teal→blue→indigo spectrum stripe.
     *
     * @return array<string, mixed>
     */
    private function footerSection(): array
    {
        $brandCol = $this->column(3, [
            $this->blockNode(BlockType::Logo, [
                'src' => self::ASSETS.'logo.svg',
                'alt' => 'Modrix Commerce · Laravel 13',
                'text' => 'modrix',
                'link' => '/',
                'width' => '176px',
            ], [
                'maxWidth' => $this->responsive('176px'),
            ]),
            $this->paragraph('Free, open-source e-commerce you host yourself. MIT licensed and built on Laravel.', [
                'fontSize' => $this->responsive('12.5px'),
                'lineHeight' => $this->responsive('1.6'),
                'color' => $this->responsive(DesignTokens::ref('footer_text', '#4a5565')),
                'maxWidth' => $this->responsive('250px'),
                'margin' => $this->responsive(['top' => '10px']),
            ]),
        ]);

        $platformLinks = [
            ...$this->container('columns', '16px', null, ['columns' => [6, 6], 'stackBelow' => 'mobile']),
            'children' => [
                $this->verticalFooterNav([
                    ['label' => 'Features overview', 'url' => '/platform'],
                    ['label' => 'What is included', 'url' => '/platform#domains'],
                    ['label' => 'Themes & modules', 'url' => '/modules'],
                    ['label' => 'Use cases', 'url' => '/solutions'],
                ]),
                $this->verticalFooterNav([
                    ['label' => 'Payment modules', 'url' => '/modules'],
                    ['label' => 'Roles & audit log', 'url' => '/platform#domains'],
                    ['label' => 'Install guide', 'url' => '/developers'],
                    ['label' => 'Try the demo', 'url' => '/demo'],
                ]),
            ],
        ];

        $platformCol = $this->column(4, [
            $this->h4('Overview', [
                'fontSize' => $this->responsive('14.5px'),
                'fontWeight' => $this->responsive('500'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                'margin' => $this->responsive(['bottom' => '10px']),
            ]),
            $platformLinks,
        ]);

        $supportCol = $this->column(3, [
            $this->h4('Help', [
                'fontSize' => $this->responsive('14.5px'),
                'fontWeight' => $this->responsive('500'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                'margin' => $this->responsive(['bottom' => '10px']),
            ]),
            $this->verticalFooterNav([
                ['label' => 'Install guide', 'url' => '/developers'],
                ['label' => 'Payment modules', 'url' => '/modules'],
                ['label' => 'Demo import', 'url' => '/demo'],
                ['label' => 'MIT license', 'url' => '/developers#security'],
            ]),
        ]);

        $socialItems = [
            ['icon' => 'workflow', 'label' => 'Source code', 'url' => '/developers#cli'],
            ['icon' => 'globe', 'label' => 'Use cases', 'url' => '/solutions'],
            ['icon' => 'play', 'label' => 'Try the demo', 'url' => '/demo'],
        ];

        $socialCol = $this->column(2, array_map(fn (array $item): array => [
            ...$this->container('row', '10px', null, ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->blockNode(BlockType::Icon, [
                    'name' => $item['icon'],
                    'size' => '15px',
                ], [
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->inlineLink($item['label'], $item['url'], [
                    'fontSize' => $this->responsive('13px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive(DesignTokens::ref('footer_text', '#4a5565')),
                ]),
            ],
        ], $socialItems), ['gap' => $this->responsive('10px')]);

        $bottomCopyright = $this->row([$this->column(12, [
            $this->blockNode(BlockType::Divider, [], [
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#dce3ec'), 'top' => '1px']),
                'margin' => $this->responsive(['bottom' => '20px']),
            ]),
            $this->paragraph('Copyright © 2026 Modrix Commerce contributors. Free, open-source software under the MIT license.', [
                'fontSize' => $this->responsive('12px'),
                'color' => $this->responsive(DesignTokens::ref('muted', '#718096')),
                'textAlign' => $this->responsive('center'),
                'margin' => $this->responsive(['bottom' => '10px']),
            ]),
            $this->blockNode(BlockType::Navigation, [
                'items' => [
                    ['label' => 'Features', 'url' => '/platform'],
                    ['label' => 'Themes & modules', 'url' => '/modules'],
                    ['label' => 'Use cases', 'url' => '/solutions'],
                    ['label' => 'Get started', 'url' => '/developers'],
                    ['label' => 'Demo', 'url' => '/demo'],
                ],
                'orientation' => 'horizontal',
                'alignment' => 'center',
            ], [
                'fontSize' => $this->responsive('12px'),
                'color' => $this->responsive(DesignTokens::ref('footer_text', '#4a5565')),
                'gap' => $this->responsive('26px'),
            ]),
        ])], $this->wideRow());

        return $this->tagSection('footer', $this->section('Footer · Vertex', [
            $this->row([$brandCol, $platformCol, $supportCol, $socialCol], $this->wideRow([
                'padding' => $this->responsive(['top' => '48px', 'bottom' => '36px']),
            ])),
            $bottomCopyright,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('footer_bg', '#ffffff')),
            'color' => $this->responsive(DesignTokens::ref('footer_text', '#4a5565')),
            'padding' => ['desktop' => ['top' => '0px', 'bottom' => '40px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '0px', 'bottom' => '32px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Compact navy header section shared by inner marketing routes.
     *
     * @return array<string, mixed>
     */
    private function innerHeroSection(string $eyebrow, string $title, string $lead): array
    {
        return $this->tagSection('hero', $this->section('Page Header · Vertex', [
            $this->row([$this->column(12, [
                $this->paragraph($eyebrow, [
                    'fontSize' => $this->responsive('11.5px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.14em'),
                    'color' => $this->responsive('#7cd4f8'),
                    'margin' => $this->responsive(['bottom' => '10px']),
                ]),
                $this->h1($title, [
                    'fontSize' => ['desktop' => '38px', 'mobile' => '28px'],
                    'fontWeight' => $this->responsive('300'),
                    'lineHeight' => $this->responsive('1.2'),
                    'color' => $this->responsive('#ffffff'),
                    'maxWidth' => $this->responsive('780px'),
                ]),
                $this->paragraph($lead, [
                    'fontSize' => $this->responsive('15.5px'),
                    'lineHeight' => $this->responsive('1.65'),
                    'color' => $this->responsive('#d8e7f4'),
                    'maxWidth' => $this->responsive('720px'),
                    'margin' => $this->responsive(['top' => '10px']),
                ]),
            ])], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#0c2340')),
            'color' => $this->responsive('#ffffff'),
            'padding' => ['desktop' => ['top' => '52px', 'bottom' => '52px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '40px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Tech stack 4-column architecture pillars (/platform#stack).
     *
     * @return array<string, mixed>
     */
    private function techPillarsSection(): array
    {
        $pillars = [
            ['name' => 'Laravel and PHP 8.3+', 'badge' => 'Framework', 'text' => 'A standard Laravel application, so any PHP developer can read it and change it.'],
            ['name' => 'MySQL, MariaDB or SQLite', 'badge' => 'Database', 'text' => 'Works with the database your hosting already provides.'],
            ['name' => 'React and Inertia', 'badge' => 'Front end', 'text' => 'The storefront and the admin are React pages served by Laravel.'],
            ['name' => 'Themes', 'badge' => 'Design', 'text' => 'Change the look of your store with a theme. Styling uses Tailwind CSS.'],
        ];

        $cols = array_map(fn (array $p): array => $this->column(3, [[
            ...$this->container('stack', '8px', 'domain-card', [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'padding' => $this->responsive(['top' => '20px', 'bottom' => '20px', 'left' => '20px', 'right' => '20px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->paragraph($p['badge'], [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.08em'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->h3($p['name'], [
                    'fontSize' => $this->responsive('17.5px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph($p['text'], [
                    'fontSize' => $this->responsive('13px'),
                    'lineHeight' => $this->responsive('1.55'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                ]),
            ],
        ]]), $pillars);

        return $this->tagSection('spotlight', $this->section('Technology', [
            $this->row($cols, $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '48px', 'bottom' => '48px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '36px', 'bottom' => '36px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * What the free core includes, in plain words (the Features page).
     *
     * @return array<string, mixed>
     */
    private function domainsSection(): array
    {
        $domains = [
            ['Store', 'Products and catalog', 'Products, variants, attributes, brands and inventory.'],
            ['Store', 'Cart and checkout', 'A shopping cart, guest checkout and coupons.'],
            ['Store', 'Taxes and shipping', 'Set up taxes and shipping options for the places you sell to.'],
            ['Store', 'Orders and invoices', 'Manage orders, use bulk actions and create PDF invoices.'],
            ['Store', 'Digital downloads', 'Sell files that customers download after they buy.'],
            ['Store', 'Sales reports', 'See how your store is doing in simple reports.'],
            ['Content', 'Pages', 'Write About, FAQ and policy pages with a text editor, SEO fields and menu placement.'],
            ['Content', 'Page builder', 'Design your home page visually with sections and blocks.'],
            ['Content', 'Languages and SEO', 'Translate your store and content. A sitemap is created for you.'],
            ['Admin', 'Staff roles and audit log', 'Give each person the access they need and see who changed what.'],
            ['Admin', 'Settings and email', 'Store details, maintenance mode, two-factor sign-in and email through your own SMTP server.'],
            ['Admin', 'Backups and updates', 'Scheduled backups, and core updates with a rollback if something goes wrong.'],
        ];

        $header = $this->row([$this->column(12, [
            $this->h2('What is included', [
                'fontSize' => ['desktop' => '30px', 'mobile' => '24px'],
                'fontWeight' => $this->responsive('300'),
                'textAlign' => $this->responsive('center'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
            ]),
            $this->paragraph('These features are part of the free core. Modules can add more.', [
                'fontSize' => $this->responsive('14.5px'),
                'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                'textAlign' => $this->responsive('center'),
                'maxWidth' => $this->responsive('680px'),
                'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto', 'top' => '8px', 'bottom' => '20px']),
            ]),
        ])], $this->wideRow());

        $rows = [];
        foreach (array_chunk($domains, 4) as $group) {
            $cols = array_map(fn (array $d): array => $this->column(3, [[
                ...$this->container('stack', '6px', 'domain-card', [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                    'padding' => $this->responsive(['top' => '18px', 'bottom' => '18px', 'left' => '18px', 'right' => '18px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->paragraph(strtoupper($d[0]), [
                        'fontSize' => $this->responsive('11px'),
                        'fontWeight' => $this->responsive('600'),
                        'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                    ]),
                    $this->h4($d[1], [
                        'fontSize' => $this->responsive('17px'),
                        'fontWeight' => $this->responsive('500'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                    ]),
                    $this->paragraph($d[2], [
                        'fontSize' => $this->responsive('13px'),
                        'lineHeight' => $this->responsive('1.55'),
                        'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                    ]),
                ],
            ]]), $group);
            $rows[] = $this->row($cols, $this->wideRow());
        }

        return $this->tagSection('domains', $this->section('What is included', [
            $header,
            ...$rows,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
            'padding' => ['desktop' => ['top' => '64px', 'bottom' => '68px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '44px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Filterable list of example modules (the Themes & Modules page).
     *
     * @return array<string, mixed>
     */
    private function modulesCatalogSection(): array
    {
        $filters = [
            ['key' => 'all', 'label' => 'All modules'],
            ['key' => 'payments', 'label' => 'Payments'],
            ['key' => 'marketplace', 'label' => 'Orders and delivery'],
            ['key' => 'growth', 'label' => 'Marketing and sales'],
            ['key' => 'security', 'label' => 'Privacy'],
            ['key' => 'cloud', 'label' => 'SEO and tools'],
        ];

        $labels = array_column($filters, 'label', 'key');

        $filterBar = [
            ...$this->container('row', '10px', 'module-filters', [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => $this->responsive('center'),
                'margin' => $this->responsive(['bottom' => '20px']),
            ]),
            'children' => array_map(fn (array $f, int $idx): array => $this->blockNode(BlockType::Button, [
                'label' => $f['label'],
                'url' => '#module-filter-'.$f['key'],
                'variant' => $idx === 0 ? 'primary' : 'outline',
                'size' => 'sm',
                'vertexFilter' => $f['key'],
            ], [
                'borderRadius' => $this->responsive('4px'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#cbd5e1')]),
                'color' => $this->responsive($idx === 0 ? '#ffffff' : DesignTokens::ref('heading', '#2d3748')),
                'backgroundColor' => $this->responsive($idx === 0 ? DesignTokens::ref('primary', '#006994') : '#ffffff'),
            ]), $filters, array_keys($filters)),
        ];

        // [name, filter, what it does]. Examples of what can be added; the list is not exhaustive.
        $modules = [
            ['PayPal', 'payments', 'Accept payments through PayPal at checkout.'],
            ['Razorpay', 'payments', 'Accept payments through Razorpay at checkout.'],
            ['Store Pickup', 'marketplace', 'Let customers collect their orders from your location.'],
            ['Order Returns', 'marketplace', 'Handle return requests for orders.'],
            ['Multi-Vendor', 'marketplace', 'Let several sellers sell in one store.'],
            ['SMS', 'marketplace', 'Send text message notifications.'],
            ['Product Reviews', 'growth', 'Let customers rate and review products.'],
            ['Wishlist', 'growth', 'Let customers save products for later.'],
            ['Newsletter', 'growth', 'Collect email sign-ups and send newsletters.'],
            ['Flash Sales', 'growth', 'Run time-limited sales on selected products.'],
            ['Cart Recovery', 'growth', 'Remind customers about items they left in their cart.'],
            ['GDPR Consent', 'security', 'Ask visitors for cookie and privacy consent.'],
            ['Advanced SEO', 'cloud', 'More control over page titles, descriptions and search settings.'],
            ['Google Analytics', 'cloud', 'Add Google Analytics tracking to your store.'],
            ['AI Assistant', 'cloud', 'Help write product descriptions and page copy with your own AI key.'],
        ];

        $cards = array_map(fn (array $m): array => $this->column(4, [[
            ...$this->container('stack', '8px', 'module-card', [
                'vertexModuleCat' => $m[1],
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '22px', 'bottom' => '22px', 'left' => '22px', 'right' => '22px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->paragraph(strtoupper($labels[$m[1]]), [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->h4($m[0], [
                    'fontSize' => $this->responsive('17.5px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph($m[2], [
                    'fontSize' => $this->responsive('13px'),
                    'lineHeight' => $this->responsive('1.55'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                ]),
            ],
        ]]), $modules);

        $rows = array_map(fn (array $chunk): array => $this->row($chunk, $this->wideRow()), array_chunk($cards, 3));

        return $this->tagSection('modules', $this->section('Module List', [
            $this->row([$this->column(12, [
                $this->h2('Example modules', [
                    'fontSize' => ['desktop' => '30px', 'mobile' => '24px'],
                    'fontWeight' => $this->responsive('300'),
                    'textAlign' => $this->responsive('center'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                    'margin' => $this->responsive(['bottom' => '8px']),
                ]),
                $this->paragraph('Pick a group to filter the list. These are examples of what can be added; install them from Admin → Extensions or upload a ZIP.', [
                    'fontSize' => $this->responsive('14.5px'),
                    'textAlign' => $this->responsive('center'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                    'margin' => $this->responsive(['bottom' => '18px']),
                ]),
                $filterBar,
            ])], $this->wideRow()),
            ...$rows,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '56px', 'bottom' => '68px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Three solution track cards for `/solutions`.
     *
     * @return array<string, mixed>
     */
    private function solutionsTracksSection(): array
    {
        $tracks = [
            [
                'img' => 'solve-stack.svg',
                'eyebrow' => 'SMALL SHOPS',
                'title' => 'Run your own online shop',
                'body' => 'Install it on regular hosting, add your products and take orders without a monthly platform fee. Payment providers still charge their own fees.',
                'linkLabel' => 'See what is included >',
                'url' => '/platform',
            ],
            [
                'img' => 'spotlight-modules.svg',
                'eyebrow' => 'AGENCIES',
                'title' => 'Build stores for clients',
                'body' => 'Use the same free core for every client project. The MIT license allows commercial and client work.',
                'linkLabel' => 'Read the install guide >',
                'url' => '/developers',
            ],
            [
                'img' => 'topic-gateways.svg',
                'eyebrow' => 'DEVELOPERS',
                'title' => 'Extend it your way',
                'body' => 'It is a standard Laravel application. Write your own themes and modules, or change the code directly.',
                'linkLabel' => 'See themes and modules >',
                'url' => '/modules',
            ],
        ];

        $cols = array_map(fn (array $t): array => $this->column(4, [[
            ...$this->container('stack', '12px', 'spotlight-card', [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '26px', 'bottom' => '26px', 'left' => '24px', 'right' => '24px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->image(self::ASSETS.$t['img'], $t['title'], ['width' => $this->responsive('100%'), 'maxWidth' => $this->responsive('260px')]),
                $this->paragraph($t['eyebrow'], [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.08em'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->h3($t['title'], [
                    'fontSize' => $this->responsive('19px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph($t['body'], [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.6'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                ]),
                $this->inlineLink($t['linkLabel'], $t['url'], [
                    'fontSize' => $this->responsive('12.5px'),
                    'fontWeight' => $this->responsive('600'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                    'margin' => $this->responsive(['top' => '6px']),
                ]),
            ],
        ]]), $tracks);

        return $this->tagSection('spotlight', $this->section('Use Cases', [
            $this->row($cols, $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '56px', 'bottom' => '56px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '40px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Developer CLI workflow and built-in security checklist (/developers).
     *
     * @return array<string, mixed>
     */
    private function developersQuickstartSection(): array
    {
        $cliCard = [
            ...$this->container('stack', '14px', 'domain-card', [
                'backgroundColor' => $this->responsive('#0c2340'),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'padding' => $this->responsive(['top' => '28px', 'bottom' => '28px', 'left' => '28px', 'right' => '28px']),
                'color' => $this->responsive('#ffffff'),
            ]),
            'children' => [
                $this->paragraph('INSTALL WITH COMPOSER AND ARTISAN', [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.1em'),
                    'color' => $this->responsive('#7cd4f8'),
                ]),
                $this->h3('Install on your computer or server', [
                    'fontSize' => $this->responsive('22px'),
                    'fontWeight' => $this->responsive('300'),
                    'color' => $this->responsive('#ffffff'),
                ]),
                $this->paragraph('Set your database details in the .env file before you run the migrations.', [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.6'),
                    'color' => $this->responsive('#d8e7f4'),
                ]),
                $this->paragraph("composer install\nnpm install && npm run build\ncp .env.example .env\nphp artisan key:generate\nphp artisan migrate --seed", [
                    'fontSize' => $this->responsive('13px'),
                    'lineHeight' => $this->responsive('1.75'),
                    'color' => $this->responsive('#e2edf7'),
                    'backgroundColor' => $this->responsive('#07172b'),
                    'padding' => $this->responsive(['top' => '16px', 'bottom' => '16px', 'left' => '16px', 'right' => '16px']),
                    'borderRadius' => $this->responsive('4px'),
                ]),
                $this->blockNode(BlockType::Button, [
                    'label' => 'Copy the install commands',
                    'url' => '#copy-install',
                    'variant' => 'outline',
                    'size' => 'sm',
                    'vertexRole' => 'outline-cta',
                    'vertexCopy' => 'composer install && npm install && npm run build && cp .env.example .env && php artisan key:generate && php artisan migrate --seed',
                ], [
                    'color' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => '#ffffff']),
                    'borderRadius' => $this->responsive('3px'),
                    'backgroundColor' => $this->responsive('transparent'),
                ]),
            ],
        ];

        $secCard = [
            ...$this->container('stack', '12px', 'domain-card', [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'padding' => $this->responsive(['top' => '28px', 'bottom' => '28px', 'left' => '28px', 'right' => '28px']),
            ]),
            'children' => [
                $this->paragraph('GOOD TO KNOW', [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.1em'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->h3('Hosting and license', [
                    'fontSize' => $this->responsive('22px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph('• Runs on shared hosting, a VPS or a Git-based platform.', ['fontSize' => $this->responsive('13.5px'), 'color' => $this->responsive(DesignTokens::ref('body', '#596573'))]),
                $this->paragraph('• Needs PHP 8.3 or newer and MySQL 8, MariaDB or SQLite.', ['fontSize' => $this->responsive('13.5px'), 'color' => $this->responsive(DesignTokens::ref('body', '#596573'))]),
                $this->paragraph('• MIT license: free for personal, commercial and client projects.', ['fontSize' => $this->responsive('13.5px'), 'color' => $this->responsive(DesignTokens::ref('body', '#596573'))]),
                $this->paragraph('• Staff roles, an audit log and two-factor sign-in are available in the admin.', ['fontSize' => $this->responsive('13.5px'), 'color' => $this->responsive(DesignTokens::ref('body', '#596573'))]),
            ],
        ];

        return $this->tagSection('domains', $this->section('Install Guide', [
            $this->row([
                $this->column(6, [$cliCard]),
                $this->column(6, [$secCard]),
            ], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
            'padding' => ['desktop' => ['top' => '56px', 'bottom' => '56px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '40px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Dedicated Interactive Demo & 1-Click Theme Demo Import section (/demo).
     *
     * @return array<string, mixed>
     */
    private function demoImportSection(): array
    {
        $previewPanel = [
            ...$this->container('stack', '12px', null, [
                'padding' => $this->responsive(['top' => '16px']),
            ]),
            'children' => [
                $this->h4('Six example pages, plus a header and a footer', [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph('Importing the demo adds the header, the footer and these pages: Home, Features, Themes and modules, Use cases, Get started and Demo. Every one of them can be edited in the page builder.', [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.6'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                ]),
                $this->sharpPrimaryButton('Open Themes to import the demo', '/admin/themes'),
            ],
        ];

        $cliPanel = [
            ...$this->container('stack', '12px', null, [
                'padding' => $this->responsive(['top' => '16px']),
            ]),
            'children' => [
                $this->h4('Or use the command line', [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph("php artisan cms:sync\nphp artisan cms:theme activate modrix/vertex --trust\n# Then click 'Import demo' in Appearance -> Themes", [
                    'fontSize' => $this->responsive('13px'),
                    'lineHeight' => $this->responsive('1.7'),
                    'backgroundColor' => $this->responsive('#0c2340'),
                    'color' => $this->responsive('#e2edf7'),
                    'padding' => $this->responsive(['top' => '14px', 'bottom' => '14px', 'left' => '16px', 'right' => '16px']),
                    'borderRadius' => $this->responsive('4px'),
                ]),
                $this->blockNode(BlockType::Button, [
                    'label' => 'Copy the commands',
                    'url' => '#copy-demo',
                    'variant' => 'secondary',
                    'size' => 'sm',
                    'vertexCopy' => 'php artisan cms:sync && php artisan cms:theme activate modrix/vertex --trust',
                ]),
            ],
        ];

        $customizerPanel = [
            ...$this->container('stack', '12px', null, [
                'padding' => $this->responsive(['top' => '16px']),
            ]),
            'children' => [
                $this->h4('Change colors and fonts', [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                $this->paragraph('Colors, fonts and buttons can be changed in Appearance → Theme settings. The hero color, the announcement strip, the footer stripe and the top link bar can be changed in Appearance → Customize.', [
                    'fontSize' => $this->responsive('13.5px'),
                    'lineHeight' => $this->responsive('1.6'),
                    'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                ]),
                $this->sharpPrimaryButton('Open Theme settings', '/admin/themes'),
            ],
        ];

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'tabs' => [
                    ['id' => null, 'label' => 'Import the demo'],
                    ['id' => null, 'label' => 'Command line'],
                    ['id' => null, 'label' => 'Colors and fonts'],
                ],
            ]),
            'children' => [$previewPanel, $cliPanel, $customizerPanel],
        ];

        $leftPreview = [
            ...$this->container('stack', '14px', 'spotlight-card', [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '24px', 'bottom' => '24px', 'left' => '24px', 'right' => '24px']),
            ]),
            'children' => [
                $this->image(self::ASSETS.'demo-console.svg', 'Illustration of the Modrix Commerce admin and the demo import screen', [
                    'width' => $this->responsive('100%'),
                ]),
                $tabs,
            ],
        ];

        $routes = [
            ['Home (/)', 'An introduction, the main features and where to start.', '/'],
            ['Features (/platform)', 'What the free core includes, in plain words.', '/platform'],
            ['Themes and modules (/modules)', 'Example modules you can add, with a filter.', '/modules'],
            ['Use cases (/solutions)', 'Who Modrix Commerce is for.', '/solutions'],
            ['Get started (/developers)', 'Install commands and hosting needs.', '/developers'],
            ['Demo (/demo)', 'How to import this demo.', '/demo'],
        ];

        $checklistCard = [
            ...$this->container('stack', '12px', 'domain-card', [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'padding' => $this->responsive(['top' => '24px', 'bottom' => '24px', 'left' => '24px', 'right' => '24px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->paragraph('INCLUDED PAGES', [
                    'fontSize' => $this->responsive('11px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('0.1em'),
                    'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                ]),
                $this->h3('Pages in the demo', [
                    'fontSize' => $this->responsive('20px'),
                    'fontWeight' => $this->responsive('400'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                ]),
                ...array_map(fn (array $r): array => [
                    ...$this->container('stack', '2px', null, [
                        'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#edf2f7'), 'bottom' => '1px']),
                        'padding' => $this->responsive(['bottom' => '10px']),
                    ]),
                    'children' => [
                        $this->h4($r[0], [
                            'fontSize' => $this->responsive('14.5px'),
                            'fontWeight' => $this->responsive('500'),
                            'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                        ], $r[2]),
                        $this->paragraph($r[1], [
                            'fontSize' => $this->responsive('12.5px'),
                            'lineHeight' => $this->responsive('1.5'),
                            'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                        ]),
                    ],
                ], $routes),
            ],
        ];

        return $this->tagSection('spotlight', $this->section('Demo Import', [
            $this->row([
                $this->column(7, [$leftPreview]),
                $this->column(5, [$checklistCard]),
            ], $this->wideRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#eef2f5')),
            'padding' => ['desktop' => ['top' => '56px', 'bottom' => '64px', 'left' => '28px', 'right' => '28px'], 'mobile' => ['top' => '40px', 'bottom' => '48px', 'left' => '16px', 'right' => '16px']],
        ]));
    }

    /**
     * Build a wide horizontal card matching VMware's Trending Topics cards.
     *
     * @return array<string, mixed>
     */
    private function horizontalTopicCard(
        string $asset,
        string $alt,
        string $title,
        string $description,
        string $ctaLabel,
        string $url,
    ): array {
        $cols = [
            ...$this->container('columns', '20px', null, [
                'columns' => [4, 8],
                'stackBelow' => 'mobile',
            ]),
            'children' => [
                [
                    ...$this->container('stack', '0px', null, [
                        'alignItems' => $this->responsive('center'),
                        'justifyContent' => $this->responsive('center'),
                    ]),
                    'children' => [
                        $this->image(self::ASSETS.$asset, $alt, [
                            'width' => $this->responsive('100%'),
                            'maxWidth' => $this->responsive('180px'),
                        ], $url),
                    ],
                ],
                [
                    ...$this->container('stack', '8px', null, [
                        'justifyContent' => $this->responsive('center'),
                    ]),
                    'children' => [
                        $this->h3($title, [
                            'fontSize' => $this->responsive('18px'),
                            'fontWeight' => $this->responsive('400'),
                            'lineHeight' => $this->responsive('1.35'),
                            'color' => $this->responsive(DesignTokens::ref('heading', '#2d3748')),
                        ], $url),
                        $this->paragraph($description, [
                            'fontSize' => $this->responsive('13px'),
                            'lineHeight' => $this->responsive('1.6'),
                            'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                        ]),
                        $this->inlineLink($ctaLabel, $url, [
                            'fontSize' => $this->responsive('11.5px'),
                            'fontWeight' => $this->responsive('600'),
                            'letterSpacing' => $this->responsive('0.05em'),
                            'color' => $this->responsive(DesignTokens::ref('primary', '#006994')),
                            'margin' => $this->responsive(['top' => '4px']),
                        ]),
                    ],
                ],
            ],
        ];

        return [
            ...$this->container('stack', '0px', 'topic-card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#dce3ec')]),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '6px')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '24px', 'bottom' => '24px', 'left' => '24px', 'right' => '24px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [$cols],
        ];
    }

    /* ---------------------------------------------------------------------- */
    /* Document & block builder helpers */
    /* ---------------------------------------------------------------------- */

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private function design(string $key, string $name, string $category, string $description, array $sections): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'document' => $this->document($sections),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private function document(array $sections): array
    {
        return TemplatePresentation::document([
            'schemaVersion' => 1,
            'sections' => $sections,
        ]);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function tagSection(string $block, array $section): array
    {
        $section['settings'] = [
            'designCollection' => self::COLLECTION,
            'designBlock' => $block,
        ];

        return $section;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function section(string $name, array $rows, array $style = []): array
    {
        $isSticky = (bool) ($style['sticky'] ?? false);
        unset($style['sticky']);

        return [
            'id' => $this->nextId(),
            'name' => $name,
            'width' => 'full',
            'sticky' => $isSticky,
            'style' => array_merge([
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
                'color' => $this->responsive(DesignTokens::ref('body', '#596573')),
                'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#ffffff')),
            ], $style),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function row(array $columns, array $style = [], string $stackBelow = 'mobile'): array
    {
        return [
            'id' => $this->nextId(),
            'stackBelow' => $stackBelow,
            'style' => array_merge([
                'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']],
                'gap' => ['desktop' => DesignTokens::ref('grid_gap', '24px'), 'mobile' => '16px'],
            ], $style),
            'columns' => $columns,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function wideRow(array $extra = []): array
    {
        return array_merge([
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
        ], $extra);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function column(int $span, array $blocks, array $style = []): array
    {
        return [
            'id' => $this->nextId(),
            'span' => ['desktop' => $span, 'tablet' => $span >= 6 ? 12 : 6, 'mobile' => 12],
            'style' => $style,
            'blocks' => $blocks,
        ];
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function container(string $layout, string $gap, ?string $role = null, array $style = []): array
    {
        $props = [
            'layout' => $layout,
            'gap' => $gap,
        ];

        foreach (['columns', 'stackBelow', 'vertexModuleCat'] as $propKey) {
            if (array_key_exists($propKey, $style)) {
                $props[$propKey] = $style[$propKey];
                unset($style[$propKey]);
            }
        }

        if ($role !== null) {
            $props['vertexRole'] = $role;
        }

        return $this->blockNode(BlockType::Container, $props, $style);
    }

    /** @return array<string, mixed> */
    private function utilityLink(string $label, string $url): array
    {
        return $this->inlineLink($label, $url, [
            'fontSize' => $this->responsive('12px'),
            'fontWeight' => $this->responsive('400'),
            'color' => $this->responsive(DesignTokens::ref('muted', '#64748b')),
        ]);
    }

    /**
     * Clickable clean link rendered via native Button (`variant: link`) so labels & URLs edit cleanly in the Builder.
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function inlineLink(string $label, string $url, array $style = []): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'url' => $url,
            'variant' => 'link',
            'size' => 'sm',
            'vertexRole' => 'inline-link',
        ], array_merge([
            'textAlign' => $this->responsive('left'),
        ], $style));
    }

    /**
     * @param  list<array{label: string, url: string}>  $items
     * @return array<string, mixed>
     */
    private function verticalFooterNav(array $items): array
    {
        return $this->blockNode(BlockType::Navigation, [
            'items' => $items,
            'orientation' => 'vertical',
            'alignment' => 'left',
        ], [
            'fontSize' => $this->responsive('13.5px'),
            'color' => $this->responsive(DesignTokens::ref('footer_text', '#4a5565')),
            'gap' => $this->responsive('8px'),
        ]);
    }

    /** @return array<string, mixed> */
    private function sharpPrimaryButton(string $label, string $url): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'url' => $url,
            'variant' => 'primary',
            'size' => 'sm',
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary', '#006994')),
            'color' => $this->responsive(DesignTokens::ref('on_primary', '#ffffff')),
            'borderRadius' => $this->responsive('3px'),
            'fontSize' => $this->responsive('12px'),
            'fontWeight' => $this->responsive('600'),
            'letterSpacing' => $this->responsive('0.05em'),
            'padding' => $this->responsive(['top' => '10px', 'bottom' => '10px', 'left' => '18px', 'right' => '18px']),
        ]);
    }

    /** @return array<string, mixed> */
    private function outlineCtaButton(string $label, string $url): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'url' => $url,
            'variant' => 'outline',
            'size' => 'sm',
            'vertexRole' => 'outline-cta',
        ], [
            'backgroundColor' => $this->responsive('transparent'),
            'color' => $this->responsive('#ffffff'),
            'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => '#ffffff']),
            'borderRadius' => $this->responsive('3px'),
            'fontSize' => $this->responsive('12px'),
            'fontWeight' => $this->responsive('600'),
            'letterSpacing' => $this->responsive('0.05em'),
            'padding' => $this->responsive(['top' => '10px', 'bottom' => '10px', 'left' => '22px', 'right' => '22px']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h1(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['level' => 'h1', 'text' => $text], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h2(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['level' => 'h2', 'text' => $text], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h3(string $text, array $style = [], ?string $link = null): array
    {
        return $this->blockNode(BlockType::Heading, [
            'level' => 'h3',
            'text' => $text,
            'link' => $link,
        ], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h4(string $text, array $style = [], ?string $link = null): array
    {
        return $this->blockNode(BlockType::Heading, [
            'level' => 'h4',
            'text' => $text,
            'link' => $link,
        ], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function paragraph(string $text, array $style = [], ?string $link = null): array
    {
        return $this->blockNode(BlockType::Paragraph, [
            'text' => $text,
            'link' => $link,
        ], $style);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function image(string $src, string $alt, array $style = [], ?string $link = null): array
    {
        return $this->blockNode(BlockType::Image, [
            'src' => $src,
            'alt' => $alt,
            'link' => $link,
        ], $style);
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function blockNode(BlockType $type, array $props = [], array $style = []): array
    {
        return [
            'id' => $this->nextId(),
            'type' => $type->value,
            'props' => array_merge(BlockRegistry::defaultProps($type), $props),
            'style' => $style,
        ];
    }

    /** @return array<string, mixed> */
    private function responsive(mixed $value): array
    {
        return ['desktop' => $value];
    }

    private function nextId(): string
    {
        $this->idCounter++;
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $n = $this->idCounter;
        $suffix = '';

        for ($i = 0; $i < 16; $i++) {
            $suffix = $alphabet[$n % 32].$suffix;
            $n = intdiv($n, 32);
        }

        return '01J9VTX000'.$suffix;
    }
}
