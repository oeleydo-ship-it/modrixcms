<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\TemplatePresentation;
use App\Cms\Themes\DesignTokens;

/**
 * Reusable design blocks and page templates for the Larkspur Family Health theme (`modrix/larkspur`).
 *
 * Every section carries `settings.designCollection = larkspur` and reads the active Theme Settings
 * through `var(--site-*)` references, so colour, font, button and spacing changes made in
 * Appearance → Theme settings reach every page. Tints (band blue, warm notice) are mixed from
 * the primary and accent colours for the same reason.
 */
final class LarkspurDesigns
{
    public const COLLECTION = 'larkspur';

    private const ASSETS = '/templates/larkspur/';

    private const PHONE = '(555) 310-4477';

    private const PHONE_URL = 'tel:+15553104477';

    private const EMAIL = 'care@larkspurhealth.com';

    /** Primary mixed with white: the soft blue band behind the marquee and announcement bar. */
    private const BAND = 'color-mix(in srgb, var(--site-primary, #1a6083) 40%, #ffffff)';

    /** Primary mixed with white: chips and soft icon wells. */
    private const INK_SOFT = 'color-mix(in srgb, var(--site-primary, #1a6083) 12%, #ffffff)';

    /** Accent mixed with white: warm notice backgrounds. */
    private const SIGNAL_SOFT = 'color-mix(in srgb, var(--site-accent, #ca9352) 25%, #ffffff)';

    /** Accent darkened: text on warm notices. */
    private const SIGNAL_TEXT = 'color-mix(in srgb, var(--site-accent, #ca9352) 59%, #000000)';

    private const CARD_SHADOW = '0 4px 24px -8px rgba(21, 57, 76, 0.14)';

    /** @var list<array{name: string, slug: string, floor: string, address: string, hours: string, transit: string, parking: string, plans: list<array{0: string, 1: string}>}> */
    private const CLINICS = [
        ['name' => 'Orchard Street', 'slug' => 'orchard-street', 'floor' => 'Clinic, ground floor', 'address' => '214 Orchard Street, Portland, OR 97205', 'hours' => 'Mon to Fri 8 am–5 pm, Tue and Thu until 7 pm. Saturday 9 am–1 pm.', 'transit' => '3-minute walk from Orchard St station (Green, Blue lines)', 'parking' => 'Free lot, 32 spaces, 4 accessible bays', 'plans' => [['Insurance accepted', 'ink'], ['Medicare', 'ink'], ['Medicaid', 'ink'], ['Self-pay', 'signal']]],
        ['name' => 'Bellweather Lane', 'slug' => 'bellweather-lane', 'floor' => 'Clinic, second floor', 'address' => '78 Bellweather Lane, Portland, OR 97214', 'hours' => 'Mon to Fri 8 am–6 pm, Tue and Thu until 7 pm. Closed weekends.', 'transit' => 'Bus 15 stops outside. 5-minute walk from Belmont station', 'parking' => 'Street parking, step-free drop-off at the door', 'plans' => [['Insurance accepted', 'ink'], ['Medicare', 'ink'], ['Self-pay', 'signal'], ['Sliding-fee scale', 'signal']]],
        ['name' => 'Marlow Park', 'slug' => 'marlow-park', 'floor' => 'Clinic, garden level', 'address' => '9 Marlow Park Road, Portland, OR 97219', 'hours' => 'Mon to Fri 8 am–6 pm, Tue and Thu until 7 pm. Saturday 9 am–1 pm.', 'transit' => '4-minute walk from Marlow Park station (Orange line)', 'parking' => 'Shared lot, level entrance at the side', 'plans' => [['Insurance accepted', 'ink'], ['Medicare', 'ink'], ['Medicaid', 'ink'], ['Self-pay', 'signal']]],
    ];

    /** @var list<array{name: string, role: string, languages: string, days: string, open: bool}> */
    private const PROVIDERS = [
        ['name' => 'Dr. Priya Raman, MD', 'role' => 'Family medicine', 'languages' => 'English, Tamil', 'days' => 'Orchard St: Mon, Wed, Fri', 'open' => true],
        ['name' => 'Dr. Samuel Okafor, DO', 'role' => 'Family and sports medicine', 'languages' => 'English, Igbo', 'days' => 'Marlow Park: Mon to Thu', 'open' => true],
        ['name' => 'Mei Lin Chen, NP', 'role' => 'Pediatrics and women’s health', 'languages' => 'English, Mandarin', 'days' => 'Bellweather Ln: Tue, Thu', 'open' => true],
        ['name' => 'Dr. Thomas Albright, MD', 'role' => 'Family medicine, chronic care', 'languages' => 'English, German', 'days' => 'Marlow Park: Mon, Thu, Sat', 'open' => false],
    ];

    private int $idCounter = 0;

