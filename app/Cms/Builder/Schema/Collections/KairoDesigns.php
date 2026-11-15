<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\TemplatePresentation;
use App\Cms\Themes\DesignTokens;

/**
 * Reusable design blocks and multi-page templates for the Kairo Career theme (`modrix/kairo`).
 *
 * Every section carries `settings.designCollection = kairo` and reads active Theme Settings via
 * `var(--site-*)` references, so updates in Appearance → Theme settings propagate across every page.
 */
final class KairoDesigns
{
    public const COLLECTION = 'kairo';

    private const LOGO = '/templates/kairo/logo.svg';

    private const LOGO_LIGHT = '/templates/kairo/logo-light.svg';

    private const AVATARS_STRIP = '/templates/kairo/hero-avatars.svg';

    private const EMAIL = 'hello@kairoapp.io';

    private const PHONE = '+1 (415) 890-4210';

    private const ADDRESS = '548 Market Street, Suite 420, San Francisco, CA';

    private int $idCounter = 0;

    /**
     * Reusable design blocks surfaced in the Visual Builder library under the `Kairo` tab.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->design('kairo-header', 'Kairo · Crisp SaaS Header & Mobile Drawer', 'structure', 'White frosted sticky navigation bar with icon badge brand, single-line menu links, sign-in shortcut and pill CTA button.', [$this->headerSection()]),
            $this->design('kairo-footer', 'Kairo · Four-Column Light SaaS Footer', 'structure', 'Clean white four-column SaaS footer with brand mission, circular social icons, navigation groups and subtle bottom rule.', [$this->footerSection()]),
            $this->design('kairo-hero', 'Kairo · Dual-Phone App Hero Showcase', 'hero', 'Soft-surface rounded hero pairing a top-rated app badge, bold headline, dual store buttons and social proof beside dual iPhone mockups.', [$this->heroSection()]),
            $this->design('kairo-logos', 'Kairo · Continuous Partner Logo Marquee', 'social_proof', 'Centered trust caption above an infinite smooth-scrolling partner team logo strip.', [$this->logosMarqueeSection()]),
            $this->design('kairo-top-features', 'Kairo · Top Features Bento Cards', 'content', 'Three soft grey bento cards pairing rounded shadow icon badges, crisp feature summaries and embedded app UI previews.', [$this->topFeaturesSection()]),
            $this->design('kairo-advantages', 'Kairo · Advantages Split & Interactive Stack', 'content', 'Rounded surface banner pairing a live smartphone Career Hub screen with selectable highlight cards.', [$this->advantagesSection()]),
            $this->design('kairo-stages', 'Kairo · 4 Simple Stages 2×2 Bento', 'content', 'Four-step guided onboarding bento grid featuring profile cards, role breakdown ticks, offer cards and smart filters.', [$this->stagesSection()]),
            $this->design('kairo-why-us', 'Kairo · Alternating Why Choose Us Showcase', 'content', 'Three alternating feature splits showcasing continuous role chip marquees, readiness arc gauge and active interview talent card.', [$this->whyChooseSection()]),
            $this->design('kairo-testimonials', 'Kairo · Six-Card Reviews Grid & Proof Pill', 'social_proof', 'Six bordered user review cards with avatars and five-star ratings anchored by a blue floating social proof pill.', [$this->testimonialsSection()]),
            $this->design('kairo-pricing', 'Kairo · Interactive Monthly/Yearly Pricing', 'conversion', 'Three-tier pricing plans with pill billing toggle (-20% Yearly), blue recommended outline and Kairo Recommends bottom tag.', [$this->pricingSection()]),
            $this->design('kairo-comparison', 'Kairo · Feature Comparison Matrix', 'content', 'Clean bordered plan comparison matrix comparing starter, pro and enterprise capabilities row by row.', [$this->comparisonSection()]),
            $this->design('kairo-faq', 'Kairo · Sticky Split FAQ Accordion', 'content', 'Sticky FAQ intro beside rounded white bordered accordion rows with plus-to-minus expand toggles.', [$this->faqSection()]),
            $this->design('kairo-cta-download', 'Kairo · Triple-Phone App Download Banner', 'conversion', 'Soft rounded download banner with store CTA buttons above three fanned iPhone devices emerging from the bottom edge.', [$this->downloadCtaSection()]),
            $this->design('kairo-page-hero', 'Kairo · Centered Inner Page Hero', 'hero', 'Airy centered white subpage header with icon pill badge, bold H1 and subtitle.', [$this->pageHeroSection('Features', "Smart Tools Built For\nFaster Career Moves", 'Discover intelligent matching, transparent pay insights and streamlined one-tap applications built for modern talent.')]),
            $this->design('kairo-about-gallery', 'Kairo · Story Gallery & Key Stats', 'content', 'Asymmetric three-panel visual story gallery paired with key satisfaction and matching impact stats.', [$this->aboutStorySection()]),
            $this->design('kairo-values', 'Kairo · Core Values Sticky Split', 'content', 'Sticky vision introduction beside bordered horizontal value principles cards.', [$this->coreValuesSection()]),
            $this->design('kairo-blog-grid', 'Kairo · Editorial Articles Archive', 'content', 'Three-column crisp bordered career journal articles grid with category pills and reading previews.', [$this->articlesSection()]),
            $this->design('kairo-contact-split', 'Kairo · Contact Desk & Quick Inquiry', 'content', 'Direct Kairo support channels, response times and structured inquiry guidance.', [$this->contactSplitSection()]),
        ];
    }

    /**
     * Standalone multi-section whole-page templates (each includes crisp header + footer).
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
                'category' => $definition['category'],
                'description' => $definition['description'],
                'document' => $this->document([
                    $this->headerSection(),
                    ...$sections,
                    $this->footerSection(),
                ]),
            ];
        }, $this->pageDefinitions());
    }

    /**
     * Body page documents exported for `theme.json` demo import (wrapped by global site header/footer).
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
                'key' => 'kairo-home',
                'name' => 'Kairo · App Landing Showcase',
                'slug' => 'home',
                'category' => 'saas',
                'description' => 'Complete Kairo Career homepage with dual-phone hero, partner marquee, 3-card top features bento, advantages split, 4 simple stages, alternating why choose us rows, reviews grid, interactive Monthly/Yearly pricing, split FAQ accordion and triple-phone CTA banner.',
                'sections' => fn (): array => [
                    $this->heroSection(),
                    $this->logosMarqueeSection(),
                    $this->topFeaturesSection(),
                    $this->advantagesSection(),
                    $this->stagesSection(),
                    $this->whyChooseSection(),
                    $this->testimonialsSection(),
                    $this->pricingSection(),
                    $this->faqSection(),
                    $this->downloadCtaSection(),
                ],
            ],
            [
                'key' => 'kairo-features',
                'name' => 'Kairo · Platform Features',
                'slug' => 'features',
                'category' => 'saas',
                'description' => 'Deep dive into Kairo matching filters, phone preview showcase, workflow stages and platform advantages.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Features', "Smart Tools for Your\nCareer Trajectory", 'Explore real-time salary insights, automated shortlist updates and precision matching designed around your skills.'),
                    $this->topFeaturesSection(),
                    $this->advantagesSection(),
                    $this->stagesSection(),
                    $this->downloadCtaSection(),
                ],
            ],
            [
                'key' => 'kairo-about',
                'name' => 'Kairo · About Us & Mission',
                'slug' => 'about',
                'category' => 'saas',
                'description' => 'The Kairo founding story, 3-panel product studio gallery, trusted teams strip, core values split and social proof.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('About Us', 'The Story of Kairo', 'At Kairo, we connect skilled professionals directly with ambitious teams through transparent tools and calm product craft.'),
                    $this->aboutStorySection(),
                    $this->logosMarqueeSection(),
                    $this->coreValuesSection(),
                    $this->testimonialsSection(),
                    $this->downloadCtaSection(),
                ],
            ],
            [
                'key' => 'kairo-pricing',
                'name' => 'Kairo · Plans & Comparison Matrix',
                'slug' => 'pricing',
                'category' => 'saas',
                'description' => 'Monthly and Yearly (-20%) subscription cards with full capability matrix, FAQ and app download CTA.',
                'sections' => fn (): array => [
                    $this->pricingSection(true),
                    $this->comparisonSection(),
                    $this->faqSection(),
                    $this->downloadCtaSection(),
                ],
            ],
            [
                'key' => 'kairo-blog',
                'name' => 'Kairo · Career Journal & Insights',
                'slug' => 'articles',
                'category' => 'saas',
                'description' => 'Curated career strategies, compensation benchmarks and interview preparation guides.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Articles & Guides', 'Research for Your Next Move', 'Practical guides from talent advisors, engineering leads and product mentors across global tech hubs.'),
                    $this->articlesSection(),
                    $this->faqSection(),
                    $this->downloadCtaSection(),
                ],
            ],
            [
                'key' => 'kairo-contact',
                'name' => 'Kairo · Contact & Support',
                'slug' => 'contact',
                'category' => 'saas',
                'description' => 'Direct team support channels, San Francisco and London desk coordinates and FAQ assistance.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Contact Us', 'Let Us Help You Move Faster', 'Reach our product onboarding or enterprise hiring teams directly for demos, partnerships and member support.'),
                    $this->contactSplitSection(),
                    $this->faqSection(),
                ],
            ],
        ];
    }

    /* ====================================================================== */
    /* HEADER & FOOTER */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function headerSection(): array
    {
        $nav = $this->blockNode(BlockType::Navigation, [
            'items' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Features', 'url' => '/features'],
                ['label' => 'About us', 'url' => '/about'],
                ['label' => 'Pricing', 'url' => '/pricing'],
                ['label' => 'Blog', 'url' => '/articles'],
            ],
            'orientation' => 'horizontal',
            'mobileBehaviour' => 'drawer',
        ], [
            'color' => $this->responsive(DesignTokens::ref('header_text')),
            'textAlign' => $this->responsive('center'),
            'gap' => ['desktop' => DesignTokens::ref('nav_gap'), 'mobile' => '8px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('nav_weight')),
            'fontSize' => ['desktop' => '15px', 'mobile' => '16px'],
        ]);

        $logo = $this->blockNode(BlockType::Logo, [
            'src' => self::LOGO,
            'alt' => 'Kairo Career',
            'url' => '/',
        ], [
            'height' => ['desktop' => '34px', 'mobile' => '30px'],
        ]);

        $ctaGroup = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'row',
                'gap' => '18px',
                'kairoRole' => 'header-cta',
            ], [
                'justifyContent' => $this->responsive('flex-end'),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                $this->ctaButton('Plans', '/pricing', 'ghost', null, [
                    'color' => $this->responsive(DesignTokens::ref('header_text')),
                    'fontWeight' => $this->responsive('500'),
                    'padding' => $this->responsive(['top' => '10px', 'right' => '14px', 'bottom' => '10px', 'left' => '14px']),
                ]),
                $this->ctaButton('Contact', '/contact', 'primary', 'arrow-right', [
                    'padding' => $this->responsive(['top' => '10px', 'right' => '20px', 'bottom' => '10px', 'left' => '20px']),
                ]),
            ],
        ];

        $bar = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [2, 7, 3],
                'gap' => '16px',
                'stackBelow' => 'none',
                'kairoRole' => 'header-bar',
            ], [
                'width' => $this->responsive('100%'),
                'minHeight' => ['desktop' => DesignTokens::ref('header_height'), 'mobile' => '64px'],
                'alignItems' => $this->responsive('center'),
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']],
            ]),
            'children' => [$logo, $nav, $ctaGroup],
        ];

        return $this->tagSection('header', $this->section('Site Header', [
            $this->row([$this->column(12, [$bar])], [
                'maxWidth' => $this->responsive('none'),
            ]),
        ], [
            'sticky' => true,
            'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg')),
            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
            'padding' => [
                'desktop' => ['top' => '6px', 'right' => '24px', 'bottom' => '6px', 'left' => '24px'],
                'mobile' => ['top' => '6px', 'right' => '16px', 'bottom' => '6px', 'left' => '16px'],
            ],
            'zIndex' => $this->responsive('70'),
        ]));
    }

    /** @return array<string, mixed> */
    private function footerSection(): array
    {
        $linkList = fn (string $heading, array $items): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px']),
            'children' => [
                $this->paragraph($heading, DesignTokens::ref('heading'), '15px', 'left', ['fontWeight' => $this->responsive('600')]),
                $this->blockNode(BlockType::Navigation, [
                    'items' => $items,
                    'orientation' => 'vertical',
                    'mobileBehaviour' => 'keep',
                ], [
                    'color' => $this->responsive(DesignTokens::ref('footer_text')),
                    'fontSize' => ['desktop' => '14.5px', 'mobile' => '14px'],
                    'gap' => $this->responsive('10px'),
                ]),
            ],
        ];

        $brandCol = $this->column(4, [
            $this->blockNode(BlockType::Logo, ['src' => self::LOGO, 'alt' => 'Kairo Career', 'url' => '/'], ['height' => ['desktop' => '34px', 'mobile' => '30px']]),
            $this->paragraph('Kairo is a smart career platform helping skilled professionals connect with verified hiring teams and transparent roles faster.', DesignTokens::ref('footer_text'), '14.5px', 'left', ['maxWidth' => $this->responsive('32ch')]),
            $this->blockNode(BlockType::SocialIcons, [
                'networks' => [
                    ['network' => 'linkedin', 'url' => '/contact'],
                    ['network' => 'x', 'url' => '/contact'],
                    ['network' => 'instagram', 'url' => '/contact'],
                    ['network' => 'youtube', 'url' => '/contact'],
                ],
                'size' => '18px',
                'shape' => 'circle',
            ], [
                'color' => $this->responsive(DesignTokens::ref('muted')),
            ]),
        ], ['gap' => $this->responsive('16px')]);

        $bottomRule = $this->blockNode(BlockType::Divider, [
            'style' => 'solid',
            'thickness' => '1px',
            'width' => '100%',
        ], [
            'color' => $this->responsive(DesignTokens::ref('border')),
        ]);

        return $this->tagSection('footer', $this->section('Site Footer', [
            $this->row([
                $brandCol,
                $this->column(3, [$linkList('Quick Links', [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Features', 'url' => '/features'],
                    ['label' => 'About us', 'url' => '/about'],
                    ['label' => 'Pricing', 'url' => '/pricing'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ])]),
                $this->column(3, [$linkList('Product', [
                    ['label' => 'Smart Matching', 'url' => '/features'],
                    ['label' => 'Salary Benchmarks', 'url' => '/features'],
                    ['label' => 'Monthly & Yearly Plans', 'url' => '/pricing'],
                    ['label' => 'Plan Comparison', 'url' => '/pricing'],
                ])]),
                $this->column(2, [$linkList('Resources', [
                    ['label' => 'Articles & Guides', 'url' => '/articles'],
                    ['label' => 'About Our Story', 'url' => '/about'],
                    ['label' => 'Member Support', 'url' => '/contact'],
                ])]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '32px', 'mobile' => '28px'],
                'margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '28px']],
            ]),
            $this->row([
                $this->column(12, [$bottomRule]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'margin' => ['desktop' => ['bottom' => '24px']],
            ]),
            $this->row([
                $this->column(4, [$this->paragraph('Built for Modern Teams', DesignTokens::ref('footer_text'), '13.5px', 'left')]),
                $this->column(4, [$this->paragraph('Copyright © 2026 Kairo Career. All Rights Reserved.', DesignTokens::ref('footer_text'), '13.5px', 'center')]),
                $this->column(4, [$this->paragraph('Powered by Modrix CMS', DesignTokens::ref('footer_text'), '13.5px', 'right')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '16px', 'mobile' => '10px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('footer_bg')),
            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
            'padding' => [
                'desktop' => ['top' => '72px', 'right' => '24px', 'bottom' => '40px', 'left' => '24px'],
                'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '32px', 'left' => '20px'],
            ],
        ]));
    }

    /* ====================================================================== */
    /* HOMEPAGE & FEATURE SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function heroSection(): array
    {
        $ratingPill = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'row',
                'gap' => '8px',
                'kairoRole' => 'hero-rating-pill',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('badge_radius')),
                'padding' => $this->responsive(['top' => '7px', 'right' => '14px', 'bottom' => '7px', 'left' => '14px']),
                'width' => $this->responsive('fit-content'),
                'alignItems' => $this->responsive('center'),
                'boxShadow' => $this->responsive('0 2px 8px rgba(12, 4, 7, 0.04)'),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => 'star', 'library' => 'lucide', 'size' => '14px'], ['color' => $this->responsive(DesignTokens::ref('accent'))]),
                $this->paragraph('4.9/5 Top-Rated Career Companion App', DesignTokens::ref('heading'), '13px', 'left', ['fontWeight' => $this->responsive('600')]),
            ],
        ];

        $ctaRow = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px'], ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->ctaButton('Download on Appstore', '/pricing', 'primary', 'arrow-right'),
                $this->ctaButton('Get it on Google Play', '/features', 'surface-outline', 'arrow-right'),
            ],
        ];

        $trustRow = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px', 'kairoRole' => 'trust-row'], ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->image('Verified Kairo members avatars', ['width' => $this->responsive('104px'), 'height' => $this->responsive('38px'), 'objectFit' => 'contain', 'loading' => 'eager'], self::AVATARS_STRIP),
                $this->paragraph('Trusted by <b>40,000+ users</b>', DesignTokens::ref('heading'), '14.5px', 'left'),
            ],
        ];

        $heroCard = $this->row([
            $this->column(7, [
                $ratingPill,
                $this->h1("One Platform To\nFind & Land Roles"),
                $this->paragraph('Personalized opportunities, verified pay ranges and instant interview tracking built to accelerate every career step.', DesignTokens::ref('muted'), '17px', 'left', ['maxWidth' => $this->responsive('46ch')]),
                $ctaRow,
                $trustRow,
            ], [
                'gap' => $this->responsive('22px'),
                'justifyContent' => $this->responsive('center'),
            ]),
            $this->column(5, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'kairoRole' => 'hero-devices']),
                    'children' => [
                        $this->image('Kairo mobile career matching app shown on dual smartphones', [
                            'width' => $this->responsive('100%'),
                            'height' => ['desktop' => '470px', 'mobile' => '320px'],
                            'objectFit' => 'contain',
                            'loading' => 'eager',
                        ], '/templates/kairo/hero-phones.svg'),
                    ],
                ],
            ], [
                'justifyContent' => $this->responsive('center'),
            ]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'borderRadius' => $this->responsive(DesignTokens::ref('section_radius')),
            'padding' => [
                'desktop' => ['top' => '52px', 'right' => '48px', 'bottom' => '52px', 'left' => '52px'],
                'mobile' => ['top' => '32px', 'right' => '20px', 'bottom' => '28px', 'left' => '20px'],
            ],
            'gap' => ['desktop' => '24px', 'mobile' => '28px'],
            'alignItems' => $this->responsive('center'),
        ]);

        return $this->tagSection('hero', $this->section('Dual-Phone Hero Showcase', [$heroCard], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => [
                'desktop' => ['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'],
                'mobile' => ['top' => '16px', 'right' => '12px', 'bottom' => '16px', 'left' => '12px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function logosMarqueeSection(): array
    {
        $partners = ['NovaStack', 'VerveCloud', 'LoomGrid', 'Northstar', 'PulseWork', 'Skybridge', 'OrbitAI', 'FormaLabs'];
        $chips = [];
        foreach ([$partners, $partners] as $pass) {
            foreach ($pass as $name) {
                $chips[] = $this->blockNode(BlockType::Container, [
                    'layout' => 'row',
                    'gap' => '8px',
                    'kairoRole' => 'logo-chip',
                ], [
                    'alignItems' => $this->responsive('center'),
                    'padding' => $this->responsive(['top' => '10px', 'right' => '22px', 'bottom' => '10px', 'left' => '22px']),
                ]);
                $idx = array_key_last($chips);
                $chips[$idx]['children'] = [
                    $this->blockNode(BlockType::Icon, ['name' => 'layers', 'library' => 'lucide', 'size' => '18px'], ['color' => $this->responsive(DesignTokens::ref('muted'))]),
                    $this->paragraph($name, DesignTokens::ref('muted'), '17px', 'left', ['fontWeight' => $this->responsive('700'), 'letterSpacing' => $this->responsive('-0.02em')]),
                ];
            }
        }

        $track = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '28px', 'kairoRole' => 'marquee-track']),
            'children' => $chips,
        ];

        $viewport = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'kairoRole' => 'marquee-viewport']),
            'children' => [$track],
        ];

        return $this->tagSection('logos', $this->section('Trusted Partner Logos', [
            $this->row([
                $this->column(12, [
                    $this->paragraph("Trusted by the world's most innovative teams", DesignTokens::ref('muted'), '14.5px', 'center', ['fontWeight' => $this->responsive('500')]),
                    $viewport,
                ], ['gap' => $this->responsive('24px'), 'alignItems' => $this->responsive('center')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => [
                'desktop' => ['top' => '42px', 'right' => '24px', 'bottom' => '52px', 'left' => '24px'],
                'mobile' => ['top' => '28px', 'right' => '16px', 'bottom' => '32px', 'left' => '16px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function topFeaturesSection(): array
    {
        $bento = fn (string $icon, string $title, string $body, string $img): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, [
                    'layout' => 'stack',
                    'gap' => '16px',
                    'kairoRole' => 'bento-card',
                ], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '28px', 'right' => '26px', 'bottom' => '22px', 'left' => '26px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->iconBadge($icon),
                    $this->h4($title),
                    $this->paragraph($body, DesignTokens::ref('muted'), '15px'),
                    $this->image($title.' interface preview', [
                        'width' => $this->responsive('100%'),
                        'height' => ['desktop' => '200px', 'mobile' => '180px'],
                        'objectFit' => $this->responsive('contain'),
                    ], $img),
                ],
            ],
        ]);

        return $this->tagSection('top-features', $this->section('Top Features Bento', [
            $this->sectionIntro('Top Features', 'crown', "Tools That Power\nYour Career Move", 'Discover personalized role recommendations, salary ranges and real-time analytics from a single calm workspace.'),
            $this->row([
                $bento('zap', 'Smart Job Matching', 'Calibrate desired roles, schedules and compensation bands for curated matches that actually fit.', '/templates/kairo/feature-salary-ui.svg'),
                $bento('building-2', 'Company Insights', 'Preview team speed, interview feedback ratings and compensation clarity before applying.', '/templates/kairo/feature-insights-ui.svg'),
                $bento('chart-no-axes-combined', 'Real-Time Analytics', 'Track profile reach, response velocity and weekly shortlist growth in clear metrics.', '/templates/kairo/feature-chart-ui.svg'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '18px'],
                'alignItems' => $this->responsive('stretch'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function advantagesSection(): array
    {
        $advCard = fn (string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'stack',
                'gap' => '6px',
                'kairoRole' => 'advantage-card',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1.5px', 'right' => '1.5px', 'bottom' => '1.5px', 'left' => '1.5px']),
                'borderRadius' => $this->responsive('18px'),
                'padding' => $this->responsive(['top' => '20px', 'right' => '22px', 'bottom' => '20px', 'left' => '22px']),
                'cursor' => $this->responsive('pointer'),
            ]),
            'children' => [
                $this->h4($title),
                $this->paragraph($body, DesignTokens::ref('muted'), '14.5px'),
            ],
        ];

        $panel = $this->row([
            $this->column(5, [
                $this->image('Kairo Career Hub smartphone view showing live interview alerts', [
                    'width' => $this->responsive('100%'),
                    'height' => ['desktop' => '490px', 'mobile' => '360px'],
                    'objectFit' => 'contain',
                ], '/templates/kairo/advantage-phone.svg'),
            ], ['justifyContent' => $this->responsive('center')]),
            $this->column(7, [
                $this->pillBadge('The Advantages', 'sparkles'),
                $this->h2("Unlock exclusive benefits\nand control your search"),
                $this->paragraph('Manage outreach, compare offers and receive direct recruiter updates without noisy spreadsheets.', DesignTokens::ref('muted'), '16px'),
                $advCard('Welcome to Your Career Hub', 'Monitor shortlisted teams, upcoming interviews and compensation clarity in one calm timeline.'),
                $advCard('Instant Match Alerts', 'Get notified the moment verified teams open positions within your salary range and stack.'),
                $advCard('Automated Portfolio Sync', 'Attach case studies, code links and tailored highlights automatically on every one-tap apply.'),
                $advCard('Direct Hiring Conversations', 'Message hiring leads and talent partners cleanly with clear response timelines.'),
            ], ['gap' => $this->responsive('14px')]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'borderRadius' => $this->responsive(DesignTokens::ref('section_radius')),
            'padding' => [
                'desktop' => ['top' => '56px', 'right' => '48px', 'bottom' => '56px', 'left' => '48px'],
                'mobile' => ['top' => '36px', 'right' => '20px', 'bottom' => '36px', 'left' => '20px'],
            ],
            'gap' => ['desktop' => '36px', 'mobile' => '24px'],
            'alignItems' => $this->responsive('center'),
        ]);

        return $this->tagSection('advantages', $this->section('Advantages Split', [$panel], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => [
                'desktop' => ['top' => '32px', 'right' => '24px', 'bottom' => '48px', 'left' => '24px'],
                'mobile' => ['top' => '20px', 'right' => '12px', 'bottom' => '28px', 'left' => '12px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function stagesSection(): array
    {
        $card = fn (string $icon, string $title, string $body, string $img): array => $this->column(6, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'kairoRole' => 'stage-card'], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '20px', 'left' => '28px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->iconBadge($icon, true),
                    $this->h4($title),
                    $this->paragraph($body, DesignTokens::ref('muted'), '15px'),
                    $this->image($title.' step preview', [
                        'width' => $this->responsive('100%'),
                        'height' => ['desktop' => '195px', 'mobile' => '170px'],
                        'objectFit' => $this->responsive('contain'),
                    ], $img),
                ],
            ],
        ]);

        return $this->tagSection('stages', $this->section('4 Simple Stages', [
            $this->sectionIntro('Stages', 'layers', "Discover how it works in\n4 simple steps", 'Go from profile setup to direct interviews with zero repetitive forms or black-box job boards.'),
            $this->row([
                $card('users', 'Set up your personal profile', 'Showcase flagship projects, core skills and availability to unlock high-signal role matching.', '/templates/kairo/stage-profile-ui.svg'),
                $card('compass', 'Explore tailored opportunities', 'See real-time distribution of active teams actively recruiting across your discipline and level.', '/templates/kairo/stage-distribution-ui.svg'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '18px'],
                'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '18px']],
            ]),
            $this->row([
                $card('check', 'Benchmark pay & offers early', 'Compare verified monthly and annual compensation bands transparently before committing time.', '/templates/kairo/stage-payroll-ui.svg'),
                $card('target', 'Apply with zero noise filters', 'Filter cleanly by remote policy, engineering culture, tech stack and team growth stage.', '/templates/kairo/stage-filter-ui.svg'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '18px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function whyChooseSection(): array
    {
        $chipTrack = function (array $labels, bool $reverse = false): array {
            $items = [];
            foreach ([$labels, $labels] as $pass) {
                foreach ($pass as $label) {
                    $items[] = [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '8px', 'kairoRole' => 'role-pill'], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'borderRadius' => $this->responsive('12px'),
                            'padding' => $this->responsive(['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px']),
                            'alignItems' => $this->responsive('center'),
                        ]),
                        'children' => [
                            $this->blockNode(BlockType::Icon, ['name' => 'star', 'library' => 'lucide', 'size' => '14px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                            $this->paragraph($label, DesignTokens::ref('heading'), '13.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                        ],
                    ];
                }
            }

            return [
                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px', 'kairoRole' => $reverse ? 'marquee-reverse' : 'marquee-track']),
                'children' => $items,
            ];
        };

        $chipShowcase = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'kairoRole' => 'why-chips-card'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'padding' => $this->responsive(['top' => '44px', 'right' => '24px', 'bottom' => '44px', 'left' => '24px']),
                'overflow' => $this->responsive('hidden'),
            ]),
            'children' => [
                $chipTrack(['Product Designer', 'Backend Architect', 'UX Researcher', 'Growth Lead']),
                $chipTrack(['Frontend Engineer', 'Brand Designer', 'Staff Engineer', 'Operations Lead'], true),
                $chipTrack(['iOS Developer', 'Data Scientist', 'Technical Writer', 'Design Systems']),
            ],
        ];

        $storeBtns = fn (): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px']),
            'children' => [
                $this->ctaButton('Download App', '/pricing', 'primary', 'arrow-right'),
                $this->ctaButton('Learn More', '/features', 'surface-outline'),
            ],
        ];

        return $this->tagSection('why-us', $this->section('Why Choose Us Showcase', [
            $this->sectionIntro('Why Choose Us', 'shield', "Built for candidates who value\ntime, clarity and momentum", 'Every workflow in Kairo cuts out busywork so you focus on meaningful conversations.'),
            // Alternating Row 1: Chips Left, Text Right
            $this->row([
                $this->column(6, [$chipShowcase]),
                $this->column(6, [
                    $this->h3('Personalized job matching based on your skills and ambitions'),
                    $this->paragraph('Skip endless keyword searching. Kairo maps your strengths and compensation floor against live roles calibrated daily.', DesignTokens::ref('muted'), '16px'),
                    $storeBtns(),
                ], ['gap' => $this->responsive('18px'), 'justifyContent' => $this->responsive('center')]),
            ], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => '48px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '36px']]]),
            // Alternating Row 2: Text Left, Gauge Right
            $this->row([
                $this->column(6, [
                    $this->h3('Stay up-to-date with timely updates on roles and interview readiness'),
                    $this->paragraph('Track profile strength percentiles, recruiter feedback scores and active shortlists in clear real-time indicators.', DesignTokens::ref('muted'), '16px'),
                    $storeBtns(),
                ], ['gap' => $this->responsive('18px'), 'justifyContent' => $this->responsive('center')]),
                $this->column(6, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px'], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                            'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                        ]),
                        'children' => [
                            $this->image('Profile readiness gauge and shortlists overview', ['width' => $this->responsive('100%'), 'height' => ['desktop' => '340px', 'mobile' => '260px'], 'objectFit' => $this->responsive('contain')], '/templates/kairo/why-gauge-ui.svg'),
                        ],
                    ],
                ]),
            ], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => '48px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '36px']]]),
            // Alternating Row 3: Talent Card Left, Text Right
            $this->row([
                $this->column(6, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px'], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                            'padding' => $this->responsive(['top' => '20px', 'right' => '20px', 'bottom' => '20px', 'left' => '20px']),
                        ]),
                        'children' => [
                            $this->image('Talent profile connected to 1,200 daily roles', ['width' => $this->responsive('100%'), 'height' => ['desktop' => '350px', 'mobile' => '260px'], 'objectFit' => $this->responsive('contain')], '/templates/kairo/why-talent-card.svg'),
                        ],
                    ],
                ]),
                $this->column(6, [
                    $this->h3('A trusted platform connecting professionals with top hiring teams'),
                    $this->paragraph('Every team on Kairo commits to published compensation ranges and timely candidate updates across every interview stage.', DesignTokens::ref('muted'), '16px'),
                    $storeBtns(),
                ], ['gap' => $this->responsive('18px'), 'justifyContent' => $this->responsive('center')]),
            ], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => '48px', 'mobile' => '24px'], 'alignItems' => $this->responsive('center')]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function testimonialsSection(): array
    {
        $reviews = [
            ['Claire Bennett', 'Senior Product Designer · Austin', '/templates/kairo/avatar-1.svg', 'Kairo cut out weeks of cold applications. Within ten days I was speaking directly with two design directors at transparent comp ranges.'],
            ['Devon Patel', 'Staff Backend Engineer · NYC', '/templates/kairo/avatar-2.svg', 'Seeing exact salary bands and real team response times upfront saved me from three mismatched loops and landed a great remote offer.'],
            ['Hana Takahashi', 'Growth Lead · Seattle', '/templates/kairo/avatar-3.svg', 'The readiness metrics and mobile alerts meant I never missed a recruiter follow-up while wrapping up my previous role.'],
            ['Jonas Lindqvist', 'Design Systems Lead · Stockholm', '/templates/kairo/avatar-4.svg', 'Every card on Kairo feels calm and respectful of your focus. Zero clutter, clear stages and high-intent engineering teams.'],
            ['Nadia Okafor', 'Product Manager · London', '/templates/kairo/avatar-5.svg', 'Moving to a senior PM role took less than a month because the filters matched domain fit instead of generic buzzwords.'],
            ['Lucas Meyer', 'Full-Stack Architect · Berlin', '/templates/kairo/avatar-6.svg', 'My portfolio and interview notes stayed synced across desktop and iPhone. Easily the cleanest career companion I have used.'],
        ];

        $cards = array_map(function (array $item): array {
            [$name, $role, $avatar, $quote] = $item;

            return $this->column(4, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px', 'kairoRole' => 'review-card'], [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                        'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                        'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                        'padding' => $this->responsive(['top' => '26px', 'right' => '26px', 'bottom' => '26px', 'left' => '26px']),
                        'height' => $this->responsive('100%'),
                    ]),
                    'children' => [
                        $this->blockNode(BlockType::Testimonial, [
                            'quote' => $quote,
                            'authorName' => ucfirst($name),
                            'authorTitle' => $role,
                            'avatarSrc' => $avatar,
                            'rating' => 5,
                            'layout' => 'bare',
                        ], [
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                            'fontSize' => $this->responsive('15px'),
                        ]),
                    ],
                ],
            ]);
        }, $reviews);

        $blueProofPill = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'row',
                'gap' => '12px',
                'kairoRole' => 'social-proof-pill',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                'color' => $this->responsive(DesignTokens::ref('on_primary')),
                'borderRadius' => $this->responsive('999px'),
                'padding' => $this->responsive(['top' => '10px', 'right' => '22px', 'bottom' => '10px', 'left' => '14px']),
                'width' => $this->responsive('fit-content'),
                'alignItems' => $this->responsive('center'),
                'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto', 'top' => '16px']],
                'boxShadow' => $this->responsive('0 14px 28px -10px rgba(0, 102, 255, 0.45)'),
            ]),
            'children' => [
                $this->image('Kairo community members', ['width' => $this->responsive('78px'), 'height' => $this->responsive('28px'), 'objectFit' => 'contain'], self::AVATARS_STRIP),
                $this->paragraph('Trusted by 40,000+ Customers', DesignTokens::ref('on_primary'), '14px', 'left', ['fontWeight' => $this->responsive('600')]),
            ],
        ];

        return $this->tagSection('reviews', $this->section('Trusted Reviews Grid', [
            $this->sectionIntro('Reviews', 'heart', 'Trusted by 40,000+ users', 'Hear from designers, engineers and leaders who moved forward cleanly with Kairo.'),
            $this->row(array_slice($cards, 0, 3), ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '22px'], 'mobile' => ['bottom' => '16px']]]),
            $this->row(array_slice($cards, 3, 3), ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '16px']]),
            $this->row([$this->column(12, [$blueProofPill], ['alignItems' => $this->responsive('center')])], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'margin' => ['desktop' => ['top' => '24px']]]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function pricingSection(bool $isH1 = false): array
    {
        $tierCard = function (string $name, string $subtitle, string $price, string $period, string $limit, array $features, bool $recommended = false): array {
            $featRows = array_map(fn (string $label): array => [
                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '10px'], ['alignItems' => $this->responsive('center')]),
                'children' => [
                    $this->blockNode(BlockType::Icon, ['name' => 'check', 'library' => 'lucide', 'size' => '16px'], ['color' => $this->responsive($recommended ? DesignTokens::ref('primary') : DesignTokens::ref('heading'))]),
                    $this->paragraph($label, DesignTokens::ref('body'), '14.5px'),
                ],
            ], $features);

            $cardChildren = [
                $this->h4($name),
                $this->paragraph($subtitle, DesignTokens::ref('muted'), '14.5px'),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '6px'], ['alignItems' => $this->responsive('baseline')]),
                    'children' => [
                        $this->h2($price, 'left', ['fontSize' => ['desktop' => '42px', 'mobile' => '34px']]),
                        $this->paragraph($period, DesignTokens::ref('muted'), '14.5px'),
                    ],
                ],
                $this->paragraph($limit, DesignTokens::ref('muted'), '13.5px'),
                $this->ctaButton('Get Started Now', '/contact', $recommended ? 'primary' : 'surface-outline', 'arrow-right', ['width' => $this->responsive('100%')]),
                $this->blockNode(BlockType::Divider, ['style' => 'solid', 'thickness' => '1px', 'width' => '100%'], ['color' => $this->responsive(DesignTokens::ref('border'))]),
                $this->paragraph('Features:', DesignTokens::ref('heading'), '14px', 'left', ['fontWeight' => $this->responsive('600')]),
                ...$featRows,
            ];

            if ($recommended) {
                $cardChildren[] = [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '6px', 'kairoRole' => 'recommend-pill'], [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                        'borderRadius' => $this->responsive('999px'),
                        'padding' => $this->responsive(['top' => '7px', 'right' => '20px', 'bottom' => '7px', 'left' => '20px']),
                        'justifyContent' => $this->responsive('center'),
                        'width' => $this->responsive('fit-content'),
                        'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto', 'top' => '10px']],
                    ]),
                    'children' => [
                        $this->paragraph('Kairo Recommends', DesignTokens::ref('on_primary'), '12.5px', 'center', ['fontWeight' => $this->responsive('600')]),
                    ],
                ];
            }

            return [
                ...$this->blockNode(BlockType::Container, [
                    'layout' => 'stack',
                    'gap' => '14px',
                    'kairoRole' => $recommended ? 'pricing-card-pro' : 'pricing-card',
                ], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'border' => $this->responsive(['style' => 'solid', 'color' => $recommended ? DesignTokens::ref('primary') : DesignTokens::ref('border'), 'top' => $recommended ? '2px' : '1px', 'right' => $recommended ? '2px' : '1px', 'bottom' => $recommended ? '2px' : '1px', 'left' => $recommended ? '2px' : '1px']),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '32px', 'right' => '28px', 'bottom' => '30px', 'left' => '28px']),
                    'height' => $this->responsive('100%'),
                    'boxShadow' => $this->responsive($recommended ? DesignTokens::ref('shadow') : 'none'),
                ]),
                'children' => $cardChildren,
            ];
        };

        $billingPanel = function (string $proPrice, string $entPrice, string $suffix) use ($tierCard): array {
            return [
                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '24px', 'stackBelow' => 'mobile']),
                'children' => [
                    $tierCard('Basic Plan', 'Essential discovery for active job seekers', 'Free', '', 'Up to 10 saved roles & weekly match digest.', [
                        'Smart skill & role matching',
                        'Weekly compensation insights',
                        'Up to 10 saved opportunities',
                        'Mobile & web companion sync',
                        'Standard profile strength check',
                    ]),
                    $tierCard('Pro Plan', 'Built for ambitious professionals ready to move', $proPrice, $suffix, 'Unlimited one-tap applications & full insights.', [
                        'Unlimited role matching & alerts',
                        'Real-time salary benchmarks',
                        'Priority recruiter introductions',
                        'Portfolio & resume tailoring',
                        'Direct interview prep timelines',
                    ], true),
                    $tierCard('Enterprise Plan', 'Complete talent platform for growing teams', $entPrice, $suffix, 'Manage hiring teams & up to 40 active seats.', [
                        'Custom team workspace & exports',
                        'Verified salary range publishing',
                        'Candidate pipeline collaboration',
                        'Role distribution analytics',
                        'Dedicated 24/7 partner support',
                    ]),
                ],
            ];
        };

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'tabs' => [
                    ['id' => null, 'label' => 'Monthly'],
                    ['id' => null, 'label' => 'Yearly · Save 20%'],
                ],
            ], [
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
            ]),
            'children' => [
                $billingPanel('$29', '$79', '/ per month'),
                $billingPanel('$23', '$63', '/ per month (billed yearly)'),
            ],
        ];

        return $this->tagSection('pricing', $this->section('Pricing & Plans', [
            $this->sectionIntro('Pricing & Plans', 'star', 'Choose a plan that suits better', 'Scalable plans designed around clarity and speed at every stage of your career or team growth.', $isH1),
            $this->row([$this->column(12, [$tabs])], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function comparisonSection(): array
    {
        $rows = [
            ['Job Alerts', 'Limited daily digest', 'Unlimited instant alerts', 'Unlimited + Priority'],
            ['Apply to Roles', 'Standard applications', 'One-Tap Fast Apply', 'Fast Apply + Portfolio Tailoring'],
            ['Saved Opportunities', '10 saved roles', '50 saved roles', 'Unlimited workspace'],
            ['Compensation Data', 'Regional medians', 'Full salary benchmarks', 'Verified team benchmarks'],
            ['Interview Guidance', 'Community guides', 'Role checklists & scripts', 'Dedicated career coach sync'],
            ['Mobile & Web Sync', '✓ Included', '✓ Included', '✓ Priority Sync + API'],
        ];

        $headerRow = $this->matrixRow('Features in the plan', 'Basic plan', 'Pro plan', 'Enterprise plan', true);
        $bodyRows = array_map(fn (array $r): array => $this->matrixRow($r[0], $r[1], $r[2], $r[3], false), $rows);

        $tableCard = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'kairoRole' => 'comparison-matrix'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'overflow' => $this->responsive('hidden'),
            ]),
            'children' => [$headerRow, ...$bodyRows],
        ];

        return $this->tagSection('comparison', $this->section('Plan Comparison Matrix', [
            $this->sectionIntro('Top Features', 'crown', 'Explore our full capability matrix', 'Compare every feature across our free starter plan, Pro accelerator and Enterprise team suite.'),
            $this->row([$this->column(12, [$tableCard])], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width'))]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function faqSection(): array
    {
        $faqItems = [
            ['question' => 'Is Kairo free for individual job seekers?', 'answer' => 'Yes. Our Basic Plan includes smart skill matching, 10 saved opportunities and weekly compensation insights at zero cost.'],
            ['question' => 'How does Kairo verify company compensation bands?', 'answer' => 'Hiring partners publish active base salary ranges before roles go live, validated against regional market benchmarks across 40+ cities.'],
            ['question' => 'Can I switch between Monthly and Yearly billing anytime?', 'answer' => 'Yes. Upgrade, switch to Yearly billing to save 20%, or pause your Pro subscription inside your account with zero lock-in fees.'],
            ['question' => 'Do recruiters see my profile before I apply?', 'answer' => 'Only when you opt in to Verified Discovery. You control full visibility for current employers and private portfolio links.'],
            ['question' => 'How fast do hiring teams respond on Kairo?', 'answer' => 'Verified studios commit to clear 5-business-day status updates so you are never left guessing inside automated black boxes.'],
            ['question' => 'Does Kairo support remote and international roles?', 'answer' => 'Yes. Filter opportunities seamlessly by remote-first, hybrid hub cities, relocation sponsorship and timezone overlap.'],
        ];

        return $this->tagSection('faq', $this->section('FAQ Accordion', [
            $this->row([
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px', 'kairoRole' => 'sticky-col']),
                        'children' => [
                            $this->pillBadge('FAQ', 'shield'),
                            $this->h2("Got Questions?\nWe've Got Answers"),
                            $this->paragraph("Can't find the exact detail you are looking for? Reach our support team directly and get a reply within 24 hours.", DesignTokens::ref('muted'), '16px'),
                            $this->ctaButton('Contact Support', '/contact', 'primary', 'arrow-right'),
                        ],
                    ],
                ]),
                $this->column(7, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px', 'kairoRole' => 'faq-list']),
                        'children' => [
                            $this->blockNode(BlockType::Faq, [
                                'initialOpen' => true,
                                'allowMultipleOpen' => false,
                                'emitSchemaOrg' => true,
                                'items' => $faqItems,
                            ], [
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
                            ]),
                        ],
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '48px', 'mobile' => '28px'],
                'alignItems' => $this->responsive('flex-start'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function downloadCtaSection(): array
    {
        $banner = $this->row([
            $this->column(12, [
                $this->pillBadge('Join Now', 'users'),
                $this->h2("Download Our App And\nFind Your Next Role", 'center'),
                $this->paragraph('Start matching with verified teams, transparent pay ranges and effortless one-tap applications today.', DesignTokens::ref('muted'), '16.5px', 'center', ['maxWidth' => $this->responsive('54ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px'], ['justifyContent' => $this->responsive('center')]),
                    'children' => [
                        $this->ctaButton('Download on Appstore', '/pricing', 'primary', 'arrow-right'),
                        $this->ctaButton('Get it on Google Play', '/features', 'surface-outline', 'arrow-right'),
                    ],
                ],
                $this->image('Three fanned Kairo mobile devices showing app home, roles and offer tracker', [
                    'width' => $this->responsive('100%'),
                    'maxWidth' => $this->responsive('780px'),
                    'height' => ['desktop' => '320px', 'mobile' => '210px'],
                    'objectFit' => $this->responsive('contain'),
                    'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto', 'top' => '16px']],
                ], '/templates/kairo/cta-triple-phones.svg'),
            ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('20px')]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'borderRadius' => $this->responsive(DesignTokens::ref('section_radius')),
            'padding' => [
                'desktop' => ['top' => '68px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
                'mobile' => ['top' => '44px', 'right' => '20px', 'bottom' => '0px', 'left' => '20px'],
            ],
            'overflow' => $this->responsive('hidden'),
        ]);

        return $this->tagSection('cta-download', $this->section('Download App CTA Banner', [$banner], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => [
                'desktop' => ['top' => '32px', 'right' => '24px', 'bottom' => '56px', 'left' => '24px'],
                'mobile' => ['top' => '20px', 'right' => '12px', 'bottom' => '32px', 'left' => '12px'],
            ],
        ]));
    }

    /* ====================================================================== */
    /* INNER SUBPAGE SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function pageHeroSection(string $badge, string $headline, string $subtitle): array
    {
        return $this->tagSection('page-hero', $this->section('Centered Subpage Hero', [
            $this->row([
                $this->column(12, [
                    $this->pillBadge($badge, 'sparkles'),
                    $this->h1($headline, 'center'),
                    $this->paragraph($subtitle, DesignTokens::ref('muted'), '17px', 'center', ['maxWidth' => $this->responsive('56ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('16px')]),
            ], ['maxWidth' => $this->responsive(DesignTokens::ref('content_width'))]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => [
                'desktop' => ['top' => '72px', 'right' => '24px', 'bottom' => '36px', 'left' => '24px'],
                'mobile' => ['top' => '42px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function aboutStorySection(): array
    {
        $stat = fn (string $value, string $label): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '6px'], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']),
                ]),
                'children' => [
                    $this->h2($value, 'left'),
                    $this->paragraph($label, DesignTokens::ref('muted'), '14.5px'),
                ],
            ],
        ]);

        return $this->tagSection('about-story', $this->section('About Story Gallery', [
            $this->row([
                $this->column(5, [$this->image('Kairo product collaboration studio', ['width' => $this->responsive('100%'), 'height' => ['desktop' => '340px', 'mobile' => '220px'], 'objectFit' => $this->responsive('cover'), 'borderRadius' => $this->responsive(DesignTokens::ref('card_radius'))], '/templates/kairo/about-gallery-1.svg')]),
                $this->column(3, [$this->image('Built for speed and direct teams', ['width' => $this->responsive('100%'), 'height' => ['desktop' => '340px', 'mobile' => '220px'], 'objectFit' => $this->responsive('cover'), 'borderRadius' => $this->responsive(DesignTokens::ref('card_radius'))], '/templates/kairo/about-gallery-2.svg')]),
                $this->column(4, [$this->image('Global hiring velocity metrics', ['width' => $this->responsive('100%'), 'height' => ['desktop' => '340px', 'mobile' => '220px'], 'objectFit' => $this->responsive('cover'), 'borderRadius' => $this->responsive(DesignTokens::ref('card_radius'))], '/templates/kairo/about-gallery-3.svg')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '20px', 'mobile' => '16px'],
                'margin' => ['desktop' => ['bottom' => '28px']],
            ]),
            $this->row([
                $stat('98.4%', 'Member satisfaction score across completed role placements'),
                $stat('40,000+', 'Active professionals matching cleanly every week'),
                $stat('4,200+', 'Verified hiring teams publishing transparent ranges'),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '20px', 'mobile' => '16px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function coreValuesSection(): array
    {
        $valueRow = fn (string $icon, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '18px', 'kairoRole' => 'value-card'], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                'alignItems' => $this->responsive('flex-start'),
            ]),
            'children' => [
                $this->iconBadge($icon, true),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '6px']),
                    'children' => [
                        $this->h4($title),
                        $this->paragraph($body, DesignTokens::ref('muted'), '15px'),
                    ],
                ],
            ],
        ];

        return $this->tagSection('values', $this->section('Core Values Split', [
            $this->row([
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'kairoRole' => 'sticky-col']),
                        'children' => [
                            $this->pillBadge('Our Vision', 'compass'),
                            $this->h2('Our core values'),
                            $this->paragraph('Guiding principles behind every workflow, salary benchmark and hiring signal across Kairo.', DesignTokens::ref('muted'), '16px'),
                        ],
                    ],
                ]),
                $this->column(7, [
                    $valueRow('clock', 'Efficiency First', 'Streamlined tools and direct scheduling designed to help professionals land offers without repetitive forms.'),
                    $valueRow('zap', 'Empowering Growth', 'Actionable compensation data and readiness benchmarks to negotiate confidently at every career turn.'),
                    $valueRow('shield', 'Trust & Transparency', 'Clear salary ranges, privacy-first discovery controls and verified recruiter response expectations.'),
                ], ['gap' => $this->responsive('18px')]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '48px', 'mobile' => '24px'],
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function articlesSection(): array
    {
        $posts = [
            ['/templates/kairo/blog-1.svg', 'Career Guide · 6 min read', 'Navigating Salary Transparency Across Distributed Teams', 'Learn how verified salary bands shape offer negotiations and reduce interview cycles by half.'],
            ['/templates/kairo/blog-2.svg', 'Interviewing · 5 min read', 'Structured Portfolio Reviews That Win Senior Offers', 'Focus panel discussions around architecture decisions, impact metrics and real delivery trade-offs.'],
            ['/templates/kairo/blog-3.svg', 'Product Hiring · 7 min read', 'How High-Growth Studios Screen For Systems Thinking', 'An inside look at the signal teams prioritize when scaling product design and engineering pods.'],
        ];

        $columns = array_map(fn (array $post): array => $this->column(4, [
            [
                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '14px', 'kairoRole' => 'bento-card'], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '20px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px']),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    $this->image($post[2], ['width' => $this->responsive('100%'), 'height' => ['desktop' => '190px', 'mobile' => '170px'], 'objectFit' => $this->responsive('cover'), 'borderRadius' => $this->responsive('14px')], $post[0]),
                    $this->paragraph($post[1], DesignTokens::ref('primary'), '13px', 'left', ['fontWeight' => $this->responsive('600')]),
                    $this->h4($post[2]),
                    $this->paragraph($post[3], DesignTokens::ref('muted'), '14.5px'),
                    $this->ctaButton('Read Article', '/articles', 'text-dark', 'arrow-right'),
                ],
            ],
        ]), $posts);

        return $this->tagSection('blog-grid', $this->section('Editorial Articles Grid', [
            $this->row($columns, ['maxWidth' => $this->responsive(DesignTokens::ref('content_width')), 'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '18px'], 'alignItems' => $this->responsive('stretch')]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /** @return array<string, mixed> */
    private function contactSplitSection(): array
    {
        $infoItem = fn (string $icon, string $label, string $value): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->iconBadge($icon, true),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '2px']),
                    'children' => [
                        $this->paragraph($label, DesignTokens::ref('muted'), '13px'),
                        $this->paragraph($value, DesignTokens::ref('heading'), '15.5px', 'left', ['fontWeight' => $this->responsive('600')]),
                    ],
                ],
            ],
        ];

        return $this->tagSection('contact', $this->section('Contact Split Desk', [
            $this->row([
                $this->column(5, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px'], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                            'padding' => $this->responsive(['top' => '30px', 'right' => '28px', 'bottom' => '30px', 'left' => '28px']),
                        ]),
                        'children' => [
                            $this->h4('Direct Support Channels'),
                            $this->paragraph('Connect directly with Kairo onboarding advisors or enterprise hiring specialists.', DesignTokens::ref('muted'), '15px'),
                            $infoItem('mail', 'Email Desk', self::EMAIL),
                            $infoItem('phone', 'Telephone', self::PHONE),
                            $infoItem('map-pin', 'Headquarters', self::ADDRESS),
                        ],
                    ],
                ]),
                $this->column(7, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px'], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px']),
                        ]),
                        'children' => [
                            $this->pillBadge('Book a Demo', 'sparkles'),
                            $this->h3('Looking to onboard your hiring team?'),
                            $this->paragraph('Share your role volume and team structure via email at <b>hello@kairoapp.io</b> or explore instant workspace pricing on mobile and web.', DesignTokens::ref('muted'), '15.5px'),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px']),
                                'children' => [
                                    $this->ctaButton('Explore Pricing Plans', '/pricing', 'primary', 'arrow-right'),
                                    $this->ctaButton('View Platform Features', '/features', 'surface-outline'),
                                ],
                            ],
                        ],
                    ],
                ]),
            ], [
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'gap' => ['desktop' => '28px', 'mobile' => '20px'],
                'alignItems' => $this->responsive('stretch'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /* ====================================================================== */
    /* REUSABLE UI HELPERS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function sectionIntro(string $badge, string $icon, string $title, string $subtitle, bool $asH1 = false): array
    {
        return $this->row([
            $this->column(12, [
                $this->pillBadge($badge, $icon),
                $asH1 ? $this->h1($title, 'center') : $this->h2($title, 'center'),
                $this->paragraph($subtitle, DesignTokens::ref('muted'), '16.5px', 'center', ['maxWidth' => $this->responsive('56ch'), 'margin' => ['desktop' => ['left' => 'auto', 'right' => 'auto']]]),
            ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('14px')]),
        ], [
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
            'margin' => ['desktop' => ['bottom' => '44px'], 'mobile' => ['bottom' => '28px']],
        ]);
    }

    /** @return array<string, mixed> */
    private function pillBadge(string $label, string $icon = 'star'): array
    {
        return [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'row',
                'gap' => '8px',
                'kairoRole' => 'pill-badge',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('badge_radius')),
                'padding' => $this->responsive(['top' => '6px', 'right' => '14px', 'bottom' => '6px', 'left' => '12px']),
                'width' => $this->responsive('fit-content'),
                'alignItems' => $this->responsive('center'),
                'boxShadow' => $this->responsive('0 2px 6px rgba(12, 4, 7, 0.04)'),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '14px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                $this->paragraph($label, DesignTokens::ref('heading'), '13px', 'left', ['fontWeight' => $this->responsive('600')]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function iconBadge(string $icon, bool $blueTint = false): array
    {
        return [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '0px'], [
                'backgroundColor' => $this->responsive($blueTint ? DesignTokens::ref('primary') : DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => $blueTint ? DesignTokens::ref('primary') : DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive('12px'),
                'width' => $this->responsive('44px'),
                'height' => $this->responsive('44px'),
                'alignItems' => $this->responsive('center'),
                'justifyContent' => $this->responsive('center'),
                'boxShadow' => $this->responsive('0 4px 10px rgba(12, 4, 7, 0.05)'),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '20px'], [
                    'color' => $this->responsive($blueTint ? DesignTokens::ref('on_primary') : DesignTokens::ref('primary')),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function matrixRow(string $feature, string $basic, string $pro, string $ent, bool $header = false): array
    {
        $cell = fn (string $txt, bool $bold = false): array => $this->paragraph(
            $txt,
            $bold || $header ? DesignTokens::ref('heading') : DesignTokens::ref('body'),
            $header ? '15.5px' : '14.5px',
            'left',
            ['fontWeight' => $this->responsive($header || $bold ? '600' : '400')],
        );

        return [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [3, 3, 3, 3],
                'gap' => '16px',
                'stackBelow' => 'mobile',
            ], [
                'backgroundColor' => $this->responsive($header ? DesignTokens::ref('surface') : DesignTokens::ref('background')),
                'border' => $header ? [] : $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
                'padding' => $this->responsive(['top' => '18px', 'right' => '26px', 'bottom' => '18px', 'left' => '26px']),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                $cell($feature, true),
                $cell($basic),
                $cell($pro, true),
                $cell($ent),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function ctaButton(string $label, string $url, string $variant = 'primary', ?string $iconRight = null, array $style = []): array
    {
        $isPrimary = $variant === 'primary';
        $isOutline = $variant === 'surface-outline';

        $defaults = [
            'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
            'fontSize' => ['desktop' => '15px', 'mobile' => '14.5px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('button_weight')),
            'borderRadius' => $this->responsive(DesignTokens::ref('button_radius')),
            'padding' => $this->responsive(['top' => '13px', 'right' => '22px', 'bottom' => '13px', 'left' => '22px']),
        ];

        if ($isPrimary) {
            $defaults['backgroundColor'] = $this->responsive(DesignTokens::ref('primary'));
            $defaults['color'] = $this->responsive(DesignTokens::ref('on_primary'));
        } elseif ($isOutline) {
            $defaults['backgroundColor'] = $this->responsive(DesignTokens::ref('background'));
            $defaults['color'] = $this->responsive(DesignTokens::ref('heading'));
            $defaults['border'] = $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']);
        } else {
            $defaults['backgroundColor'] = $this->responsive('transparent');
            $defaults['color'] = $this->responsive(DesignTokens::ref('primary'));
            $defaults['padding'] = $this->responsive(['top' => '4px', 'right' => '0px', 'bottom' => '4px', 'left' => '0px']);
        }

        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'size' => 'md',
            'variant' => $isPrimary ? 'primary' : 'outline',
            'iconRight' => $iconRight,
        ], array_merge($defaults, $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h1(string $text, string $align = 'left', array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h1', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'fontSize' => ['desktop' => 'clamp(2.5rem, 4.6vw, calc(var(--site-h1-size, 3.5rem) * 1.12))', 'mobile' => '36px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight')),
            'lineHeight' => $this->responsive(DesignTokens::ref('heading_line_height')),
            'letterSpacing' => ['desktop' => DesignTokens::ref('heading_tracking'), 'mobile' => '-0.02em'],
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h2(string $text, string $align = 'left', array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h2', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'fontSize' => ['desktop' => 'clamp(2rem, 3.6vw, calc(var(--site-h2-size, 2.75rem) * 1.12))', 'mobile' => '30px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight')),
            'lineHeight' => $this->responsive('1.12'),
            'letterSpacing' => ['desktop' => DesignTokens::ref('heading_tracking'), 'mobile' => '-0.02em'],
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h3(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h3', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'fontSize' => ['desktop' => 'var(--site-h3-size, 2rem)', 'mobile' => '24px'],
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight')),
            'lineHeight' => $this->responsive('1.2'),
            'letterSpacing' => ['desktop' => '-0.02em'],
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h4(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => 'h4', 'link' => null], array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'fontSize' => ['desktop' => 'var(--site-h4-size, 1.35rem)', 'mobile' => '18px'],
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
            'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
            'color' => $this->responsive($color),
            'fontSize' => $this->responsive($size),
            'lineHeight' => $this->responsive(DesignTokens::ref('line_height')),
            'textAlign' => $this->responsive($align),
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function image(string $alt, array $style, string $src): array
    {
        $rawFit = $style['objectFit'] ?? 'cover';
        $objectFit = is_array($rawFit) ? (string) ($rawFit['desktop'] ?? 'cover') : (string) $rawFit;
        $rawLoading = $style['loading'] ?? 'lazy';
        $loading = is_array($rawLoading) ? (string) ($rawLoading['desktop'] ?? 'lazy') : (string) $rawLoading;
        unset($style['objectFit'], $style['loading']);

        return $this->blockNode(BlockType::Image, [
            'src' => $src,
            'alt' => $alt,
            'objectFit' => $objectFit,
            'loading' => $loading,
        ], $style);
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionPadding(): array
    {
        return [
            'desktop' => ['top' => DesignTokens::ref('section_padding'), 'right' => '24px', 'bottom' => DesignTokens::ref('section_padding'), 'left' => '24px'],
            'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '48px', 'left' => '20px'],
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
        $section['settings'] = array_merge($section['settings'] ?? [], [
            'designCollection' => self::COLLECTION,
            'designBlock' => $block,
        ]);

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
                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                'gap' => ['desktop' => DesignTokens::ref('grid_gap'), 'mobile' => '16px'],
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

        return '01J9KRA000'.$suffix;
    }
}
