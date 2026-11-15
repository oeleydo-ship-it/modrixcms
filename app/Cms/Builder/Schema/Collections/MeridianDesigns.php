<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Themes\DesignTokens;

/**
 * Reusable design blocks and multi-page templates for the Meridian Advisory theme (`modrix/meridian`).
 *
 * Every section carries `settings.designCollection = meridian` and reads active Theme Settings via
 * `--site-*` CSS custom properties so typography, colours, spacing, and radii update dynamically
 * across all pages and blocks.
 */
class MeridianDesigns
{
    public const COLLECTION = 'meridian';

    private const LOGO = '/templates/meridian/logo.svg';

    private const LOGO_LIGHT = '/templates/meridian/logo-light.svg';

    private const EMAIL = 'hello@meridianpath.org';

    private const PHONE = '+44 20 7946 0940';

    private const ADDRESS = '44 Bishopsgate, London EC2N 4AH';

    private const PHOTOS = [
        'hero_advisory' => '1552664730-d307ca884978',
        'trust_meeting' => '1573497019940-1c28c88b4f3e',
        'trust_collab' => '1600880292203-757bb62b4baf',
        'about_map' => '1522202176988-66273c2fd55f',
        'consult_desk' => '1556761175-5973dc0f32e7',
        'office_hero' => '1497215728101-856f4ea42174',
        'route_study' => '1541339907198-e08756dedf3f',
        'route_work' => '1497366216548-37526070297c',
        'route_travel' => '1436491865332-7a61a109cc05',
        'route_business' => '1486406146926-c627a92ad1ab',
        'route_residency' => '1600585154340-be6161a56a0c',
        'route_family' => '1511895426328-dc8714191300',
        'dest_sydney' => '1506973035872-a4ec16b8e8d9',
        'dest_newyork' => '1496442226666-8d4d0e62e6e9',
        'dest_berlin' => '1560969184-10fe8719e047',
        'dest_london' => '1513635269975-59663e0ac1ad',
        'dest_paris' => '1502602898657-3e91760cbb34',
        'dest_toronto' => '1517935706615-2717063c2225',
        'proc_1' => '1517245386807-bb43f82c33c4',
        'proc_2' => '1450101499163-c8848c66ca85',
        'proc_3' => '1554224155-8d04cb21cd6c',
        'proc_4' => '1540575467063-178a50c2df87',
        'story_1' => '1573496359142-b8d87734a5a2',
        'story_2' => '1560250097-0b93528c311a',
        'story_3' => '1580489944761-15a19d654956',
        'lead_adviser' => '1507003211169-0a1dd7228f2d',
        'adviser_2' => '1534528741775-53994a69daeb',
        'adviser_3' => '1519085360753-af0119f7cbe7',
        'adviser_4' => '1544005313-94ddf0286df2',
        'why_travel' => '1488646953014-85cb44e25828',
        'blog_1' => '1554224154-26032ffc0d07',
        'blog_2' => '1521791136064-7986c2920216',
        'blog_3' => '1551836022-d5d88e9218df',
        'blog_4' => '1544717305-2782549b5136',
    ];

    private int $idCounter = 0;