    /**
     * Reusable design blocks surfaced in the Visual Builder library.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->design('larkspur-header', 'Larkspur · Announcement Bar & Clinic Header', 'structure', 'Soft blue announcement strip above a white navigation bar with flower mark, centred menu, patient portal link and pill booking button.', [$this->headerSection()]),
            $this->design('larkspur-footer', 'Larkspur · Slate Clinic Footer', 'structure', 'Dark slate footer with brand promise, booking button, clinic addresses, three link columns and an emergency notice.', [$this->footerSection()]),
            $this->design('larkspur-hero', 'Larkspur · Full-Bleed Photo Hero', 'hero', 'Edge-to-edge clinic photo under a deep ink gradient, large light headline with word-by-word reveal and two pill buttons.', [$this->heroSection()]),
            $this->design('larkspur-marquee', 'Larkspur · Promise Marquee Band', 'social_proof', 'Soft blue band with an endlessly scrolling row of care promises separated by four-point sparkles.', [$this->marqueeSection()]),
            $this->design('larkspur-visit-types', 'Larkspur · Visit Types & Self-Pay Prices', 'content', 'Rounded video-visit photo card beside three price ticket cards with self-pay amounts and schedule chips.', [$this->visitTypesSection()]),
            $this->design('larkspur-treat', 'Larkspur · What We Treat Category Tabs', 'content', 'Vertical category tabs that switch a description, ticked condition list and photo for each area of care.', [$this->treatSection()]),
            $this->design('larkspur-clinics', 'Larkspur · Three Clinic Location Cards', 'content', 'Three location cards with exterior photo, ink name plaque, address, hours, transit, plan chips and a warm same-day notice.', [$this->clinicsSection()]),
            $this->design('larkspur-providers', 'Larkspur · Clinician Portrait Grid', 'content', 'Four clinician portraits with availability badges, specialty, languages and clinic days.', [$this->providersSection()]),
            $this->design('larkspur-coverage', 'Larkspur · Insurance Plans & Fee Card', 'conversion', 'Accepted plan tiles, coordinator promise panel and a self-pay price list card with included extras.', [$this->coverageSection()]),
            $this->design('larkspur-reviews', 'Larkspur · Patient Review Carousel', 'social_proof', 'Photo-topped patient review cards in a carousel with star ratings and patient-since notes.', [$this->reviewsSection()]),
            $this->design('larkspur-faq', 'Larkspur · Tabbed FAQ & Coordinator Card', 'content', 'Coordinator contact card beside topic tabs, each holding its own accordion of questions.', [$this->faqSection()]),
            $this->design('larkspur-book-band', 'Larkspur · Booking Band & Appointment Card', 'conversion', 'Soft blue booking band with call-to-action buttons beside a tilted appointment card.', [$this->bookBandSection()]),
            $this->design('larkspur-page-hero', 'Larkspur · Inner Page Header', 'hero', 'Quiet inner page header with eyebrow pill, two-tone headline and supporting text on a soft blue band.', [$this->pageHeroSection('Our services', 'Care for', 'every stage of life', 'From same-day sick visits to long-term care, our family clinicians look after children, adults and older patients under one roof.')]),
            $this->design('larkspur-stats', 'Larkspur · Clinic Numbers Band', 'social_proof', 'Four large figures with captions separated by hairlines on a white band.', [$this->statsSection()]),
            $this->design('larkspur-services-catalog', 'Larkspur · Filterable Photo Service Cards', 'content', 'Pill filter tabs (All services plus each care area) over two-column photo cards with search aliases, chips, price and a view link.', [$this->servicesCatalogSection()]),
            $this->design('larkspur-wide-photo', 'Larkspur · Wide Rounded Photo Band', 'media', 'Full content-width rounded photo that settles from a gentle zoom, for page openers and story breaks.', [$this->widePhotoSection()]),
            $this->design('larkspur-services-grid', 'Larkspur · Services Icon Grid', 'content', 'Six service cards with soft icon wells, short descriptions and arrow links.', [$this->servicesGridSection()]),
            $this->design('larkspur-steps', 'Larkspur · New Patient Steps', 'content', 'Three numbered steps for joining the practice, each in a bordered card with a checklist.', [$this->stepsSection()]),
            $this->design('larkspur-spotlight', 'Larkspur · Clinician Spotlight Split', 'content', 'Large clinician portrait beside a quote, credentials list and booking button.', [$this->spotlightSection()]),
            $this->design('larkspur-hours', 'Larkspur · Opening Hours Table', 'content', 'Weekly opening hours for all three clinics in a clean bordered table with a same-day notice.', [$this->hoursSection()]),
            $this->design('larkspur-contact', 'Larkspur · Contact Desk Split', 'content', 'Phone, email, portal and address cards beside a message panel with response times.', [$this->contactSection()]),
            $this->design('larkspur-cta-banner', 'Larkspur · Ink Call-to-Action Banner', 'conversion', 'Deep ink rounded banner with a two-tone headline and booking buttons.', [$this->ctaBannerSection()]),
            $this->design('larkspur-health-notes', 'Larkspur · Health Notes Articles', 'content', 'Three article cards with photos, topic chips and reading time for a clinic blog.', [$this->healthNotesSection()]),
        ];
    }

    /**
     * Whole-page templates (each wrapped in the Larkspur header and footer).
     *
     * @return list<array<string, mixed>>
     */
    public function pages(): array
    {
        return array_map(function (array $definition): array {
            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'category' => $definition['category'],
                'description' => $definition['description'],
                'document' => $this->document([
                    $this->headerSection(),
                    ...$definition['sections'](),
                    $this->footerSection(),
                ]),
            ];
        }, $this->pageDefinitions());
    }

    /**
     * Body documents for the `theme.json` demo import (the site header and footer wrap them).
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
                'key' => 'larkspur-home',
                'name' => 'Larkspur · Family Clinic Home',
                'slug' => 'home',
                'category' => 'healthcare',
                'description' => 'Full-bleed hero, promise marquee, visit types and prices, what we treat tabs, clinic cards, clinicians, insurance and fees, reviews, tabbed FAQ and booking band.',
                'sections' => fn (): array => [
                    $this->heroSection(),
                    $this->marqueeSection(),
                    $this->visitTypesSection(),
                    $this->treatSection(),
                    $this->clinicsSection(),
                    $this->providersSection(),
                    $this->coverageSection(),
                    $this->reviewsSection(),
                    $this->faqSection(),
                    $this->bookBandSection(),
                ],
            ],
            [
                'key' => 'larkspur-services',
                'name' => 'Larkspur · Services',
                'slug' => 'services',
                'category' => 'healthcare',
                'description' => 'Services header with wide team photo, filterable photo service cards, visit types and a booking banner.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Our services', 'Comprehensive care', 'for your family’s health', 'Your family’s health deserves a human touch. Find accessible, unhurried care built around your everyday life.', true, false),
                    $this->widePhotoSection(),
                    $this->servicesCatalogSection(),
                    $this->visitTypesSection(),
                    $this->ctaBannerSection(),
                ],
            ],
            [
                'key' => 'larkspur-clinicians',
                'name' => 'Larkspur · Clinicians',
                'slug' => 'clinicians',
                'category' => 'healthcare',
                'description' => 'Clinician header, portrait grid, spotlight split, patient reviews and booking band.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Our clinicians', 'Seven clinicians.', 'Choose who fits you.', 'Every profile lists languages, age groups and clinic days, so you can book the right person the first time.', true),
                    $this->providersSection(),
                    $this->spotlightSection(),
                    $this->reviewsSection(),
                    $this->bookBandSection(),
                ],
            ],
            [
                'key' => 'larkspur-locations',
                'name' => 'Larkspur · Locations & Hours',
                'slug' => 'locations',
                'category' => 'healthcare',
                'description' => 'Locations header, clinic cards, opening hours table, numbers band and booking banner.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Our clinics', 'Three clinics,', 'open late twice a week', 'Every clinic sees adults and children, takes most insurance and holds same-day sick visits each weekday morning.', true),
                    $this->clinicsSection(false),
                    $this->hoursSection(),
                    $this->statsSection(),
                    $this->ctaBannerSection(),
                ],
            ],
            [
                'key' => 'larkspur-fees',
                'name' => 'Larkspur · Fees & Insurance',
                'slug' => 'fees',
                'category' => 'healthcare',
                'description' => 'Fees header, insurance plans and price card, visit types, billing FAQ and booking band.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Fees & insurance', 'Clear prices,', 'before you book', 'We post every self-pay price and check your insurance plan before your visit, so there is never a surprise bill.', true),
                    $this->coverageSection(false),
                    $this->visitTypesSection(),
                    $this->faqSection(),
                    $this->bookBandSection(),
                ],
            ],
            [
                'key' => 'larkspur-new-patients',
                'name' => 'Larkspur · New Patients',
                'slug' => 'new-patients',
                'category' => 'healthcare',
                'description' => 'New patient header, joining steps, numbers band, health notes and booking band.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('New patients', 'Joining Larkspur', 'takes ten minutes', 'Register online, choose a clinician and book your first visit. Most new patients are seen within a week.', true),
                    $this->stepsSection(),
                    $this->statsSection(),
                    $this->healthNotesSection(),
                    $this->bookBandSection(),
                ],
            ],
            [
                'key' => 'larkspur-contact',
                'name' => 'Larkspur · Contact',
                'slug' => 'contact',
                'category' => 'healthcare',
                'description' => 'Contact header, contact desk split, opening hours and FAQ.',
                'sections' => fn (): array => [
                    $this->pageHeroSection('Contact', 'Talk to', 'our front desk', 'Call, message through the portal or visit any clinic. We reply to every message within one working hour.', true),
                    $this->contactSection(),
                    $this->hoursSection(),
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
        $announcement = [
            ...$this->container('row', '24px', 'announcement', [
                'backgroundColor' => $this->responsive(self::BAND),
                'justifyContent' => $this->responsive('center'),
                'alignItems' => $this->responsive('center'),
                'padding' => $this->responsive(['top' => '9px', 'right' => '24px', 'bottom' => '9px', 'left' => '24px']),
                'flexWrap' => $this->responsive('wrap'),
            ]),
            'children' => [
                $this->paragraph('Flu and COVID boosters now available. Walk in at any clinic.', DesignTokens::ref('secondary'), '14px', 'center', ['larkspurRole' => 'announce-item']),
                $this->paragraph('Not an emergency service. In an emergency, call 911.', DesignTokens::ref('secondary'), '14px', 'center', ['larkspurRole' => 'announce-item']),
                $this->paragraph('<a href="'.self::PHONE_URL.'">'.self::PHONE.'</a>', DesignTokens::ref('secondary'), '14px', 'center', ['larkspurRole' => 'announce-item']),
            ],
        ];

        $nav = $this->blockNode(BlockType::Navigation, [
            'items' => [
                ['label' => 'Services', 'url' => '/services'],
                ['label' => 'Clinicians', 'url' => '/clinicians'],
                ['label' => 'Locations', 'url' => '/locations'],
                ['label' => 'Fees & insurance', 'url' => '/fees'],
                ['label' => 'New patients', 'url' => '/new-patients'],
            ],
            'orientation' => 'horizontal',
            'mobileBehaviour' => 'drawer',
        ], [
            'color' => $this->responsive(DesignTokens::ref('header_text')),
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'fontWeight' => $this->responsive(DesignTokens::ref('nav_weight')),
            'fontSize' => ['desktop' => '16px', 'mobile' => '18px'],
            'gap' => ['desktop' => DesignTokens::ref('nav_gap'), 'mobile' => '8px'],
            'textAlign' => $this->responsive('center'),
        ]);

        $logo = $this->blockNode(BlockType::Logo, [
            'src' => self::ASSETS.'logo.svg',
            'alt' => 'Larkspur Family Health',
            'url' => '/',
        ], [
            'height' => ['desktop' => '32px', 'mobile' => '28px'],
        ]);

        $actions = [
            ...$this->container('row', '20px', 'header-cta', [
                'justifyContent' => $this->responsive('flex-end'),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                $this->ctaButton('Patient portal', '/contact', 'text'),
                $this->ctaButton('Book a visit', '/new-patients'),
            ],
        ];

        $bar = [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [2, 7, 3],
                'gap' => '24px',
                'stackBelow' => 'none',
                'larkspurRole' => 'header-bar',
            ], [
                'alignItems' => $this->responsive('center'),
                'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
                'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
                'padding' => ['desktop' => ['top' => '14px', 'right' => '24px', 'bottom' => '14px', 'left' => '24px'], 'mobile' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px']],
            ]),
            'children' => [$logo, $nav, $actions],
        ];

        return $this->tagSection('header', $this->section('Site Header', [
            $this->row([
                $this->column(12, [$announcement, $bar], ['gap' => $this->responsive('0px')]),
            ], ['maxWidth' => $this->responsive('none'), 'gap' => $this->responsive('0px')]),
        ], [
            'sticky' => true,
            'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg')),
            'boxShadow' => $this->responsive('inset 0 -1px 0 '.DesignTokens::ref('border')),
            'padding' => $this->responsive(['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px']),
            'zIndex' => $this->responsive('70'),
        ]));
    }

    /** @return array<string, mixed> */
    private function footerSection(): array
    {
        $onDark = DesignTokens::ref('on_dark');
        $onDarkMuted = DesignTokens::ref('footer_text');

        $column = function (string $title, array $links) use ($onDark, $onDarkMuted): array {
            return [
                ...$this->container('stack', '12px', 'footer-column'),
                'children' => [
                    $this->h4($title, ['color' => $this->responsive($onDark), 'fontSize' => $this->responsive('16px'), 'fontWeight' => $this->responsive('500')]),
                    ...array_map(fn (array $link): array => $this->paragraph('<a href="'.$link[1].'">'.$link[0].'</a>', $onDarkMuted, '14px', 'left', ['larkspurRole' => 'footer-link']), $links),
                ],
            ];
        };

        $contact = [
            ...$this->container('stack', '12px', 'footer-column'),
            'children' => [
                $this->h4('Contact', ['color' => $this->responsive($onDark), 'fontSize' => $this->responsive('16px'), 'fontWeight' => $this->responsive('500')]),
                ...array_map(fn (array $clinic): array => $this->paragraph($clinic['address'], $onDarkMuted, '14px'), self::CLINICS),
                $this->paragraph('<a href="'.self::PHONE_URL.'">'.self::PHONE.'</a>', $onDark, '14px', 'left', ['larkspurRole' => 'footer-link']),
                $this->paragraph('<a href="mailto:'.self::EMAIL.'">'.self::EMAIL.'</a>', $onDark, '14px', 'left', ['larkspurRole' => 'footer-link']),
            ],
        ];

        return $this->tagSection('footer', $this->section('Site Footer', [
            $this->row([
                $this->column(8, [
                    $this->blockNode(BlockType::Logo, ['src' => self::ASSETS.'logo-light.svg', 'alt' => 'Larkspur Family Health', 'url' => '/'], ['height' => $this->responsive('32px')]),
                    $this->h3('Family medicine across Portland. Welcoming new patients at all three clinics.', [
                        'color' => $this->responsive($onDark),
                        'fontSize' => ['desktop' => 'calc(var(--site-h4-size, 1.2rem) * 1.25)', 'mobile' => '20px'],
                        'lineHeight' => $this->responsive('1.333'),
                        'maxWidth' => $this->responsive('34rem'),
                    ]),
                ], ['gap' => $this->responsive('20px')]),
                $this->column(4, [$this->ctaButton('Book an appointment', '/new-patients', 'light')], ['justifyContent' => $this->responsive('flex-end'), 'alignItems' => ['desktop' => 'flex-end', 'mobile' => 'flex-start']]),
            ], $this->contentRow(['alignItems' => $this->responsive('flex-end'), 'padding' => $this->responsive(['bottom' => '40px']), 'border' => $this->responsive(['style' => 'solid', 'color' => 'color-mix(in srgb, var(--site-on-dark, #ffffff) 16%, transparent)', 'bottom' => '1px'])])),
            $this->row([
                $this->column(4, [$contact]),
                $this->column(8, [[
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '24px', 'stackBelow' => 'mobile']),
                    'children' => [
                        $column('Care', [['Services', '/services'], ['Same-day visits', '/services'], ['Video visits', '/services'], ['Vaccinations', '/services'], ['Tests & screening', '/services']]),
                        $column('Practice', [['Clinicians', '/clinicians'], ['Locations', '/locations'], ['Fees & insurance', '/fees'], ['New patients', '/new-patients']]),
                        $column('Help', [['Patient portal', '/contact'], ['FAQ', '/fees'], ['Contact', '/contact'], ['Accessibility', '/contact']]),
                    ],
                ]]),
            ], $this->contentRow(['padding' => $this->responsive(['top' => '40px', 'bottom' => '40px'])])),
            $this->row([
                $this->column(12, [
                    $this->notice('<b>Not an emergency service.</b> For chest pain, trouble breathing or a serious injury, call 911.'),
                    $this->paragraph('© 2026 Larkspur Family Health. All rights reserved.', $onDarkMuted, '13px', 'left', ['margin' => $this->responsive(['top' => '24px'])]),
                ]),
            ], $this->contentRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('footer_bg')),
            'color' => $this->responsive($onDark),
            'padding' => [
                'desktop' => ['top' => '80px', 'right' => '24px', 'bottom' => '48px', 'left' => '24px'],
                'mobile' => ['top' => '56px', 'right' => '20px', 'bottom' => '36px', 'left' => '20px'],
            ],
        ]));
    }

    /* ====================================================================== */
    /* HOMEPAGE SECTIONS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function heroSection(): array
    {
        $onDark = DesignTokens::ref('on_dark');

        return $this->tagSection('hero', $this->section('Photo Hero', [
            $this->row([
                $this->column(8, [
                    $this->h1('<em>Same-day care</em> for every age in your home', 'left', [
                        'color' => $this->responsive($onDark),
                        'larkspurRole' => 'split-heading',
                        'maxWidth' => $this->responsive('44rem'),
                    ]),
                    $this->paragraph('Seven family doctors and nurse practitioners across three neighborhood clinics. Same-day sick visits, evening video visits and most insurance plans welcome.', $onDark, '18px', 'left', ['maxWidth' => $this->responsive('36rem'), 'lineHeight' => $this->responsive('1.556'), 'larkspurRole' => 'fade-up']),
                    [
                        ...$this->container('row', '16px', 'fade-up', ['flexWrap' => $this->responsive('wrap')]),
                        'children' => [
                            $this->ctaButton('Book a visit', '/new-patients', 'light'),
                            $this->ctaButton('Check your coverage', '/fees', 'ghost-light'),
                        ],
                    ],
                ], ['gap' => $this->responsive('32px'), 'justifyContent' => $this->responsive('flex-end')]),
            ], $this->contentRow()),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('secondary')),
            'backgroundImage' => $this->responsive(sprintf('url("%shero.jpg")', self::ASSETS)),
            'overlayColor' => $this->responsive('rgba(12, 43, 59, 0.55)'),
            'backgroundSize' => $this->responsive('cover'),
            'backgroundPosition' => $this->responsive('center'),
            'minHeight' => ['desktop' => 'min(46rem, calc(100svh - 7.5rem))', 'tablet' => '560px', 'mobile' => '520px'],
            'display' => $this->responsive('flex'),
            'alignItems' => $this->responsive('flex-end'),
            'padding' => [
                'desktop' => ['top' => '128px', 'right' => '24px', 'bottom' => '80px', 'left' => '24px'],
                'mobile' => ['top' => '64px', 'right' => '20px', 'bottom' => '40px', 'left' => '20px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function marqueeSection(): array
    {
        $promises = ['Welcoming new patients', 'Same-day sick visits', 'Video visits 7 am to 9 pm', 'Late hours Tue and Thu', 'Most insurance accepted'];
        $items = [];

        foreach ([$promises, $promises] as $pass) {
            foreach ($pass as $promise) {
                $items[] = [
                    ...$this->container('row', '40px', 'marquee-item', ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->paragraph($promise, DesignTokens::ref('secondary'), '16px', 'left', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font')), 'whiteSpace' => $this->responsive('nowrap')]),
                        $this->blockNode(BlockType::Icon, ['name' => 'sparkle', 'library' => 'lucide', 'size' => '14px'], ['color' => $this->responsive(DesignTokens::ref('secondary'))]),
                    ],
                ];
            }
        }

        return $this->tagSection('marquee', $this->section('Promise Marquee', [
            $this->row([
                $this->column(12, [[
                    ...$this->container('stack', '0px', 'marquee-viewport'),
                    'children' => [[
                        ...$this->container('row', '40px', 'marquee-track'),
                        'children' => $items,
                    ]],
                ]]),
            ], ['maxWidth' => $this->responsive('none'), 'gap' => $this->responsive('0px')]),
        ], [
            'backgroundColor' => $this->responsive(self::BAND),
            'padding' => $this->responsive(['top' => '16px', 'right' => '0px', 'bottom' => '16px', 'left' => '0px']),
        ]));
    }

    /** @return array<string, mixed> */
    private function visitTypesSection(): array
    {
        $ticket = function (string $icon, string $price, string $sub, string $title, array $chips, string $text): array {
            return [
                ...$this->blockNode(BlockType::Container, [
                    'layout' => 'columns',
                    'columns' => [4, 8],
                    'gap' => '0px',
                    'stackBelow' => 'mobile',
                    'larkspurRole' => 'ticket card',
                ], [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'boxShadow' => $this->responsive(self::CARD_SHADOW),
                    'overflow' => $this->responsive('hidden'),
                ]),
                'children' => [
                    [
                        ...$this->container('stack', '4px', 'ticket-stub', [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                            'border' => $this->responsive(['style' => 'dashed', 'color' => DesignTokens::ref('border'), 'right' => '1px']),
                        ]),
                        'children' => [
                            $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '20px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                            $this->eyebrowText('Self-pay', DesignTokens::ref('primary')),
                            $this->paragraph($price, DesignTokens::ref('primary'), '32px', 'left', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font')), 'lineHeight' => $this->responsive('1.25'), 'letterSpacing' => $this->responsive('-0.04rem')]),
                            $this->paragraph($sub, DesignTokens::ref('muted'), '14px'),
                        ],
                    ],
                    [
                        ...$this->container('stack', '12px', null, ['padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '28px'])]),
                        'children' => [
                            [
                                ...$this->container('row', '8px', null, ['alignItems' => $this->responsive('center'), 'flexWrap' => $this->responsive('wrap')]),
                                'children' => [
                                    $this->h3($title, ['fontSize' => ['desktop' => 'calc(var(--site-h4-size, 1.2rem) * 1.25)', 'mobile' => '22px']]),
                                    ...array_map(fn (string $chip): array => $this->chip($chip), $chips),
                                ],
                            ],
                            $this->paragraph($text, DesignTokens::ref('body'), '16px'),
                        ],
                    ],
                ],
            ];
        };

        $videoCard = [
            ...$this->container('stack', '0px', 'photo-card', [
                'backgroundImage' => $this->responsive(sprintf('url("%svideo-visit.jpg")', self::ASSETS)),
                'backgroundSize' => $this->responsive('cover'),
                'backgroundPosition' => $this->responsive('center'),
                'borderRadius' => $this->responsive('2rem'),
                'minHeight' => ['desktop' => '100%', 'mobile' => '420px'],
                'justifyContent' => $this->responsive('flex-end'),
                'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px']),
            ]),
            'children' => [[
                ...$this->container('stack', '4px', 'photo-caption', [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '20px', 'bottom' => '16px', 'left' => '20px']),
                ]),
                'children' => [
                    $this->eyebrowText('Video', DesignTokens::ref('primary')),
                    $this->h4('Seen from your sofa'),
                    $this->paragraph('Most video visits take 15 minutes, and prescriptions go straight to your pharmacy.', DesignTokens::ref('body'), '14px'),
                ],
            ]],
        ];

        return $this->tagSection('visit-types', $this->section('Visit Types & Prices', [
            $this->sectionHeader('Ways to be seen', 'Visit in person, call,', 'or see us on video', 'Every visit type has a posted price. With insurance you pay your usual copay, and work notes and referral letters are always included.'),
            $this->row([
                $this->column(5, [$videoCard], ['larkspurRole' => 'fade-up']),
                $this->column(7, [
                    $ticket('plus', '$120', 'New patients $180', 'In clinic', ['Mon to Sat', 'All clinics'], 'Best for physicals, injuries, rashes and vaccines.'),
                    $ticket('video', '$90', 'Or your copay', 'Video visit', ['7 am–9 pm daily', 'About 15 min'], 'Best for colds, refills and check-ins without leaving home.'),
                    $ticket('phone', '$60', 'Or your copay', 'Phone call-back', ['Within 1 hour', 'Weekdays'], 'Best for refills, work notes and quick questions.'),
                ], ['gap' => $this->responsive('16px')]),
            ], $this->contentRow(['gap' => ['desktop' => '20px', 'mobile' => '16px'], 'alignItems' => $this->responsive('stretch')])),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function treatSection(): array
    {
        $categories = [
            ['Feeling unwell', 'unwell', 'When you feel run down, you should not have to guess why. We listen closely, explain what is going on and help you recover faster.', ['Colds', 'Flu', 'Sore throat', 'Infections', 'Minor injuries']],
            ['Routine check-ups', 'checkup', 'Stay ahead of your health with annual physicals, well-child visits, sports physicals and women’s health screenings.', ['Annual physicals', 'Well-child visits', 'Sports physicals', 'Women’s health', 'Blood pressure checks']],
            ['Ongoing conditions', 'chronic', 'Steady, long-term care for diabetes, high blood pressure, asthma, cholesterol, thyroid and joint pain.', ['Diabetes', 'High blood pressure', 'Asthma', 'Cholesterol', 'Thyroid']],
            ['Mental health', 'mental', 'Talk with someone who has time for you. We help with anxiety, low mood, stress and sleep, and refer on when you need more.', ['Anxiety', 'Depression', 'Stress', 'Sleep', 'Life changes']],
            ['Vaccinations', 'vaccine', 'Stay protected at every age, from childhood shots to the yearly flu vaccine. Walk-ins welcome Monday to Saturday.', ['Flu shots', 'Childhood vaccines', 'Tetanus boosters', 'Shingles', 'Travel advice']],
            ['Tests & screening', 'tests', 'Most tests happen on site. Results go to your portal and your clinician calls when something needs a closer look.', ['Bloodwork', 'Urine tests', 'Cholesterol checks', 'Diabetes screening', 'Cancer screening referrals']],
        ];

        $panels = array_map(function (array $category): array {
            [$name, $photo, $text, $items] = $category;

            return [
                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '48px', 'stackBelow' => 'tablet', 'larkspurRole' => 'treat-panel']),
                'children' => [
                    [
                        ...$this->container('stack', '20px'),
                        'children' => [
                            $this->h3($name, ['fontSize' => ['desktop' => 'calc(var(--site-h4-size, 1.2rem) * 1.25)', 'mobile' => '22px']]),
                            $this->paragraph($text, DesignTokens::ref('body'), '16px'),
                            [
                                ...$this->container('stack', '0px', 'tick-list'),
                                'children' => array_map(fn (string $item): array => $this->tickRow($item), $items),
                            ],
                        ],
                    ],
                    $this->image($name, [
                        'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                        'aspectRatio' => $this->responsive('4 / 3.3'),
                        'width' => $this->responsive('100%'),
                        'objectFit' => 'cover',
                    ], self::ASSETS.'treat-'.$photo.'.jpg'),
                ],
            ];
        }, $categories);

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'vertical',
                'tabs' => array_map(fn (array $category): array => ['id' => null, 'label' => $category[0]], $categories),
                'larkspurRole' => 'category-tabs',
            ], [
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
            ]),
            'children' => $panels,
        ];

        return $this->tagSection('treat', $this->section('What We Treat', [
            $this->sectionHeader('What we treat', 'What brings', 'you in today?', 'Clear answers and unhurried care for whatever you need. Most visits can be booked within the same week.', true),
            $this->row([$this->column(12, [$tabs])], $this->contentRow()),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /** @return array<string, mixed> */
    private function clinicsSection(bool $withHeader = true): array
    {
        $cards = [];

        foreach (self::CLINICS as $index => $clinic) {
            $cards[] = $this->column(4, [[
                ...$this->container('stack', '0px', 'clinic-card card', [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'borderRadius' => $this->responsive('1.5rem'),
                    'boxShadow' => $this->responsive(self::CARD_SHADOW),
                    'overflow' => $this->responsive('hidden'),
                    'height' => $this->responsive('100%'),
                ]),
                'children' => [
                    [
                        ...$this->container('stack', '0px', 'clinic-photo', [
                            'backgroundImage' => $this->responsive(sprintf('url("%sclinic-%d.jpg")', self::ASSETS, $index + 1)),
                            'backgroundSize' => $this->responsive('cover'),
                            'backgroundPosition' => $this->responsive('center'),
                            'minHeight' => $this->responsive('220px'),
                            'justifyContent' => $this->responsive('flex-end'),
                            'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px']),
                        ]),
                        'children' => [[
                            ...$this->container('stack', '2px', 'clinic-plaque', [
                                'backgroundColor' => $this->responsive(DesignTokens::ref('secondary')),
                                'borderRadius' => $this->responsive('12px'),
                                'padding' => $this->responsive(['top' => '12px', 'right' => '20px', 'bottom' => '12px', 'left' => '20px']),
                                'width' => $this->responsive('fit-content'),
                            ]),
                            'children' => [
                                $this->eyebrowText('Larkspur Family Health', DesignTokens::ref('on_dark_muted'), '11px'),
                                $this->h3($clinic['name'], ['color' => $this->responsive(DesignTokens::ref('on_dark')), 'fontSize' => $this->responsive('22px')]),
                                $this->paragraph($clinic['floor'], DesignTokens::ref('on_dark_muted'), '12px'),
                            ],
                        ]],
                    ],
                    [
                        ...$this->container('stack', '12px', null, ['padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']), 'flexGrow' => $this->responsive('1')]),
                        'children' => [
                            $this->iconLine('map-pin', $clinic['address']),
                            $this->iconLine('clock', $clinic['hours']),
                            $this->iconLine('train-front', $clinic['transit']),
                            $this->iconLine('car', $clinic['parking']),
                            [
                                ...$this->container('row', '8px', null, ['flexWrap' => $this->responsive('wrap')]),
                                'children' => array_map(fn (array $plan): array => $this->chip($plan[0], $plan[1] === 'signal'), $clinic['plans']),
                            ],
                            $this->notice('Same-day sick visits: call before 10 am.'),
                            [
                                ...$this->container('row', '20px', null, ['alignItems' => $this->responsive('center'), 'flexWrap' => $this->responsive('wrap'), 'margin' => $this->responsive(['top' => '8px'])]),
                                'children' => [
                                    $this->ctaButton('Book here', '/new-patients'),
                                    $this->ctaButton('Get directions', '/locations', 'text'),
                                ],
                            ],
                        ],
                    ],
                ],
            ]], ['larkspurRole' => 'fade-up']);
        }

        $rows = [];

        if ($withHeader) {
            $rows[] = $this->sectionHeader('Our clinics', 'Three clinics,', 'open late twice a week', 'Every Larkspur clinic sees adults and children, takes most insurance and holds same-day sick visits each weekday morning.');
        }

        $rows[] = $this->row($cards, $this->contentRow(['gap' => ['desktop' => '16px', 'mobile' => '16px'], 'alignItems' => $this->responsive('stretch')]), 'tablet');

        return $this->tagSection('clinics', $this->section('Clinic Locations', $rows, ['padding' => $this->sectionPadding(! $withHeader)]));
    }

    /** @return array<string, mixed> */
    private function providersSection(): array
    {
        $columns = [];

        foreach (self::PROVIDERS as $index => $provider) {
            $columns[] = $this->column(3, [[
                ...$this->container('stack', '12px', 'provider-card'),
                'children' => [
                    [
                        ...$this->container('stack', '0px', 'provider-photo', [
                            'backgroundColor' => $this->responsive(self::INK_SOFT),
                            'backgroundImage' => $this->responsive(sprintf('url("%sprovider-%d.jpg")', self::ASSETS, $index + 1)),
                            'backgroundSize' => $this->responsive('cover'),
                            'backgroundPosition' => $this->responsive('center top'),
                            'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                            'aspectRatio' => $this->responsive('5 / 6'),
                            'justifyContent' => $this->responsive('flex-end'),
                            'alignItems' => $this->responsive('flex-start'),
                            'padding' => $this->responsive(['top' => '12px', 'right' => '12px', 'bottom' => '12px', 'left' => '12px']),
                        ]),
                        'children' => [$this->statusBadge($provider['open'] ? 'Welcoming new patients' : 'Waitlist', $provider['open'])],
                    ],
                    [
                        ...$this->container('stack', '2px', null, [
                            'padding' => $this->responsive(['bottom' => '12px']),
                            'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
                        ]),
                        'children' => [
                            $this->h3($provider['name'], ['fontSize' => $this->responsive('20px')]),
                            $this->paragraph($provider['role'], DesignTokens::ref('muted'), '14px'),
                        ],
                    ],
                    $this->iconLine('languages', $provider['languages']),
                    $this->iconLine('map-pin', $provider['days']),
                ],
            ]], ['larkspurRole' => 'fade-up']);
        }

        return $this->tagSection('providers', $this->section('Clinician Grid', [
            $this->sectionHeader('Our clinicians', 'Seven clinicians.', 'Choose who fits you.', 'Each profile lists languages, age groups and clinic days, so you can book the right person the first time.'),
            $this->row($columns, $this->contentRow(['gap' => ['desktop' => '20px', 'mobile' => '24px']]), 'mobile'),
            $this->row([
                $this->column(12, [$this->ctaButton('Meet every clinician', '/clinicians', 'outline')], ['alignItems' => $this->responsive('center')]),
            ], $this->contentRow(['margin' => $this->responsive(['top' => '48px', 'left' => 'auto', 'right' => 'auto'])])),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /** @return array<string, mixed> */
    private function coverageSection(bool $withHeading = true): array
    {
        $plans = ['Northpeak Health', 'Cedarline Care', 'Bluewater Mutual', 'Tidewater Plans', 'Pinecrest Senior', 'Statewell Health'];
        $fees = [['New patient visit', '$180'], ['Returning patient visit', '$120'], ['Video visit', '$90'], ['Phone call-back', '$60'], ['Annual physical', '$235'], ['Sports physical', '$70']];

        $planTiles = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '12px', 'stackBelow' => 'none', 'larkspurRole' => 'plan-grid']),
            'children' => array_map(fn (string $plan): array => [
                ...$this->container('row', '12px', 'plan-tile card', [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'borderRadius' => $this->responsive('12px'),
                    'boxShadow' => $this->responsive('0 2px 14px -6px rgba(21, 57, 76, 0.18)'),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px']),
                    'alignItems' => $this->responsive('center'),
                ]),
                'children' => [
                    $this->blockNode(BlockType::Icon, ['name' => 'shield-check', 'library' => 'lucide', 'size' => '22px'], ['color' => $this->responsive(DesignTokens::ref('heading'))]),
                    $this->paragraph($plan, DesignTokens::ref('heading'), '15px', 'left', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font')), 'fontWeight' => $this->responsive('500')]),
                ],
            ], $plans),
        ];

        $promise = [
            ...$this->container('stack', '20px', 'coordinator-panel', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            ]),
            'children' => [
                $this->personLine('We check your plan before you book', 'Lucia Ortega, patient care coordinator'),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '16px', 'stackBelow' => 'mobile']),
                    'children' => [
                        $this->iconStack('phone', 'Call or send your member ID through the portal'),
                        $this->iconStack('check', 'We confirm your network and copay within one working hour'),
                        $this->iconStack('calendar-check', 'Book knowing the price, with no surprise bill'),
                    ],
                ],
                $this->ctaButton('Call '.self::PHONE, self::PHONE_URL, 'outline'),
            ],
        ];

        $feeRows = array_map(fn (array $fee): array => [
            ...$this->container('row', '16px', 'fee-row', [
                'justifyContent' => $this->responsive('space-between'),
                'alignItems' => $this->responsive('center'),
                'padding' => $this->responsive(['top' => '14px', 'bottom' => '14px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
            ]),
            'children' => [
                $this->paragraph($fee[0], DesignTokens::ref('heading'), '16px'),
                $this->paragraph($fee[1], DesignTokens::ref('primary'), '20px', 'right', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font'))]),
            ],
        ], $fees);

        $feeCard = [
            ...$this->container('stack', '0px', 'fee-card card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'boxShadow' => $this->responsive('0 4px 24px -8px rgba(21, 57, 76, 0.18)'),
                'overflow' => $this->responsive('hidden'),
            ]),
            'children' => [
                [
                    ...$this->container('stack', '4px', null, [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                        'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                    ]),
                    'children' => [
                        $this->eyebrowText('Without insurance', DesignTokens::ref('body')),
                        $this->h3('Self-pay prices'),
                    ],
                ],
                [
                    ...$this->container('stack', '0px', null, ['padding' => $this->responsive(['top' => '8px', 'right' => '24px', 'bottom' => '8px', 'left' => '24px'])]),
                    'children' => [
                        ...$feeRows,
                        $this->eyebrowText('Included at no extra cost', DesignTokens::ref('body'), '13px', ['margin' => $this->responsive(['top' => '20px', 'bottom' => '8px'])]),
                        $this->iconLine('check', 'Work and school notes'),
                        $this->iconLine('check', 'Referral letters'),
                        $this->iconLine('check', 'Refills for medicines you already take'),
                    ],
                ],
                [
                    ...$this->container('stack', '12px', null, [
                        'padding' => $this->responsive(['top' => '20px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                        'border' => $this->responsive(['style' => 'dashed', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
                        'margin' => $this->responsive(['top' => '16px']),
                    ]),
                    'children' => [
                        $this->paragraph('Change or cancel up to 24 hours ahead at no cost. Later than that, or a missed visit, is $35.', DesignTokens::ref('body'), '14px'),
                        $this->paragraph('Balances over $200 can be spread over 3 to 6 months, interest-free.', DesignTokens::ref('body'), '14px'),
                        $this->ctaButton('See all fees', '/fees', 'outline'),
                    ],
                ],
            ],
        ];

        $leftBlocks = $withHeading
            ? [
                $this->eyebrow('Insurance and fees'),
                $this->h2('<em>We likely take</em> your plan', 'left', ['larkspurRole' => 'split-heading']),
            ]
            : [$this->h3('Insurance plans we accept', ['fontSize' => ['desktop' => 'var(--site-h3-size, 2rem)', 'mobile' => '26px']])];

        return $this->tagSection('coverage', $this->section('Insurance & Fees', [
            $this->row([
                $this->column(7, [
                    ...$leftBlocks,
                    $this->paragraph('We work with most commercial plans plus Medicare and Medicaid. If yours is not listed, call and we will check it for you.', DesignTokens::ref('body'), '18px', 'left', ['maxWidth' => $this->responsive('35rem'), 'lineHeight' => $this->responsive('1.556')]),
                    $planTiles,
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '24px', 'stackBelow' => 'mobile']),
                        'children' => [
                            [...$this->container('stack', '8px'), 'children' => [$this->h4('Plan not listed?'), $this->paragraph('Call '.self::PHONE.'. Networks change often, and we check yours before you book.', DesignTokens::ref('body'), '14px')]],
                            [...$this->container('stack', '8px'), 'children' => [$this->h4('No insurance?'), $this->paragraph('Self-pay prices are posted here, and a sliding-fee scale is open to anyone who asks.', DesignTokens::ref('body'), '14px')]],
                        ],
                    ],
                    $promise,
                ], ['gap' => $this->responsive('24px')]),
                $this->column(5, [$feeCard], ['larkspurRole' => 'fade-up']),
            ], $this->contentRow(['gap' => ['desktop' => '40px', 'mobile' => '32px'], 'alignItems' => $this->responsive('flex-start')]), 'tablet'),
        ], ['padding' => $this->sectionPadding(! $withHeading)]));
    }

    /** @return array<string, mixed> */
    private function reviewsSection(): array
    {
        $reviews = [
            ['“Dr. Raman has looked after our family for eight years. She remembers the little things without checking a screen.”', 'Helen W.', 'Patient since 2018'],
            ['“I booked a video visit after the kids were asleep and had the prescription ready before the pharmacy closed.”', 'Marcus J.', 'Patient since 2022'],
            ['“They got my blood pressure under control without a single rushed visit, and the nurse checks in by text.”', 'Daniel K.', 'Patient since 2020'],
            ['“Same-day sick visit on a Monday. I called at 8, was seen by 10 and home in time for lunch.”', 'Sofia R.', 'Patient since 2023'],
            ['“Mei Lin explained my daughter’s asthma plan twice without rushing us. We finally feel in control.”', 'Aisha B.', 'Family member'],
            ['“The portal actually works. Results, messages and refills without a single phone call.”', 'Grant L.', 'Patient since 2021'],
        ];

        $slides = [];

        foreach ($reviews as $index => [$quote, $name, $since]) {
            $slides[] = [
                ...$this->container('stack', '0px', 'review-card card', [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                    'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                    'boxShadow' => $this->responsive(self::CARD_SHADOW),
                    'overflow' => $this->responsive('hidden'),
                    'height' => $this->responsive('100%'),
                    'margin' => $this->responsive(['bottom' => '16px', 'left' => '2px', 'right' => '2px']),
                ]),
                'children' => [
                    $this->image('', ['aspectRatio' => $this->responsive('4 / 3'), 'width' => $this->responsive('100%'), 'objectFit' => 'cover'], self::ASSETS.'review-'.(($index % 4) + 1).'.jpg'),
                    [
                        ...$this->container('stack', '12px', null, ['padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'])]),
                        'children' => [
                            $this->paragraph('★★★★★', DesignTokens::ref('accent'), '16px', 'left', ['letterSpacing' => $this->responsive('2px')]),
                            $this->paragraph($quote, DesignTokens::ref('heading'), '15px', 'left', ['lineHeight' => $this->responsive('1.5')]),
                            [
                                ...$this->container('stack', '0px', null, ['padding' => $this->responsive(['top' => '8px'])]),
                                'children' => [
                                    $this->paragraph($name, DesignTokens::ref('primary'), '15px', 'left', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font'))]),
                                    $this->paragraph($since, DesignTokens::ref('muted'), '14px'),
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $this->tagSection('reviews', $this->section('Patient Reviews', [
            $this->row([
                $this->column(12, [
                    $this->eyebrow('What patients say'),
                    $this->h2('<em>4.8 stars across</em> 910 reviews', 'left', ['larkspurRole' => 'split-heading']),
                ], ['gap' => $this->responsive('16px')]),
            ], $this->contentRow(['margin' => ['desktop' => ['bottom' => '48px', 'left' => 'auto', 'right' => 'auto'], 'mobile' => ['bottom' => '32px', 'left' => 'auto', 'right' => 'auto']]])),
            $this->row([
                $this->column(12, [[
                    ...$this->blockNode(BlockType::Carousel, [
                        'autoplay' => false,
                        'loop' => true,
                        'showArrows' => true,
                        'showDots' => false,
                        'slidesPerView' => 4,
                        'slidesPerViewMobile' => 1,
                        'larkspurRole' => 'review-carousel',
                    ], ['gap' => $this->responsive('20px')]),
                    'children' => $slides,
                ]]),
            ], $this->contentRow()),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /** @return array<string, mixed> */
    private function faqSection(): array
    {
        $topics = [
            'Booking' => [
                ['How soon can I be seen?', 'Same-day sick visits open every weekday morning: call before 10 am. New patient visits are usually available within one to two weeks.'],
                ['How do I change or cancel a visit?', 'Use the link in your confirmation text or call the front desk. Please give us 24 hours so another patient can take the time.'],
                ['Can I choose my clinician?', 'Yes. Pick a clinician when you book, or take the first open time and we match you with whoever is available.'],
                ['Do you see children?', 'Yes. Our family clinicians see every age, from newborn checks to school and sports physicals.'],
                ['What if it is an emergency?', 'Larkspur is not an emergency service. For chest pain, trouble breathing or a serious injury, call 911 or go to the nearest emergency room.'],
            ],
            'Billing' => [
                ['Which insurance plans do you accept?', 'Most major plans plus Medicare and Medicaid. Call us and we will confirm your plan before you book.'],
                ['What if I do not have insurance?', 'Self-pay prices are posted up front: an in-clinic visit is $120, a video visit $90 and a phone visit $60.'],
                ['When will I get a bill?', 'Any balance left after insurance is emailed about 30 days after your visit. Pay online, by phone or at the front desk.'],
                ['Can I set up a payment plan?', 'Yes. Billing can split any balance into monthly payments with no interest.'],
            ],
            'Prescriptions' => [
                ['How do I request a refill?', 'Ask your pharmacy to send a refill request, or call the front desk. Most refills are ready in two business days.'],
                ['When will I get my lab results?', 'Most results are ready in three to five business days. Your clinician calls if anything needs follow-up.'],
                ['Can you send prescriptions to any pharmacy?', 'Yes. Tell us your preferred pharmacy when you book and we send prescriptions there electronically.'],
            ],
            'Visits' => [
                ['What should I bring to my first visit?', 'A photo ID, your insurance card and a list of the medicines you take. Arrive 10 minutes early to check in.'],
                ['How do video visits work?', 'We text you a secure link. Open it on your phone or computer a few minutes before; no app needed.'],
                ['How long does a visit take?', 'A routine visit is about 20 minutes with your clinician. A new patient visit takes about 40 minutes.'],
            ],
        ];

        $panels = [];

        foreach ($topics as $items) {
            $panels[] = $this->blockNode(BlockType::Faq, [
                'initialOpen' => true,
                'allowMultipleOpen' => false,
                'emitSchemaOrg' => false,
                'items' => array_map(fn (array $item): array => ['question' => $item[0], 'answer' => $item[1]], $items),
                'larkspurRole' => 'faq-list',
            ], [
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'fontFamily' => $this->responsive(DesignTokens::ref('body_font')),
            ]);
        }

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'tabs' => array_map(fn (string $label): array => ['id' => null, 'label' => $label], array_keys($topics)),
                'larkspurRole' => 'faq-tabs',
            ], [
                'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            ]),
            'children' => $panels,
        ];

        $coordinator = [
            ...$this->container('stack', '16px', 'coordinator-card card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'boxShadow' => $this->responsive('0 4px 24px -8px rgba(21, 57, 76, 0.16)'),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                'margin' => $this->responsive(['top' => '16px']),
            ]),
            'children' => [
                $this->personLine('Lucia Ortega', 'Patient care coordinator'),
                $this->paragraph('Still unsure? Lucia calls back within one hour, Monday to Friday, 8 am–6 pm.', DesignTokens::ref('body'), '14px'),
                [
                    ...$this->container('row', '16px', null, ['alignItems' => $this->responsive('center'), 'flexWrap' => $this->responsive('wrap')]),
                    'children' => [
                        $this->ctaButton('Contact us', '/contact'),
                        $this->ctaButton(self::PHONE, self::PHONE_URL, 'text'),
                    ],
                ],
            ],
        ];

        return $this->tagSection('faq', $this->section('Tabbed FAQ', [
            $this->row([
                $this->column(4, [
                    $this->eyebrow('FAQ'),
                    $this->h2('<em>Questions?</em><br>We are here to help', 'left', ['larkspurRole' => 'split-heading']),
                    $this->paragraph('Answers to what patients ask our front desk most.', DesignTokens::ref('body'), '16px'),
                    $coordinator,
                ], ['gap' => $this->responsive('16px'), 'larkspurRole' => 'sticky-col']),
                $this->column(8, [$tabs]),
            ], $this->contentRow(['gap' => ['desktop' => '64px', 'mobile' => '32px'], 'alignItems' => $this->responsive('flex-start')]), 'tablet'),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /** @return array<string, mixed> */
    private function bookBandSection(): array
    {
        $line = fn (string $key, string $value): array => [
            ...$this->container('row', '24px', null, [
                'justifyContent' => $this->responsive('space-between'),
                'padding' => $this->responsive(['top' => '10px', 'bottom' => '10px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
            ]),
            'children' => [
                $this->paragraph($key, DesignTokens::ref('muted'), '14px'),
                $this->paragraph($value, DesignTokens::ref('heading'), '14px', 'right'),
            ],
        ];

        $card = [
            ...$this->container('stack', '0px', 'appointment-card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'borderRadius' => $this->responsive('1.5rem'),
                'boxShadow' => $this->responsive('0 24px 60px -24px rgba(21, 57, 76, 0.35)'),
                'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
            ]),
            'children' => [
                [
                    ...$this->container('row', '16px', null, ['justifyContent' => $this->responsive('space-between'), 'alignItems' => $this->responsive('flex-start')]),
                    'children' => [
                        [...$this->container('stack', '2px'), 'children' => [$this->eyebrowText('Your visit', DesignTokens::ref('muted')), $this->h3('New patient', ['fontSize' => $this->responsive('28px')])]],
                        $this->chip('40 min', true),
                    ],
                ],
                [
                    ...$this->container('row', '16px', null, [
                        'justifyContent' => $this->responsive('space-between'),
                        'alignItems' => $this->responsive('center'),
                        'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                        'borderRadius' => $this->responsive('12px'),
                        'padding' => $this->responsive(['top' => '14px', 'right' => '16px', 'bottom' => '14px', 'left' => '16px']),
                        'margin' => $this->responsive(['top' => '20px', 'bottom' => '12px']),
                    ]),
                    'children' => [
                        $this->paragraph('Check-in code', DesignTokens::ref('body'), '13px'),
                        $this->paragraph('LK 0214', DesignTokens::ref('secondary'), '22px', 'right', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font')), 'letterSpacing' => $this->responsive('0.1rem')]),
                    ],
                ],
                $line('Clinic', 'Orchard Street'),
                $line('Clinician', 'Dr. Priya Raman, MD'),
                $line('When', 'Weekday morning'),
                $line('Bring', 'Photo ID, insurance card, medicine list'),
                [
                    ...$this->container('row', '8px', null, ['flexWrap' => $this->responsive('wrap'), 'margin' => $this->responsive(['top' => '16px'])]),
                    'children' => [$this->chip('Text reminder'), $this->chip('Free to reschedule')],
                ],
            ],
        ];

        return $this->tagSection('book-band', $this->section('Booking Band', [
            $this->row([
                $this->column(7, [
                    $this->eyebrow('Book a visit'),
                    $this->h2('<em>Book today.</em> Most new patients are seen within a week.', 'left', ['larkspurRole' => 'split-heading', 'maxWidth' => $this->responsive('40rem')]),
                    $this->paragraph('Choose a clinic, a clinician and a time of day. We confirm by text within one working hour.', DesignTokens::ref('body'), '18px', 'left', ['lineHeight' => $this->responsive('1.556')]),
                    [
                        ...$this->container('row', '16px', null, ['flexWrap' => $this->responsive('wrap')]),
                        'children' => [
                            $this->ctaButton('Book an appointment', '/new-patients'),
                            $this->ctaButton('Call '.self::PHONE, self::PHONE_URL, 'outline'),
                        ],
                    ],
                    $this->iconLine('triangle-alert', 'Not an emergency service. In an emergency, call 911.'),
                ], ['gap' => $this->responsive('24px')]),
                $this->column(5, [$card], ['larkspurRole' => 'fade-up']),
            ], $this->contentRow(['gap' => ['desktop' => '64px', 'mobile' => '40px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'padding' => $this->sectionPadding(),
        ]));
    }

    /* ====================================================================== */
    /* EXTRA BLOCKS */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function pageHeroSection(string $badge, string $lead, string $rest, string $text, bool $asH1 = false, bool $onSurface = true): array
    {
        $heading = $asH1
            ? $this->h1('<em>'.$lead.'</em> '.$rest, 'left', ['larkspurRole' => 'split-heading', 'fontSize' => ['desktop' => 'clamp(2.5rem, 5vw, calc(var(--site-h1-size, 3.25rem) * 1.077))', 'mobile' => '38px']])
            : $this->h2('<em>'.$lead.'</em> '.$rest, 'left', ['larkspurRole' => 'split-heading']);

        return $this->tagSection('page-hero', $this->section('Inner Page Header', [
            $this->row([
                $this->column(7, [$this->eyebrow($badge), $heading], ['gap' => $this->responsive('16px')]),
                $this->column(5, [
                    $this->paragraph($text, DesignTokens::ref('body'), '18px', 'left', ['lineHeight' => $this->responsive('1.556'), 'larkspurRole' => 'fade-up']),
                ], ['justifyContent' => $this->responsive('flex-end')]),
            ], $this->contentRow(['gap' => ['desktop' => '64px', 'mobile' => '20px'], 'alignItems' => $this->responsive('flex-end')]), 'tablet'),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref($onSurface ? 'surface' : 'background')),
            'padding' => [
                'desktop' => ['top' => '120px', 'right' => '24px', 'bottom' => '80px', 'left' => '24px'],
                'mobile' => ['top' => '64px', 'right' => '20px', 'bottom' => '48px', 'left' => '20px'],
            ],
        ]));
    }

    /** @return array<string, mixed> */
    private function statsSection(): array
    {
        $stats = [['7', 'Clinicians across three clinics'], ['4.8', 'Average rating from 910 reviews'], ['1 hr', 'Typical call-back time'], ['6 days', 'Open every week, with late hours']];

        return $this->tagSection('stats', $this->section('Clinic Numbers', [
            $this->row(array_map(fn (array $stat): array => $this->column(3, [
                $this->paragraph($stat[0], DesignTokens::ref('primary'), 'clamp(3rem, 6vw, 4.5rem)', 'left', [
                    'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
                    'lineHeight' => $this->responsive('1'),
                    'letterSpacing' => $this->responsive('-0.12rem'),
                    'larkspurRole' => 'count-up',
                ]),
                $this->paragraph($stat[1], DesignTokens::ref('body'), '16px'),
            ], [
                'gap' => $this->responsive('12px'),
                'padding' => $this->responsive(['top' => '24px', 'left' => '24px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'left' => '1px']),
                'larkspurRole' => 'fade-up',
            ]), $stats), $this->contentRow(['gap' => ['desktop' => '0px', 'mobile' => '16px']]), 'mobile'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function servicesGridSection(): array
    {
        $services = [
            ['thermometer', 'Same-day sick visits', 'Seen the same morning for fevers, infections, rashes and minor injuries.'],
            ['clipboard-check', 'Physicals & check-ups', 'Annual, school and sports physicals with clear next steps.'],
            ['heart-pulse', 'Chronic care', 'Ongoing plans for diabetes, blood pressure, asthma and thyroid.'],
            ['brain', 'Mental health', 'Unhurried support for anxiety, low mood, stress and sleep.'],
            ['syringe', 'Vaccinations', 'Childhood shots, flu vaccines, boosters and travel advice.'],
            ['flask-conical', 'Lab tests', 'On-site bloodwork and screening with results in your portal.'],
        ];

        $cards = array_map(fn (array $service): array => $this->column(4, [[
            ...$this->container('stack', '16px', 'service-card card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->iconWell($service[0]),
                $this->h3($service[1], ['fontSize' => ['desktop' => 'calc(var(--site-h4-size, 1.2rem) * 1.25)', 'mobile' => '22px']]),
                $this->paragraph($service[2], DesignTokens::ref('body'), '16px'),
                $this->ctaButton('Book this visit', '/new-patients', 'text'),
            ],
        ]], ['larkspurRole' => 'fade-up']), $services);

        return $this->tagSection('services-grid', $this->section('Services Grid', [
            $this->sectionHeader('What we offer', 'Everyday care,', 'close to home', 'Six kinds of visits cover almost everything a family needs, and we refer you to trusted specialists for the rest.'),
            $this->row(array_slice($cards, 0, 3), $this->contentRow(['gap' => ['desktop' => '16px', 'mobile' => '16px'], 'margin' => $this->responsive(['bottom' => '16px', 'left' => 'auto', 'right' => 'auto'])]), 'tablet'),
            $this->row(array_slice($cards, 3, 3), $this->contentRow(['gap' => ['desktop' => '16px', 'mobile' => '16px']]), 'tablet'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function widePhotoSection(): array
    {
        return $this->tagSection('wide-photo', $this->section('Wide Photo', [
            $this->row([
                $this->column(12, [
                    $this->image('Larkspur clinic reception with a wooden front desk and armchairs', [
                        'width' => $this->responsive('100%'),
                        'aspectRatio' => ['desktop' => '1328 / 460', 'mobile' => '4 / 3'],
                        'borderRadius' => $this->responsive('2rem'),
                        'objectFit' => 'cover',
                    ], self::ASSETS.'hero.jpg'),
                ], ['larkspurRole' => 'fade-up']),
            ], $this->contentRow()),
        ], [
            'padding' => [
                'desktop' => ['top' => '0px', 'right' => '24px', 'bottom' => '64px', 'left' => '24px'],
                'mobile' => ['top' => '0px', 'right' => '20px', 'bottom' => '40px', 'left' => '20px'],
            ],
        ]));
    }

    /**
     * Photo service cards behind pill filter tabs: "All services" plus one tab per care area.
     *
     * @return array<string, mixed>
     */
    private function servicesCatalogSection(): array
    {
        $services = [
            ['Feeling unwell', 'Colds, flu and fevers', 'sick visit, sore throat', 'Seen the same day when you call before 10 am, or on video from home.', ['Same-day', 'Video'], 'From $90', 'treat-unwell'],
            ['Routine check-ups', 'Annual physical', 'check-up, wellness visit', 'A full yearly review with screenings and time to talk, without being rushed out the door.', ['In clinic'], '$235, or $0 with most plans', 'treat-checkup'],
            ['Routine check-ups', 'Well-child visits', 'pediatric check-up', 'Growth checks, vaccines and school forms for newborns to 18.', ['In clinic'], '$0 with most plans', 'review-2'],
            ['Ongoing conditions', 'High blood pressure', 'hypertension, high BP', 'Regular checks and medication reviews. Most follow-ups can be done by video.', ['In clinic', 'Video'], 'From $90', 'treat-chronic'],
            ['Ongoing conditions', 'Diabetes care', 'blood sugar, A1C', 'A plan you can live with, glucose reviews every three months and a nurse who checks in by text.', ['In clinic', 'Video'], 'From $120', 'review-3'],
            ['Mental health', 'Anxiety and low mood', 'stress, depression, sleep', 'Talk with a clinician who has time for you, with follow-ups by video when that is easier.', ['In clinic', 'Video'], 'From $90', 'treat-mental'],
            ['Vaccinations', 'Flu and COVID shots', 'boosters, walk-in vaccine', 'Walk in at any clinic Monday to Saturday. No appointment needed.', ['Walk-in'], '$0 with most plans', 'treat-vaccine'],
            ['Tests & screening', 'Bloodwork and labs', 'blood test, lab results', 'Drawn on site with results in your portal, usually within three to five days.', ['In clinic'], 'From $35', 'treat-tests'],
        ];

        $card = fn (array $service): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [5, 7],
                'gap' => '20px',
                'stackBelow' => 'mobile',
                'larkspurRole' => 'service-photo-card card',
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '12px', 'right' => '20px', 'bottom' => '12px', 'left' => '12px']),
                'alignItems' => $this->responsive('stretch'),
            ]),
            'children' => [
                $this->image($service[1], [
                    'width' => $this->responsive('100%'),
                    'height' => ['desktop' => '100%', 'mobile' => 'auto'],
                    'minHeight' => $this->responsive('180px'),
                    'aspectRatio' => ['mobile' => '4 / 3'],
                    'borderRadius' => $this->responsive('12px'),
                    'objectFit' => 'cover',
                ], self::ASSETS.$service[6].'.jpg'),
                [
                    ...$this->container('stack', '8px', null, ['padding' => $this->responsive(['top' => '8px', 'bottom' => '4px'])]),
                    'children' => [
                        $this->h3($service[1], ['fontSize' => ['desktop' => '22px', 'mobile' => '20px'], 'lineHeight' => $this->responsive('1.3')]),
                        $this->paragraph('also searched as '.$service[2], DesignTokens::ref('muted'), '13px'),
                        $this->paragraph($service[3], DesignTokens::ref('body'), '15px'),
                        [
                            ...$this->container('row', '8px', null, ['flexWrap' => $this->responsive('wrap'), 'margin' => $this->responsive(['top' => '4px'])]),
                            'children' => array_map(fn (string $chip): array => $this->chip($chip), $service[4]),
                        ],
                        [
                            ...$this->container('row', '12px', 'service-foot', [
                                'justifyContent' => $this->responsive('space-between'),
                                'alignItems' => $this->responsive('center'),
                                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
                                'padding' => $this->responsive(['top' => '12px']),
                                'margin' => $this->responsive(['top' => 'auto']),
                            ]),
                            'children' => [
                                $this->paragraph($service[5], DesignTokens::ref('heading'), '14px'),
                                $this->ctaButton('View service', '/new-patients', 'text'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $grid = fn (array $items): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [6, 6],
                'gap' => '20px',
                'stackBelow' => 'tablet',
                'larkspurRole' => 'service-grid',
            ]),
            'children' => array_map($card, array_values($items)),
        ];

        $areas = array_values(array_unique(array_column($services, 0)));
        $panels = [$grid($services)];

        foreach ($areas as $area) {
            $panels[] = $grid(array_filter($services, fn (array $service): bool => $service[0] === $area));
        }

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'tabs' => array_map(fn (string $label): array => ['id' => null, 'label' => $label], ['All services', ...$areas]),
                'larkspurRole' => 'faq-tabs service-tabs',
            ], [
                'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            ]),
            'children' => $panels,
        ];

        return $this->tagSection('services-catalog', $this->section('Service Catalog', [
            $this->row([$this->column(12, [$tabs])], $this->contentRow([
                'padding' => ['desktop' => ['top' => '48px'], 'mobile' => ['top' => '32px']],
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
            ])),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /** @return array<string, mixed> */
    private function stepsSection(): array
    {
        $steps = [
            ['01', 'Register online', 'Fill in a short form with your details, insurance and current medicines.', ['Takes about ten minutes', 'Save and finish later']],
            ['02', 'Choose a clinician', 'Pick by language, clinic or specialty, or let us match you.', ['Profiles list clinic days', 'Switch any time']],
            ['03', 'Book your first visit', 'A 40-minute new patient visit to get to know you and your history.', ['Text reminder the day before', 'Free to reschedule']],
        ];

        $cards = array_map(fn (array $step): array => $this->column(4, [[
            ...$this->container('stack', '16px', 'step-card card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'boxShadow' => $this->responsive(self::CARD_SHADOW),
                'padding' => $this->responsive(['top' => '32px', 'right' => '28px', 'bottom' => '32px', 'left' => '28px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->paragraph($step[0], DesignTokens::ref('accent'), '48px', 'left', ['fontFamily' => $this->responsive(DesignTokens::ref('heading_font')), 'lineHeight' => $this->responsive('1'), 'letterSpacing' => $this->responsive('-0.09rem')]),
                $this->h3($step[1], ['fontSize' => ['desktop' => 'calc(var(--site-h4-size, 1.2rem) * 1.25)', 'mobile' => '22px']]),
                $this->paragraph($step[2], DesignTokens::ref('body'), '16px'),
                [
                    ...$this->container('stack', '0px', 'tick-list'),
                    'children' => array_map(fn (string $item): array => $this->tickRow($item), $step[3]),
                ],
            ],
        ]], ['larkspurRole' => 'fade-up']), $steps);

        return $this->tagSection('steps', $this->section('New Patient Steps', [
            $this->sectionHeader('How to join', 'Three steps', 'to your first visit', 'Most new patients go from registration to their first appointment within a week.'),
            $this->row($cards, $this->contentRow(['gap' => ['desktop' => '20px', 'mobile' => '16px'], 'alignItems' => $this->responsive('stretch')]), 'tablet'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function spotlightSection(): array
    {
        return $this->tagSection('spotlight', $this->section('Clinician Spotlight', [
            $this->row([
                $this->column(5, [
                    $this->image('Portrait of Dr. Priya Raman', [
                        'borderRadius' => $this->responsive('2rem'),
                        'aspectRatio' => $this->responsive('4 / 5'),
                        'width' => $this->responsive('100%'),
                        'objectFit' => 'cover',
                    ], self::ASSETS.'provider-1.jpg'),
                ], ['larkspurRole' => 'fade-up']),
                $this->column(7, [
                    $this->eyebrow('Clinician spotlight'),
                    $this->h2('<em>“Good care starts</em> with having time to listen.”', 'left', ['larkspurRole' => 'split-heading']),
                    $this->paragraph('Dr. Priya Raman has practised family medicine for fifteen years and leads our Orchard Street clinic. She cares for whole families, from newborn checks to managing long-term conditions in later life.', DesignTokens::ref('body'), '18px', 'left', ['lineHeight' => $this->responsive('1.556')]),
                    [
                        ...$this->container('stack', '0px', 'tick-list'),
                        'children' => [
                            $this->tickRow('Board certified in family medicine'),
                            $this->tickRow('Speaks English and Tamil'),
                            $this->tickRow('Orchard Street: Monday, Wednesday and Friday'),
                        ],
                    ],
                    $this->ctaButton('Book with Dr. Raman', '/new-patients'),
                ], ['gap' => $this->responsive('24px'), 'justifyContent' => $this->responsive('center')]),
            ], $this->contentRow(['gap' => ['desktop' => '64px', 'mobile' => '32px'], 'alignItems' => $this->responsive('center')]), 'tablet'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function hoursSection(): array
    {
        $rows = [
            ['Clinic', 'Mon–Fri', 'Tue & Thu', 'Saturday', true],
            ['Orchard Street', '8 am–5 pm', 'until 7 pm', '9 am–1 pm', false],
            ['Bellweather Lane', '8 am–6 pm', 'until 7 pm', 'Closed', false],
            ['Marlow Park', '8 am–6 pm', 'until 7 pm', '9 am–1 pm', false],
            ['Video visits', '7 am–9 pm', '7 am–9 pm', '7 am–9 pm', false],
        ];

        $table = [
            ...$this->container('stack', '0px', 'hours-table card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'overflow' => $this->responsive('hidden'),
            ]),
            'children' => array_map(function (array $row): array {
                $isHead = $row[4];
                $cell = fn (string $text, bool $strong = false): array => $this->paragraph($text, $isHead || $strong ? DesignTokens::ref('primary') : DesignTokens::ref('heading'), $isHead ? '14px' : '15px', 'left', [
                    'fontFamily' => $this->responsive($isHead || $strong ? DesignTokens::ref('heading_font') : DesignTokens::ref('body_font')),
                    'fontWeight' => $this->responsive($isHead ? '600' : '400'),
                ]);

                return [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 3, 3, 2], 'gap' => '16px', 'stackBelow' => 'none', 'larkspurRole' => 'hours-row'], [
                        'backgroundColor' => $this->responsive($isHead ? DesignTokens::ref('surface') : DesignTokens::ref('background')),
                        'border' => $isHead ? [] : $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
                        'padding' => $this->responsive(['top' => '16px', 'right' => '24px', 'bottom' => '16px', 'left' => '24px']),
                        'alignItems' => $this->responsive('center'),
                    ]),
                    'children' => [$cell($row[0], true), $cell($row[1]), $cell($row[2]), $cell($row[3])],
                ];
            }, $rows),
        ];

        return $this->tagSection('hours', $this->section('Opening Hours', [
            $this->sectionHeader('Opening hours', 'Open six days,', 'late twice a week', 'Same-day sick visits are held every weekday morning. Call before 10 am to get a time.'),
            $this->row([
                $this->column(12, [$table, $this->notice('Holiday hours are posted in the patient portal two weeks ahead.')], ['gap' => $this->responsive('16px')]),
            ], $this->contentRow()),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function contactSection(): array
    {
        $channel = fn (string $icon, string $title, string $text, string $link, string $url): array => [
            ...$this->container('stack', '12px', 'contact-card card', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('card_radius')),
                'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                'height' => $this->responsive('100%'),
            ]),
            'children' => [
                $this->iconWell($icon),
                $this->h4($title),
                $this->paragraph($text, DesignTokens::ref('body'), '14px'),
                $this->ctaButton($link, $url, 'text'),
            ],
        ];

        return $this->tagSection('contact', $this->section('Contact Desk', [
            $this->row([
                $this->column(7, [[
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '16px', 'stackBelow' => 'mobile']),
                    'children' => [
                        $channel('phone', 'Call the front desk', 'Monday to Friday 8 am–6 pm, Saturday 9 am–1 pm.', self::PHONE, self::PHONE_URL),
                        $channel('mail', 'Email us', 'For non-urgent questions. We reply within one working day.', self::EMAIL, 'mailto:'.self::EMAIL),
                        $channel('message-circle', 'Patient portal', 'Message your clinician, see results and request refills.', 'Open the portal', '/contact'),
                        $channel('map-pin', 'Visit a clinic', 'Three clinics across Portland, each a short walk from transit.', 'See locations', '/locations'),
                    ],
                ]]),
                $this->column(5, [[
                    ...$this->container('stack', '20px', 'contact-panel', [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('secondary')),
                        'borderRadius' => $this->responsive('1.5rem'),
                        'padding' => $this->responsive(['top' => '36px', 'right' => '32px', 'bottom' => '36px', 'left' => '32px']),
                        'height' => $this->responsive('100%'),
                    ]),
                    'children' => [
                        $this->eyebrowText('Response times', DesignTokens::ref('on_dark_muted')),
                        $this->h3('We answer every message within one working hour.', ['color' => $this->responsive(DesignTokens::ref('on_dark'))]),
                        $this->paragraph('Lucia and the front desk team check your insurance, book visits and pass clinical questions to your clinician the same day.', DesignTokens::ref('on_dark_muted'), '16px'),
                        $this->personLine('Lucia Ortega', 'Patient care coordinator', true),
                        $this->ctaButton('Book a visit', '/new-patients', 'light'),
                    ],
                ]], ['larkspurRole' => 'fade-up']),
            ], $this->contentRow(['gap' => ['desktop' => '24px', 'mobile' => '20px'], 'alignItems' => $this->responsive('stretch')]), 'tablet'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function ctaBannerSection(): array
    {
        return $this->tagSection('cta-banner', $this->section('Ink CTA Banner', [
            $this->row([
                $this->column(8, [
                    $this->h2('<em>Feeling unwell today?</em> Call before 10 am for a same-day visit.', 'left', ['larkspurRole' => 'split-heading', 'color' => $this->responsive(DesignTokens::ref('on_dark'))]),
                    $this->paragraph('Or book a video visit any day between 7 am and 9 pm.', DesignTokens::ref('on_dark_muted'), '18px'),
                ], ['gap' => $this->responsive('16px')]),
                $this->column(4, [[
                    ...$this->container('row', '12px', null, ['flexWrap' => $this->responsive('wrap'), 'justifyContent' => ['desktop' => 'flex-end', 'mobile' => 'flex-start']]),
                    'children' => [
                        $this->ctaButton('Book a visit', '/new-patients', 'light'),
                        $this->ctaButton('Call '.self::PHONE, self::PHONE_URL, 'ghost-light'),
                    ],
                ]], ['justifyContent' => $this->responsive('center')]),
            ], $this->contentRow([
                'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                'borderRadius' => $this->responsive('2rem'),
                'padding' => ['desktop' => ['top' => '64px', 'right' => '56px', 'bottom' => '64px', 'left' => '56px'], 'mobile' => ['top' => '40px', 'right' => '24px', 'bottom' => '40px', 'left' => '24px']],
                'gap' => ['desktop' => '40px', 'mobile' => '24px'],
                'alignItems' => $this->responsive('center'),
            ]), 'tablet'),
        ], ['padding' => $this->sectionPadding()]));
    }

    /** @return array<string, mixed> */
    private function healthNotesSection(): array
    {
        $posts = [
            ['treat-vaccine', 'Vaccines', 'Flu season checklist for the whole family', '4 min read'],
            ['treat-checkup', 'Check-ups', 'What happens at an annual physical, step by step', '5 min read'],
            ['treat-mental', 'Mental health', 'Small sleep changes that make a real difference', '6 min read'],
        ];

        $cards = array_map(fn (array $post): array => $this->column(4, [[
            ...$this->container('stack', '16px', 'note-card'),
            'children' => [
                $this->image($post[2], ['borderRadius' => $this->responsive(DesignTokens::ref('card_radius')), 'aspectRatio' => $this->responsive('4 / 3'), 'width' => $this->responsive('100%'), 'objectFit' => 'cover'], self::ASSETS.$post[0].'.jpg'),
                [
                    ...$this->container('row', '12px', null, ['alignItems' => $this->responsive('center')]),
                    'children' => [$this->chip($post[1]), $this->paragraph($post[3], DesignTokens::ref('muted'), '13px')],
                ],
                $this->h3($post[2], ['fontSize' => ['desktop' => '22px', 'mobile' => '20px'], 'lineHeight' => $this->responsive('1.3')]),
                $this->ctaButton('Read the note', '/new-patients', 'text'),
            ],
        ]], ['larkspurRole' => 'fade-up']), $posts);

        return $this->tagSection('health-notes', $this->section('Health Notes', [
            $this->sectionHeader('Health notes', 'Advice from', 'our clinicians', 'Short, practical guides written by the people who see you in clinic.'),
            $this->row($cards, $this->contentRow(['gap' => ['desktop' => '20px', 'mobile' => '28px']]), 'tablet'),
        ], ['padding' => $this->sectionPadding(false)]));
    }

    /* ====================================================================== */
    /* PIECES */
    /* ====================================================================== */

    /** @return array<string, mixed> */
    private function sectionHeader(string $eyebrow, string $lead, string $rest, string $text, bool $withRule = false): array
    {
        return $this->row([
            $this->column(7, [
                $this->eyebrow($eyebrow),
                $this->h2('<em>'.$lead.'</em> '.$rest, 'left', ['larkspurRole' => 'split-heading']),
            ], ['gap' => $this->responsive('16px')]),
            $this->column(5, [
                $this->paragraph($text, DesignTokens::ref('body'), '18px', 'left', ['lineHeight' => $this->responsive('1.556'), 'larkspurRole' => 'fade-up']),
            ], ['justifyContent' => $this->responsive('flex-end')]),
        ], $this->contentRow([
            'gap' => ['desktop' => '64px', 'mobile' => '20px'],
            'alignItems' => $this->responsive('flex-end'),
            'margin' => ['desktop' => ['bottom' => '64px', 'left' => 'auto', 'right' => 'auto'], 'mobile' => ['bottom' => '40px', 'left' => 'auto', 'right' => 'auto']],
            'padding' => $withRule ? ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '32px']] : [],
            'border' => $withRule ? $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']) : [],
        ]), 'tablet');
    }

    /** Eyebrow pill: ringed ink dot and label. @return array<string, mixed> */
    private function eyebrow(string $label): array
    {
        return [
            ...$this->container('row', '8px', 'eyebrow', [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'borderRadius' => $this->responsive(DesignTokens::ref('badge_radius')),
                'padding' => $this->responsive(['top' => '4px', 'right' => '12px', 'bottom' => '4px', 'left' => '4px']),
                'width' => $this->responsive('fit-content'),
                'alignItems' => $this->responsive('center'),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => 'circle-dot', 'library' => 'lucide', 'size' => '24px', 'larkspurRole' => 'eyebrow-dot'], [
                    'color' => $this->responsive(DesignTokens::ref('primary')),
                    'backgroundColor' => $this->responsive(self::BAND),
                    'borderRadius' => $this->responsive('999px'),
                ]),
                $this->paragraph($label, DesignTokens::ref('heading'), '14px'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function eyebrowText(string $label, string $color, string $size = '13px', array $style = []): array
    {
        return $this->paragraph(strtoupper($label), $color, $size, 'left', array_merge([
            'letterSpacing' => $this->responsive('0.0525rem'),
            'fontWeight' => $this->responsive('500'),
        ], $style));
    }

    /** @return array<string, mixed> */
    private function chip(string $label, bool $warm = false): array
    {
        return $this->paragraph($label, $warm ? self::SIGNAL_TEXT : DesignTokens::ref('secondary'), '13px', 'left', [
            'backgroundColor' => $this->responsive($warm ? self::SIGNAL_SOFT : self::INK_SOFT),
            'borderRadius' => $this->responsive(DesignTokens::ref('badge_radius')),
            'padding' => $this->responsive(['top' => '3px', 'right' => '10px', 'bottom' => '3px', 'left' => '10px']),
            'width' => $this->responsive('fit-content'),
            'whiteSpace' => $this->responsive('nowrap'),
            'larkspurRole' => $warm ? 'chip chip-warm' : 'chip',
        ]);
    }

    /** @return array<string, mixed> */
    private function statusBadge(string $label, bool $open): array
    {
        return $this->paragraph($label, DesignTokens::ref('heading'), '13px', 'left', [
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
            'borderRadius' => $this->responsive(DesignTokens::ref('badge_radius')),
            'padding' => $this->responsive(['top' => '3px', 'right' => '10px', 'bottom' => '3px', 'left' => '10px']),
            'width' => $this->responsive('fit-content'),
            'larkspurRole' => $open ? 'chip' : 'chip chip-warm',
        ]);
    }

    /** @return array<string, mixed> */
    private function notice(string $text): array
    {
        return $this->paragraph($text, self::SIGNAL_TEXT, '14px', 'left', [
            'backgroundColor' => $this->responsive(self::SIGNAL_SOFT),
            'borderRadius' => $this->responsive('8px'),
            'padding' => $this->responsive(['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px']),
        ]);
    }

    /** @return array<string, mixed> */
    private function iconLine(string $icon, string $text): array
    {
        return [
            ...$this->container('row', '12px', null, ['alignItems' => $this->responsive('flex-start')]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '18px'], ['color' => $this->responsive(DesignTokens::ref('primary')), 'flexShrink' => $this->responsive('0'), 'margin' => $this->responsive(['top' => '1px'])]),
                $this->paragraph($text, DesignTokens::ref('heading'), '14px'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function iconStack(string $icon, string $text): array
    {
        return [
            ...$this->container('stack', '8px'),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '18px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                $this->paragraph($text, DesignTokens::ref('heading'), '14px'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function iconWell(string $icon): array
    {
        return [
            ...$this->container('row', '0px', 'icon-well', [
                'backgroundColor' => $this->responsive(self::INK_SOFT),
                'borderRadius' => $this->responsive('14px'),
                'width' => $this->responsive('48px'),
                'height' => $this->responsive('48px'),
                'alignItems' => $this->responsive('center'),
                'justifyContent' => $this->responsive('center'),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => $icon, 'library' => 'lucide', 'size' => '22px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function tickRow(string $text): array
    {
        return [
            ...$this->container('row', '12px', 'tick-row', [
                'alignItems' => $this->responsive('center'),
                'padding' => $this->responsive(['top' => '12px', 'bottom' => '12px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'top' => '1px']),
            ]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => 'circle-check', 'library' => 'lucide', 'size' => '20px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                $this->paragraph($text, DesignTokens::ref('heading'), '15px'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function personLine(string $title, string $subtitle, bool $onDark = false): array
    {
        return [
            ...$this->container('row', '14px', null, ['alignItems' => $this->responsive('center')]),
            'children' => [
                $this->image('Lucia Ortega, patient care coordinator', [
                    'width' => $this->responsive('56px'),
                    'height' => $this->responsive('56px'),
                    'borderRadius' => $this->responsive('999px'),
                    'flexShrink' => $this->responsive('0'),
                    'objectFit' => 'cover',
                ], self::ASSETS.'coordinator.jpg'),
                [
                    ...$this->container('stack', '2px'),
                    'children' => [
                        $this->h4($title, $onDark ? ['color' => $this->responsive(DesignTokens::ref('on_dark'))] : []),
                        $this->paragraph($subtitle, $onDark ? DesignTokens::ref('on_dark_muted') : DesignTokens::ref('body'), '14px'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Pill button with the round arrow cell (drawn in CSS). Variants: primary, light, outline,
     * ghost-light and text.
     *
     * @return array<string, mixed>
     */
    private function ctaButton(string $label, string $url, string $variant = 'primary'): array
    {
        $style = [
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'fontSize' => $this->responsive('16px'),
            'fontWeight' => $this->responsive(DesignTokens::ref('button_weight')),
            'borderRadius' => $this->responsive(DesignTokens::ref('button_radius')),
        ];

        $style += match ($variant) {
            'light' => [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'color' => $this->responsive(DesignTokens::ref('primary')),
            ],
            'outline' => [
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('primary'), 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            ],
            'ghost-light' => [
                'backgroundColor' => $this->responsive('transparent'),
                'color' => $this->responsive(DesignTokens::ref('on_dark')),
                'border' => $this->responsive(['style' => 'solid', 'color' => 'color-mix(in srgb, var(--site-on-dark, #ffffff) 50%, transparent)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            ],
            'text' => [
                'backgroundColor' => $this->responsive('transparent'),
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'padding' => $this->responsive(['top' => '4px', 'right' => '0px', 'bottom' => '4px', 'left' => '0px']),
            ],
            default => [
                'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                'color' => $this->responsive(DesignTokens::ref('on_primary')),
            ],
        };

        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'size' => 'md',
            'variant' => in_array($variant, ['outline', 'ghost-light'], true) ? 'outline' : ($variant === 'text' ? 'link' : 'primary'),
            'larkspurRole' => 'btn btn-'.$variant,
        ], $style);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h1(string $text, string $align = 'left', array $style = []): array
    {
        return $this->heading('h1', $text, array_merge([
            'fontSize' => ['desktop' => 'clamp(2.75rem, 5.4vw, calc(var(--site-h1-size, 3.25rem) * 1.385))', 'mobile' => '40px'],
            'lineHeight' => $this->responsive('1.11'),
            'letterSpacing' => ['desktop' => 'calc('.DesignTokens::ref('heading_tracking').' * 1)', 'mobile' => '-0.02em'],
            'textAlign' => $this->responsive($align),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h2(string $text, string $align = 'left', array $style = []): array
    {
        return $this->heading('h2', $text, array_merge([
            'fontSize' => ['desktop' => 'clamp(2.25rem, 4vw, calc(var(--site-h2-size, 2.4rem) * 1.25))', 'mobile' => '32px'],
            'lineHeight' => $this->responsive('1.167'),
            'textAlign' => $this->responsive($align),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h3(string $text, array $style = []): array
    {
        return $this->heading('h3', $text, array_merge([
            'fontSize' => ['desktop' => 'calc(var(--site-h3-size, 1.5rem) * 1.333)', 'mobile' => '26px'],
            'lineHeight' => $this->responsive('1.25'),
            'color' => $this->responsive(DesignTokens::ref('primary')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function h4(string $text, array $style = []): array
    {
        return $this->heading('h4', $text, array_merge([
            'fontSize' => $this->responsive('20px'),
            'lineHeight' => $this->responsive('1.4'),
            'color' => $this->responsive(DesignTokens::ref('primary')),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function heading(string $level, string $text, array $style): array
    {
        $role = $style['larkspurRole'] ?? null;
        unset($style['larkspurRole']);

        $props = ['text' => $text, 'level' => $level, 'link' => null];

        if (is_string($role)) {
            $props['larkspurRole'] = $role;
        }

        return $this->blockNode(BlockType::Heading, $props, array_merge([
            'fontFamily' => $this->responsive(DesignTokens::ref('heading_font')),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'fontWeight' => $this->responsive(DesignTokens::ref('heading_weight')),
            'letterSpacing' => ['desktop' => DesignTokens::ref('heading_tracking'), 'mobile' => '-0.02em'],
            'margin' => $this->responsive('0'),
        ], $style));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function paragraph(string $text, string $color, string $size = '16px', string $align = 'left', array $style = []): array
    {
        $role = $style['larkspurRole'] ?? null;
        unset($style['larkspurRole']);

        $props = ['text' => $text];

        if (is_string($role)) {
            $props['larkspurRole'] = $role;
        }

        return $this->blockNode(BlockType::Paragraph, $props, array_merge([
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
        $objectFit = (string) ($style['objectFit'] ?? 'cover');
        unset($style['objectFit']);

        return $this->blockNode(BlockType::Image, [
            'src' => $src,
            'alt' => $alt,
            'objectFit' => $objectFit,
            'loading' => 'lazy',
        ], $style);
    }

    /**
     * A container block (children are added by the caller).
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function container(string $layout, string $gap, ?string $role = null, array $style = []): array
    {
        $props = ['layout' => $layout, 'gap' => $gap];

        if ($role !== null) {
            $props['larkspurRole'] = $role;
        }

        return $this->blockNode(BlockType::Container, $props, $style);
    }

    /**
     * Default row style: centred at the theme content width.
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function contentRow(array $style = []): array
    {
        return array_merge([
            'maxWidth' => $this->responsive(DesignTokens::ref('content_width')),
        ], array_filter($style, fn (mixed $value): bool => $value !== []));
    }

    /** @return array<string, mixed> */
    private function sectionPadding(bool $withTop = true): array
    {
        return [
            'desktop' => ['top' => $withTop ? DesignTokens::ref('section_padding') : '0px', 'right' => '24px', 'bottom' => DesignTokens::ref('section_padding'), 'left' => '24px'],
            'mobile' => ['top' => $withTop ? '64px' : '0px', 'right' => '20px', 'bottom' => '64px', 'left' => '20px'],
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
                'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
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
        $role = $style['larkspurRole'] ?? null;
        unset($style['larkspurRole']);

        $column = [
            'id' => $this->nextId(),
            'span' => ['desktop' => $span, 'tablet' => $span >= 6 ? 12 : 6, 'mobile' => 12],
            'style' => $style,
            'blocks' => $blocks,
        ];

        // Columns have no props: a role on a column is carried by wrapping its blocks.
        if (is_string($role)) {
            $column['blocks'] = [[
                ...$this->container('stack', '0px', $role, ['height' => $this->responsive('100%')]),
                'children' => $blocks,
            ]];
        }

        return $column;
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

        return '01J9MRK000'.$suffix;
    }
}
