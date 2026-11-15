<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\TemplatePresentation;
use App\Cms\Themes\DesignTokens;

/**
 * Reusable design blocks and multi-page templates for the Sable Systems theme (`modrix/sable`).
 *
 * Faithful to warm editorial architectural minimalism: crisp vertical frame rails,
 * `+` crosshair junctions, Geist display & UI typography, two-tone hero headings,
 * floating translucent header capsule with 4-column mega dropdown, interactive 3-step
 * workflow switcher, 4-card asymmetric bento grid, FAQ accordion and giant cropped
 * bottom footer watermark.
 *
 * Every section reads Theme Settings (`--site-*`) and Customize options (`--sable-*`) live.
 */
final class SableDesigns
{
    public const COLLECTION = 'sable';

    private const ASSETS = '/templates/sable/';

    private const BRAND = 'Sable';

    private const FULL_BRAND = 'Sable Systems';

    private const EMAIL = 'hello@sablesystems.ai';

    private const PHONE = '+1 (415) 890-4210';

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
                'sable-header',
                'Sable · Capsule Header & Dropdown',
                'structure',
                'Sticky light header with logo left, frosted center capsule navigation with a 4-column Pages dropdown, and dark Start a project CTA pill.',
                [$this->headerSection()],
            ),
            $this->design(
                'sable-footer',
                'Sable · Architectural Footer & Watermark',
                'structure',
                'Framed 6-column editorial footer with brand details, 5 structured link groups, and a giant bottom-cropped Sable watermark.',
                [$this->footerSection()],
            ),
            $this->design(
                'sable-hero',
                'Sable · Two-Tone Hero & Chat Preview',
                'hero',
                'Split hero featuring two-tone Geist headline, two checklist highlights, an interactive warm gradient AI chat card, and a monochrome trust strip.',
                [$this->heroSection()],
            ),
            $this->design(
                'sable-services',
                'Sable · Four-Card Service Strip',
                'content',
                'Split heading row with soft pill link above four sand-toned capability cards with Lucide icons.',
                [$this->servicesSection()],
            ),
            $this->design(
                'sable-process',
                'Sable · Interactive How We Work Switcher',
                'content',
                'Interactive three-step Discovery, Build, and Deploy selector synced with an animated progress preview card.',
                [$this->processSection()],
            ),
            $this->design(
                'sable-stats',
                'Sable · Four-Column Impact Metrics',
                'social_proof',
                'Architectural horizontal band displaying four concise headline metrics with bottom crosshair framing.',
                [$this->statsSection()],
            ),
            $this->design(
                'sable-cases',
                'Sable · Two-Up Case Studies',
                'social_proof',
                'Two side-by-side gradient case study frames with centered white brand badges and outcome summaries.',
                [$this->caseStudiesSection()],
            ),
            $this->design(
                'sable-testimonial',
                'Sable · Portrait Testimonial Split',
                'social_proof',
                'Editorial studio portrait paired with a high-contrast client quote, name and executive title.',
                [$this->testimonialSection()],
            ),
            $this->design(
                'sable-bento',
                'Sable · Four-Card Asymmetric Bento Grid',
                'content',
                'Asymmetric 7/5 and 5/7 bento grid combining dark warm gradient action cards with sand benchmark charts.',
                [$this->bentoSection()],
            ),
            $this->design(
                'sable-faq',
                'Sable · Editorial FAQ Accordion',
                'structure',
                'Centered heading above a structured FAQ block with Schema.org support.',
                [$this->faqSection()],
            ),
            $this->design(
                'sable-articles',
                'Sable · Three-Column Latest Articles',
                'content',
                'Three editorial research article cards with warm visual thumbnails, dates and Read more links.',
                [$this->articlesSection()],
            ),
            $this->design(
                'sable-cta-band',
                'Sable · Dual-Pill Call to Action',
                'conversion',
                'Framed pre-footer split CTA band with View all services and Start a project pill buttons.',
                [$this->ctaBandSection()],
            ),
            $this->design(
                'sable-pricing',
                'Sable · Three-Tier Engagement Models',
                'commerce',
                'Clear sprint, production deployment and embedded AI partner plans.',
                [$this->pricingSection()],
            ),
            $this->design(
                'sable-contact',
                'Sable · Centered Project Intake Card',
                'conversion',
                'Warm sand-surfaced centered project inquiry form card paired with direct studio channels.',
                [$this->contactSection()],
            ),
        ];
    }

    /**
     * Complete page templates (with header and footer) available in the Visual Builder.
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
     * Body-only demo pages imported by `theme.json`.
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
                'key' => 'sable-home',
                'name' => 'Sable · Home',
                'slug' => 'home',
                'category' => 'ai_agency',
                'description' => 'Full Sable Systems studio landing page: two-tone hero, 4-card services, process switcher, stats, case studies, testimonial, bento grid, FAQ, articles and CTA.',
                'sections' => fn (): array => [
                    $this->heroSection(),
                    $this->servicesSection(),
                    $this->processSection(),
                    $this->statsSection(),
                    $this->caseStudiesSection(),
                    $this->testimonialSection(),
                    $this->bentoSection(),
                    $this->faqSection(),
                    $this->articlesSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-about',
                'name' => 'Sable · About',
                'slug' => 'about',
                'category' => 'ai_agency',
                'description' => 'Company story, engineering principles, studio team roster, impact stats and client endorsement.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'ABOUT SABLE',
                        'Built for teams who ship<br><em>reliable automation</em>',
                        'We pair applied machine learning engineers with product designers to replace manual operational bottlenecks with testable software.',
                    ),
                    $this->aboutStorySection(),
                    $this->statsSection(),
                    $this->aboutTeamSection(),
                    $this->testimonialSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-services',
                'name' => 'Sable · Services',
                'slug' => 'services',
                'category' => 'ai_agency',
                'description' => 'Complete AI engineering capabilities, 4-card asymmetric bento system view, delivery workflow and CTA.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'CAPABILITIES',
                        'Focused AI engineering<br><em>from prototype to prod</em>',
                        'Every engagement ships versioned prompts, deterministic evals, clean API bridges and team handover documentation.',
                    ),
                    $this->servicesSection(),
                    $this->bentoSection(),
                    $this->processSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-case-studies',
                'name' => 'Sable · Case Studies',
                'slug' => 'case-studies',
                'category' => 'ai_agency',
                'description' => 'Client deployment outcomes across logistics, financial compliance and ecommerce support.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'CASE STUDIES',
                        'Production systems with<br><em>measured ROI</em>',
                        'How operations teams cut ticket queues, accelerate underwriting triage and eliminate brittle spreadsheet handoffs.',
                    ),
                    $this->caseStudiesSection(includeThird: true),
                    $this->statsSection(),
                    $this->testimonialSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-pricing',
                'name' => 'Sable · Pricing',
                'slug' => 'pricing',
                'category' => 'ai_agency',
                'description' => 'Transparent Discovery Sprint, Production System and Embedded Partner engagement models with FAQ.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'PRICING',
                        'Transparent scope with<br><em>fixed weekly cadence</em>',
                        'Start with a two-week Discovery Sprint or move straight to a production agent rollout with clear deliverables.',
                    ),
                    $this->pricingSection(),
                    $this->faqSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-blog',
                'name' => 'Sable · Journal',
                'slug' => 'blog',
                'category' => 'ai_agency',
                'description' => 'Field notes on retrieval evaluation, deterministic routing guardrails and production latency engineering.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'JOURNAL',
                        'Field notes on<br><em>applied AI systems</em>',
                        'Engineering essays, evaluation patterns and architectural postmortems from live enterprise rollouts.',
                    ),
                    $this->articlesSection(),
                    $this->bentoSection(),
                    $this->ctaBandSection(),
                ],
            ],
            [
                'key' => 'sable-contact',
                'name' => 'Sable · Contact',
                'slug' => 'contact',
                'category' => 'ai_agency',
                'description' => 'Project intake form card with direct partner email, discovery call scheduling and FAQs.',
                'sections' => fn (): array => [
                    $this->innerHeroSection(
                        'START A PROJECT',
                        'Tell us what you need<br><em>automated first</em>',
                        'Share your current workflow bottlenecks and target timeline. An engineering partner replies within one business day.',
                    ),
                    $this->contactSection(),
                    $this->faqSection(),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function headerSection(): array
    {
        $logo = $this->blockNode(BlockType::Logo, [
            'src' => self::ASSETS.'logo.svg',
            'alt' => self::FULL_BRAND,
            'text' => self::BRAND,
            'link' => '/',
            'width' => '124px',
        ], [
            'maxWidth' => $this->responsive('124px'),
        ]);

        $navigation = $this->blockNode(BlockType::Navigation, [
            'items' => [
                [
                    'label' => 'Pages',
                    'url' => '/',
                    'children' => [
                        ['label' => 'Home', 'url' => '/', 'description' => 'Company · Overview', 'icon' => 'home'],
                        ['label' => 'About', 'url' => '/about', 'description' => 'Company · Team', 'icon' => 'users'],
                        ['label' => 'Services', 'url' => '/services', 'description' => 'Resources · Capabilities', 'icon' => 'workflow'],
                        ['label' => 'Case studies', 'url' => '/case-studies', 'description' => 'Resources · Outcomes', 'icon' => 'layers'],
                        ['label' => 'Pricing', 'url' => '/pricing', 'description' => 'Resources · Sprints', 'icon' => 'calculator'],
                        ['label' => 'Blog', 'url' => '/blog', 'description' => 'Resources · Articles', 'icon' => 'book-open'],
                        ['label' => 'Contact', 'url' => '/contact', 'description' => 'Reach Out · Brief', 'icon' => 'mail'],
                        ['label' => 'FAQ & Support', 'url' => '/contact#faq', 'description' => 'Legal · Direct Desk', 'icon' => 'shield-check'],
                    ],
                ],
                ['label' => 'About', 'url' => '/about'],
                ['label' => 'Services', 'url' => '/services'],
                ['label' => 'Blog', 'url' => '/blog'],
            ],
            'orientation' => 'horizontal',
            'mobileBehaviour' => 'drawer',
        ]);

        $capsule = [
            ...$this->container('row', '0px', 'nav-capsule', [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [$navigation],
        ];

        $cta = $this->primaryPill('Start a project', '/contact');

        return $this->tagSection('header', $this->section('Header · Sable Capsule', [
            $this->row([
                $this->column(3, [$logo], ['justifyContent' => $this->responsive('center')]),
                $this->column(6, [$capsule], ['justifyContent' => $this->responsive('center'), 'alignItems' => $this->responsive('center')]),
                $this->column(3, [$cta], ['justifyContent' => $this->responsive('center'), 'alignItems' => ['desktop' => 'flex-end', 'mobile' => 'flex-start']]),
            ], $this->railRow(['alignItems' => $this->responsive('center'), 'gap' => ['desktop' => '16px', 'mobile' => '12px']]), 'tablet'),
        ], [
            'sticky' => true,
            'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg', '#fdfcfc')),
            'padding' => [
                'desktop' => ['top' => '14px', 'bottom' => '14px', 'left' => '24px', 'right' => '24px'],
                'mobile' => ['top' => '12px', 'bottom' => '12px', 'left' => '16px', 'right' => '16px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function footerSection(): array
    {
        $brandCol = $this->column(4, [
            $this->blockNode(BlockType::Logo, [
                'src' => self::ASSETS.'logo.svg',
                'alt' => self::FULL_BRAND,
                'text' => self::BRAND,
                'link' => '/',
                'width' => '120px',
            ], ['maxWidth' => $this->responsive('120px')]),
            $this->paragraph('Practical AI agents, internal automations and custom ML models shipped for revenue and operations teams.', DesignTokens::ref('muted', '#716b63'), '14px', 'left', [
                'maxWidth' => $this->responsive('260px'),
                'lineHeight' => $this->responsive('1.55'),
            ]),
            $this->paragraph('© 2026 '.self::FULL_BRAND.' · '.self::EMAIL, DesignTokens::ref('muted', '#716b63'), '13px'),
        ], ['gap' => $this->responsive('14px')]);

        $colGroup = fn (string $heading, array $items): array => [
            ...$this->container('stack', '10px'),
            'children' => [
                $this->paragraph($heading, DesignTokens::ref('heading', '#131314'), '13.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                $this->verticalFooterNav($items),
            ],
        ];

        $linksGrid = [
            ...$this->container('columns', '20px', null, [
                'columns' => [1, 1, 1, 1, 1],
                'stackBelow' => 'tablet',
            ]),
            'children' => [
                $colGroup('Company', [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'About', 'url' => '/about'],
                    ['label' => 'Pricing', 'url' => '/pricing'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ]),
                $colGroup('Resources', [
                    ['label' => 'Blog', 'url' => '/blog'],
                    ['label' => 'Case studies', 'url' => '/case-studies'],
                    ['label' => 'FAQ', 'url' => '/contact#faq'],
                ]),
                $colGroup('Services', [
                    ['label' => 'AI Agents', 'url' => '/services'],
                    ['label' => 'Automation', 'url' => '/services#process'],
                    ['label' => 'Fine-tuning', 'url' => '/services#bento'],
                    ['label' => 'Advisory', 'url' => '/services'],
                ]),
                $colGroup('Social', [
                    ['label' => 'LinkedIn', 'url' => '/contact'],
                    ['label' => 'X / Twitter', 'url' => '/contact'],
                    ['label' => 'GitHub', 'url' => '/contact'],
                ]),
                $colGroup('Legal', [
                    ['label' => 'Privacy Policy', 'url' => '/contact#faq'],
                    ['label' => 'Terms of Use', 'url' => '/contact#faq'],
                    ['label' => 'Security', 'url' => '/about'],
                ]),
            ],
        ];

        $watermark = [
            ...$this->container('stack', '0px', 'footer-watermark'),
            'children' => [
                $this->image(self::ASSETS.'footer-watermark.svg', 'Sable editorial wordmark', [
                    'width' => $this->responsive('100%'),
                ]),
            ],
        ];

        return $this->tagSection('footer', $this->section('Footer · Sable Editorial', [
            $this->row([$brandCol, $this->column(8, [$linksGrid])], $this->railRow(['gap' => ['desktop' => '36px', 'mobile' => '28px']]), 'tablet'),
            $this->row([$this->column(12, [$watermark])], $this->railRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('footer_bg', '#fdfcfc')),
            'padding' => [
                'desktop' => ['top' => '64px', 'bottom' => '0px', 'left' => '28px', 'right' => '28px'],
                'mobile' => ['top' => '44px', 'bottom' => '0px', 'left' => '18px', 'right' => '18px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function heroSection(): array
    {
        $checkItem = fn (string $label): array => [
            ...$this->container('row', '10px', null, ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '16px'], ['color' => $this->responsive('var(--sable-accent, var(--site-accent, #eb812c))')]),
                $this->paragraph($label, DesignTokens::ref('heading', '#131314'), '14.5px', 'left', ['fontWeight' => $this->responsive('500')]),
            ],
        ];

        $leftCol = $this->column(6, [
            $this->h1('<em>Get better leads</em><br>using AI systems', [
                'fontSize' => ['desktop' => '48px', 'tablet' => '40px', 'mobile' => '34px'],
                'fontWeight' => $this->responsive('500'),
                'lineHeight' => $this->responsive('1.05'),
                'letterSpacing' => $this->responsive('-2.2px'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
            ]),
            $this->paragraph('Custom autonomous agents and workflow automations designed to qualify inbound demand and eliminate manual back-office friction.', DesignTokens::ref('muted', '#716b63'), '17px', 'left', [
                'lineHeight' => $this->responsive('1.55'),
                'maxWidth' => $this->responsive('460px'),
            ]),
            [
                ...$this->container('row', '12px', null, ['alignItems' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '6px', 'bottom' => '6px'])]),
                'children' => [
                    $this->primaryPill('Start a project', '/contact'),
                    $this->softPill('View services', '/services'),
                ],
            ],
            [
                ...$this->container('stack', '8px', null, ['margin' => $this->responsive(['top' => '12px'])]),
                'children' => [
                    $checkItem('Personalized custom AI agents'),
                    $checkItem('Streamlined workflow automation'),
                ],
            ],
        ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('16px')]);

        $agentMsg = fn (string $text): array => [
            ...$this->container('stack', '0px', null, [
                'backgroundColor' => $this->responsive('rgba(253, 252, 252, 0.94)'),
                'borderRadius' => $this->responsive('14px'),
                'padding' => $this->responsive(['top' => '13px', 'bottom' => '13px', 'left' => '16px', 'right' => '16px']),
                'maxWidth' => $this->responsive('82%'),
            ]),
            'children' => [
                $this->paragraph($text, '#131314', '14px', 'left', ['lineHeight' => $this->responsive('1.45')]),
            ],
        ];

        $userMsg = fn (string $text): array => [
            ...$this->container('stack', '0px', null, [
                'backgroundColor' => $this->responsive('rgba(255, 255, 255, 0.2)'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => 'rgba(255, 255, 255, 0.28)']),
                'borderRadius' => $this->responsive('14px'),
                'padding' => $this->responsive(['top' => '13px', 'bottom' => '13px', 'left' => '16px', 'right' => '16px']),
                'margin' => $this->responsive(['left' => 'auto']),
                'maxWidth' => $this->responsive('78%'),
            ]),
            'children' => [
                $this->paragraph($text, '#fdfcfc', '14px', 'left', ['lineHeight' => $this->responsive('1.45')]),
            ],
        ];

        $composer = [
            ...$this->container('row', '10px', 'hero-chat-input', [
                'backgroundColor' => $this->responsive('rgba(19, 19, 20, 0.48)'),
                'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => 'rgba(255, 255, 255, 0.24)']),
                'borderRadius' => $this->responsive('999px'),
                'padding' => $this->responsive(['top' => '10px', 'bottom' => '10px', 'left' => '18px', 'right' => '10px']),
                'justifyContent' => $this->responsive('space-between'),
                'alignItems' => $this->responsive('center'),
                'margin' => $this->responsive(['top' => '18px']),
            ]),
            'children' => [
                $this->paragraph('Type a message...', 'rgba(253, 252, 252, 0.78)', '13.5px'),
                $this->blockNode(BlockType::Icon, ['name' => 'arrow-right', 'size' => '16px'], [
                    'backgroundColor' => $this->responsive('#fdfcfc'),
                    'color' => $this->responsive('#131314'),
                    'borderRadius' => $this->responsive('999px'),
                    'padding' => $this->responsive(['top' => '6px', 'bottom' => '6px', 'left' => '6px', 'right' => '6px']),
                ]),
            ],
        ];

        $rightCard = [
            ...$this->container('stack', '12px', 'hero-chat-stage', [
                'padding' => $this->responsive(['top' => '28px', 'bottom' => '24px', 'left' => '24px', 'right' => '24px']),
                'minHeight' => ['desktop' => '410px', 'mobile' => '340px'],
                'justifyContent' => $this->responsive('space-between'),
            ]),
            'children' => [
                [
                    ...$this->container('stack', '12px'),
                    'children' => [
                        $agentMsg('Morning! 18 inbound RFPs arrived overnight. I scored 6 high-fit accounts and drafted custom scopes.'),
                        $userMsg('Sync the top 3 into HubSpot and book discovery calls with Elena.'),
                        $agentMsg('Done — CRM records enriched, calendar slots sent, and Slack alerts posted to #enterprise-deals.'),
                    ],
                ],
                $composer,
            ],
        ];

        $partnerMark = fn (string $label): array => $this->paragraph($label, '#8c857d', '15px', 'center', [
            'fontWeight' => $this->responsive('600'),
            'letterSpacing' => $this->responsive('-0.02em'),
        ]);

        $trustBar = $this->row([
            $this->column(4, [
                $this->paragraph('Trusted by leading developers and brands', DesignTokens::ref('muted', '#716b63'), '13.5px'),
            ], ['justifyContent' => $this->responsive('center')]),
            $this->column(8, [[
                ...$this->container('columns', '16px', null, ['columns' => [1, 1, 1, 1, 1], 'alignItems' => $this->responsive('center')]),
                'children' => [
                    $partnerMark('MODULUS'),
                    $partnerMark('Northgate'),
                    $partnerMark('KESTREL'),
                    $partnerMark('verge.io'),
                    $partnerMark('Alder AI'),
                ],
            ]], ['justifyContent' => $this->responsive('center')]),
        ], $this->railRow([
            'alignItems' => $this->responsive('center'),
            'margin' => ['desktop' => ['top' => '52px'], 'mobile' => ['top' => '36px']],
            'padding' => $this->responsive(['top' => '28px']),
            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border', '#edebe9'), 'top' => '1px']),
        ]), 'tablet');

        return $this->tagSection('hero', $this->section('Hero · Sable Studio', [
            $this->row([
                $leftCol,
                $this->column(6, [$rightCard]),
            ], $this->railRow(['gap' => ['desktop' => '40px', 'mobile' => '28px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
            $trustBar,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => [
                'desktop' => ['top' => '64px', 'bottom' => '42px', 'left' => '28px', 'right' => '28px'],
                'mobile' => ['top' => '36px', 'bottom' => '28px', 'left' => '18px', 'right' => '18px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function innerHeroSection(string $eyebrow, string $titleHtml, string $subtitle): array
    {
        return $this->tagSection('inner-hero', $this->section('Inner Hero · Sable', [
            $this->row([
                $this->column(10, [
                    $this->paragraph($eyebrow, 'var(--sable-accent, var(--site-accent, #eb812c))', '12.5px', 'left', [
                        'fontWeight' => $this->responsive('600'),
                        'letterSpacing' => $this->responsive('0.08em'),
                    ]),
                    $this->h1($titleHtml, [
                        'fontSize' => ['desktop' => '48px', 'tablet' => '38px', 'mobile' => '32px'],
                        'fontWeight' => $this->responsive('500'),
                        'lineHeight' => $this->responsive('1.06'),
                        'letterSpacing' => $this->responsive('-2.2px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    ]),
                    $this->paragraph($subtitle, DesignTokens::ref('muted', '#716b63'), '17px', 'left', [
                        'lineHeight' => $this->responsive('1.55'),
                        'maxWidth' => $this->responsive('620px'),
                    ]),
                ], ['gap' => $this->responsive('14px')]),
            ], $this->railRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => [
                'desktop' => ['top' => '68px', 'bottom' => '56px', 'left' => '28px', 'right' => '28px'],
                'mobile' => ['top' => '42px', 'bottom' => '36px', 'left' => '18px', 'right' => '18px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function servicesSection(): array
    {
        $card = fn (string $icon, string $title, string $body): array => $this->column(3, [[
            ...$this->container('stack', '14px', 'service-card', [
                'backgroundColor' => $this->responsive('var(--sable-surface, var(--site-surface, #f5f3f1))'),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius', '16px')),
                'padding' => $this->responsive(['top' => '26px', 'bottom' => '26px', 'left' => '24px', 'right' => '24px']),
                'height' => $this->responsive('100%'),
                'justifyContent' => $this->responsive('space-between'),
            ]),
            'children' => [
                [
                    ...$this->container('stack', '12px'),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '22px'], [
                            'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                        ]),
                        $this->h4($title, [
                            'fontSize' => $this->responsive('19px'),
                            'fontWeight' => $this->responsive('500'),
                            'letterSpacing' => $this->responsive('-0.4px'),
                            'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                        ]),
                    ],
                ],
                $this->paragraph($body, DesignTokens::ref('muted', '#716b63'), '14.5px', 'left', ['lineHeight' => $this->responsive('1.55')]),
            ],
        ]]);

        $header = $this->row([
            $this->column(8, [
                $this->h2('What we deliver', [
                    'fontSize' => ['desktop' => '32px', 'mobile' => '28px'],
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-1.2px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                ]),
                $this->paragraph('Practical systems that connect models directly to your existing stack.', DesignTokens::ref('muted', '#716b63'), '16px'),
            ], ['gap' => $this->responsive('8px')]),
            $this->column(4, [$this->softPill('View all services', '/services')], [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => ['desktop' => 'flex-end', 'mobile' => 'flex-start'],
            ]),
        ], $this->railRow(['alignItems' => $this->responsive('center'), 'margin' => $this->responsive(['bottom' => '28px'])]), 'tablet');

        return $this->tagSection('services', $this->section('Services · Four Capabilities', [
            $header,
            $this->row([
                $card('sparkle', 'Intelligent Agents', 'Autonomous triage, support and research workers wired directly into your APIs.'),
                $card('workflow', 'Process Automation', 'Reliable ETL pipelines, webhook bridges and CRM enrichment with zero brittle spreadsheets.'),
                $card('layers', 'Machine Learning', 'Domain fine-tuning, structured classification and eval harnesses tuned for speed.'),
                $card('chart-no-axes-combined', 'Strategic Advisory', 'Clear build-vs-buy roadmaps, model cost audits and team enablement.'),
            ], $this->railRow(['gap' => ['desktop' => '16px', 'mobile' => '14px'], 'alignItems' => $this->responsive('stretch')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function processSection(): array
    {
        $stepItem = fn (string $numTitle, string $copy, bool $active = false): array => [
            ...$this->container('stack', '6px', $active ? 'process-step is-active' : 'process-step', [
                'padding' => $this->responsive(['top' => '20px', 'bottom' => '20px', 'left' => '20px', 'right' => '16px']),
            ]),
            'children' => [
                $this->h4($numTitle, [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('500'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                ]),
                $this->paragraph($copy, DesignTokens::ref('muted', '#716b63'), '14.5px', 'left', ['lineHeight' => $this->responsive('1.5')]),
            ],
        ];

        $panelCard = function (string $title, string $weekBadge, array $checks, int $pct): array {
            $checkLine = fn (string $item): array => [
                ...$this->container('row', '10px', null, ['alignItems' => $this->responsive('center')]),
                'children' => [
                    $this->blockNode(BlockType::Icon, ['name' => 'circle-check', 'size' => '16px'], ['color' => $this->responsive('var(--sable-accent, var(--site-accent, #eb812c))')]),
                    $this->paragraph($item, DesignTokens::ref('heading', '#131314'), '14px', 'left', ['fontWeight' => $this->responsive('500')]),
                ],
            ];

            return [
                ...$this->container('stack', '18px', 'process-panel', [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#edebe9')]),
                    'borderRadius' => $this->responsive('16px'),
                    'padding' => $this->responsive(['top' => '28px', 'bottom' => '28px', 'left' => '28px', 'right' => '28px']),
                    'width' => $this->responsive('100%'),
                ]),
                'children' => [
                    [
                        ...$this->container('row', '12px', null, ['justifyContent' => $this->responsive('space-between'), 'alignItems' => $this->responsive('center')]),
                        'children' => [
                            $this->h4($title, ['fontSize' => $this->responsive('20px'), 'fontWeight' => $this->responsive('500'), 'color' => $this->responsive('#131314')]),
                            $this->paragraph($weekBadge, 'var(--sable-accent, var(--site-accent, #eb812c))', '12.5px', 'center', [
                                'fontWeight' => $this->responsive('600'),
                                'backgroundColor' => $this->responsive('rgba(235, 129, 44, 0.12)'),
                                'padding' => $this->responsive(['top' => '4px', 'bottom' => '4px', 'left' => '12px', 'right' => '12px']),
                                'borderRadius' => $this->responsive('999px'),
                            ]),
                        ],
                    ],
                    [
                        ...$this->container('stack', '12px'),
                        'children' => array_map($checkLine, $checks),
                    ],
                    $this->blockNode(BlockType::ProgressBar, [
                        'value' => $pct,
                        'max' => 100,
                        'label' => 'Automated coverage',
                        'showPercentage' => true,
                    ], ['accentColor' => $this->responsive('#131314')]),
                ],
            ];
        };

        $leftSteps = $this->column(5, [[
            ...$this->container('stack', '4px'),
            'children' => [
                $stepItem('1. Discovery', 'We shadow your team, inspect edge cases and quantify manual time spent before writing a line of code.', true),
                $stepItem('2. Build', 'Two-week build sprints wire models, tool definitions and deterministic evals into your staging stack.'),
                $stepItem('3. Deploy & Support', 'Shadow traffic checks, SLA alerting and ongoing prompt tuning keep reliability above 99%.'),
            ],
        ]]);

        $rightStage = $this->column(7, [[
            ...$this->container('stack', '14px', null, [
                'backgroundColor' => $this->responsive('var(--sable-surface, var(--site-surface, #f5f3f1))'),
                'borderRadius' => $this->responsive('18px'),
                'padding' => ['desktop' => ['top' => '32px', 'bottom' => '32px', 'left' => '32px', 'right' => '32px'], 'mobile' => ['top' => '20px', 'bottom' => '20px', 'left' => '20px', 'right' => '20px']],
                'justifyContent' => $this->responsive('center'),
            ]),
            'children' => [
                $panelCard('Discovery call', 'Week 1', [
                    'Scope highest-ROI manual workflows',
                    'Audit internal webhooks, schemas & CRM fields',
                    'Define strict human escalation guardrails',
                ], 25),
                $panelCard('System architecture', 'Weeks 2–4', [
                    'Connect semantic index & tool schemas',
                    'Execute automated red-team regression suite',
                    'Tune inference latency & fallback routing',
                ], 65),
                $panelCard('Launch & support', 'Week 5+', [
                    'Canary validation against historical tickets',
                    'Real-time cost & guardrail telemetry alerts',
                    'Full repository & runbook ownership handover',
                ], 100),
            ],
        ]]);

        return $this->tagSection('process', $this->section('Process · How We Work', [
            $this->row([
                $this->column(12, [
                    $this->h2('How we work?', [
                        'fontSize' => ['desktop' => '34px', 'mobile' => '28px'],
                        'fontWeight' => $this->responsive('500'),
                        'letterSpacing' => $this->responsive('-1.2px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                        'textAlign' => $this->responsive('center'),
                    ]),
                    $this->paragraph('Click any phase to inspect what ships during that week.', DesignTokens::ref('muted', '#716b63'), '16px', 'center'),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('8px')]),
            ], $this->railRow(['margin' => $this->responsive(['bottom' => '36px'])])),
            $this->row([$leftSteps, $rightStage], $this->railRow(['gap' => ['desktop' => '32px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function statsSection(): array
    {
        $stat = fn (string $num, string $caption): array => $this->column(3, [
            $this->h3($num, [
                'fontSize' => ['desktop' => '40px', 'mobile' => '32px'],
                'fontWeight' => $this->responsive('500'),
                'letterSpacing' => $this->responsive('-1.6px'),
                'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                'textAlign' => $this->responsive('center'),
            ]),
            $this->paragraph($caption, DesignTokens::ref('muted', '#716b63'), '14px', 'center'),
        ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('6px')]);

        return $this->tagSection('stats', $this->section('Stats · Impact Strip', [
            $this->row([
                $this->column(12, [
                    $this->paragraph('Built to deliver measurable impact', DesignTokens::ref('muted', '#716b63'), '13.5px', 'center', [
                        'fontWeight' => $this->responsive('500'),
                    ]),
                ]),
            ], $this->railRow(['margin' => $this->responsive(['bottom' => '28px'])])),
            $this->row([
                $stat('200+', 'Automated workflows live'),
                $stat('6 wks', 'Median time to production'),
                $stat('12+', 'Hours saved per rep weekly'),
                $stat('90 days', 'Measured ROI payback'),
            ], $this->railRow(['gap' => ['desktop' => '20px', 'mobile' => '24px']]), 'mobile'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => [
                'desktop' => ['top' => '52px', 'bottom' => '52px', 'left' => '28px', 'right' => '28px'],
                'mobile' => ['top' => '36px', 'bottom' => '36px', 'left' => '18px', 'right' => '18px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function caseStudiesSection(bool $includeThird = false): array
    {
        $caseCard = fn (string $img, string $badgeText, string $title, string $summary, string $url): array => [
            ...$this->container('stack', '16px', 'case-card'),
            'children' => [
                [
                    ...$this->container('stack', '0px', 'case-media'),
                    'children' => [
                        $this->image(self::ASSETS.$img, $title, [
                            'width' => $this->responsive('100%'),
                            'height' => ['desktop' => '280px', 'mobile' => '220px'],
                            'borderRadius' => $this->responsive('14px'),
                        ], $url),
                        [
                            ...$this->container('stack', '0px', 'case-badge', [
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'borderRadius' => $this->responsive('10px'),
                                'padding' => $this->responsive(['top' => '10px', 'bottom' => '10px', 'left' => '20px', 'right' => '20px']),
                            ]),
                            'children' => [
                                $this->paragraph($badgeText, '#131314', '14.5px', 'center', ['fontWeight' => $this->responsive('600')]),
                            ],
                        ],
                    ],
                ],
                $this->h3($title, [
                    'fontSize' => $this->responsive('21px'),
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-0.5px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                ], $url),
                $this->paragraph($summary, DesignTokens::ref('muted', '#716b63'), '14.5px', 'left', ['lineHeight' => $this->responsive('1.55')]),
            ],
        ];

        $header = $this->row([
            $this->column(8, [
                $this->h2('Case studies', [
                    'fontSize' => ['desktop' => '32px', 'mobile' => '28px'],
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-1.2px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                ]),
                $this->paragraph('Real systems deployed inside fast-moving operations teams.', DesignTokens::ref('muted', '#716b63'), '16px'),
            ], ['gap' => $this->responsive('8px')]),
            $this->column(4, [$this->softPill('View all cases', '/case-studies')], [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => ['desktop' => 'flex-end', 'mobile' => 'flex-start'],
            ]),
        ], $this->railRow(['alignItems' => $this->responsive('center'), 'margin' => $this->responsive(['bottom' => '28px'])]), 'tablet');

        $cols = $includeThird ? [
            $this->column(4, [$caseCard('case-modulus.svg', 'MODULUS', 'Cutting freight quote response from 4 hours to 90 seconds', 'Automated carrier rate extraction and lane validation across 14,000 monthly quotes.', '/case-studies')]),
            $this->column(4, [$caseCard('case-kestrel.svg', 'KESTREL', '92% straight-through document verification for onboarding', 'Structured extraction with confidence-based escalation into compliance queues.', '/case-studies')]),
            $this->column(4, [$caseCard('case-verge.svg', 'verge.io', 'Zero-touch returns & warranty routing across 6 storefronts', 'Unified RMA agent wired directly into Modrix order history and warehouse webhooks.', '/case-studies')]),
        ] : [
            $this->column(6, [$caseCard('case-modulus.svg', 'MODULUS', 'Cutting freight quote response from 4 hours to 90 seconds', 'Automated carrier rate extraction and lane validation across 14,000 monthly quotes.', '/case-studies')]),
            $this->column(6, [$caseCard('case-kestrel.svg', 'KESTREL', '92% straight-through document verification for onboarding', 'Structured extraction with confidence-based escalation into compliance queues.', '/case-studies')]),
        ];

        return $this->tagSection('cases', $this->section('Case Studies', [
            $header,
            $this->row($cols, $this->railRow(['gap' => ['desktop' => '24px', 'mobile' => '24px']]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function testimonialSection(): array
    {
        return $this->tagSection('testimonial', $this->section('Testimonial · Executive Quote', [
            $this->row([
                $this->column(4, [
                    $this->image(self::ASSETS.'portrait-client.svg', 'Marcus Halvorsen, COO at Modulus', [
                        'width' => $this->responsive('100%'),
                        'maxWidth' => $this->responsive('300px'),
                        'borderRadius' => $this->responsive('16px'),
                    ]),
                ], ['alignItems' => ['desktop' => 'flex-start', 'mobile' => 'center']]),
                $this->column(8, [
                    $this->h3('“Sable replaced four brittle spreadsheets and two external contractors in five weeks. Our reps trust the system because every edge case escalates cleanly.”', [
                        'fontSize' => ['desktop' => '28px', 'mobile' => '22px'],
                        'fontWeight' => $this->responsive('400'),
                        'lineHeight' => $this->responsive('1.35'),
                        'letterSpacing' => $this->responsive('-0.8px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    ]),
                    [
                        ...$this->container('stack', '4px', null, ['margin' => $this->responsive(['top' => '8px'])]),
                        'children' => [
                            $this->paragraph('Marcus Halvorsen', DesignTokens::ref('heading', '#131314'), '15.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                            $this->paragraph('Chief Operating Officer, Modulus Logistics', DesignTokens::ref('muted', '#716b63'), '14px'),
                        ],
                    ],
                ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('18px')]),
            ], $this->railRow(['gap' => ['desktop' => '40px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function bentoSection(): array
    {
        $darkCard = fn (string $img, string $title, string $body): array => [
            ...$this->container('stack', '16px', 'bento-card-dark', [
                'backgroundColor' => $this->responsive('#171413'),
                'borderRadius' => $this->responsive('18px'),
                'padding' => $this->responsive(['top' => '22px', 'bottom' => '26px', 'left' => '22px', 'right' => '22px']),
                'height' => $this->responsive('100%'),
                'justifyContent' => $this->responsive('space-between'),
            ]),
            'children' => [
                $this->image(self::ASSETS.$img, $title, [
                    'width' => $this->responsive('100%'),
                    'borderRadius' => $this->responsive('12px'),
                ]),
                [
                    ...$this->container('stack', '6px', null, ['padding' => $this->responsive(['left' => '4px', 'right' => '4px'])]),
                    'children' => [
                        $this->h4($title, ['fontSize' => $this->responsive('21px'), 'fontWeight' => $this->responsive('500'), 'letterSpacing' => $this->responsive('-0.5px'), 'color' => $this->responsive('#fdfcfc')]),
                        $this->paragraph($body, '#c7c2bc', '14.5px', 'left', ['lineHeight' => $this->responsive('1.5')]),
                    ],
                ],
            ],
        ];

        $sandCard = fn (string $img, string $title, string $body): array => [
            ...$this->container('stack', '16px', null, [
                'backgroundColor' => $this->responsive('var(--sable-surface, var(--site-surface, #f5f3f1))'),
                'borderRadius' => $this->responsive('18px'),
                'padding' => $this->responsive(['top' => '22px', 'bottom' => '26px', 'left' => '22px', 'right' => '22px']),
                'height' => $this->responsive('100%'),
                'justifyContent' => $this->responsive('space-between'),
            ]),
            'children' => [
                $this->image(self::ASSETS.$img, $title, [
                    'width' => $this->responsive('100%'),
                    'borderRadius' => $this->responsive('12px'),
                ]),
                [
                    ...$this->container('stack', '6px', null, ['padding' => $this->responsive(['left' => '4px', 'right' => '4px'])]),
                    'children' => [
                        $this->h4($title, ['fontSize' => $this->responsive('21px'), 'fontWeight' => $this->responsive('500'), 'letterSpacing' => $this->responsive('-0.5px'), 'color' => $this->responsive(DesignTokens::ref('heading', '#131314'))]),
                        $this->paragraph($body, DesignTokens::ref('muted', '#716b63'), '14.5px', 'left', ['lineHeight' => $this->responsive('1.5')]),
                    ],
                ],
            ],
        ];

        $intro = $this->row([
            $this->column(12, [
                $this->h2('Systems engineered for daily operations', [
                    'fontSize' => ['desktop' => '34px', 'mobile' => '28px'],
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-1.2px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    'textAlign' => $this->responsive('center'),
                ]),
                $this->paragraph('Built for speed, auditability and clean handoffs to human teams.', DesignTokens::ref('muted', '#716b63'), '16px', 'center'),
            ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('8px')]),
        ], $this->railRow(['margin' => $this->responsive(['bottom' => '36px'])]));

        $rowTop = $this->row([
            $this->column(7, [$darkCard('bento-action.svg', 'Agents that take real action', 'Read tickets, query databases, update CRMs and post verified summaries safely.')]),
            $this->column(5, [$sandCard('bento-workflow.svg', 'Workflow automation', 'Cut repetitive copy-paste triage by 12 hours per week across ops and finance.')]),
        ], $this->railRow(['gap' => ['desktop' => '18px', 'mobile' => '18px'], 'margin' => $this->responsive(['bottom' => '18px']), 'alignItems' => $this->responsive('stretch')]), 'tablet');

        $rowBottom = $this->row([
            $this->column(5, [$sandCard('bento-ml.svg', 'Custom ML & Fine-tuning', 'Task-specific domain tuning lifts classification precision from 72% to 92%.')]),
            $this->column(7, [$darkCard('bento-clarity.svg', 'A clear yes or no, not a maybe', 'Every output crosses deterministic confidence gates before touching customer accounts.')]),
        ], $this->railRow(['gap' => ['desktop' => '18px', 'mobile' => '18px'], 'alignItems' => $this->responsive('stretch')]), 'tablet');

        return $this->tagSection('bento', $this->section('Bento · 4-Card Asymmetric Grid', [
            $intro,
            $rowTop,
            $rowBottom,
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function faqSection(): array
    {
        return $this->tagSection('faq', $this->section('FAQ · Common Questions', [
            $this->row([
                $this->column(12, [
                    $this->h2('Common questions', [
                        'fontSize' => ['desktop' => '32px', 'mobile' => '26px'],
                        'fontWeight' => $this->responsive('500'),
                        'letterSpacing' => $this->responsive('-1.2px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                        'textAlign' => $this->responsive('center'),
                    ]),
                ]),
            ], $this->railRow(['margin' => $this->responsive(['bottom' => '28px'])])),
            $this->row([
                $this->column(12, [
                    $this->blockNode(BlockType::Faq, [
                        'initialOpen' => true,
                        'allowMultipleOpen' => false,
                        'emitSchemaOrg' => true,
                        'items' => [
                            [
                                'question' => 'Do we own the prompts, code and fine-tuned weights at handover?',
                                'answer' => 'Yes. Everything runs in your own cloud or private VPC and transfers to your GitHub organization with zero lock-in licensing.',
                            ],
                            [
                                'question' => 'How long does a typical AI agent deployment take?',
                                'answer' => 'Discovery sprints finish in 2 weeks. Full production rollouts with telemetry and human escalation typically launch within 4 to 6 weeks.',
                            ],
                            [
                                'question' => 'How do you prevent hallucinations on customer-facing actions?',
                                'answer' => 'Every external tool call passes deterministic JSON schema validation, confidence thresholds and automatic human escalation rules.',
                            ],
                            [
                                'question' => 'Can you integrate directly with Modrix Commerce and our existing ERP?',
                                'answer' => 'Yes. We connect cleanly with REST/GraphQL commerce backends, HubSpot, Salesforce, Postgres, Stripe and internal webhooks.',
                            ],
                        ],
                    ]),
                ]),
            ], $this->railRow(['maxWidth' => $this->responsive('820px')])),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function articlesSection(): array
    {
        $card = fn (string $img, string $date, string $title, string $excerpt): array => $this->column(4, [[
            ...$this->container('stack', '12px'),
            'children' => [
                $this->image(self::ASSETS.$img, $title, [
                    'width' => $this->responsive('100%'),
                    'borderRadius' => $this->responsive('14px'),
                ], '/blog'),
                $this->paragraph($date, DesignTokens::ref('muted', '#716b63'), '13px'),
                $this->h4($title, [
                    'fontSize' => $this->responsive('19px'),
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-0.4px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    'lineHeight' => $this->responsive('1.3'),
                ], '/blog'),
                $this->paragraph($excerpt, DesignTokens::ref('muted', '#716b63'), '14px', 'left', ['lineHeight' => $this->responsive('1.55')]),
                $this->inlineLink('Read more →', '/blog'),
            ],
        ]]);

        $header = $this->row([
            $this->column(8, [
                $this->h2('Latest articles', [
                    'fontSize' => ['desktop' => '32px', 'mobile' => '28px'],
                    'fontWeight' => $this->responsive('500'),
                    'letterSpacing' => $this->responsive('-1.2px'),
                    'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                ]),
                $this->paragraph('Notes from production evaluations, agent routing and latency tuning.', DesignTokens::ref('muted', '#716b63'), '16px'),
            ], ['gap' => $this->responsive('8px')]),
            $this->column(4, [$this->softPill('View all articles', '/blog')], [
                'justifyContent' => $this->responsive('center'),
                'alignItems' => ['desktop' => 'flex-end', 'mobile' => 'flex-start'],
            ]),
        ], $this->railRow(['alignItems' => $this->responsive('center'), 'margin' => $this->responsive(['bottom' => '28px'])]), 'tablet');

        return $this->tagSection('articles', $this->section('Articles · Editorial Row', [
            $header,
            $this->row([
                $card('article-1.svg', 'Oct 2026', 'Why deterministic routers beat giant multi-agent swarms', 'Keeping tool selection predictable cuts p95 latency in half while making failures trivial to trace.'),
                $card('article-2.svg', 'Sep 2026', 'Building an eval harness before writing your first prompt', 'Three practical datasets that stop silent quality regressions across weekly model upgrades.'),
                $card('article-3.svg', 'Aug 2026', 'Grounded retrieval over complex product & policy catalogs', 'Hybrid BM25 plus semantic chunking patterns for high-SKU commerce support desks.'),
            ], $this->railRow(['gap' => ['desktop' => '22px', 'mobile' => '28px']]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function ctaBandSection(): array
    {
        return $this->tagSection('cta-band', $this->section('CTA · Get Started with Sable', [
            $this->row([
                $this->column(7, [
                    $this->h2('Get started with Sable', [
                        'fontSize' => ['desktop' => '40px', 'mobile' => '30px'],
                        'fontWeight' => $this->responsive('500'),
                        'letterSpacing' => $this->responsive('-1.4px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    ]),
                    $this->paragraph('Book a 25-minute scoping session to identify your highest-impact automation candidate.', DesignTokens::ref('muted', '#716b63'), '16.5px'),
                ], ['gap' => $this->responsive('10px')]),
                $this->column(5, [[
                    ...$this->container('row', '12px', null, ['alignItems' => $this->responsive('center'), 'justifyContent' => ['desktop' => 'flex-end', 'mobile' => 'flex-start']]),
                    'children' => [
                        $this->softPill('View all services', '/services'),
                        $this->primaryPill('Start a project', '/contact'),
                    ],
                ]], ['justifyContent' => $this->responsive('center')]),
            ], $this->railRow(['alignItems' => $this->responsive('center'), 'gap' => ['desktop' => '24px', 'mobile' => '20px']]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function aboutStorySection(): array
    {
        return $this->tagSection('about-story', $this->section('About · Principles & Studio', [
            $this->row([
                $this->column(6, [
                    $this->image(self::ASSETS.'about-studio.svg', 'Sable Systems engineering principles', [
                        'width' => $this->responsive('100%'),
                        'borderRadius' => $this->responsive('16px'),
                    ]),
                ]),
                $this->column(6, [
                    $this->h2('Software discipline applied to probabilistic models', [
                        'fontSize' => ['desktop' => '32px', 'mobile' => '26px'],
                        'fontWeight' => $this->responsive('500'),
                        'letterSpacing' => $this->responsive('-1.2px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    ]),
                    $this->paragraph('Most AI demos stall between notebook prototype and messy production webhooks. We treat prompts like typed code: tested against golden cases, observed in real time, and backed by human fallback paths.', DesignTokens::ref('muted', '#716b63'), '16px', 'left', ['lineHeight' => $this->responsive('1.6')]),
                    $this->primaryPill('Explore our capabilities', '/services'),
                ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('16px')]),
            ], $this->railRow(['gap' => ['desktop' => '36px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function aboutTeamSection(): array
    {
        $member = fn (string $img, string $name, string $role): array => $this->column(4, [[
            ...$this->container('stack', '10px'),
            'children' => [
                $this->image(self::ASSETS.$img, $name, ['width' => $this->responsive('100%'), 'borderRadius' => $this->responsive('16px')]),
                $this->h4($name, ['fontSize' => $this->responsive('19px'), 'fontWeight' => $this->responsive('500'), 'color' => $this->responsive(DesignTokens::ref('heading', '#131314'))]),
                $this->paragraph($role, DesignTokens::ref('muted', '#716b63'), '14px'),
            ],
        ]]);

        return $this->tagSection('about-team', $this->section('About · Leadership Team', [
            $this->row([
                $this->column(12, [
                    $this->h2('Engineering partners', [
                        'fontSize' => ['desktop' => '32px', 'mobile' => '26px'],
                        'fontWeight' => $this->responsive('500'),
                        'letterSpacing' => $this->responsive('-1.2px'),
                        'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                    ]),
                ]),
            ], $this->railRow(['margin' => $this->responsive(['bottom' => '24px'])])),
            $this->row([
                $member('team-1.svg', 'Elena Rostova', 'Managing Partner & Systems Lead'),
                $member('team-2.svg', 'David Thorne', 'Principal ML & Evaluation Architect'),
                $member('team-3.svg', 'Sora Lindqvist', 'Head of Workflow Design'),
            ], $this->railRow(['gap' => ['desktop' => '22px', 'mobile' => '24px']]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function pricingSection(): array
    {
        $tier = function (string $name, string $price, string $cadence, string $desc, array $features, bool $featured = false): array {
            $bullet = fn (string $text): array => [
                ...$this->container('row', '10px', null, ['alignItems' => $this->responsive('center')]),
                'children' => [
                    $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '15px'], ['color' => $this->responsive($featured ? '#eb812c' : DesignTokens::ref('heading', '#131314'))]),
                    $this->paragraph($text, $featured ? '#e5e1dc' : DesignTokens::ref('muted', '#716b63'), '14px'),
                ],
            ];

            return $this->column(4, [[
                ...$this->container('stack', '18px', null, [
                    'backgroundColor' => $this->responsive($featured ? '#131314' : 'var(--sable-surface, var(--site-surface, #f5f3f1))'),
                    'borderRadius' => $this->responsive('18px'),
                    'padding' => $this->responsive(['top' => '30px', 'bottom' => '30px', 'left' => '26px', 'right' => '26px']),
                    'height' => $this->responsive('100%'),
                    'justifyContent' => $this->responsive('space-between'),
                ]),
                'children' => [
                    [
                        ...$this->container('stack', '12px'),
                        'children' => [
                            $this->paragraph($name, $featured ? '#eb812c' : DesignTokens::ref('heading', '#131314'), '14px', 'left', ['fontWeight' => $this->responsive('600')]),
                            $this->h3($price.' '.$cadence, ['fontSize' => $this->responsive('32px'), 'fontWeight' => $this->responsive('500'), 'letterSpacing' => $this->responsive('-1px'), 'color' => $this->responsive($featured ? '#fdfcfc' : '#131314')]),
                            $this->paragraph($desc, $featured ? '#b6b0a8' : DesignTokens::ref('muted', '#716b63'), '14.5px'),
                            [
                                ...$this->container('stack', '10px', null, ['margin' => $this->responsive(['top' => '8px'])]),
                                'children' => array_map($bullet, $features),
                            ],
                        ],
                    ],
                    $featured ? $this->softPill('Book discovery', '/contact') : $this->primaryPill('Start sprint', '/contact'),
                ],
            ]]);
        };

        return $this->tagSection('pricing', $this->section('Pricing · Engagement Models', [
            $this->row([
                $tier('Discovery Sprint', '$6,500', '· 2 weeks', 'Audit data quality, latency budgets and launch a working benchmark prototype.', [
                    'Architecture & ROI blueprint',
                    'Eval dataset + golden benchmark',
                    'Interactive staging prototype',
                ]),
                $tier('Production Agent', '$18,000', '· 6 weeks', 'End-to-end engineering of a production AI workflow wired into your live tools.', [
                    'Full CRM, ERP & API integrations',
                    'Confidence gates + human escalation',
                    'Telemetry dashboard & source repo',
                ], true),
                $tier('Embedded Partner', '$9,500', '· / month', 'Dedicated senior AI engineering pod expanding automations every sprint.', [
                    'Continuous model tuning & evals',
                    'Priority Slack & SLA channel',
                    'Unlimited workflow expansions',
                ]),
            ], $this->railRow(['gap' => ['desktop' => '18px', 'mobile' => '18px'], 'alignItems' => $this->responsive('stretch')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function contactSection(): array
    {
        return $this->tagSection('contact', $this->section('Contact · Project Intake Card', [
            $this->row([
                $this->column(8, [[
                    ...$this->container('stack', '14px', 'contact-form-mount', [
                        'backgroundColor' => $this->responsive('var(--sable-surface, var(--site-surface, #f5f3f1))'),
                        'borderRadius' => $this->responsive('20px'),
                        'padding' => ['desktop' => ['top' => '40px', 'bottom' => '40px', 'left' => '40px', 'right' => '40px'], 'mobile' => ['top' => '26px', 'bottom' => '26px', 'left' => '22px', 'right' => '22px']],
                    ]),
                    'children' => [
                        $this->h3('Send us your project scope', [
                            'fontSize' => $this->responsive('26px'),
                            'fontWeight' => $this->responsive('500'),
                            'letterSpacing' => $this->responsive('-0.8px'),
                            'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
                        ]),
                        $this->paragraph('Or email '.self::EMAIL.' / call '.self::PHONE.' directly.', DesignTokens::ref('muted', '#716b63'), '15px'),
                    ],
                ]]),
            ], $this->railRow(['justifyContent' => $this->responsive('center')])),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /* ====================================================================== */
    /* REUSABLE NODE BUILDERS */
    /* ====================================================================== */

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
                'color' => $this->responsive(DesignTokens::ref('body', '#716b63')),
                'backgroundColor' => $this->responsive(DesignTokens::ref('background', '#fdfcfc')),
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
    private function railRow(array $extra = []): array
    {
        return array_merge([
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width', '1180px')),
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

        foreach (['columns', 'stackBelow'] as $propKey) {
            if (array_key_exists($propKey, $style)) {
                $props[$propKey] = $style[$propKey];
                unset($style[$propKey]);
            }
        }

        if ($role !== null) {
            $props['sableRole'] = $role;
        }

        return $this->blockNode(BlockType::Container, $props, $style);
    }

    /** @return array<string, mixed> */
    private function primaryPill(string $label, string $url): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'url' => $url,
            'variant' => 'primary',
            'size' => 'md',
            'sableRole' => 'btn-primary',
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('heading', '#131314')),
            'color' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('999px'),
            'fontSize' => $this->responsive('14px'),
            'fontWeight' => $this->responsive('500'),
            'padding' => $this->responsive(['top' => '12px', 'bottom' => '12px', 'left' => '22px', 'right' => '22px']),
        ]);
    }

    /** @return array<string, mixed> */
    private function softPill(string $label, string $url): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'url' => $url,
            'variant' => 'outline',
            'size' => 'md',
            'sableRole' => 'btn-soft',
        ], [
            'backgroundColor' => $this->responsive('var(--sable-surface, var(--site-surface, #f5f3f1))'),
            'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
            'border' => $this->responsive(['style' => 'solid', 'width' => '1px', 'color' => DesignTokens::ref('border', '#edebe9')]),
            'borderRadius' => $this->responsive('999px'),
            'fontSize' => $this->responsive('14px'),
            'fontWeight' => $this->responsive('500'),
            'padding' => $this->responsive(['top' => '12px', 'bottom' => '12px', 'left' => '22px', 'right' => '22px']),
        ]);
    }

    /**
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
        ], array_merge([
            'textAlign' => $this->responsive('left'),
            'color' => $this->responsive(DesignTokens::ref('heading', '#131314')),
            'fontSize' => $this->responsive('14px'),
            'fontWeight' => $this->responsive('500'),
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
            'color' => $this->responsive(DesignTokens::ref('footer_text', '#716b63')),
            'gap' => $this->responsive('8px'),
        ]);
    }

    /** @return array<string, mixed> */
    private function sectionPadding(): array
    {
        return [
            'desktop' => ['top' => '76px', 'bottom' => '76px', 'left' => '28px', 'right' => '28px'],
            'mobile' => ['top' => '48px', 'bottom' => '48px', 'left' => '18px', 'right' => '18px'],
        ];
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
    private function paragraph(string $text, string $color, string $size = '15px', string $align = 'left', array $extra = []): array
    {
        return $this->blockNode(BlockType::Paragraph, ['text' => $text], array_merge([
            'color' => $this->responsive($color),
            'fontSize' => $this->responsive($size),
            'textAlign' => $this->responsive($align),
        ], $extra));
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

        return '01J9SB0000'.$suffix;
    }
}