    /**
     * Reusable design blocks surfaced in the Visual Builder library under the `Meridian` tab.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->headerDesign(),
            $this->footerDesign(),
            $this->heroDesign(),
            $this->trustDesign(),
            $this->routesTabsDesign(),
            $this->aboutOverviewDesign(),
            $this->destinationsGalleryDesign(),
            $this->whyChooseDesign(),
            $this->servicesDarkDesign(),
            $this->processDesign(),
            $this->diagnosticsDesign(),
            $this->storiesDesign(),
            $this->liveDeskDesign(),
            $this->faqDesign(),
            $this->insightsDesign(),
            $this->pageHeroDesign(),
            $this->aboutCharterDesign(),
            $this->teamLeadershipDesign(),
            $this->serviceTiersDesign(),
            $this->programsCatalogDesign(),
            $this->destinationsDirectoryDesign(),
            $this->articlesArchiveDesign(),
            $this->contactSplitDesign(),
        ];
    }

    /**
     * Built-in standalone page templates registered with the template library (includes header & footer).
     *
     * @return list<array<string, mixed>>
     */
    public function pages(): array
    {
        return array_map(function (array $definition): array {
            $sections = $definition['sections']();

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'category' => $definition['category'],
                'description' => $definition['description'],
                'document' => $this->document([
                    $this->headerSection(),
                    ...$sections,
                    $this->footerSection(),
                ]),
            ];
        }, $this->pageDefinitions(false));
    }

    /**
     * Page body documents exported as theme demo JSON files (wrapped by global site header/footer).
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
        ], $this->pageDefinitions(true));
    }

    /**
     * Shared page definitions so standalone templates and theme demo pages share one source of truth.
     *
     * @return list<array{key: string, name: string, slug: string, category: string, description: string, sections: callable(): list<array<string, mixed>>}>
     */
    private function pageDefinitions(bool $withCaptureBlocks = false): array
    {
        return [
            [
                'key' => 'meridian-home',
                'name' => 'Meridian · Home Showcase',
                'slug' => 'home',
                'category' => 'advisory',
                'description' => 'Full editorial landing experience with quick eligibility form, vertical mobility tabs, destinations marquee gallery, dark services split, process timeline, live desk feed, FAQ accordion and insights grid.',
                'sections' => fn (): array => [
                    $this->heroSection($withCaptureBlocks),
                    $this->trustSection(),
                    $this->routesTabsSection(),
                    $this->aboutOverviewSection(),
                    $this->destinationsGallerySection(),
                    $this->whyChooseSection(),
                    $this->servicesDarkSection(),
                    $this->processSection(),
                    $this->diagnosticsSection(),
                    $this->storiesSection(),
                    $this->liveDeskSection(),
                    $this->faqSection(),
                    $this->insightsSection(),
                ],
            ],
            [
                'key' => 'meridian-about',
                'name' => 'Meridian · About & Partners',
                'slug' => 'about',
                'category' => 'advisory',
                'description' => 'Practice story, accreditation metrics, operating charter, senior partner portraits, client stories and FAQ.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Our Practice', 'Cross-Border Counsel Built on <u>Accountability</u>', 'Independent case strategy for skilled professionals, founders and relocating families across 42 jurisdictions.', 'trust_collab'),
                    $this->trustSection(),
                    $this->aboutCharterSection(),
                    $this->whyChooseSection(),
                    $this->teamLeadershipSection(),
                    $this->storiesSection(),
                    $this->faqSection(),
                ],
            ],
            [
                'key' => 'meridian-services',
                'name' => 'Meridian · Advisory Services',
                'slug' => 'services',
                'category' => 'advisory',
                'description' => 'Dark frosted advisory capabilities, engagement scope tiers, four-stage filing methodology and diagnostic safeguards.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Advisory Scope', 'Precision Execution Across Every <u>Filing</u> Stage', 'From initial statutory scoping through biometrics, consular representation and post-arrival registration.', 'consult_desk'),
                    $this->servicesDarkSection(),
                    $this->serviceTiersSection(),
                    $this->processSection(),
                    $this->diagnosticsSection(),
                    $this->faqSection(),
                ],
            ],
            [
                'key' => 'meridian-programs',
                'name' => 'Meridian · Mobility Programs',
                'slug' => 'programs',
                'category' => 'advisory',
                'description' => 'Interactive vertical route explorer paired with structured visa & residency comparison cards and client outcomes.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Mobility Routes', 'Six Core Pathways Tailored to Your <u>Mandate</u>', 'Structured guidance for academic study, employer sponsorship, venture founders, global travelers, residents and families.', 'office_hero'),
                    $this->routesTabsSection(),
                    $this->programsCatalogSection(),
                    $this->processSection(),
                    $this->storiesSection(),
                ],
            ],
            [
                'key' => 'meridian-destinations',
                'name' => 'Meridian · Global Destinations',
                'slug' => 'destinations',
                'category' => 'advisory',
                'description' => 'Signature destinations gallery marquee, regional desk directory with lightbox photo gallery, commitments and FAQ.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Jurisdictions', 'Regional Desks Inside Six Leading <u>Cities</u>', 'Dedicated local compliance leads bridging immigration regulations, tax registration and settlement logistics.', 'dest_london'),
                    $this->destinationsGallerySection(),
                    $this->destinationsDirectorySection(),
                    $this->whyChooseSection(),
                    $this->faqSection(),
                ],
            ],
            [
                'key' => 'meridian-insights',
                'name' => 'Meridian · Policy Briefings',
                'slug' => 'insights',
                'category' => 'advisory',
                'description' => 'Featured editorial briefing paired with a multi-column policy, mobility and relocation research archive.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Policy & Research', 'Clear Briefings on Shifting <u>Rules</u>', 'Practical analysis from our regional desks on talent thresholds, processing windows and compliance updates.', 'blog_2'),
                    $this->articlesArchiveSection(),
                    $this->faqSection(),
                ],
            ],
            [
                'key' => 'meridian-contact',
                'name' => 'Meridian · Book Consultation',
                'slug' => 'contact',
                'category' => 'advisory',
                'description' => 'Interactive intake assessment form, direct Bishopsgate London desk details, international phone lines and interactive map.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Speak With Counsel', 'Schedule Your Private Route <u>Review</u>', 'Share your destination timeline and objectives to receive a written scope brief within one business day.', 'proc_1'),
                    $this->contactSplitSection($withCaptureBlocks),
                    $this->faqSection(),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function headerDocument(): array
    {
        return $this->document([$this->headerSection()]);
    }

    /** @return array<string, mixed> */
    public function footerDocument(): array
    {
        return $this->document([$this->footerSection(true)]);
    }

    /**
     * Header & Footer Design Blocks.
     *
     * @return array<string, mixed>
     */
    private function headerDesign(): array
    {
        return $this->design(
            'meridian-header',
            'Meridian · Floating Header & Mega Menus',
            'structure',
            'Translucent forest glass navigation pill with three multi-column icon dropdowns, mobile drawer accordion and primary consultation CTA.',
            [$this->headerSection()],
        );
    }

    /** @return array<string, mixed> */
    private function footerDesign(): array
    {
        return $this->design(
            'meridian-footer',
            'Meridian · Glass Pre-Footer & Dark Footer',
            'structure',
            'Rounded full-bleed dark CTA banner over a four-column dark forest footer with newsletter opt-in and Bishopsgate desk details.',
            [$this->footerSection()],
        );
    }

    /** @return array<string, mixed> */
    private function heroDesign(): array
    {
        return $this->design(
            'meridian-hero',
            'Meridian · Hero & Quick Assessment',
            'hero',
            'Full-bleed dark-scrimmed hero with curved gold accent headline underline, dual CTAs and dark frosted assessment form widget.',
            [$this->heroSection()],
        );
    }

    /** @return array<string, mixed> */
    private function trustDesign(): array
    {
        return $this->design(
            'meridian-trust',
            'Meridian · Credentials & Metrics',
            'social_proof',
            'Sage statement header over tall portrait photo beside a 2x2 grid of numbered outcome counters.',
            [$this->trustSection()],
        );
    }

    /** @return array<string, mixed> */
    private function routesTabsDesign(): array
    {
        return $this->design(
            'meridian-routes-tabs',
            'Meridian · Interactive Pathway Tabs',
            'content',
            'Vertical sidebar tabs switching across six mobility tracks with criteria bullets, turnaround horizon and destination photo.',
            [$this->routesTabsSection()],
        );
    }

    /** @return array<string, mixed> */
    private function aboutOverviewDesign(): array
    {
        return $this->design(
            'meridian-about-overview',
            'Meridian · Editorial Studio Showcase',
            'media',
            'Split editorial statement paired with a rounded landscape photograph.',
            [$this->aboutOverviewSection()],
        );
    }

    /** @return array<string, mixed> */
    private function destinationsGalleryDesign(): array
    {
        return $this->design(
            'meridian-destinations-gallery',
            'Meridian · Destinations Section Gallery',
            'media',
            'Continuous horizontal scrolling marquee of signature global destination cards on a dark forest canvas.',
            [$this->destinationsGallerySection()],
        );
    }

    /** @return array<string, mixed> */
    private function whyChooseDesign(): array
    {
        return $this->design(
            'meridian-why-us',
            'Meridian · Sticky Commitments Grid',
            'content',
            'Three-column split with sticky heading, tall architectural photo and hairline-divided practice standards.',
            [$this->whyChooseSection()],
        );
    }

    /** @return array<string, mixed> */
    private function servicesDarkDesign(): array
    {
        return $this->design(
            'meridian-services-dark',
            'Meridian · Frosted Advisory Services',
            'content',
            'Dark architectural section with 2x2 frosted glass service cards beside a tall consultation portrait.',
            [$this->servicesDarkSection()],
        );
    }

    /** @return array<string, mixed> */
    private function processDesign(): array
    {
        return $this->design(
            'meridian-process',
            'Meridian · 4-Stage Filing Timeline',
            'content',
            'Split heading over a dotted timeline track and four image-topped filing methodology cards.',
            [$this->processSection()],
        );
    }

    /** @return array<string, mixed> */
    private function diagnosticsDesign(): array
    {
        return $this->design(
            'meridian-diagnostics',
            'Meridian · Common Hurdles & Remedies',
            'content',
            'Six diagnostic cards pairing refusal risk statistics with Meridian preventative remedies.',
            [$this->diagnosticsSection()],
        );
    }

    /** @return array<string, mixed> */
    private function storiesDesign(): array
    {
        return $this->design(
            'meridian-stories',
            'Meridian · Sticky Client Stories',
            'social_proof',
            'Dark showcase with sticky narrative intro beside a vertical stack of cream case-outcome cards.',
            [$this->storiesSection()],
        );
    }

    /** @return array<string, mixed> */
    private function liveDeskDesign(): array
    {
        return $this->design(
            'meridian-live-desk',
            'Meridian · Live Advisory Caseload',
            'social_proof',
            'Lead partner spotlight card paired with a dual-row continuous live caseload ticker marquee.',
            [$this->liveDeskSection()],
        );
    }

    /** @return array<string, mixed> */
    private function faqDesign(): array
    {
        return $this->design(
            'meridian-faq',
            'Meridian · FAQ Accordion & Callout',
            'content',
            'Sticky FAQ intro and dark photo consultation card beside circular-toggle accordion questions.',
            [$this->faqSection()],
        );
    }

    /** @return array<string, mixed> */
    private function insightsDesign(): array
    {
        return $this->design(
            'meridian-insights',
            'Meridian · Insights & Briefings Grid',
            'content',
            'Four-column policy and relocation briefings grid with photos, topic pills and reading links.',
            [$this->insightsSection()],
        );
    }

    /** @return array<string, mixed> */
    private function pageHeroDesign(): array
    {
        return $this->design(
            'meridian-page-hero',
            'Meridian · Inner Page Banner',
            'hero',
            'Compact dark photo banner with curved accent underline and concise subpage introduction.',
            [$this->pageHeroSection('Advisory Scope', 'Structured Pathways Across Every <u>Jurisdiction</u>', 'Focused legal and logistical counsel for high-stakes cross-border moves.', 'office_hero')],
        );
    }

    /** @return array<string, mixed> */
    private function aboutCharterDesign(): array
    {
        return $this->design(
            'meridian-about-charter',
            'Meridian · Practice Charter Cards',
            'content',
            'Three structured governance and quality-assurance principles.',
            [$this->aboutCharterSection()],
        );
    }

    /** @return array<string, mixed> */
    private function teamLeadershipDesign(): array
    {
        return $this->design(
            'meridian-team-leadership',
            'Meridian · Senior Counsel Directory',
            'content',
            'Four senior regional partners with portraits, qualifications and desk locations.',
            [$this->teamLeadershipSection()],
        );
    }

    /** @return array<string, mixed> */
    private function serviceTiersDesign(): array
    {
        return $this->design(
            'meridian-service-tiers',
            'Meridian · Advisory Engagement Scopes',
            'content',
            'Three transparent service retainer tiers covering solo applicants, founders and corporate HR teams.',
            [$this->serviceTiersSection()],
        );
    }

    /** @return array<string, mixed> */
    private function programsCatalogDesign(): array
    {
        return $this->design(
            'meridian-programs-catalog',
            'Meridian · Six-Track Programs Grid',
            'content',
            'Six-card detailed catalogue across Study, Work, Founder, Visit, Residency and Family routes.',
            [$this->programsCatalogSection()],
        );
    }

    /** @return array<string, mixed> */
    private function destinationsDirectoryDesign(): array
    {
        return $this->design(
            'meridian-destinations-directory',
            'Meridian · Regional Desks & Gallery Grid',
            'media',
            'Regional office showcase cards paired with an interactive city photography gallery.',
            [$this->destinationsDirectorySection()],
        );
    }

    /** @return array<string, mixed> */
    private function articlesArchiveDesign(): array
    {
        return $this->design(
            'meridian-articles-archive',
            'Meridian · Featured Briefing & Archive',
            'content',
            'Featured policy report split banner followed by a six-card briefings archive.',
            [$this->articlesArchiveSection()],
        );
    }

    /** @return array<string, mixed> */
    private function contactSplitDesign(): array
    {
        return $this->design(
            'meridian-contact-split',
            'Meridian · Consultation Form & Office Map',
            'content',
            'Interactive consultation form alongside direct desk coordinates and an embedded street map.',
            [$this->contactSplitSection()],
        );
    }

    /* ====================================================================== */
    /* SITEWIDE SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function headerSection(): array
    {
        $navItems = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About', 'url' => '/about'],
            [
                'label' => 'Programs',
                'url' => '/programs',
                'children' => [
                    [
                        'label' => 'Executive Work Permits',
                        'url' => '/programs',
                        'description' => 'Employer-sponsored & intra-group transfers',
                        'icon' => 'building-2',
                    ],
                    [
                        'label' => 'Founder & Investor Visas',
                        'url' => '/programs',
                        'description' => 'Capital, talent & innovation endorsements',
                        'icon' => 'chart-no-axes-combined',
                    ],
                    [
                        'label' => 'Academic & Study Routes',
                        'url' => '/programs',
                        'description' => 'University enrolment & post-study stay',
                        'icon' => 'book-open',
                    ],
                    [
                        'label' => 'Long-Stay Residency',
                        'url' => '/programs',
                        'description' => 'Permanent residence & tax-aligned paths',
                        'icon' => 'home',
                    ],
                    [
                        'label' => 'Family Reunification',
                        'url' => '/programs',
                        'description' => 'Partner, child & dependent filings',
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Global Business Travel',
                        'url' => '/programs',
                        'description' => 'Multi-entry delegations & short stays',
                        'icon' => 'compass',
                    ],
                ],
            ],
            [
                'label' => 'Destinations',
                'url' => '/destinations',
                'children' => [
                    [
                        'label' => 'United Kingdom · London',
                        'url' => '/destinations',
                        'description' => 'Skilled Worker, Innovator & Global Talent',
                        'icon' => 'map-pin',
                    ],
                    [
                        'label' => 'Germany · Berlin',
                        'url' => '/destinations',
                        'description' => 'EU Blue Card & Chancery Opportunity Card',
                        'icon' => 'map-pin',
                    ],
                    [
                        'label' => 'Canada · Toronto',
                        'url' => '/destinations',
                        'description' => 'Express Entry, Start-Up & PNP streams',
                        'icon' => 'map-pin',
                    ],
                    [
                        'label' => 'Australia · Sydney',
                        'url' => '/destinations',
                        'description' => 'Skills in Demand & Employer Nomination',
                        'icon' => 'map-pin',
                    ],
                    [
                        'label' => 'United States · New York',
                        'url' => '/destinations',
                        'description' => 'O-1A, L-1A Executive & E-2 Treaty routes',
                        'icon' => 'map-pin',
                    ],
                    [
                        'label' => 'France · Paris',
                        'url' => '/destinations',
                        'description' => 'Passeport Talent & French Tech tracks',
                        'icon' => 'map-pin',
                    ],
                ],
            ],
            [
                'label' => 'Resources',
                'url' => '/insights',
                'children' => [
                    [
                        'label' => 'Advisory Services',
                        'url' => '/services',
                        'description' => 'Retainers, compliance & document vetting',
                        'icon' => 'shield',
                    ],
                    [
                        'label' => 'Policy Briefings',
                        'url' => '/insights',
                        'description' => 'Quarterly analysis & rule updates',
                        'icon' => 'layers',
                    ],
                    [
                        'label' => 'All Programs Overview',
                        'url' => '/programs',
                        'description' => 'Compare eligibility & processing windows',
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'Book Consultation',
                        'url' => '/contact',
                        'description' => 'Speak directly with regional counsel',
                        'icon' => 'calendar-check',
                    ],
                ],
            ],
            ['label' => 'Contact', 'url' => '/contact'],
        ];

        $logo = $this->blockNode(BlockType::Logo, [
            'src' => self::LOGO_LIGHT,
            'alt' => 'Meridian Advisory',
            'url' => '/',
        ], [
            'height' => ['desktop' => '34px', 'mobile' => '30px'],
        ]);

        $nav = $this->blockNode(BlockType::Navigation, [
            'items' => $navItems,
            'orientation' => 'horizontal',
            'mobileBehaviour' => 'drawer',
        ], [
            'color' => $this->responsive(DesignTokens::ref('header_text', '#f7f8ee')),
            'textAlign' => $this->responsive('center'),
            'gap' => ['desktop' => '20px', 'mobile' => '8px'],
        ]);

        $cta = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'row',
                'gap' => '0px',
                'meridianRole' => 'header-cta',
            ], [
                'justifyContent' => $this->responsive('flex-end'),
            ]),
            'children' => [
                $this->ctaButton('Book Consultation', '/contact', 'light', 'arrow-right'),
            ],
        ];

        $pillBar = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [2, 8, 2],
                'gap' => '16px',
                'stackBelow' => 'none',
                'meridianRole' => 'header-bar',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg', '#15241b')),
                'borderRadius' => $this->responsive('22px'),
                'padding' => [
                    'desktop' => ['top' => '12px', 'right' => '20px', 'bottom' => '12px', 'left' => '22px'],
                    'mobile' => ['top' => '10px', 'right' => '14px', 'bottom' => '10px', 'left' => '16px'],
                ],
                'alignItems' => $this->responsive('center'),
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']],
            ]),
            'children' => [
                $logo,
                $nav,
                $cta,
            ],
        ];

        return $this->tagSection('header', $this->section('Site Header', [
            $this->row([$this->column(12, [$pillBar])], [
                'maxWidth' => $this->responsive('none'),
            ]),
        ], [
            'sticky' => true,
            'padding' => [
                'desktop' => ['top' => '16px', 'right' => '24px', 'bottom' => '12px', 'left' => '24px'],
                'mobile' => ['top' => '12px', 'right' => '12px', 'bottom' => '8px', 'left' => '12px'],
            ],
            'backgroundColor' => $this->responsive('transparent'),
            'zIndex' => $this->responsive('70'),
        ]));
    }

    /** @return array<string, mixed> */
    private function footerSection(bool $withOptin = false): array
    {
        $ctaBanner = $this->row([
            $this->column(12, [
                $this->eyebrowPill('BEGIN YOUR MOVE', 'dark'),
                $this->h2("Ready to Map Your Next\nJurisdiction With <u>Precision</u>?", DesignTokens::ref('on_dark', '#f7f8ee'), 'center'),
                $this->paragraph('Speak directly with a senior case strategist for a clear eligibility opinion, realistic timeline and transparent fixed scope.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '17px', 'center', ['maxWidth' => $this->responsive('56ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['justifyContent' => $this->responsive('center')]),
                    'children' => [
                        $this->ctaButton('Book Strategy Call', '/contact', 'accent', 'arrow-right'),
                        $this->ctaButton('View All Programs', '/programs', 'ghost-dark'),
                    ],
                ],
            ], [
                'alignItems' => $this->responsive('center'),
                'gap' => $this->responsive('20px'),
                'padding' => [
                    'desktop' => ['top' => '68px', 'right' => '40px', 'bottom' => '68px', 'left' => '40px'],
                    'mobile' => ['top' => '42px', 'right' => '22px', 'bottom' => '42px', 'left' => '22px'],
                ],
                'backgroundColor' => $this->responsive('rgba(21, 36, 27, 0.72)'),
                'backgroundImage' => $this->responsive(sprintf('url("%s")', $this->photoUrl('consult_desk', 1800))),
                'backgroundSize' => $this->responsive('cover'),
                'backgroundPosition' => $this->responsive('center'),
                'overlayColor' => $this->responsive('rgba(16, 28, 21, 0.74)'),
                'borderRadius' => $this->responsive(DesignTokens::ref('section_radius', '24px')),
                'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(255,255,255,0.16)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            ]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            'margin' => ['desktop' => ['bottom' => '64px'], 'mobile' => ['bottom' => '42px']],
        ]);

        $footerLink = fn (array $items): array => $this->blockNode(BlockType::Navigation, [
            'items' => $items,
            'orientation' => 'vertical',
            'mobileBehaviour' => 'stack',
        ], [
            'color' => $this->responsive(DesignTokens::ref('on_dark_muted', '#c8d4cc')),
            'fontSize' => $this->responsive('14px'),
            'gap' => $this->responsive('10px'),
        ]);

        $colHeading = fn (string $title): array => $this->paragraph($title, DesignTokens::ref('footer_text', '#f7f8ee'), '15px', 'left', [
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font', 'Parkinsans, sans-serif')),
            'fontWeight' => $this->responsive('600'),
            'margin' => ['desktop' => ['bottom' => '10px']],
        ]);

        $columnsRow = $this->row([
            $this->column(4, [
                $this->blockNode(BlockType::Logo, ['src' => self::LOGO_LIGHT, 'alt' => 'Meridian Advisory', 'url' => '/'], ['height' => ['desktop' => '36px', 'mobile' => '32px']]),
                $this->paragraph('Boutique international mobility, corporate sponsorship and residency advisory serving clients across 42 jurisdictions.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14px', 'left', ['maxWidth' => $this->responsive('34ch')]),
                $this->paragraph(self::ADDRESS."\n".self::PHONE.' · '.self::EMAIL, DesignTokens::ref('footer_text', '#f7f8ee'), '13.5px'),
                $this->blockNode(BlockType::SocialIcons, [
                    'networks' => [
                        ['network' => 'linkedin', 'url' => 'https://linkedin.com'],
                        ['network' => 'instagram', 'url' => 'https://instagram.com'],
                        ['network' => 'youtube', 'url' => 'https://youtube.com'],
                    ],
                    'size' => '16px',
                    'shape' => 'circle',
                ], [
                    'color' => $this->responsive(DesignTokens::ref('accent', '#b98b43')),
                ]),
            ], ['gap' => $this->responsive('14px')]),
            $this->column(2, [
                $colHeading('Practice'),
                $footerLink([
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'About Us', 'url' => '/about'],
                    ['label' => 'Advisory Services', 'url' => '/services'],
                    ['label' => 'Destinations', 'url' => '/destinations'],
                    ['label' => 'Policy Briefings', 'url' => '/insights'],
                    ['label' => 'Book Call', 'url' => '/contact'],
                ]),
            ]),
            $this->column(3, [
                $colHeading('Core Programs'),
                $footerLink([
                    ['label' => 'Executive Work Permits', 'url' => '/programs'],
                    ['label' => 'Founder & Investor Paths', 'url' => '/programs'],
                    ['label' => 'Academic & Study Visas', 'url' => '/programs'],
                    ['label' => 'Long-Stay Residency', 'url' => '/programs'],
                    ['label' => 'Family Reunification', 'url' => '/programs'],
                ]),
            ]),
            $this->column(3, [
                $colHeading('Monthly Policy Digest'),
                $this->paragraph('Concise threshold changes and embassy scheduling notices straight from our regional desks.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '13.5px'),
                $withOptin
                    ? [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '8px', 'meridianRole' => 'footer-optin']),
                        'children' => [
                            $this->blockNode(BlockType::EmailOptin, [
                                'formId' => null,
                                'layout' => 'inline',
                                'label' => 'Work email',
                                'showLabel' => false,
                                'placeholder' => 'Enter your email',
                                'submitLabel' => 'Subscribe',
                            ]),
                        ],
                    ]
                    : $this->ctaButton('Read Policy Briefings', '/insights', 'accent', 'arrow-right'),
            ], ['gap' => $this->responsive('12px')]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            'gap' => ['desktop' => '32px', 'mobile' => '28px'],
        ]);

        $legalRow = $this->row([
            $this->column(12, [
                $this->paragraph('© 2026 Meridian Advisory LLP · Regulated Cross-Border Mobility Counsel · Privacy & Terms', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '13px', 'left', [
                    'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(255,255,255,0.14)', 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                    'padding' => ['desktop' => ['top' => '22px']],
                    'maxWidth' => $this->responsive('none'),
                ]),
            ]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            'margin' => ['desktop' => ['top' => '48px'], 'mobile' => ['top' => '32px']],
        ]);

        return $this->tagSection('footer', $this->section('Site Footer', [
            $ctaBanner,
            $columnsRow,
            $legalRow,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('footer_bg', '#15241b')),
            'color' => $this->responsive(DesignTokens::ref('footer_text', '#f7f8ee')),
            'padding' => [
                'desktop' => ['top' => '72px', 'right' => '24px', 'bottom' => '40px', 'left' => '24px'],
                'mobile' => ['top' => '48px', 'right' => '16px', 'bottom' => '28px', 'left' => '16px'],
            ],
        ]));
    }

    /* ====================================================================== */
    /* LANDING & REUSABLE CONTENT SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function heroSection(bool $withForm = false): array
    {
        $leftCol = [
            $this->eyebrowPill('GLOBAL MOBILITY COUNSEL · LONDON & 6 DESKS', 'dark'),
            $this->h1("Cross-Border Mobility,\nGuided With <u>Clarity</u>", DesignTokens::ref('on_dark', '#f7f8ee'), 'left'),
            $this->paragraph('Meridian coordinates employer-sponsored permits, founder endorsements and family residency filings across forty-two jurisdictions with fixed milestones and accountable case leads.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '18px', 'left', ['maxWidth' => $this->responsive('54ch')]),
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['margin' => ['desktop' => ['top' => '6px']]]),
                'children' => [
                    $this->ctaButton('Book Free Assessment', '/contact', 'accent', 'arrow-right'),
                    $this->ctaButton('Explore Programs', '/programs', 'ghost-dark'),
                ],
            ],
        ];

        $formCard = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'stack',
                'gap' => '14px',
                'meridianRole' => 'hero-form',
            ], [
                'backgroundColor' => $this->responsive('rgba(21, 36, 27, 0.72)'),
                'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(255,255,255,0.2)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => ['desktop' => ['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px'], 'mobile' => ['top' => '22px', 'right' => '18px', 'bottom' => '22px', 'left' => '18px']],
            ]),
            'children' => $withForm
                ? [
                    $this->h4('Quick Route Check', DesignTokens::ref('on_dark', '#f7f8ee')),
                    $this->paragraph('Share your details for a same-week preliminary eligibility note.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14px'),
                    $this->blockNode(BlockType::Form, [
                        'formId' => null,
                        'layout' => 'stacked',
                        'submitLabel' => 'Request Route Opinion ->',
                        'consentText' => 'Reviewed privately by a senior regional case adviser.',
                    ]),
                ]
                : [
                    $this->h4('Quick Route Check', DesignTokens::ref('on_dark', '#f7f8ee')),
                    $this->paragraph('Three concise milestones from scoping to arrival.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14px'),
                    $this->paragraph("01 · Statutory eligibility verification\n02 · Sworn evidence & apostille dossier\n03 · Consular filing & arrival registration", DesignTokens::ref('on_dark', '#f7f8ee'), '14px', 'left', ['lineHeight' => $this->responsive('1.9')]),
                    $this->ctaButton('Request Route Opinion ->', '/contact', 'accent', 'arrow-right'),
                ],
        ];

        return $this->tagSection('hero', $this->section('Hero & Quick Assessment', [
            $this->row([
                $this->column(7, $leftCol, ['gap' => $this->responsive('20px'), 'justifyContent' => $this->responsive('flex-end')]),
                $this->column(5, [$formCard], ['justifyContent' => $this->responsive('flex-end')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '40px', 'mobile' => '28px'],
                'alignItems' => $this->responsive('flex-end'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
            'backgroundImage' => $this->responsive(sprintf('url("%s")', $this->photoUrl('hero_advisory', 1920))),
            'backgroundSize' => $this->responsive('cover'),
            'backgroundPosition' => $this->responsive('center'),
            'minHeight' => ['desktop' => '88vh', 'mobile' => 'auto'],
            'display' => $this->responsive('flex'),
            'alignItems' => $this->responsive('flex-end'),
            'padding' => [
                'desktop' => ['top' => '144px', 'right' => '24px', 'bottom' => '72px', 'left' => '24px'],
                'mobile' => ['top' => '112px', 'right' => '16px', 'bottom' => '48px', 'left' => '16px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function trustSection(): array
    {
        $statCard = fn (string $num, string $value, string $title, string $note): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'stack',
                'gap' => '10px',
                'meridianRole' => 'hover-card',
                'effect' => 'reveal',
            ], [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            ]),
            'children' => [
                $this->paragraph("[{$num}]", DesignTokens::ref('accent', '#b98b43'), '12px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.08em')]),
                $this->h3($value, DesignTokens::ref('heading', '#15241b')),
                $this->paragraph($title, DesignTokens::ref('heading', '#15241b'), '16px', 'left', ['fontWeight' => $this->responsive('600')]),
                $this->paragraph($note, DesignTokens::ref('muted', '#606a64'), '14px'),
            ],
        ];

        $portrait = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
            ]),
            'children' => [
                $this->image('Senior Meridian migration adviser reviewing residency strategy', [
                    'height' => ['desktop' => '460px', 'mobile' => '280px'],
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                ], $this->photoUrl('trust_meeting', 1000)),
            ],
        ];

        $statsGrid = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [6, 6],
                'gap' => '18px',
                'stackBelow' => 'mobile',
            ]),
            'children' => [
                $statCard('01', '98.4%', 'First-Submission Approvals', 'Backed by dual-partner statutory review before every lodgement.'),
                $statCard('02', '42', 'Covered Jurisdictions', 'Direct consular coordination across Europe, North America and APAC.'),
                $statCard('03', '14 yrs', 'Specialized Practice', 'Long-standing compliance track record for founders and employers.'),
                $statCard('04', '3,400+', 'Relocations Completed', 'Professionals, researchers and families settled seamlessly.'),
            ],
        ];

        return $this->tagSection('trust', $this->section('Credentials & Metrics', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('PROVEN TRACK RECORD', 'light'),
                    $this->h2("Accountable Counsel Backed by\nVerifiable Case Outcomes", DesignTokens::ref('heading', '#15241b'), 'left'),
                ], ['gap' => $this->responsive('14px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '28px']],
            ]),
            $this->row([
                $this->column(5, [$portrait]),
                $this->column(7, [$statsGrid]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '28px', 'mobile' => '20px'],
                'alignItems' => $this->responsive('stretch'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function routesTabsSection(): array
    {
        $routes = [
            [
                'tab' => 'Academic & Study',
                'tag' => 'STUDENT & RESEARCH TRACKS',
                'title' => 'University Enrolment & Post-Study Pathways',
                'summary' => 'Coordinated admission documentation, financial Maintenance audits and graduate work extensions across UK, EU, Canadian and Australian campuses.',
                'bullets' => "· Audited CAS, I-20 & CoE compliance checks\n· Sponsor bank balance & scholarship verification\n· Smooth transition into post-study talent permits",
                'horizon' => 'Avg. 3–5 weeks preparation',
                'photo' => 'route_study',
            ],
            [
                'tab' => 'Executive Work',
                'tag' => 'SPONSORED TALENT & TRANSFERS',
                'title' => 'Skilled Worker, EU Blue Card & L-1A Transfers',
                'summary' => 'End-to-end employer sponsorship licensing, salary threshold structuring and senior executive transfer filings with zero HR bottlenecks.',
                'bullets' => "· Sponsor licence audits & allocation petitions\n· Multi-jurisdiction intracompany transfers\n· Accompanying dependant permits filed concurrently",
                'horizon' => 'Avg. 2–4 weeks preparation',
                'photo' => 'route_work',
            ],
            [
                'tab' => 'Investor & Founder',
                'tag' => 'ENTREPRENEUR & INNOVATION',
                'title' => 'Venture Founder & Capital Endorsements',
                'summary' => 'Defensible business cases, endorsing body submissions and capital source traceability for founders relocating leadership headquarters.',
                'bullets' => "· Commercial viability dossiers & financial modelling\n· Endorsement body & O-1A criteria framing\n· Corporate incorporation aligned to immigration milestones",
                'horizon' => 'Avg. 4–6 weeks preparation',
                'photo' => 'route_business',
            ],
            [
                'tab' => 'Global Travel',
                'tag' => 'EXECUTIVE VISIT & SCHENGEN',
                'title' => 'Short-Stay Business & Board Delegation Visas',
                'summary' => 'Priority consular appointment coordination and multi-entry itineraries for frequent board travel, trade missions and client engagements.',
                'bullets' => "· Priority VFS / TLS appointment monitoring\n· Invitation letter & permissible activity scoping\n· Multi-year renewal continuity records",
                'horizon' => 'Avg. 5–10 days preparation',
                'photo' => 'route_travel',
            ],
            [
                'tab' => 'Long-Stay Residency',
                'tag' => 'SETTLEMENT & PASSIVE INCOME',
                'title' => 'Independent Means & Permanent Residence',
                'summary' => 'Structured long-term residency tracks linking continuous lawful residence, physical presence tracking and tax domicile handoffs.',
                'bullets' => "· Day-count compliance & absence audits\n· Language & civic readiness planning\n· Indefinite leave & permanent card renewal",
                'horizon' => 'Avg. 4–8 weeks preparation',
                'photo' => 'route_residency',
            ],
            [
                'tab' => 'Family Unity',
                'tag' => 'SPOUSE & DEPENDANT FILINGS',
                'title' => 'Partner, Children & Household Reunification',
                'summary' => 'Sensitivity and rigorous evidentiary assembly ensuring spouses and dependants travel together or join seamlessly without split approvals.',
                'bullets' => "· Apostilled civil registry & relationship evidence\n· Accommodation & maintenance adequacy proofs\n· Schooling enrollment letter alignment",
                'horizon' => 'Avg. 3–6 weeks preparation',
                'photo' => 'route_family',
            ],
        ];

        $tabHeaders = array_map(fn (array $r): array => ['id' => null, 'label' => $r['tab']], $routes);

        $panels = array_map(fn (array $r): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [6, 6],
                'gap' => '28px',
                'stackBelow' => 'mobile',
            ], [
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px']),
                    'children' => [
                        $this->eyebrowPill($r['tag'], 'light'),
                        $this->h3($r['title'], DesignTokens::ref('heading', '#15241b')),
                        $this->paragraph($r['summary'], DesignTokens::ref('body', '#425349'), '15.5px'),
                        $this->paragraph($r['bullets'], DesignTokens::ref('heading', '#15241b'), '14px', 'left', ['lineHeight' => $this->responsive('1.85')]),
                        $this->paragraph($r['horizon'], DesignTokens::ref('accent', '#b98b43'), '13px', 'left', ['fontWeight' => $this->responsive('600')]),
                        $this->ctaButton('Assess This Route', '/contact', 'primary', 'arrow-right'),
                    ],
                ],
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                        'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    ]),
                    'children' => [
                        $this->image($r['title'], [
                            'height' => ['desktop' => '360px', 'mobile' => '230px'],
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                        ], $this->photoUrl($r['photo'], 1000)),
                    ],
                ],
            ],
        ], $routes);

        $tabsBlock = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'vertical',
                'tabs' => $tabHeaders,
            ], [
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font', 'DM Sans, sans-serif')),
                'gap' => ['desktop' => '28px', 'mobile' => '16px'],
            ]),
            'children' => $panels,
        ];

        return $this->tagSection('routes', $this->section('Interactive Pathway Tabs', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('PATHWAY FINDER', 'light'),
                    $this->h2("Explore Mobility Categories\nby Objective & Horizon", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '42px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $this->column(12, [$tabsBlock]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function aboutOverviewSection(): array
    {
        return $this->tagSection('about-overview', $this->section('Editorial Studio Showcase', [
            $this->row([
                $this->column(7, [
                    $this->eyebrowPill('INSIDE MERIDIAN', 'light'),
                    $this->h2("Built Around Senior Judgment,\nNot Automated Checklists", DesignTokens::ref('heading', '#15241b')),
                ], ['gap' => $this->responsive('12px')]),
                $this->column(5, [
                    $this->paragraph('Immigration outcomes turn on narrative consistency, financial evidence and timing. Every Meridian engagement pairs a dedicated regional solicitor with bilingual document verification.', DesignTokens::ref('body', '#425349'), '16px'),
                    $this->ctaButton('Read Our Practice Story', '/about', 'primary', 'arrow-right'),
                ], ['gap' => $this->responsive('16px'), 'justifyContent' => $this->responsive('flex-end')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '26px']],
                'gap' => ['desktop' => '32px', 'mobile' => '18px'],
            ]),
            $this->row([
                $this->column(12, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom', 'effect' => 'reveal'], [
                            'borderRadius' => $this->responsive(DesignTokens::ref('section_radius', '24px')),
                        ]),
                        'children' => [
                            $this->image('Meridian advisory team strategy briefing across world jurisdictions', [
                                'height' => ['desktop' => '480px', 'mobile' => '240px'],
                                'borderRadius' => $this->responsive(DesignTokens::ref('section_radius', '24px')),
                            ], $this->photoUrl('about_map', 1600)),
                        ],
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function destinationsGallerySection(): array
    {
        $cities = [
            ['photo' => 'dest_sydney', 'city' => 'Sydney, Australia', 'track' => 'SKILLED & EMPLOYER NOMINATION', 'desc' => 'High-demand engineering, tech and executive transfers into New South Wales and Victoria.'],
            ['photo' => 'dest_newyork', 'city' => 'New York, United States', 'track' => 'O-1A TALENT & L-1A EXECUTIVE', 'desc' => 'Extraordinary ability petitions and transatlantic expansion entities for founders and leaders.'],
            ['photo' => 'dest_berlin', 'city' => 'Berlin, Germany', 'track' => 'EU BLUE CARD & OPPORTUNITY CARD', 'desc' => 'Streamlined recognition and specialist work permits into Europe’s largest economy.'],
            ['photo' => 'dest_london', 'city' => 'London, United Kingdom', 'track' => 'GLOBAL TALENT & SKILLED WORKER', 'desc' => 'Home Office sponsor licensing, Innovator Founder endorsements and partner routes.'],
            ['photo' => 'dest_paris', 'city' => 'Paris, France', 'track' => 'PASSEPORT TALENT & TECH', 'desc' => 'Four-year renewable multi-family residence permits for executives and venture builders.'],
            ['photo' => 'dest_toronto', 'city' => 'Toronto, Canada', 'track' => 'EXPRESS ENTRY & START-UP VISA', 'desc' => 'Direct permanent residence pathways and bilateral intra-company work authorizations.'],
        ];

        $buildCards = fn (): array => array_map(fn (array $c): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'meridianRole' => 'hover-card'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#f6f3ee')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '18px', 'right' => '18px', 'bottom' => '22px', 'left' => '18px']),
                'width' => ['desktop' => '340px', 'mobile' => '280px'],
                'custom' => ['flex-shrink' => '0'],
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                        'borderRadius' => $this->responsive('14px'),
                    ]),
                    'children' => [
                        $this->image($c['city'], [
                            'height' => ['desktop' => '210px', 'mobile' => '180px'],
                            'borderRadius' => $this->responsive('14px'),
                        ], $this->photoUrl($c['photo'], 800)),
                    ],
                ],
                $this->paragraph($c['track'], DesignTokens::ref('accent', '#b98b43'), '11.5px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.06em')]),
                $this->h4($c['city'], DesignTokens::ref('heading', '#15241b')),
                $this->paragraph($c['desc'], DesignTokens::ref('body', '#425349'), '14px'),
                $this->ctaButton('View Regional Desk', '/destinations', 'text-dark', 'arrow-right'),
            ],
        ], $cities);

        // Build two distinct card sets with unique ULIDs so CSS translateX(-50%) loops seamlessly.
        $marqueeRow = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'marquee-viewport']),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '22px', 'meridianRole' => 'marquee-track']),
                    'children' => [...$buildCards(), ...$buildCards()],
                ],
            ],
        ];

        return $this->tagSection('destinations-gallery', $this->section('Destinations Section Gallery', [
            $this->row([
                $this->column(8, [
                    $this->eyebrowPill('FEATURED CORRIDORS', 'dark'),
                    $this->h2("Signature Relocation Hubs\nWith Local Desk Coverage", DesignTokens::ref('on_dark', '#f7f8ee')),
                ], ['gap' => $this->responsive('12px')]),
                $this->column(4, [
                    $this->ctaButton('Explore All Destinations', '/destinations', 'accent', 'arrow-right'),
                ], ['justifyContent' => $this->responsive('flex-end'), 'alignItems' => $this->responsive('flex-end')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '42px'], 'mobile' => ['bottom' => '28px']],
            ]),
            $this->row([
                $this->column(12, [$marqueeRow]),
            ], [
                'maxWidth' => $this->responsive('100%'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function whyChooseSection(): array
    {
        $commitment = fn (string $num, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '6px'], [
                'padding' => ['desktop' => ['bottom' => '22px']],
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '0', 'right' => '0', 'bottom' => '1px', 'left' => '0']),
            ]),
            'children' => [
                $this->paragraph($num, DesignTokens::ref('accent', '#b98b43'), '12px', 'left', ['fontWeight' => $this->responsive('600')]),
                $this->h4($title, DesignTokens::ref('heading', '#15241b')),
                $this->paragraph($body, DesignTokens::ref('body', '#425349'), '15px'),
            ],
        ];

        return $this->tagSection('why-us', $this->section('Sticky Commitments Grid', [
            $this->row([
                $this->column(4, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px', 'meridianRole' => 'sticky-col']),
                        'children' => [
                            $this->eyebrowPill('WHY MERIDIAN', 'light'),
                            $this->h2("Discipline at Every\nTouchpoint", DesignTokens::ref('heading', '#15241b')),
                            $this->paragraph('Predictability replaces anxiety when every document, financial threshold and embassy window is checked before lodgement.', DesignTokens::ref('body', '#425349'), '16px'),
                            $this->ctaButton('Inspect Our Standards', '/services', 'primary', 'arrow-right'),
                        ],
                    ],
                ]),
                $this->column(4, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                        ]),
                        'children' => [
                            $this->image('International travel preparation with passport and boarding pass', [
                                'height' => ['desktop' => '520px', 'mobile' => '300px'],
                                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                            ], $this->photoUrl('why_travel', 900)),
                        ],
                    ],
                ]),
                $this->column(4, [
                    $commitment('01 / TRANSPARENCY', 'Fixed Milestone Scoping', 'Clear fee stages and statutory fee breakdowns agreed in writing before a single form is opened.'),
                    $commitment('02 / OVERSIGHT', 'Dual-Adviser File Review', 'Every petition receives secondary sign-off by a regional compliance lead prior to consular submission.'),
                    $commitment('03 / PRECISION', 'Source-of-Funds Structuring', 'Bank narratives, tax transcripts and corporate resolutions harmonized to match embassy scrutiny standards.'),
                    $commitment('04 / CONTINUITY', 'Arrival & Tax Registrations', 'Guidance continues past visa stamp issuance into local municipal registration and renewal calendars.'),
                ], ['gap' => $this->responsive('22px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '32px', 'mobile' => '28px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function servicesDarkSection(): array
    {
        $card = fn (string $icon, string $title, string $summary): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'stack',
                'gap' => '12px',
                'meridianRole' => 'service-card',
                'effect' => 'reveal',
            ], [
                'backgroundColor' => $this->responsive('rgba(247, 248, 238, 0.07)'),
                'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(247, 248, 238, 0.14)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '22px'], ['color' => $this->responsive(DesignTokens::ref('accent', '#b98b43'))]),
                $this->h4($title, DesignTokens::ref('on_dark', '#f7f8ee')),
                $this->paragraph($summary, DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14.5px'),
                $this->ctaButton('Explore Scope', '/services', 'link-light', 'arrow-right'),
            ],
        ];

        $cardsGrid = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [6, 6],
                'gap' => '18px',
                'stackBelow' => 'mobile',
            ]),
            'children' => [
                $card('building-2', 'Employer Sponsorship', 'Sponsor licensing, compliant right-to-work audits and international executive transfers.'),
                $card('shield', 'Refusal & RFE Defence', 'Forensic administrative review and fresh evidentiary submissions after prior refusals.'),
                $card('clipboard-check', 'Document Legalisation', 'Sworn translation, notarisation and Hague apostille coordination across 40+ registries.'),
                $card('globe', 'Family & Settlement', 'Coordinated spouse filings, birth registrations and indefinite stay renewals.'),
            ],
        ];

        return $this->tagSection('services-dark', $this->section('Frosted Advisory Services', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('ADVISORY CAPABILITIES', 'dark'),
                    $this->h2("Integrated Counsel From\nEligibility to Settlement", DesignTokens::ref('on_dark', '#f7f8ee'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '42px'], 'mobile' => ['bottom' => '26px']],
            ]),
            $this->row([
                $this->column(7, [$cardsGrid]),
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                        ]),
                        'children' => [
                            $this->image('Private Meridian legal strategy session', [
                                'height' => ['desktop' => '470px', 'mobile' => '280px'],
                                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                            ], $this->photoUrl('consult_desk', 1000)),
                        ],
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '28px', 'mobile' => '22px'],
                'alignItems' => $this->responsive('stretch'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
            'backgroundImage' => $this->responsive(sprintf('url("%s")', $this->photoUrl('route_business', 1800))),
            'backgroundSize' => $this->responsive('cover'),
            'backgroundPosition' => $this->responsive('center'),
            'overlayColor' => $this->responsive('rgba(21, 36, 27, 0.9)'),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function processSection(): array
    {
        $stepCard = fn (string $num, string $title, string $desc, string $photo): array => $this->column(3, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '22px', 'left' => '16px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                            'borderRadius' => $this->responsive('12px'),
                        ]),
                        'children' => [
                            $this->image($title, ['height' => ['desktop' => '170px', 'mobile' => '190px'], 'borderRadius' => $this->responsive('12px')], $this->photoUrl($photo, 700)),
                        ],
                    ],
                    $this->paragraph("STEP {$num}", DesignTokens::ref('accent', '#b98b43'), '11.5px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.08em')]),
                    $this->h4($title, DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($desc, DesignTokens::ref('body', '#425349'), '14px'),
                ],
            ],
        ]);

        return $this->tagSection('process', $this->section('4-Stage Filing Timeline', [
            $this->row([
                $this->column(6, [
                    $this->eyebrowPill('METHODOLOGY', 'light'),
                    $this->h2("A Four-Stage Cadence Built\nto Eliminate Guesswork", DesignTokens::ref('heading', '#15241b')),
                ], ['gap' => $this->responsive('12px')]),
                $this->column(6, [
                    $this->paragraph('Every dossier moves through verified checkpoints with fixed owner accountability and proactive consular monitoring.', DesignTokens::ref('body', '#425349'), '16px'),
                ], ['justifyContent' => $this->responsive('flex-end')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '34px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $this->column(12, [
                    $this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'process-track'], ['height' => $this->responsive('18px')]),
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '34px'], 'mobile' => ['bottom' => '22px']],
            ]),
            $this->row([
                $stepCard('01', 'Route Scoping & Audit', 'Statutory eligibility assessment, salary floor checks and clear chronology before opening filings.', 'proc_1'),
                $stepCard('02', 'Evidence Assembly', 'Curated statements, sworn translations, apostilles and financial traceability.', 'proc_2'),
                $stepCard('03', 'Consular Lodgement', 'Biometric scheduling, legal cover advocacy and active portal tracking until decision.', 'proc_3'),
                $stepCard('04', 'Arrival & Registration', 'Local residence card pickup, tax identification setup and renewal reminders.', 'proc_4'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#f6f3ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function diagnosticsSection(): array
    {
        $issues = [
            ['code' => 'RISK 01', 'rate' => '64%', 'title' => 'Fragmented Financial Proofs', 'desc' => 'Unexplained lump deposits or holding-period gaps trigger immediate credibility refusals.', 'solve' => 'Pre-filing forensic fund tracing & lender/employer confirmation memos.'],
            ['code' => 'RISK 02', 'rate' => '51%', 'title' => 'Misaligned Role Codes', 'desc' => 'Submitting an occupation code that diverges from daily duties invalidates sponsor petitions.', 'solve' => 'SOC / NOC code mapping directly verified against contract duties.'],
            ['code' => 'RISK 03', 'rate' => '47%', 'title' => 'Lapsed Civil Apostilles', 'desc' => 'Foreign birth or marriage records missing consular legalisation stall dependant grants.', 'solve' => 'Checklist issued week one with accredited Hague apostille runners.'],
            ['code' => 'RISK 04', 'rate' => '39%', 'title' => 'Consular Bottlenecks', 'desc' => 'Booking the wrong jurisdiction center or peak-season queue delays start dates by months.', 'solve' => 'Continuous slot intelligence & lawful priority center routing.'],
            ['code' => 'RISK 05', 'rate' => '34%', 'title' => 'Inconsistent Travel History', 'desc' => 'Minor prior overstays or date mismatches left unaddressed create credibility flags.', 'solve' => 'Full disclosure cover schedules explaining prior stamps transparently.'],
            ['code' => 'RISK 06', 'rate' => '29%', 'title' => 'Sponsor Duty Gaps', 'desc' => 'Unreported address, role or shareholder shifts lead to compliance suspension.', 'solve' => 'Dedicated HR compliance tracker covering 10-day reporting windows.'],
        ];

        $cards = array_map(fn (array $i): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'meridianRole' => 'hover-card'], [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '10px'], ['justifyContent' => $this->responsive('space-between'), 'alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->paragraph($i['code'], DesignTokens::ref('muted', '#606a64'), '11.5px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.08em')]),
                        $this->paragraph($i['rate'].' of unguided RFE causes', DesignTokens::ref('accent', '#b98b43'), '12px', 'right', ['fontWeight' => $this->responsive('600')]),
                    ],
                ],
                $this->h4($i['title'], DesignTokens::ref('heading', '#15241b')),
                $this->paragraph($i['desc'], DesignTokens::ref('body', '#425349'), '14.5px'),
                $this->paragraph('MERIDIAN REMEDY: '.$i['solve'], DesignTokens::ref('heading', '#15241b'), '13px', 'left', [
                    'padding' => ['desktop' => ['top' => '12px']],
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                    'fontWeight' => $this->responsive('500'),
                ]),
            ],
        ], $issues);

        return $this->tagSection('diagnostics', $this->section('Common Hurdles & Remedies', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('PREVENTATIVE RIGOUR', 'light'),
                    $this->h2("Why Applications Stall —\nand How We Neutralise Every Risk", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '42px'], 'mobile' => ['bottom' => '26px']],
            ]),
            $this->row([
                $this->column(12, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '20px', 'stackBelow' => 'tablet']),
                        'children' => array_slice($cards, 0, 3),
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '20px']],
            ]),
            $this->row([
                $this->column(12, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '20px', 'stackBelow' => 'tablet']),
                        'children' => array_slice($cards, 3, 3),
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function storiesSection(): array
    {
        $storyCard = fn (string $photo, string $quote, string $name, string $role, string $route, string $window): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px', 'meridianRole' => 'hover-card'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '26px', 'right' => '26px', 'bottom' => '26px', 'left' => '26px']),
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [3, 9], 'gap' => '16px', 'stackBelow' => 'none'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->image($name, ['width' => $this->responsive('64px'), 'height' => $this->responsive('64px'), 'borderRadius' => $this->responsive('999px')], $this->photoUrl($photo, 240, 240)),
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '2px']),
                            'children' => [
                                $this->h4($name, DesignTokens::ref('heading', '#15241b')),
                                $this->paragraph($role, DesignTokens::ref('muted', '#606a64'), '13.5px'),
                            ],
                        ],
                    ],
                ],
                $this->paragraph('"'.$quote.'"', DesignTokens::ref('heading', '#15241b'), '17px', 'left', ['lineHeight' => $this->responsive('1.65')]),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '12px', 'stackBelow' => 'mobile'], [
                        'padding' => ['desktop' => ['top' => '14px']],
                        'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                    ]),
                    'children' => [
                        $this->paragraph("PATHWAY\n".$route, DesignTokens::ref('body', '#425349'), '12.5px'),
                        $this->paragraph("TURNAROUND\n".$window, DesignTokens::ref('accent', '#b98b43'), '12.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                    ],
                ],
            ],
        ];

        return $this->tagSection('stories', $this->section('Sticky Client Stories', [
            $this->row([
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px', 'meridianRole' => 'sticky-col']),
                        'children' => [
                            $this->eyebrowPill('CLIENT OUTCOMES', 'dark'),
                            $this->h2("Mandates Approved\nAcross Complex Profiles", DesignTokens::ref('on_dark', '#f7f8ee')),
                            $this->paragraph('Inspect how Meridian structured corporate transfers, extraordinary-ability talent petitions and multi-generational moves.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '16px'),
                            $this->ctaButton('Discuss Your Mandate', '/contact', 'accent', 'arrow-right'),
                        ],
                    ],
                ]),
                $this->column(7, [
                    $storyCard('story_1', 'Meridian rebuilt our entire endorsement brief in eleven days after an unguided rejection. Our London headquarters opened on schedule without missing a board milestone.', 'Nadia Al-Mansoor', 'Co-Founder, Kestrel BioCompute', 'UK Innovator Founder', '16 Business Days'),
                    $storyCard('story_2', 'Coordinating my L-1A executive transfer alongside schooling visas for three dependants felt daunting until Daniel mapped every apostille and consulate slot.', 'Henrik Lindholm', 'VP Engineering, Nordik Grid', 'US L-1A Intracompany', '4 Weeks End-to-End'),
                    $storyCard('story_3', 'Zero template handoffs. Their Berlin desk checked my diploma recognition and employment addendum line by line so the Blue Card approved first attempt.', 'Priya Subramanian', 'Principal Systems Architect', 'Germany EU Blue Card', '19 Calendar Days'),
                ], ['gap' => $this->responsive('20px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '40px', 'mobile' => '26px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function liveDeskSection(): array
    {
        $leadCard = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->eyebrowPill('ACTIVE CASE DESK', 'dark'),
                $this->image('Daniel Okafor, Managing Partner at Meridian Advisory', [
                    'width' => $this->responsive('84px'),
                    'height' => $this->responsive('84px'),
                    'borderRadius' => $this->responsive('999px'),
                ], $this->photoUrl('lead_adviser', 280, 280)),
                $this->h3('Daniel Okafor', DesignTokens::ref('on_dark', '#f7f8ee')),
                $this->paragraph('Managing Partner · UK & EU Desk Lead', DesignTokens::ref('accent', '#b98b43'), '13.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                $this->paragraph('Overseeing complex founder endorsements, sponsor compliance audits and dual-jurisdiction filings.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14.5px'),
                $this->ctaButton('Consult With Partner Desk', '/contact', 'accent', 'arrow-right'),
            ],
        ];

        $tickerItem = fn (string $avatar, string $initials, string $route, string $stage): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#f6f3ee')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive('16px'),
                'padding' => $this->responsive(['top' => '16px', 'right' => '18px', 'bottom' => '16px', 'left' => '18px']),
                'width' => ['desktop' => '280px', 'mobile' => '240px'],
                'custom' => ['flex-shrink' => '0'],
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [3, 9], 'gap' => '10px', 'stackBelow' => 'none'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->image($initials, ['width' => $this->responsive('38px'), 'height' => $this->responsive('38px'), 'borderRadius' => $this->responsive('999px')], $this->photoUrl($avatar, 160, 160)),
                        $this->paragraph($initials."\n".$route, DesignTokens::ref('heading', '#15241b'), '13px', 'left', ['fontWeight' => $this->responsive('600')]),
                    ],
                ],
                $this->paragraph($stage, DesignTokens::ref('accent', '#b98b43'), '12px', 'left', ['fontWeight' => $this->responsive('600')]),
            ],
        ];

        $rowOne = fn (): array => [
            $tickerItem('adviser_2', 'File #8412 · Sofia V.', 'Germany EU Blue Card', 'Approved · Residence Card Issued'),
            $tickerItem('adviser_3', 'File #8419 · Marcus T.', 'UK Skilled Worker + 2', 'Biometrics Verified · Awaiting Decision'),
            $tickerItem('adviser_4', 'File #8425 · Mei-Ling C.', 'Canada Start-Up Visa', 'Peer Review Complete · Filed'),
        ];

        $rowTwo = fn (): array => [
            $tickerItem('story_2', 'File #8431 · Eduardo R.', 'France Passeport Talent', 'Prefecture Stamp Confirmed'),
            $tickerItem('story_1', 'File #8438 · Amara K.', 'Australia 482 Transfer', 'Nomination Granted · 12 Days'),
            $tickerItem('story_3', 'File #8444 · Lukas W.', 'US O-1A Advisory', 'Petition Package Dispatched'),
        ];

        return $this->tagSection('live-desk', $this->section('Live Advisory Caseload', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('BEHIND THE WORK', 'light'),
                    $this->h2("Active Case Progress Across\nRegional Legal Desks", DesignTokens::ref('heading', '#15241b')),
                ], ['gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '36px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $this->column(4, [$leadCard]),
                $this->column(8, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'marquee-viewport']),
                        'children' => [
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '16px', 'meridianRole' => 'marquee-track']),
                                'children' => [...$rowOne(), ...$rowOne()],
                            ],
                        ],
                    ],
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'marquee-viewport']),
                        'children' => [
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '16px', 'meridianRole' => 'marquee-reverse']),
                                'children' => [...$rowTwo(), ...$rowTwo()],
                            ],
                        ],
                    ],
                ], ['gap' => $this->responsive('18px'), 'justifyContent' => $this->responsive('center')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '28px', 'mobile' => '22px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function faqSection(): array
    {
        $ctaPhotoBox = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
                'backgroundImage' => $this->responsive(sprintf('url("%s")', $this->photoUrl('office_hero', 900))),
                'backgroundSize' => $this->responsive('cover'),
                'overlayColor' => $this->responsive('rgba(21, 36, 27, 0.78)'),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
            ]),
            'children' => [
                $this->h4('Still Weighing Multiple Options?', DesignTokens::ref('on_dark', '#f7f8ee')),
                $this->paragraph('Book a 30-minute scoping review with a senior case adviser to compare timelines across two target countries.', DesignTokens::ref('on_dark_muted', '#c8d4cc'), '14.5px'),
                $this->ctaButton('Speak With Counsel', '/contact', 'accent', 'arrow-right'),
            ],
        ];

        $faq = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'faq-list']),
            'children' => [
                $this->blockNode(BlockType::Faq, [
                    'initialOpen' => true,
                    'allowMultipleOpen' => false,
                    'emitSchemaOrg' => true,
                    'items' => [
                        ['question' => 'How early before relocating should we engage Meridian?', 'answer' => 'Ideally 8 to 14 weeks prior to your intended start date. This leaves buffer for apostilles, employer sponsor allocations and consular biometrics slots without rush fees.'],
                        ['question' => 'Do you work with corporate HR teams or individual applicants?', 'answer' => 'Both. Around half of our practice supports founders and senior individuals directly, while the remainder acts as an embedded mobility desk for scaling technology and engineering companies.'],
                        ['question' => 'Can spouses and children be filed in the same dossier cycle?', 'answer' => 'Yes. Where statutory rules allow concurrent filing, we synchronize principal and dependant evidence—including school placements and marriage legalisations—so households travel together.'],
                        ['question' => 'What happens if an application previously received a refusal?', 'answer' => 'We conduct a written refusal audit first to establish whether an administrative review or a restructured re-application with fresh documentary evidence offers the strongest path.'],
                        ['question' => 'Are legal fees fixed per stage rather than open hourly retainers?', 'answer' => 'Every route comes with a fixed written milestone fee agreed before work begins, separated transparently from mandatory government and biometric tariffs.'],
                        ['question' => 'How does your team manage document translation and apostilles?', 'answer' => 'We coordinate certified translations and Hague Convention apostilles across forty-two civil registries so documents meet exact consular format on first submission.'],
                    ],
                ]),
            ],
        ];

        return $this->tagSection('faq', $this->section('FAQ Accordion & Callout', [
            $this->row([
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px', 'meridianRole' => 'sticky-col']),
                        'children' => [
                            $this->eyebrowPill('FREQUENT QUESTIONS', 'light'),
                            $this->h2("Straight Answers on\nFilings, Timelines & Fees", DesignTokens::ref('heading', '#15241b')),
                            $ctaPhotoBox,
                        ],
                    ],
                ]),
                $this->column(7, [$faq]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '36px', 'mobile' => '24px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function insightsSection(): array
    {
        $posts = [
            ['photo' => 'blog_1', 'meta' => 'POLICY BRIEF · 5 MIN', 'title' => '2026 Salary Threshold Revisions Across UK & EU Talent Permits', 'excerpt' => 'How revised wage floors reshape sponsor planning for scaling technology teams.'],
            ['photo' => 'blog_2', 'meta' => 'RESIDENCY · 6 MIN', 'title' => 'Coordinating Day-Count Compliance Ahead of Permanent Settlement', 'excerpt' => 'Practical travel-log habits that keep five-year residency clocks intact.'],
            ['photo' => 'blog_3', 'meta' => 'FOUNDERS · 4 MIN', 'title' => 'What Endorsement Bodies Look For in Cross-Border Commercial Plans', 'excerpt' => 'Moving beyond generic pitch decks to verifiable revenue and IP traction.'],
            ['photo' => 'blog_4', 'meta' => 'FAMILIES · 5 MIN', 'title' => 'Synchronizing Dependant Biometrics & International School Enrollment', 'excerpt' => 'Avoiding seasonal embassy backlogs ahead of the September academic intake.'],
        ];

        $cards = array_map(fn (array $p): array => $this->blogCard($p['photo'], $p['meta'], $p['title'], $p['excerpt'], 3), $posts);

        return $this->tagSection('insights', $this->section('Insights & Briefings Grid', [
            $this->row([
                $this->column(8, [
                    $this->eyebrowPill('INSIGHTS & UPDATES', 'light'),
                    $this->h2("Briefings From Our\nRegional Advisory Desks", DesignTokens::ref('heading', '#15241b')),
                ], ['gap' => $this->responsive('12px')]),
                $this->column(4, [
                    $this->ctaButton('View All Briefings', '/insights', 'primary', 'arrow-right'),
                ], ['justifyContent' => $this->responsive('flex-end'), 'alignItems' => $this->responsive('flex-end')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '38px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row($cards, [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /* ====================================================================== */
    /* INNER PAGE REUSABLE SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function pageHeroSection(string $eyebrow, string $heading, string $subtitle, string $photoKey): array
    {
        return $this->tagSection('hero', $this->section('Inner Page Banner', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill($eyebrow, 'dark'),
                    $this->h1($heading, DesignTokens::ref('on_dark', '#f7f8ee'), 'center'),
                    $this->paragraph($subtitle, DesignTokens::ref('on_dark_muted', '#c8d4cc'), '18px', 'center', ['maxWidth' => $this->responsive('60ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
                ], [
                    'alignItems' => $this->responsive('center'),
                    'gap' => $this->responsive('18px'),
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('dark', '#15241b')),
            'backgroundImage' => $this->responsive(sprintf('url("%s")', $this->photoUrl($photoKey, 1800))),
            'backgroundSize' => $this->responsive('cover'),
            'backgroundPosition' => $this->responsive('center'),
            'padding' => [
                'desktop' => ['top' => '164px', 'right' => '24px', 'bottom' => '84px', 'left' => '24px'],
                'mobile' => ['top' => '124px', 'right' => '16px', 'bottom' => '54px', 'left' => '16px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function aboutCharterSection(): array
    {
        $pillar = fn (string $num, string $title, string $text): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->paragraph($num, DesignTokens::ref('accent', '#b98b43'), '12px', 'left', ['fontWeight' => $this->responsive('600')]),
                    $this->h3($title, DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($text, DesignTokens::ref('body', '#425349'), '15px'),
                ],
            ],
        ]);

        return $this->tagSection('charter', $this->section('Practice Charter', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('OUR CHARTER', 'light'),
                    $this->h2("Principled Representation\nWithout Volume Shortcuts", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '36px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $pillar('01 · CANDOUR', 'Plain-Spoken Feasibility', 'If a pathway carries elevated statutory risk, we say so during initial scoping rather than mid-filing.'),
                $pillar('02 · RIGOUR', 'Zero Boilerplate Briefs', 'Every employer letter, endorsement pitch and family statement is authored fresh around primary evidence.'),
                $pillar('03 · SECURITY', 'Confidential File Custody', 'ISO-aligned encrypted workspaces preserve sensitive identity, corporate cap-table and tax records.'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '22px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#f6f3ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function teamLeadershipSection(): array
    {
        $member = fn (string $photo, string $name, string $title, string $desk): array => $this->column(3, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '20px', 'left' => '16px']),
                ]),
                'children' => [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], [
                            'borderRadius' => $this->responsive('14px'),
                        ]),
                        'children' => [
                            $this->image($name, ['height' => ['desktop' => '240px', 'mobile' => '220px'], 'borderRadius' => $this->responsive('14px')], $this->photoUrl($photo, 700, 700)),
                        ],
                    ],
                    $this->paragraph($desk, DesignTokens::ref('accent', '#b98b43'), '11.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                    $this->h4($name, DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($title, DesignTokens::ref('body', '#425349'), '14px'),
                ],
            ],
        ]);

        return $this->tagSection('team', $this->section('Senior Counsel Directory', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('LEADERSHIP', 'light'),
                    $this->h2("Senior Case Strategists\nLeading Every Practice Desk", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '38px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $member('lead_adviser', 'Daniel Okafor', 'Managing Partner · Sponsorship & Corporate', 'LONDON DESK'),
                $member('adviser_2', 'Clara Lindqvist', 'Director · EU Blue Card & DACH', 'BERLIN DESK'),
                $member('adviser_3', 'Tariq Mansour', 'Senior Counsel · North America', 'TORONTO & NYC'),
                $member('adviser_4', 'Hana Kobayashi', 'Partner · Founder & APAC Talent', 'SYDNEY DESK'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function serviceTiersSection(): array
    {
        $tier = fn (string $badge, string $name, string $scope, string $list, bool $featured = false): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive($featured ? DesignTokens::ref('dark', '#15241b') : '#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => $featured ? DesignTokens::ref('accent', '#b98b43') : DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '30px', 'right' => '28px', 'bottom' => '30px', 'left' => '28px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->paragraph($badge, DesignTokens::ref('accent', '#b98b43'), '12px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.08em')]),
                    $this->h3($name, $featured ? DesignTokens::ref('on_dark', '#f7f8ee') : DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($scope, $featured ? DesignTokens::ref('on_dark_muted', '#c8d4cc') : DesignTokens::ref('body', '#425349'), '15px'),
                    $this->paragraph($list, $featured ? DesignTokens::ref('on_dark', '#f7f8ee') : DesignTokens::ref('heading', '#15241b'), '14px', 'left', ['lineHeight' => $this->responsive('1.9')]),
                    $this->ctaButton('Request Scope Brief', '/contact', $featured ? 'accent' : 'primary', 'arrow-right'),
                ],
            ],
        ]);

        return $this->tagSection('service-tiers', $this->section('Engagement Scopes', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('ENGAGEMENT MODELS', 'light'),
                    $this->h2("Transparent Retainers Built\nAround Case Complexity", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '38px'], 'mobile' => ['bottom' => '24px']],
            ]),
            $this->row([
                $tier('SOLO APPLICANT', 'Single Filing Mandate', 'Full preparation for one principal applicant pursuing study, travel or work authorization.', "✓ Route eligibility audit\n✓ Document checklist & drafting\n✓ Portal lodgement & tracking"),
                $tier('MOST REQUESTED', 'Executive & Family Desk', 'Coordinated principal, spouse and dependant filings plus local arrival tax & settlement checklist.', "✓ Dual-partner case review\n✓ Sworn translations & apostilles\n✓ Priority consular monitoring", true),
                $tier('CORPORATE HR', 'Enterprise Sponsor Retainer', 'Embedded immigration desk for companies sponsoring recurring hires across Europe & UK.', "✓ Sponsor licence audits\n✓ Unlimited HR query SLA\n✓ Renewal & compliance radar"),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '22px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function programsCatalogSection(): array
    {
        $items = [
            ['photo' => 'route_work', 'tag' => 'SPONSORED TALENT', 'title' => 'Skilled Worker & Blue Cards', 'desc' => 'Employer-led employment permits across the UK, Germany, Netherlands and Nordics.', 'eta' => '2–4 weeks filing window'],
            ['photo' => 'route_business', 'tag' => 'FOUNDERS & VENTURE', 'title' => 'Innovator, Start-Up & O-1A', 'desc' => 'High-growth commercial endorsements for relocating founders and research leads.', 'eta' => '4–6 weeks filing window'],
            ['photo' => 'route_residency', 'tag' => 'SETTLEMENT & CAPITAL', 'title' => 'Residency & Permanent Stay', 'desc' => 'Multi-year residency permits aligned with physical presence and domicile planning.', 'eta' => '4–8 weeks filing window'],
            ['photo' => 'route_study', 'tag' => 'UNIVERSITIES & MBA', 'title' => 'Academic & Graduate Visas', 'desc' => 'Tuition & financial maintenance audits for international degree candidates.', 'eta' => '3–5 weeks filing window'],
            ['photo' => 'route_family', 'tag' => 'SPOUSE & DEPENDANTS', 'title' => 'Family Reunification Tracks', 'desc' => 'Relationship dossiers, civil apostilles and dependent school enrollment coordination.', 'eta' => '3–6 weeks filing window'],
            ['photo' => 'route_travel', 'tag' => 'BOARD & TRADE VISITS', 'title' => 'Short-Stay Business Multi-Entry', 'desc' => 'Streamlined consular bookings for executives attending boards and client sites.', 'eta' => '5–10 days filing window'],
        ];

        $cards = array_map(fn (array $item): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '22px', 'left' => '16px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], ['borderRadius' => $this->responsive('14px')]),
                        'children' => [$this->image($item['title'], ['height' => ['desktop' => '190px', 'mobile' => '190px'], 'borderRadius' => $this->responsive('14px')], $this->photoUrl($item['photo'], 800))],
                    ],
                    $this->paragraph($item['tag'], DesignTokens::ref('accent', '#b98b43'), '11.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                    $this->h4($item['title'], DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($item['desc'], DesignTokens::ref('body', '#425349'), '14px'),
                    $this->paragraph($item['eta'], DesignTokens::ref('muted', '#606a64'), '12.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                    $this->ctaButton('Book Route Assessment', '/contact', 'text-dark', 'arrow-right'),
                ],
            ],
        ]), $items);

        return $this->tagSection('programs-catalog', $this->section('All Mobility Programs Grid', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('FULL CATALOGUE', 'light'),
                    $this->h2("Compare All Six Advisory\nMobility Streams", DesignTokens::ref('heading', '#15241b'), 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '36px']],
            ]),
            $this->row(array_slice($cards, 0, 3), [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
                'margin' => ['desktop' => ['bottom' => '20px']],
            ]),
            $this->row(array_slice($cards, 3, 3), [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function destinationsDirectorySection(): array
    {
        $galleryImages = [
            ['src' => $this->photoUrl('dest_london', 900), 'alt' => 'London Financial District', 'caption' => 'London · Bishopsgate Desk'],
            ['src' => $this->photoUrl('dest_berlin', 900), 'alt' => 'Berlin Mitte Architecture', 'caption' => 'Berlin · EU Blue Card Desk'],
            ['src' => $this->photoUrl('dest_toronto', 900), 'alt' => 'Toronto Waterfront Skyline', 'caption' => 'Toronto · Express Entry Desk'],
            ['src' => $this->photoUrl('dest_sydney', 900), 'alt' => 'Sydney Harbour Panorama', 'caption' => 'Sydney · APAC Mobility Desk'],
            ['src' => $this->photoUrl('dest_newyork', 900), 'alt' => 'New York Manhattan Skyline', 'caption' => 'New York · L-1A & O-1A Desk'],
            ['src' => $this->photoUrl('dest_paris', 900), 'alt' => 'Paris Riverfront Districts', 'caption' => 'Paris · Passeport Talent Desk'],
        ];

        return $this->tagSection('destinations-directory', $this->section('Destinations Showcase Gallery Grid', [
            $this->row([
                $this->column(12, [
                    $this->eyebrowPill('CITY GALLERY', 'light'),
                    $this->h2("Panoramic Corridor Gallery &\nLocal Legal Hubs", DesignTokens::ref('heading', '#15241b'), 'center'),
                    $this->paragraph('Click any destination frame to inspect full-scale gallery view or book directly with the corresponding city desk.', DesignTokens::ref('body', '#425349'), '16px', 'center', ['maxWidth' => $this->responsive('56ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('12px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'margin' => ['desktop' => ['bottom' => '36px']],
            ]),
            $this->row([
                $this->column(12, [
                    $this->blockNode(BlockType::Gallery, [
                        'images' => $galleryImages,
                        'columns' => '3',
                        'gap' => '18px',
                        'lightbox' => true,
                    ], [
                        'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    ]),
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function articlesArchiveSection(): array
    {
        $featured = $this->row([
            $this->column(6, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], ['borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px'))]),
                    'children' => [$this->image('European and UK immigration reform report', ['height' => ['desktop' => '360px', 'mobile' => '220px'], 'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px'))], $this->photoUrl('proc_2', 1100))],
                ],
            ]),
            $this->column(6, [
                $this->eyebrowPill('FEATURED BRIEFING · 7 MIN READ', 'light'),
                $this->h3('Navigating 2026 Cross-Border Sponsor & Tax Alignment', DesignTokens::ref('heading', '#15241b')),
                $this->paragraph('A practical checklist for chief people officers and founders sequencing corporate entity setup before filing executive transfer visas.', DesignTokens::ref('body', '#425349'), '15.5px'),
                $this->ctaButton('Request Full Whitepaper', '/contact', 'primary', 'arrow-right'),
            ], ['gap' => $this->responsive('16px'), 'justifyContent' => $this->responsive('center')]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
            'backgroundColor' => $this->responsive('#ffffff'),
            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'borderRadius' => $this->responsive(DesignTokens::ref('section_radius', '24px')),
            'padding' => ['desktop' => ['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px'], 'mobile' => ['top' => '18px', 'right' => '18px', 'bottom' => '22px', 'left' => '18px']],
            'margin' => ['desktop' => ['bottom' => '38px'], 'mobile' => ['bottom' => '26px']],
            'gap' => ['desktop' => '32px', 'mobile' => '18px'],
        ]);

        $posts = [
            ['photo' => 'blog_1', 'meta' => 'POLICY BRIEF · 5 MIN', 'title' => '2026 Salary Threshold Revisions Across UK & EU Talent Permits', 'excerpt' => 'How revised wage floors reshape sponsor planning for scaling technology teams.'],
            ['photo' => 'blog_2', 'meta' => 'RESIDENCY · 6 MIN', 'title' => 'Coordinating Day-Count Compliance Ahead of Permanent Settlement', 'excerpt' => 'Practical travel-log habits that keep five-year residency clocks intact.'],
            ['photo' => 'blog_3', 'meta' => 'FOUNDERS · 4 MIN', 'title' => 'What Endorsement Bodies Look For in Commercial Business Plans', 'excerpt' => 'Moving beyond generic pitch decks to verifiable revenue and IP traction.'],
            ['photo' => 'blog_4', 'meta' => 'FAMILIES · 5 MIN', 'title' => 'Synchronizing Dependant Biometrics & School Enrollment Dates', 'excerpt' => 'Avoiding seasonal embassy backlogs ahead of the September academic intake.'],
            ['photo' => 'proc_3', 'meta' => 'COMPLIANCE · 4 MIN', 'title' => 'Sponsor Duty Audits: Ten-Day Notification Best Practices', 'excerpt' => 'Protecting sponsor ratings when executives change reporting lines or offices.'],
            ['photo' => 'dest_berlin', 'meta' => 'EUROPE · 5 MIN', 'title' => 'Germany Chancery Card vs. EU Blue Card: Choosing Wisely', 'excerpt' => 'Side-by-side criteria for engineers holding degree equivalents versus offers.'],
        ];

        $cards = array_map(fn (array $p): array => $this->blogCard($p['photo'], $p['meta'], $p['title'], $p['excerpt'], 4), $posts);

        return $this->tagSection('articles-archive', $this->section('Policy Briefings Archive', [
            $featured,
            $this->row(array_slice($cards, 0, 3), [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
                'margin' => ['desktop' => ['bottom' => '20px']],
            ]),
            $this->row(array_slice($cards, 3, 3), [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '20px', 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#f7f8ee')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function contactSplitSection(bool $withForm = false): array
    {
        $intakeBox = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px', 'meridianRole' => 'contact-form'], [
                'backgroundColor' => $this->responsive('#ffffff'),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('section_radius', '24px')),
                'padding' => $this->responsive(['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px']),
            ]),
            'children' => $withForm
                ? [
                    $this->eyebrowPill('CASE INTAKE', 'light'),
                    $this->h3('Tell Us About Your Target Move', DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph('A regional partner responds within one business day with recommended routes and indicative timing.', DesignTokens::ref('body', '#425349'), '15px'),
                    $this->blockNode(BlockType::Form, [
                        'formId' => null,
                        'layout' => 'stacked',
                        'submitLabel' => 'Send Case Inquiry ->',
                        'consentText' => 'Confidential and covered by preliminary advisory privilege.',
                    ]),
                ]
                : [
                    $this->eyebrowPill('CASE INTAKE', 'light'),
                    $this->h3('Tell Us About Your Target Move', DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph('A regional partner responds within one business day with recommended routes and indicative timing.', DesignTokens::ref('body', '#425349'), '15px'),
                    $this->image('Meridian consultation room at Bishopsgate', ['height' => ['desktop' => '210px', 'mobile' => '170px'], 'borderRadius' => $this->responsive('14px')], $this->photoUrl('proc_1', 900)),
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px']),
                        'children' => [
                            $this->ctaButton('Email Regional Desk', 'mailto:'.self::EMAIL, 'primary', 'arrow-right'),
                            $this->ctaButton('Call London Desk', 'tel:'.preg_replace('/[^\d+]/', '', self::PHONE), 'text-dark'),
                        ],
                    ],
                ],
        ];

        $deskCard = fn (string $icon, string $label, string $line1, string $line2): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '8px'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface', '#f6f3ee')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                'padding' => $this->responsive(['top' => '22px', 'right' => '22px', 'bottom' => '22px', 'left' => '22px']),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '20px'], ['color' => $this->responsive(DesignTokens::ref('accent', '#b98b43'))]),
                $this->h4($label, DesignTokens::ref('heading', '#15241b')),
                $this->paragraph($line1."\n".$line2, DesignTokens::ref('body', '#425349'), '14.5px'),
            ],
        ];

        return $this->tagSection('contact-split', $this->section('Consultation Form & Office Map', [
            $this->row([
                $this->column(7, [$intakeBox]),
                $this->column(5, [
                    $deskCard('map-pin', 'London Headquarters', self::ADDRESS, 'Monday – Friday · 08:30 – 18:30 GMT'),
                    $deskCard('phone', 'Direct Desk Line', self::PHONE, self::EMAIL),
                    $this->blockNode(BlockType::Map, [
                        'address' => self::ADDRESS,
                        'zoom' => 14,
                        'height' => '220px',
                    ], [
                        'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    ]),
                ], ['gap' => $this->responsive('18px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1280px')),
                'gap' => ['desktop' => '30px', 'mobile' => '22px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('primary_soft', '#e9f0ec')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /* ====================================================================== */
    /* REUSABLE PRIMITIVE HELPERS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function blogCard(string $photo, string $meta, string $title, string $excerpt, int $span): array
    {
        return $this->column($span, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px', 'meridianRole' => 'hover-card'], [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '20px')),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '22px', 'left' => '16px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'meridianRole' => 'photo-zoom'], ['borderRadius' => $this->responsive('14px')]),
                        'children' => [$this->image($title, ['height' => ['desktop' => '190px', 'mobile' => '190px'], 'borderRadius' => $this->responsive('14px')], $this->photoUrl($photo, 800))],
                    ],
                    $this->paragraph($meta, DesignTokens::ref('accent', '#b98b43'), '11.5px', 'left', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('0.06em')]),
                    $this->h4($title, DesignTokens::ref('heading', '#15241b')),
                    $this->paragraph($excerpt, DesignTokens::ref('body', '#425349'), '14px'),
                    $this->ctaButton('Read Briefing', '/insights', 'text-dark', 'arrow-right'),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eyebrowPill(string $label, string $surface): array
    {
        $isDark = $surface === 'dark';

        return $this->paragraph('●  '.$label, $isDark ? DesignTokens::ref('on_dark', '#f7f8ee') : DesignTokens::ref('heading', '#15241b'), '11.5px', 'left', [
            'fontWeight' => $this->responsive('600'),
            'letterSpacing' => $this->responsive('0.08em'),
            'backgroundColor' => $this->responsive($isDark ? 'rgba(255, 255, 255, 0.1)' : '#ffffff'),
            'border' => $this->responsive(['style' => 'solid', 'color' => $isDark ? 'rgba(255,255,255,0.2)' : DesignTokens::ref('border', '#cdd6d0'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'borderRadius' => $this->responsive('999px'),
            'padding' => $this->responsive(['top' => '6px', 'right' => '14px', 'bottom' => '6px', 'left' => '14px']),
            'width' => $this->responsive('fit-content'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h1(string $text, string $color, string $align = 'left', array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h1', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font', 'Parkinsans, sans-serif')),
            'color' => $this->responsive($color),
            'fontSize' => ['desktop' => DesignTokens::ref('h1_size', '58px'), 'mobile' => '34px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight', '600')),
            'lineHeight' => $this->responsive(DesignTokens::ref('heading_line_height', '1.08')),
            'letterSpacing' => ['desktop' => DesignTokens::ref('heading_tracking', '-0.025em'), 'mobile' => '-0.02em'],
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h2(string $text, string $color, string $align = 'left', array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h2', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font', 'Parkinsans, sans-serif')),
            'color' => $this->responsive($color),
            'fontSize' => ['desktop' => DesignTokens::ref('h2_size', '44px'), 'mobile' => '29px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight', '600')),
            'lineHeight' => $this->responsive('1.15'),
            'letterSpacing' => ['desktop' => '-0.02em', 'mobile' => '-0.015em'],
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h3(string $text, string $color, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h3', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font', 'Parkinsans, sans-serif')),
            'color' => $this->responsive($color),
            'fontSize' => ['desktop' => DesignTokens::ref('h3_size', '30px'), 'mobile' => '23px'],
            'fontWeight' => $this->responsive('600'),
            'lineHeight' => $this->responsive('1.22'),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h4(string $text, string $color, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h4', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font', 'Parkinsans, sans-serif')),
            'color' => $this->responsive($color),
            'fontSize' => ['desktop' => DesignTokens::ref('h4_size', '21px'), 'mobile' => '18px'],
            'fontWeight' => $this->responsive('600'),
            'lineHeight' => $this->responsive('1.3'),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function paragraph(string $text, string $color, string $size = '16px', string $align = 'left', array $style = []): array
    {
        return $this->blockNode(BlockType::Paragraph, ['text' => $text], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('body_font', 'DM Sans, sans-serif')),
            'color' => $this->responsive($color),
            'fontSize' => $this->responsive($size),
            'lineHeight' => $this->responsive('1.65'),
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /** @return array<string, mixed> */
    private function ctaButton(string $label, string $url, string $variant, ?string $iconRight = null): array
    {
        $styleMap = match ($variant) {
            'accent' => [
                'backgroundColor' => $this->responsive(DesignTokens::ref('accent', '#b98b43')),
                'color' => $this->responsive('#15241b'),
                'borderRadius' => $this->responsive(DesignTokens::ref('button_radius', '12px')),
                'padding' => $this->responsive(['top' => '14px', 'right' => '22px', 'bottom' => '14px', 'left' => '22px']),
                'fontWeight' => $this->responsive('600'),
            ],
            'light' => [
                'backgroundColor' => $this->responsive('#ffffff'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#15241b')),
                'borderRadius' => $this->responsive(DesignTokens::ref('button_radius', '12px')),
                'padding' => $this->responsive(['top' => '11px', 'right' => '18px', 'bottom' => '11px', 'left' => '18px']),
                'fontWeight' => $this->responsive('600'),
            ],
            'ghost-dark' => [
                'backgroundColor' => $this->responsive('rgba(255,255,255,0.08)'),
                'color' => $this->responsive(DesignTokens::ref('on_dark', '#f7f8ee')),
                'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(255,255,255,0.28)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('button_radius', '12px')),
                'padding' => $this->responsive(['top' => '13px', 'right' => '22px', 'bottom' => '13px', 'left' => '22px']),
                'fontWeight' => $this->responsive('600'),
            ],
            'link-light' => [
                'backgroundColor' => $this->responsive('transparent'),
                'color' => $this->responsive(DesignTokens::ref('accent', '#b98b43')),
                'padding' => $this->responsive('0'),
                'fontWeight' => $this->responsive('600'),
            ],
            'text-dark' => [
                'backgroundColor' => $this->responsive('transparent'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#15241b')),
                'padding' => $this->responsive('0'),
                'fontWeight' => $this->responsive('600'),
            ],
            default => [
                'backgroundColor' => $this->responsive(DesignTokens::ref('primary', '#15241b')),
                'color' => $this->responsive(DesignTokens::ref('on_dark', '#f7f8ee')),
                'borderRadius' => $this->responsive(DesignTokens::ref('button_radius', '12px')),
                'padding' => $this->responsive(['top' => '14px', 'right' => '22px', 'bottom' => '14px', 'left' => '22px']),
                'fontWeight' => $this->responsive('600'),
            ],
        };

        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'targetStepId' => null,
            'targetBlockId' => null,
            'newTab' => false,
            'iconLeft' => null,
            'iconRight' => $iconRight,
            'fullWidth' => false,
            'size' => 'md',
            'variant' => $variant === 'ghost-dark' ? 'outline' : 'primary',
        ], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('body_font', 'DM Sans, sans-serif')),
            'fontSize' => $this->responsive('14.5px'),
        ], $styleMap));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function image(string $alt, array $style, string $src): array
    {
        return $this->blockNode(BlockType::Image, [
            'src' => $src,
            'alt' => $alt,
            'caption' => null,
            'aspectRatio' => 'auto',
            'objectFit' => 'cover',
        ], array_merge(['width' => $this->responsive('100%')], $style));
    }

    private function photoUrl(string $key, int $width = 1200, ?int $height = null): string
    {
        $id = self::PHOTOS[$key];
        $base = "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$width}&q=80";

        return $height !== null ? $base."&h={$height}" : $base;
    }

    /** @return array<string, array<string, string>> */
    private function sectionPadding(): array
    {
        return [
            'desktop' => ['top' => DesignTokens::ref('section_padding', '92px'), 'right' => '24px', 'bottom' => DesignTokens::ref('section_padding', '92px'), 'left' => '24px'],
            'mobile' => ['top' => '58px', 'right' => '16px', 'bottom' => '58px', 'left' => '16px'],
        ];
    }

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
        return [
            'schema_version' => 1,
            'settings' => [],
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function tagSection(string $block, array $section): array
    {
        $section['settings'] = ['designCollection' => self::COLLECTION, 'designBlock' => $block];

        return $section;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function section(string $name, array $rows, array $style = [], bool $sticky = false): array
    {
        $isSticky = (bool) ($style['sticky'] ?? $sticky);
        unset($style['sticky']);

        return [
            'id' => $this->nextId(),
            'name' => $name,
            'width' => 'full',
            'sticky' => $isSticky,
            'style' => array_merge([
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font', 'DM Sans, sans-serif')),
                'color' => $this->responsive(DesignTokens::ref('heading', '#15241b')),
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
                'gap' => ['desktop' => '24px', 'mobile' => '16px'],
            ], $style),
            'columns' => $columns,
        ];
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
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function blockNode(BlockType $type, array $props = [], array $style = []): array
    {
        $defaults = BlockRegistry::defaultProps($type);

        return [
            'id' => $this->nextId(),
            'type' => $type->value,
            'props' => array_merge($defaults, $props),
            'style' => $style,
        ];
    }

    /**
     * @return array<string, mixed>
     */
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

        return '01J9MRD000'.$suffix;
    }
}
