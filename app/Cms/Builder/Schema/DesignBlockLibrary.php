<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

use Illuminate\Support\Str;

/**
 * Ready-made sections a user can drop straight onto a page.
 *
 * The palette hands over one empty block at a time, which is the right tool
 * when you know exactly what you are building and the wrong one when you are
 * staring at a blank page. These are whole sections — a hero, a pricing table,
 * a testimonial row — already composed, styled and worded, so the first act on
 * a new page is editing something rather than assembling it.
 *
 * Every definition is generated fresh on each call: the ids must be unique per
 * insertion, or dropping the same block twice would produce two nodes claiming
 * the same id and the tree operations would update both at once.
 *
 * Definitions live in PHP rather than the database because they are code — they
 * ship with the release, they reference block types this version understands,
 * and a customer editing one would be editing the product. Saved sections that
 * a *workspace* creates are a different thing entirely, and those are rows in
 * `templates`.
 *
 * Prop names here must match BlockRegistry exactly. A block's props are merged
 * over the registry defaults, so a wrong key does not error — it lands as an
 * ignored extra prop and the block renders its default text instead, which is
 * a silent, invisible failure. `text` not `html`; `label`/`url` not
 * `text`/`href`; `authorName` not `author`; `submitLabel` not `buttonText`.
 * Equally, never pass an empty list for a collection prop that the registry
 * fills in (`plans`, `items`, `networks`) — that overwrites usable defaults
 * with nothing and renders a placeholder.
 */
final class DesignBlockLibrary
{
    public const CATEGORY_HERO = 'hero';

    public const CATEGORY_CONTENT = 'content';

    public const CATEGORY_SOCIAL_PROOF = 'social_proof';

    public const CATEGORY_CONVERSION = 'conversion';

    public const CATEGORY_COMMERCE = 'commerce';

    public const CATEGORY_MEDIA = 'media';

    public const CATEGORY_SCHEDULING = 'scheduling';

    public const CATEGORY_STRUCTURE = 'structure';

    /** Sections built around a funnel step: offers, upsells, confirmations. */
    public const CATEGORY_FUNNEL = 'funnel';

    private const SUCCESS = '#16a34a';

    private const AMBER = '#f59e0b';

    /* ------------------------------------------------------------- palette */

    private const INK = '#0f172a';

    private const INK_SOFT = '#1e293b';

    private const BODY = '#475569';

    private const MUTED = '#64748b';

    private const SUBTLE = '#94a3b8';

    private const LINE = '#e2e8f0';

    private const SURFACE = '#f8fafc';

    private const PRIMARY = '#4f46e5';

    private const ON_DARK = '#cbd5e1';

    /**
     * Every design block, with a fresh set of ids.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $blocks = [
            /* hero */
            $this->heroCentred(),
            $this->heroSplit(),
            $this->heroVideo(),
            $this->heroCountdown(),

            /* content */
            $this->featureTrio(),
            $this->featureCards(),
            $this->textWithImage(),
            $this->imageWithText(),
            $this->statsBand(),
            $this->howItWorks(),

            /* social proof */
            $this->testimonialRow(),
            $this->testimonialSpotlight(),
            $this->logoStrip(),
            $this->ratingBand(),

            /* conversion */
            $this->ctaBanner(),

            /* commerce */
            $this->pricingTable(),
            $this->guaranteeBand(),

            /* media */
            $this->videoSection(),
            $this->galleryGrid(),

            /* scheduling */

            /* structure */
            $this->navbarFloatingPill(),
            $this->navbarAnnouncement(),
            $this->navbarDark(),
            $this->navbar(),
            $this->faqSection(),
            $this->footerColumns(),

            /* funnel steps */
            $this->funnelStepHeader(),
            $this->vslHero(),
            $this->bonusStack(),
            $this->scarcityBar(),
            $this->offerCard(),
            $this->upsellOffer(),
            $this->upsellProductSpotlight(),
            $this->upsellBundleChoice(),
            $this->orderBumpCard(),
            $this->downsellOffer(),
            $this->orderConfirmation(),
            $this->leadDeliveryConfirmation(),
            $this->bookingThankYou(),
            $this->webinarConfirmation(),

            /* buttons and calls to action */
            $this->buttonDesignShowcase(),
            $this->checkoutCtaPanel(),
            $this->stickyCtaBand(),
        ];

        // Modrix CMS builds single pages: funnel-step designs (upsells, order bumps…) are left out.
        $blocks = array_values(array_filter($blocks, static fn (array $block): bool => $block['category'] !== self::CATEGORY_FUNNEL));

        return array_map(static fn (array $block): array => [...$block, 'document' => TemplatePresentation::document($block['document'])], $blocks);
    }

    /**
     * One block by key, with fresh ids, or null when the key is unknown.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        foreach ($this->all() as $block) {
            if ($block['key'] === $key) {
                return $block;
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function categories(): array
    {
        return [
            ['key' => self::CATEGORY_HERO, 'label' => 'Hero'],
            ['key' => self::CATEGORY_CONTENT, 'label' => 'Content'],
            ['key' => self::CATEGORY_SOCIAL_PROOF, 'label' => 'Social proof'],
            ['key' => self::CATEGORY_CONVERSION, 'label' => 'Conversion'],
            ['key' => self::CATEGORY_COMMERCE, 'label' => 'Commerce'],
            ['key' => self::CATEGORY_MEDIA, 'label' => 'Media'],
            ['key' => self::CATEGORY_STRUCTURE, 'label' => 'Structure'],
        ];
    }

    /**
     * The keys that make up a complete, ready-to-edit landing page, in order.
     *
     * The ready-made page template is assembled from these rather than from a
     * second copy of the same markup, so a fix to a block is a fix to the
     * template too.
     *
     * @return list<string>
     */
    public function starterPageKeys(): array
    {
        return [
            'navbar',
            'hero-split',
            'logo-strip',
            'feature-trio',
            'how-it-works',
            'text-with-image',
            'stats-band',
            'testimonial-row',
            'pricing-table',
            'faq-section',
            'optin-cta',
            'footer-columns',
        ];
    }

    /* ----------------------------------------------------------------- hero */

    /**
     * @return array<string, mixed>
     */
    private function heroCentred(): array
    {
        return $this->block(
            key: 'hero-centred',
            name: 'Centred hero',
            category: self::CATEGORY_HERO,
            description: 'A headline, a line of supporting copy and one call to action.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('96px', '24px', '64px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Build funnels that actually convert', 'h1', [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('52px', '32px'),
                            'lineHeight' => $this->responsive('1.12'),
                        ]),
                        $this->paragraph(
                            'Launch a landing page, capture leads and follow up automatically — without stitching five tools together.',
                            [
                                'color' => $this->responsive(self::ON_DARK),
                                'fontSize' => $this->font('19px', '16px'),
                                'maxWidth' => $this->responsive('560px'),
                                'margin' => $this->responsive(['top' => '20px', 'right' => 'auto', 'bottom' => '32px', 'left' => 'auto']),
                            ],
                        ),
                        $this->button('Start free', 'lg'),
                        $this->paragraph('No card needed. Cancel in two clicks.', [
                            'color' => $this->responsive(self::SUBTLE),
                            'fontSize' => $this->responsive('14px'),
                            'margin' => $this->responsive(['top' => '16px']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroSplit(): array
    {
        return $this->block(
            key: 'hero-split',
            name: 'Split hero',
            category: self::CATEGORY_HERO,
            description: 'Copy on the left, an image on the right. Stacks on mobile.',
            sections: [$this->section(
                style: ['padding' => $this->pad('80px', '24px', '56px')],
                rows: [$this->row([
                    $this->column(7, [
                        $this->heading('Everything that happens after the click', 'h1', [
                            'fontSize' => $this->font('44px', '30px'),
                            'lineHeight' => $this->responsive('1.15'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->paragraph(
                            'Pages, forms, CRM, email and automation in one place, so a lead never falls through a gap between tools.',
                            [
                                'fontSize' => $this->font('19px', '16px'),
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '18px', 'bottom' => '28px']),
                            ],
                        ),
                        $this->button('See how it works', 'lg'),
                    ]),
                    $this->column(5, [
                        $this->image('A screenshot of the funnel builder', [
                            'borderRadius' => $this->responsive('14px'),
                            'boxShadow' => $this->responsive('0 18px 40px rgba(15,23,42,0.12)'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroVideo(): array
    {
        return $this->block(
            key: 'hero-video',
            name: 'Video hero',
            category: self::CATEGORY_HERO,
            description: 'A short headline above an embedded demo video.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('72px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Watch it built in four minutes', 'h1', [
                                'fontSize' => $this->font('42px', '28px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('No slides. A real funnel, from blank page to first lead.', [
                                'color' => $this->responsive(self::MUTED),
                                'fontSize' => $this->font('18px', '16px'),
                                'margin' => $this->responsive(['top' => '14px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::Video, [
                                'provider' => 'youtube',
                                'url' => null,
                                'controls' => true,
                                'aspectRatio' => '16:9',
                            ], [
                                'borderRadius' => $this->responsive('14px'),
                                'maxWidth' => $this->responsive('880px'),
                                'margin' => $this->responsive(['top' => '32px', 'right' => 'auto', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroCountdown(): array
    {
        return $this->block(
            key: 'hero-countdown',
            name: 'Hero with countdown',
            category: self::CATEGORY_HERO,
            description: 'A deadline above the headline, for a launch or a closing offer.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::PRIMARY),
                    'padding' => $this->pad('72px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->paragraph('Enrolment closes in', [
                            'color' => $this->responsive('#e0e7ff'),
                            'fontSize' => $this->responsive('13px'),
                            'textTransform' => $this->responsive('uppercase'),
                            'letterSpacing' => $this->responsive('0.1em'),
                        ]),
                        $this->blockNode(BlockType::Countdown, [
                            'mode' => 'fixed',
                            'endsAt' => null,
                            'showLabels' => true,
                            'expiredAction' => 'message',
                            'expiredMessage' => 'Enrolment has closed. Join the waiting list below.',
                        ], [
                            'color' => $this->responsive('#ffffff'),
                            'margin' => $this->responsive(['top' => '12px', 'bottom' => '28px']),
                        ]),
                        $this->heading('Doors close Friday at midnight', 'h1', [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('40px', '28px'),
                        ]),
                        $this->button('Claim my seat', 'lg', variant: 'secondary', style: [
                            'margin' => $this->responsive(['top' => '24px']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /* -------------------------------------------------------------- content */

    /**
     * @return array<string, mixed>
     */
    private function featureTrio(): array
    {
        $feature = fn (string $icon, string $heading, string $copy): array => $this->column(4, [
            $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '28px'], [
                'color' => $this->responsive(self::PRIMARY),
            ]),
            $this->heading($heading, 'h3', [
                'fontSize' => $this->responsive('20px'),
                'color' => $this->responsive(self::INK),
            ]),
            $this->paragraph($copy, [
                'color' => $this->responsive(self::BODY),
                'lineHeight' => $this->responsive('1.6'),
            ]),
        ]);

        return $this->block(
            key: 'feature-trio',
            name: 'Three features',
            category: self::CATEGORY_CONTENT,
            description: 'Three columns of icon, heading and a short paragraph.',
            sections: [$this->section(
                style: ['padding' => $this->pad('72px', '24px', '48px')],
                rows: [$this->row([
                    $feature('zap', 'Build it fast', 'Drag sections into place and edit the words. No developer, no ticket.'),
                    $feature('mail', 'Capture everything', 'Every form submission becomes a contact with its full history attached.'),
                    $feature('check', 'Follow up on its own', 'Automations pick up where the page leaves off, day or night.'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function featureCards(): array
    {
        return $this->block(
            key: 'feature-cards',
            name: 'Feature card grid',
            category: self::CATEGORY_CONTENT,
            description: 'A heading over a six-card grid of capabilities.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('72px', '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Everything in the box', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'zap', 'title' => 'Page builder', 'body' => 'Drag-and-drop sections with real responsive control.'],
                                    ['icon' => 'mail', 'title' => 'Email', 'body' => 'Broadcasts and sequences from the same contact list.'],
                                    ['icon' => 'check', 'title' => 'Automations', 'body' => 'Trigger on any event the platform emits.'],
                                    ['icon' => 'shield', 'title' => 'Custom domains', 'body' => 'Your domain, your certificate, issued automatically.'],
                                    ['icon' => 'globe', 'title' => 'Analytics', 'body' => 'Attribution from first click to paid order.'],
                                    ['icon' => 'heart', 'title' => 'A/B testing', 'body' => 'Split traffic across variants and keep the winner.'],
                                ],
                                'columns' => 3,
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function textWithImage(): array
    {
        return $this->block(
            key: 'text-with-image',
            name: 'Text beside image',
            category: self::CATEGORY_CONTENT,
            description: 'A two-column explainer with room for a screenshot on the right.',
            sections: [$this->section(
                style: ['padding' => $this->pad('72px', '24px', '48px')],
                rows: [$this->row([
                    $this->column(7, [
                        $this->heading('One place for the whole journey', 'h2', [
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->paragraph(
                            'From the first ad click to the receipt, every step is recorded against the same contact — so your reporting is not a guess.',
                            [
                                'color' => $this->responsive(self::BODY),
                                'fontSize' => $this->font('18px', '16px'),
                                'lineHeight' => $this->responsive('1.65'),
                                'margin' => $this->responsive(['top' => '16px']),
                            ],
                        ),
                        $this->button('Read the walkthrough', 'md', variant: 'outline', style: [
                            'margin' => $this->responsive(['top' => '24px']),
                        ]),
                    ]),
                    $this->column(5, [
                        $this->image('The contact timeline', [
                            'borderRadius' => $this->responsive('14px'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function imageWithText(): array
    {
        return $this->block(
            key: 'image-with-text',
            name: 'Image beside text',
            category: self::CATEGORY_CONTENT,
            description: 'The mirror of "text beside image", for alternating down a page.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('72px', '24px', '48px'),
                ],
                rows: [$this->row([
                    $this->column(5, [
                        $this->image('An automation on the canvas', [
                            'borderRadius' => $this->responsive('14px'),
                        ]),
                    ]),
                    $this->column(7, [
                        $this->heading('Automations you can read at a glance', 'h2', [
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->paragraph(
                            'Every branch is a condition on data you already hold. Open a run and see exactly which path a contact took, and why.',
                            [
                                'color' => $this->responsive(self::BODY),
                                'fontSize' => $this->font('18px', '16px'),
                                'lineHeight' => $this->responsive('1.65'),
                                'margin' => $this->responsive(['top' => '16px']),
                            ],
                        ),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function statsBand(): array
    {
        $stat = fn (string $figure, string $label): array => $this->column(3, [
            $this->heading($figure, 'h3', [
                'fontSize' => $this->font('40px', '32px'),
                'color' => $this->responsive('#ffffff'),
                'textAlign' => $this->responsive('center'),
            ]),
            $this->paragraph($label, [
                'color' => $this->responsive(self::SUBTLE),
                'fontSize' => $this->responsive('14px'),
                'textAlign' => $this->responsive('center'),
            ]),
        ]);

        return $this->block(
            key: 'stats-band',
            name: 'Stats band',
            category: self::CATEGORY_CONTENT,
            description: 'Four numbers on a dark strip — the proof line under a hero.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK_SOFT),
                    'padding' => $this->pad('48px', '24px', '40px'),
                ],
                rows: [$this->row([
                    $stat('12,400', 'Funnels published'),
                    $stat('3.1M', 'Leads captured'),
                    $stat('99.98%', 'Uptime last year'),
                    $stat('4.9/5', 'Average rating'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function howItWorks(): array
    {
        $step = fn (int $number, string $title, string $copy): array => $this->column(4, [
            $this->heading((string) $number, 'h4', [
                'fontSize' => $this->responsive('14px'),
                'color' => $this->responsive(self::PRIMARY),
                'letterSpacing' => $this->responsive('0.14em'),
                'textTransform' => $this->responsive('uppercase'),
            ]),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('21px'),
                'color' => $this->responsive(self::INK),
            ]),
            $this->paragraph($copy, [
                'color' => $this->responsive(self::BODY),
                'lineHeight' => $this->responsive('1.6'),
            ]),
        ]);

        return $this->block(
            key: 'how-it-works',
            name: 'How it works',
            category: self::CATEGORY_CONTENT,
            description: 'Three numbered steps with a heading above them.',
            sections: [$this->section(
                style: ['padding' => $this->pad('72px', '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Live in three steps', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('Most people finish the first funnel in an afternoon.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '24px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $step(1, 'Pick a template', 'Start from a funnel that already has the steps your goal needs.'),
                        $step(2, 'Edit the words', 'Click any headline on the canvas and type. Nothing else moves.'),
                        $step(3, 'Point a domain at it', 'Add your domain, wait for DNS, and the certificate arrives on its own.'),
                    ]),
                ],
            )],
        );
    }

    /* --------------------------------------------------------- social proof */

    /**
     * @return array<string, mixed>
     */
    private function testimonialRow(): array
    {
        $quote = fn (string $text, string $who, string $role): array => $this->column(4, [
            $this->blockNode(BlockType::Testimonial, [
                'quote' => $text,
                'authorName' => $who,
                'authorTitle' => $role,
                'rating' => 5,
                'layout' => 'card',
            ]),
        ]);

        return $this->block(
            key: 'testimonial-row',
            name: 'Three testimonials',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'A row of quotes with attribution and a star rating.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('72px', '24px', '48px'),
                ],
                rows: [$this->row([
                    $quote('We replaced four tools with this and our follow-up finally happens.', 'Priya N.', 'Agency owner'),
                    $quote('The funnel builder paid for itself in the first fortnight.', 'Tom H.', 'Business coach'),
                    $quote('Setup took an afternoon, not the quarter we had budgeted.', 'Leila M.', 'Head of ecommerce'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function testimonialSpotlight(): array
    {
        return $this->block(
            key: 'testimonial-spotlight',
            name: 'Testimonial spotlight',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'One long quote given the whole width, for your best result.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('80px', '24px', '56px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::Testimonial, [
                            'quote' => 'We went from a spreadsheet and a prayer to a funnel that books calls while we sleep. Revenue per lead is up 41% on the same ad spend.',
                            'authorName' => 'Dana Whitfield',
                            'authorTitle' => 'Founder, Northwind Studio',
                            'rating' => 5,
                            'layout' => 'bare',
                        ], [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('26px', '19px'),
                            'lineHeight' => $this->responsive('1.5'),
                            'maxWidth' => $this->responsive('820px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function logoStrip(): array
    {
        return $this->block(
            key: 'logo-strip',
            name: 'Logo strip',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'A quiet row of customer logos to sit under the hero.',
            sections: [$this->section(
                style: ['padding' => $this->pad('40px', '24px', '32px')],
                rows: [$this->row([
                    $this->column(12, [
                        $this->paragraph('Trusted by teams at', [
                            'textAlign' => $this->responsive('center'),
                            'color' => $this->responsive(self::SUBTLE),
                            'fontSize' => $this->responsive('13px'),
                            'textTransform' => $this->responsive('uppercase'),
                            'letterSpacing' => $this->responsive('0.1em'),
                            'margin' => $this->responsive(['bottom' => '20px']),
                        ]),
                        // Left empty on purpose: the renderer shows an "add
                        // images" placeholder, which is the instruction.
                        $this->blockNode(BlockType::Gallery, ['columns' => 5, 'gap' => '32px', 'lightbox' => false]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function ratingBand(): array
    {
        return $this->block(
            key: 'rating-band',
            name: 'Rating band',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'A single line of stars and a review count, for just under a button.',
            sections: [$this->section(
                style: [
                    'padding' => $this->pad('28px', '24px', '24px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::Testimonial, [
                            'quote' => 'Rated 4.9 out of 5 across 812 reviews.',
                            'authorName' => 'G2 and Capterra',
                            'authorTitle' => 'Verified reviews',
                            'rating' => 5,
                            'layout' => 'bare',
                        ], [
                            'color' => $this->responsive(self::MUTED),
                            'fontSize' => $this->responsive('15px'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /* ----------------------------------------------------------- conversion */

    /**
     * @return array<string, mixed>
     */
    private function ctaBanner(): array
    {
        return $this->block(
            key: 'cta-banner',
            name: 'CTA banner',
            category: self::CATEGORY_CONVERSION,
            description: 'One line and one button, for the bottom of a long page.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('56px', '24px', '40px'),
                    'borderRadius' => $this->responsive('16px'),
                ],
                rows: [$this->row([
                    $this->column(8, [
                        $this->heading('Ready to build your first funnel?', 'h2', [
                            'fontSize' => $this->font('30px', '24px'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->paragraph('Free for fourteen days. Your pages stay yours if you leave.', [
                            'color' => $this->responsive(self::MUTED),
                            'margin' => $this->responsive(['top' => '10px']),
                        ]),
                    ]),
                    $this->column(4, [
                        $this->button('Start building', 'lg', style: [
                            'textAlign' => $this->responsive('right'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /* ------------------------------------------------------------- commerce */

    /**
     * @return array<string, mixed>
     */
    private function pricingTable(): array
    {
        return $this->block(
            key: 'pricing-table',
            name: 'Pricing table',
            category: self::CATEGORY_COMMERCE,
            description: 'Three plans side by side with the middle one highlighted.',
            sections: [$this->section(
                style: ['padding' => $this->pad('80px', '24px', '56px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Simple pricing', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('Every plan includes unlimited pages and your own domain.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::PricingTable, [
                                'plans' => [
                                    [
                                        'name' => 'Starter',
                                        'price' => '$29',
                                        'interval' => 'month',
                                        'features' => ['3 funnels', '2,500 contacts', 'Email support'],
                                        'buttonLabel' => 'Choose Starter',
                                        'buttonUrl' => null,
                                        'highlighted' => false,
                                    ],
                                    [
                                        'name' => 'Growth',
                                        'price' => '$79',
                                        'interval' => 'month',
                                        'features' => ['Unlimited funnels', '25,000 contacts', 'Automations', 'A/B testing'],
                                        'buttonLabel' => 'Choose Growth',
                                        'buttonUrl' => null,
                                        'highlighted' => true,
                                    ],
                                    [
                                        'name' => 'Scale',
                                        'price' => '$199',
                                        'interval' => 'month',
                                        'features' => ['Everything in Growth', '100,000 contacts', 'Team roles', 'Priority support'],
                                        'buttonLabel' => 'Choose Scale',
                                        'buttonUrl' => null,
                                        'highlighted' => false,
                                    ],
                                ],
                                'columns' => 3,
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function guaranteeBand(): array
    {
        return $this->block(
            key: 'guarantee-band',
            name: 'Guarantee band',
            category: self::CATEGORY_COMMERCE,
            description: 'A risk-reversal strip to sit directly under a buy button.',
            sections: [$this->section(
                style: ['padding' => $this->pad('40px', '24px', '32px')],
                rows: [$this->row([
                    $this->column(2, [
                        $this->blockNode(BlockType::Icon, ['name' => 'shield', 'size' => '40px'], [
                            'color' => $this->responsive(self::PRIMARY),
                        ]),
                    ]),
                    $this->column(10, [
                        $this->heading('Thirty days, no questions asked', 'h3', [
                            'fontSize' => $this->responsive('20px'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->paragraph(
                            'Try it properly. If it has not earned its price back within a month, reply to your receipt and we refund the whole thing.',
                            [
                                'color' => $this->responsive(self::BODY),
                                'margin' => $this->responsive(['top' => '8px']),
                            ],
                        ),
                    ]),
                ])],
            )],
        );
    }

    /* ---------------------------------------------------------------- media */

    /**
     * @return array<string, mixed>
     */
    private function videoSection(): array
    {
        return $this->block(
            key: 'video-section',
            name: 'Video section',
            category: self::CATEGORY_MEDIA,
            description: 'A titled video with a call to action underneath it.',
            sections: [$this->section(
                style: [
                    'padding' => $this->pad('72px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('See it on a real funnel', 'h2', [
                            'fontSize' => $this->font('32px', '25px'),
                            'color' => $this->responsive(self::INK),
                        ]),
                        $this->blockNode(BlockType::Video, [
                            'provider' => 'youtube',
                            'url' => null,
                            'controls' => true,
                            'aspectRatio' => '16:9',
                        ], [
                            'borderRadius' => $this->responsive('14px'),
                            'maxWidth' => $this->responsive('820px'),
                            'margin' => $this->responsive(['top' => '28px', 'right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
                        ]),
                        $this->button('Start my free trial', 'lg'),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function galleryGrid(): array
    {
        return $this->block(
            key: 'gallery-grid',
            name: 'Gallery grid',
            category: self::CATEGORY_MEDIA,
            description: 'A three-across image grid with a lightbox.',
            sections: [$this->section(
                style: ['padding' => $this->pad('64px', '24px', '44px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Built with TheFunneller', 'h2', [
                                'fontSize' => $this->font('32px', '25px'),
                                'color' => $this->responsive(self::INK),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '28px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::Gallery, ['columns' => 3, 'gap' => '16px', 'lightbox' => true]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /* ----------------------------------------------------------- scheduling */

    /* ------------------------------------------------------------ structure */

    /** @return array<string, mixed> */
    private function navbarFloatingPill(): array
    {
        return $this->block(
            key: 'navbar-floating-pill',
            name: 'Navigation · Floating pill',
            category: self::CATEGORY_STRUCTURE,
            description: 'A friendly rounded header with centred links and a high-contrast call to action.',
            sections: [$this->section(
                width: 'full',
                sticky: true,
                style: [
                    'backgroundColor' => $this->responsive('transparent'),
                    'padding' => ['desktop' => ['top' => '10px', 'right' => '20px', 'bottom' => '0px', 'left' => '20px'], 'mobile' => ['top' => '8px', 'right' => '10px', 'bottom' => '0px', 'left' => '10px']],
                ],
                rows: [$this->row([
                    $this->column(3, [$this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true], ['fontFamily' => $this->responsive('Parkinsans, sans-serif'), 'fontWeight' => $this->responsive('700')])]),
                    $this->column(6, [$this->blockNode(BlockType::Navigation, [
                        'items' => [['label' => 'Home', 'url' => '#'], ['label' => 'Programs', 'url' => '#programs'], ['label' => 'About', 'url' => '#about'], ['label' => 'Contact', 'url' => '#contact']],
                        'orientation' => 'horizontal', 'mobileBehaviour' => 'drawer',
                    ], ['color' => $this->responsive('#191818'), 'fontFamily' => $this->responsive('Parkinsans, sans-serif'), 'fontSize' => $this->responsive('15px'), 'fontWeight' => $this->responsive('600'), 'justifyContent' => $this->responsive('center')])]),
                    $this->column(3, [$this->button('Apply now', 'sm', style: ['backgroundColor' => $this->responsive('#191818'), 'color' => $this->responsive('#fffEEa'), 'borderRadius' => $this->responsive('999px'), 'textAlign' => $this->responsive('right')])]),
                ], [
                    'alignItems' => $this->responsive('center'),
                    'gap' => $this->responsive('24px'),
                    'backgroundColor' => $this->responsive('#fffEEa'),
                    'padding' => ['desktop' => ['top' => '12px', 'right' => '16px', 'bottom' => '12px', 'left' => '20px'], 'mobile' => ['top' => '10px', 'right' => '12px', 'bottom' => '10px', 'left' => '14px']],
                    'borderRadius' => $this->responsive('22px'),
                    'boxShadow' => $this->responsive('0 10px 35px rgba(25,24,24,.12)'),
                ])],
            )],
        );
    }

    /** @return array<string, mixed> */
    private function navbarAnnouncement(): array
    {
        return $this->block(
            key: 'navbar-announcement',
            name: 'Navigation · Announcement',
            category: self::CATEGORY_STRUCTURE,
            description: 'A two-tier header with an editable announcement, navigation and warm pill button.',
            sections: [$this->section(
                width: 'full',
                sticky: true,
                style: ['backgroundColor' => $this->responsive('#f7f7f2')],
                rows: [
                    $this->row([$this->column(12, [$this->paragraph('Now enrolling for the next intake', ['color' => $this->responsive('#fffEEa'), 'fontSize' => $this->responsive('13px'), 'fontWeight' => $this->responsive('600'), 'textAlign' => $this->responsive('center')])])], ['backgroundColor' => $this->responsive('#2f5b46'), 'padding' => $this->responsive(['top' => '8px', 'right' => '20px', 'bottom' => '8px', 'left' => '20px'])]),
                    $this->row([
                        $this->column(3, [$this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true])]),
                        $this->column(6, [$this->blockNode(BlockType::Navigation, ['items' => [['label' => 'Discover', 'url' => '#'], ['label' => 'Programs', 'url' => '#programs'], ['label' => 'Stories', 'url' => '#stories'], ['label' => 'FAQ', 'url' => '#faq']], 'orientation' => 'horizontal', 'mobileBehaviour' => 'drawer'], ['color' => $this->responsive('#191818'), 'fontWeight' => $this->responsive('600'), 'justifyContent' => $this->responsive('center')])]),
                        $this->column(3, [$this->button('Book a tour', 'sm', style: ['backgroundColor' => $this->responsive('#f9b000'), 'color' => $this->responsive('#191818'), 'borderRadius' => $this->responsive('999px'), 'textAlign' => $this->responsive('right')])]),
                    ], ['alignItems' => $this->responsive('center'), 'padding' => $this->responsive(['top' => '14px', 'right' => '24px', 'bottom' => '14px', 'left' => '24px'])]),
                ],
            )],
        );
    }

    /** @return array<string, mixed> */
    private function navbarDark(): array
    {
        return $this->block(
            key: 'navbar-dark',
            name: 'Navigation · Dark contrast',
            category: self::CATEGORY_STRUCTURE,
            description: 'A compact dark header with bright navigation and a saffron conversion button.',
            sections: [$this->section(
                width: 'full', sticky: true,
                style: ['backgroundColor' => $this->responsive('#191818'), 'padding' => ['desktop' => ['top' => '14px', 'right' => '24px', 'bottom' => '14px', 'left' => '24px'], 'mobile' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px']]],
                rows: [$this->row([
                    $this->column(4, [$this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true], ['color' => $this->responsive('#fffEEa'), 'fontWeight' => $this->responsive('700')])]),
                    $this->column(5, [$this->blockNode(BlockType::Navigation, ['items' => [['label' => 'Offer', 'url' => '#offer'], ['label' => 'Results', 'url' => '#results'], ['label' => 'How it works', 'url' => '#process']], 'orientation' => 'horizontal', 'mobileBehaviour' => 'drawer'], ['color' => $this->responsive('#fffEEa'), 'fontWeight' => $this->responsive('600'), 'justifyContent' => $this->responsive('center')])]),
                    $this->column(3, [$this->button('Get started', 'sm', style: ['backgroundColor' => $this->responsive('#f9b000'), 'color' => $this->responsive('#191818'), 'borderRadius' => $this->responsive('999px'), 'textAlign' => $this->responsive('right')])]),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('20px')])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function navbar(): array
    {
        return $this->block(
            key: 'navbar',
            name: 'Navigation bar',
            category: self::CATEGORY_STRUCTURE,
            description: 'A logo, page links and one button, sticky to the top.',
            sections: [$this->section(
                width: 'full',
                sticky: true,
                style: [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '24px', 'bottom' => '16px', 'left' => '24px']),
                    'border' => $this->responsive(['style' => 'solid', 'color' => self::LINE, 'bottom' => '1px']),
                ],
                rows: [$this->row([
                    $this->column(3, [
                        $this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true]),
                    ]),
                    $this->column(6, [
                        $this->blockNode(BlockType::Navigation, [
                            'items' => [
                                ['label' => 'Features', 'url' => '#features'],
                                ['label' => 'Pricing', 'url' => '#pricing'],
                                ['label' => 'Reviews', 'url' => '#reviews'],
                                ['label' => 'FAQ', 'url' => '#faq'],
                            ],
                            'orientation' => 'horizontal',
                            'mobileBehaviour' => 'drawer',
                        ], [
                            'color' => $this->responsive(self::INK_SOFT),
                            'justifyContent' => $this->responsive('center'),
                        ]),
                    ]),
                    $this->column(3, [
                        $this->button('Start free', 'sm', style: [
                            'textAlign' => $this->responsive('right'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function faqSection(): array
    {
        return $this->block(
            key: 'faq-section',
            name: 'FAQ',
            category: self::CATEGORY_STRUCTURE,
            description: 'A heading and a collapsible list of questions.',
            sections: [$this->section(
                style: ['padding' => $this->pad('72px', '24px', '48px')],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Questions people ask', 'h2', [
                            'textAlign' => $this->responsive('center'),
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(self::INK),
                            'margin' => $this->responsive(['bottom' => '28px']),
                        ]),
                        $this->blockNode(BlockType::Faq, [
                            'items' => [
                                ['question' => 'Can I use my own domain?', 'answer' => 'Yes — connect it in Domains and it is live once DNS points at us. The certificate is issued automatically.'],
                                ['question' => 'Do I need a developer?', 'answer' => 'No. Everything on this page was assembled by dragging blocks and editing the words.'],
                                ['question' => 'What happens to my leads if I leave?', 'answer' => 'They are yours. Export every contact, with their full activity history, as CSV at any time.'],
                                ['question' => 'Can I cancel at any time?', 'answer' => 'Yes, from Settings → Billing. You keep access until the end of the period you paid for.'],
                            ],
                            'allowMultipleOpen' => false,
                            'emitSchemaOrg' => true,
                        ], [
                            'maxWidth' => $this->responsive('760px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function footerColumns(): array
    {
        /** @param list<array{label: string, url: string}> $items */
        $links = fn (string $title, array $items): array => $this->column(3, [
            $this->heading($title, 'h4', [
                'fontSize' => $this->responsive('13px'),
                'color' => $this->responsive(self::SUBTLE),
                'textTransform' => $this->responsive('uppercase'),
                'letterSpacing' => $this->responsive('0.1em'),
            ]),
            $this->blockNode(BlockType::Navigation, [
                'items' => $items,
                'orientation' => 'vertical',
            ], [
                'color' => $this->responsive(self::ON_DARK),
                'fontSize' => $this->responsive('15px'),
            ]),
        ]);

        return $this->block(
            key: 'footer-columns',
            name: 'Footer',
            category: self::CATEGORY_STRUCTURE,
            description: 'A four-column footer with a logo, link lists and social icons.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('64px', '24px', '48px'),
                ],
                rows: [$this->row([
                    $this->column(3, [
                        $this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true]),
                        $this->paragraph('Everything after the click.', [
                            'color' => $this->responsive(self::SUBTLE),
                            'fontSize' => $this->responsive('14px'),
                            'margin' => $this->responsive(['top' => '12px', 'bottom' => '20px']),
                        ]),
                        $this->blockNode(BlockType::SocialIcons, [
                            'networks' => [
                                ['network' => 'x', 'url' => null],
                                ['network' => 'linkedin', 'url' => null],
                                ['network' => 'youtube', 'url' => null],
                            ],
                            'size' => '18px',
                            'shape' => 'circle',
                        ], [
                            'color' => $this->responsive(self::SUBTLE),
                        ]),
                    ]),
                    $links('Product', [
                        ['label' => 'Funnels', 'url' => '#'],
                        ['label' => 'Email', 'url' => '#'],
                        ['label' => 'Automations', 'url' => '#'],
                        ['label' => 'Pricing', 'url' => '#pricing'],
                    ]),
                    $links('Company', [
                        ['label' => 'About', 'url' => '#'],
                        ['label' => 'Blog', 'url' => '#'],
                        ['label' => 'Careers', 'url' => '#'],
                        ['label' => 'Contact', 'url' => '#'],
                    ]),
                    $links('Legal', [
                        ['label' => 'Privacy', 'url' => '#'],
                        ['label' => 'Terms', 'url' => '#'],
                        ['label' => 'Cookies', 'url' => '#'],
                        ['label' => 'DPA', 'url' => '#'],
                    ]),
                ])],
            )],
        );
    }

    /* --------------------------------------------------------- funnel steps */

    /**
     * A slim header for inner funnel steps: the logo and how far through the
     * funnel the visitor is. Navigation is deliberately absent — a menu on a
     * checkout or upsell step is an exit.
     *
     * @return array<string, mixed>
     */
    private function funnelStepHeader(): array
    {
        return $this->block(
            key: 'funnel-step-header',
            name: 'Step progress header',
            category: self::CATEGORY_FUNNEL,
            description: 'Logo and a "Step 2 of 3" progress bar — no menu to leak visitors out of the funnel.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'padding' => $this->responsive(['top' => '18px', 'right' => '24px', 'bottom' => '18px', 'left' => '24px']),
                    'border' => $this->responsive(['style' => 'solid', 'color' => self::LINE, 'bottom' => '1px']),
                ],
                rows: [$this->row([
                    $this->column(4, [
                        $this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true]),
                    ]),
                    $this->column(8, [
                        $this->blockNode(BlockType::ProgressBar, [
                            'value' => 2,
                            'max' => 3,
                            'label' => 'Step 2 of 3 — Your order',
                            'showPercentage' => false,
                            'source' => 'manual',
                        ], [
                            'color' => $this->responsive(self::BODY),
                            'maxWidth' => $this->responsive('420px'),
                            'margin' => $this->responsive(['left' => 'auto']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function vslHero(): array
    {
        return $this->block(
            key: 'vsl-hero',
            name: 'Video sales letter',
            category: self::CATEGORY_FUNNEL,
            description: 'A pre-headline, big promise, the sales video and one button that moves to the next step.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('72px', '24px', '44px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('Free training · watch before it comes down', [
                                'color' => $this->responsive('#fde68a'),
                                'fontSize' => $this->responsive('13px'),
                                'fontWeight' => $this->responsive('600'),
                                'textTransform' => $this->responsive('uppercase'),
                                'letterSpacing' => $this->responsive('0.12em'),
                            ]),
                            $this->heading('How to turn cold traffic into booked calls in 30 days — without a big ad budget', 'h1', [
                                'color' => $this->responsive('#ffffff'),
                                'fontSize' => $this->font('44px', '28px'),
                                'lineHeight' => $this->responsive('1.15'),
                                'maxWidth' => $this->responsive('900px'),
                                'margin' => $this->responsive(['top' => '14px', 'right' => 'auto', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::Video, [
                                'provider' => 'youtube',
                                'url' => null,
                                'controls' => true,
                                'aspectRatio' => '16:9',
                            ], [
                                'borderRadius' => $this->responsive('16px'),
                                'boxShadow' => $this->responsive('0 30px 80px rgba(0,0,0,0.45)'),
                                'maxWidth' => $this->responsive('860px'),
                                'margin' => $this->responsive(['top' => '32px', 'right' => 'auto', 'bottom' => '32px', 'left' => 'auto']),
                            ]),
                            $this->nextStepButton('Yes — show me how', 'lg', style: [
                                'backgroundColor' => $this->responsive(self::AMBER),
                                'color' => $this->responsive(self::INK),
                                'padding' => $this->responsive(['top' => '18px', 'right' => '36px', 'bottom' => '18px', 'left' => '36px']),
                                'borderRadius' => $this->responsive('999px'),
                                'boxShadow' => $this->responsive('0 12px 30px rgba(245,158,11,0.35)'),
                            ]),
                            $this->paragraph('🔒 Secure checkout · 30-day money-back guarantee', [
                                'color' => $this->responsive(self::SUBTLE),
                                'fontSize' => $this->responsive('14px'),
                                'margin' => $this->responsive(['top' => '16px']),
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function bonusStack(): array
    {
        return $this->block(
            key: 'bonus-stack',
            name: 'Bonus stack',
            category: self::CATEGORY_FUNNEL,
            description: 'Everything included with its value, the total, today\'s price and the buy button.',
            sections: [$this->section(
                style: ['padding' => $this->pad('80px', '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Here is everything you get today', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(self::INK),
                                'margin' => $this->responsive(['bottom' => '28px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'star', 'title' => 'The core programme — $297 value', 'body' => 'Six modules that take you from first page to first sale.'],
                                    ['icon' => 'zap', 'title' => 'Bonus 1: Swipe file — $97 value', 'body' => '40 proven headlines, hooks and email subject lines.'],
                                    ['icon' => 'mail', 'title' => 'Bonus 2: Email templates — $67 value', 'body' => 'The welcome, nurture and launch sequences, ready to paste.'],
                                    ['icon' => 'heart', 'title' => 'Bonus 3: Private community — $30 value', 'body' => 'Weekly Q&A calls and feedback on your funnel.'],
                                ],
                                'columns' => 2,
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('Total value $491', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(self::MUTED),
                                'fontSize' => $this->responsive('20px'),
                                'textDecoration' => $this->responsive('line-through'),
                                'margin' => $this->responsive(['top' => '32px']),
                            ]),
                            $this->heading('Today: $47', 'h3', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('48px', '36px'),
                                'color' => $this->responsive(self::SUCCESS),
                                'margin' => $this->responsive(['top' => '4px', 'bottom' => '22px']),
                            ]),
                            $this->nextStepButton('Get instant access for $47', 'lg', style: [
                                'textAlign' => $this->responsive('center'),
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'padding' => $this->responsive(['top' => '18px', 'right' => '40px', 'bottom' => '18px', 'left' => '40px']),
                                'borderRadius' => $this->responsive('12px'),
                                'boxShadow' => $this->responsive('0 14px 30px rgba(22,163,74,0.3)'),
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function scarcityBar(): array
    {
        return $this->block(
            key: 'scarcity-bar',
            name: 'Scarcity bar',
            category: self::CATEGORY_FUNNEL,
            description: 'Spots remaining, a daily deadline and a button — urgency without shouting.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive('#fff7ed'),
                    'padding' => $this->pad('36px', '24px', '28px'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => '#fed7aa', 'top' => '1px', 'bottom' => '1px']),
                ],
                rows: [$this->row([
                    $this->column(5, [
                        $this->heading('Only 23 founding spots left', 'h3', [
                            'fontSize' => $this->responsive('22px'),
                            'color' => $this->responsive('#9a3412'),
                        ]),
                        $this->blockNode(BlockType::ProgressBar, [
                            'value' => 77,
                            'max' => 100,
                            'label' => '77 of 100 taken',
                            'showPercentage' => true,
                            'source' => 'manual',
                        ], [
                            'backgroundColor' => $this->responsive('#ea580c'),
                            'color' => $this->responsive('#9a3412'),
                            'margin' => $this->responsive(['top' => '12px']),
                        ]),
                    ]),
                    $this->column(4, [
                        $this->paragraph('Price rises at midnight', [
                            'color' => $this->responsive('#9a3412'),
                            'fontSize' => $this->responsive('13px'),
                            'fontWeight' => $this->responsive('600'),
                            'textTransform' => $this->responsive('uppercase'),
                            'letterSpacing' => $this->responsive('0.08em'),
                        ]),
                        $this->blockNode(BlockType::Countdown, [
                            'mode' => 'daily',
                            'showLabels' => true,
                            'expiredAction' => 'hide',
                        ], [
                            'fontSize' => $this->responsive('12px'),
                            'backgroundColor' => $this->responsive('#9a3412'),
                            'margin' => $this->responsive(['top' => '8px']),
                        ]),
                    ]),
                    $this->column(3, [
                        $this->nextStepButton('Claim my spot', 'lg', style: [
                            'textAlign' => $this->responsive('right'),
                            'backgroundColor' => $this->responsive('#ea580c'),
                            'borderRadius' => $this->responsive('10px'),
                            'margin' => $this->responsive(['top' => '18px']),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function offerCard(): array
    {
        return $this->block(
            key: 'offer-card',
            name: 'Single offer card',
            category: self::CATEGORY_FUNNEL,
            description: 'One highlighted offer with what is included, whose button moves to the checkout step.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('80px', '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Join today', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('One payment. Lifetime access. Every future update included.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '36px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::PricingTable, [
                                'plans' => [[
                                    'name' => 'Complete programme',
                                    'price' => '$47',
                                    'interval' => 'once',
                                    'features' => ['All six modules', 'The swipe file and templates', 'Private community access', '30-day money-back guarantee'],
                                    'buttonLabel' => 'Get instant access',
                                    'buttonUrl' => null,
                                    'action' => 'next_step',
                                    'targetStepId' => null,
                                    'highlighted' => true,
                                ]],
                                'columns' => 1,
                            ], [
                                'maxWidth' => $this->responsive('440px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * The one-time offer shown straight after checkout.
     *
     * Both answers move on: "Yes" to the next step, and the decline link too.
     * In a ready-made funnel the decline is pointed past the downsell by the
     * installer; dropped onto a page by hand, pick its target in the panel.
     *
     * @return array<string, mixed>
     */
    private function upsellOffer(): array
    {
        return $this->block(
            key: 'upsell-offer',
            name: 'Upsell (one-time offer)',
            category: self::CATEGORY_FUNNEL,
            description: 'Order progress, a 15-minute evergreen timer, the offer and clear yes / no buttons.',
            sections: [$this->section(
                style: [
                    'padding' => $this->pad('56px', '24px', '36px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::ProgressBar, [
                                'value' => 90,
                                'max' => 100,
                                'label' => 'Your order is almost complete…',
                                'showPercentage' => true,
                                'source' => 'manual',
                            ], [
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'maxWidth' => $this->responsive('520px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
                                'color' => $this->responsive(self::BODY),
                            ]),
                            $this->heading('Wait! Add the done-for-you templates for 60% off', 'h1', [
                                'fontSize' => $this->font('40px', '28px'),
                                'lineHeight' => $this->responsive('1.15'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('This page only appears once. Close it and the offer is gone.', [
                                'color' => $this->responsive('#b91c1c'),
                                'fontWeight' => $this->responsive('600'),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '20px']),
                            ]),
                            $this->blockNode(BlockType::Countdown, [
                                'mode' => 'evergreen',
                                'durationMinutes' => 15,
                                'showLabels' => true,
                                'expiredAction' => 'message',
                                'expiredMessage' => 'This offer has expired.',
                            ], [
                                'fontSize' => $this->responsive('13px'),
                                'backgroundColor' => $this->responsive('#b91c1c'),
                                'textAlign' => $this->responsive('center'),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->image('The template library', [
                                'borderRadius' => $this->responsive('16px'),
                                'boxShadow' => $this->responsive('0 20px 50px -20px rgba(15,23,42,0.35)'),
                            ]),
                        ]),
                        $this->column(6, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'check', 'title' => '25 funnel templates', 'body' => 'Import, change the words, publish.'],
                                    ['icon' => 'check', 'title' => '60 email templates', 'body' => 'Welcome, nurture, launch and win-back.'],
                                    ['icon' => 'check', 'title' => 'Setup walkthroughs', 'body' => 'Short videos for every template.'],
                                ],
                                'columns' => 1,
                            ], ['textAlign' => $this->responsive('left')]),
                            $this->paragraph('<b>Just $77 today</b> (normally $197)', [
                                'fontSize' => $this->responsive('22px'),
                                'color' => $this->responsive(self::INK),
                                'textAlign' => $this->responsive('left'),
                                'margin' => $this->responsive(['top' => '20px']),
                            ]),
                        ]),
                    ], style: ['margin' => $this->responsive(['top' => '36px'])]),
                    $this->row([
                        $this->column(12, [
                            $this->upsellButton('Yes! Add this to my order', 'lg', style: [
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'padding' => $this->responsive(['top' => '20px', 'right' => '44px', 'bottom' => '20px', 'left' => '44px']),
                                'borderRadius' => $this->responsive('12px'),
                                'boxShadow' => $this->responsive('0 14px 30px rgba(22,163,74,0.3)'),
                                'fontSize' => $this->font('20px', '17px'),
                            ]),
                            $this->nextStepButton('No thanks, I will pass on this offer', 'sm', variant: 'link', style: [
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '16px']),
                            ], name: 'Decline link'),
                        ]),
                    ], style: ['margin' => $this->responsive(['top' => '32px'])]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function downsellOffer(): array
    {
        return $this->block(
            key: 'downsell-offer',
            name: 'Downsell',
            category: self::CATEGORY_FUNNEL,
            description: 'A lighter, cheaper version for visitors who declined the upsell.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::SURFACE),
                    'padding' => $this->pad('64px', '24px', '40px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Not ready for everything? Take the essentials instead', 'h1', [
                            'fontSize' => $this->font('36px', '26px'),
                            'color' => $this->responsive(self::INK),
                            'maxWidth' => $this->responsive('760px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                        ]),
                        $this->paragraph('The 10 most-used funnel templates, on their own, for a one-time $27.', [
                            'color' => $this->responsive(self::BODY),
                            'fontSize' => $this->font('18px', '16px'),
                            'margin' => $this->responsive(['top' => '14px', 'bottom' => '28px']),
                        ]),
                    ]),
                ]), $this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::PricingTable, [
                            'plans' => [[
                                'name' => 'Essentials pack',
                                'price' => '$27',
                                'interval' => 'once',
                                'features' => ['10 funnel templates', 'Setup walkthroughs', 'Free updates'],
                                'buttonLabel' => 'Yes, add the essentials',
                                'buttonUrl' => null,
                                'action' => 'upsell_purchase',
                                'productId' => null,
                                'targetStepId' => null,
                                'highlighted' => true,
                            ]],
                            'columns' => 1,
                        ], [
                            'maxWidth' => $this->responsive('400px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            'textAlign' => $this->responsive('left'),
                        ]),
                        $this->nextStepButton('No thanks, take me to my order', 'sm', variant: 'link', style: [
                            'color' => $this->responsive(self::MUTED),
                            'margin' => $this->responsive(['top' => '20px']),
                        ], name: 'Decline link'),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderConfirmation(): array
    {
        return $this->block(
            key: 'order-confirmation',
            name: 'Order confirmation',
            category: self::CATEGORY_FUNNEL,
            description: 'A clear "you\'re in", what happens next, and where to go now.',
            sections: [$this->section(
                style: [
                    'padding' => $this->pad('80px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '44px'], [
                                'color' => $this->responsive('#ffffff'),
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'padding' => $this->responsive(['top' => '18px', 'right' => '18px', 'bottom' => '18px', 'left' => '18px']),
                                'borderRadius' => $this->responsive('999px'),
                                'width' => $this->responsive('80px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '20px', 'left' => 'auto']),
                                'boxShadow' => $this->responsive('0 12px 30px rgba(22,163,74,0.35)'),
                            ]),
                            $this->heading('You are in! Your order is confirmed', 'h1', [
                                'fontSize' => $this->font('40px', '28px'),
                                'color' => $this->responsive(self::INK),
                            ]),
                            $this->paragraph('A receipt and your login details are on their way to your inbox.', [
                                'color' => $this->responsive(self::MUTED),
                                'fontSize' => $this->font('18px', '16px'),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '40px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'mail', 'title' => '1. Check your inbox', 'body' => 'Look for an email from us within five minutes.'],
                                    ['icon' => 'play', 'title' => '2. Watch the welcome video', 'body' => 'Ten minutes on how to get the most from it.'],
                                    ['icon' => 'heart', 'title' => '3. Join the community', 'body' => 'Say hello — the first Q&A call is this week.'],
                                ],
                                'columns' => 3,
                            ], ['textAlign' => $this->responsive('left')]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::Button, [
                                'label' => 'Go to the members area',
                                'action' => 'url',
                                'url' => '#',
                                'size' => 'lg',
                                'variant' => 'primary',
                            ], ['margin' => $this->responsive(['top' => '36px'])]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function webinarConfirmation(): array
    {
        return $this->block(
            key: 'webinar-confirmation',
            name: 'Webinar confirmation',
            category: self::CATEGORY_FUNNEL,
            description: '"You\'re registered", a countdown to the start and the three things to do before it.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('80px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('Your seat is saved', [
                                'color' => $this->responsive('#86efac'),
                                'fontSize' => $this->responsive('13px'),
                                'fontWeight' => $this->responsive('700'),
                                'textTransform' => $this->responsive('uppercase'),
                                'letterSpacing' => $this->responsive('0.12em'),
                            ]),
                            $this->heading('You are registered! The workshop starts in', 'h1', [
                                'color' => $this->responsive('#ffffff'),
                                'fontSize' => $this->font('38px', '26px'),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '24px']),
                            ]),
                            $this->blockNode(BlockType::Countdown, [
                                'mode' => 'fixed',
                                'endsAt' => null,
                                'durationMinutes' => 1440,
                                'showLabels' => true,
                                'expiredAction' => 'message',
                                'expiredMessage' => 'We are live — join now from the link in your email.',
                            ], [
                                'backgroundColor' => $this->responsive('rgba(255,255,255,0.1)'),
                                'color' => $this->responsive('#ffffff'),
                                'textAlign' => $this->responsive('center'),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'mail', 'title' => 'Check your email', 'body' => 'Your join link is there. Mark it as important.'],
                                    ['icon' => 'star', 'title' => 'Block the time', 'body' => 'Stay to the end — the templates are shared live.'],
                                    ['icon' => 'play', 'title' => 'Watch the prep video', 'body' => 'Five minutes that make the workshop twice as useful.'],
                                ],
                                'columns' => 3,
                            ], [
                                'textAlign' => $this->responsive('left'),
                                'margin' => $this->responsive(['top' => '40px']),
                            ]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function upsellProductSpotlight(): array
    {
        return $this->block(
            key: 'upsell-product-spotlight',
            name: 'Upsell product spotlight',
            category: self::CATEGORY_FUNNEL,
            description: 'A visual one-click upsell with product benefits, price anchoring and a clear decline path.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive('#fff7ed'),
                    'padding' => $this->pad('72px', '24px', '42px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('EXCLUSIVE ONE-TIME OFFER', [
                                'color' => $this->responsive('#c2410c'),
                                'fontWeight' => $this->responsive('800'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                            ]),
                            $this->heading('Add the complete implementation kit', 'h1', [
                                'color' => $this->responsive(self::INK),
                                'fontSize' => $this->font('44px', '30px'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '38px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->image('Upsell product preview', [
                                'borderRadius' => $this->responsive('24px'),
                                'boxShadow' => $this->responsive('0 24px 60px -24px rgba(124,45,18,0.45)'),
                            ]),
                        ]),
                        $this->column(6, [
                            $this->heading('Everything you need to move faster', 'h2', [
                                'color' => $this->responsive(self::INK),
                                'fontSize' => $this->font('30px', '24px'),
                            ]),
                            $this->blockNode(BlockType::FeatureCards, [
                                'items' => [
                                    ['icon' => 'check', 'title' => 'Ready-to-use resources', 'body' => 'Skip the blank page and start with proven assets.'],
                                    ['icon' => 'check', 'title' => 'Step-by-step setup', 'body' => 'Follow the included implementation checklist.'],
                                    ['icon' => 'check', 'title' => 'Lifetime access', 'body' => 'Keep every update without another subscription.'],
                                ],
                                'columns' => 1,
                            ]),
                            $this->paragraph('<s>$197</s> <b>$77 today</b>', [
                                'color' => $this->responsive(self::INK),
                                'fontSize' => $this->font('24px', '20px'),
                                'margin' => $this->responsive(['top' => '20px', 'bottom' => '18px']),
                            ]),
                            $this->upsellButton('Yes, add this to my order', 'lg', [
                                'backgroundColor' => $this->responsive('#ea580c'),
                                'borderRadius' => $this->responsive('12px'),
                                'boxShadow' => $this->responsive('0 14px 30px rgba(234,88,12,0.28)'),
                            ]),
                            $this->nextStepButton('No thanks, continue without it', 'sm', 'link', [
                                'color' => $this->responsive(self::MUTED),
                                'margin' => $this->responsive(['top' => '14px']),
                            ], 'Decline link'),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function upsellBundleChoice(): array
    {
        return $this->block(
            key: 'upsell-bundle-choice',
            name: 'Upsell bundle choice',
            category: self::CATEGORY_FUNNEL,
            description: 'Two contrasting add-on choices for presenting a focused upgrade or a best-value bundle.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(self::INK),
                    'padding' => $this->pad('72px', '24px', '42px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Choose your upgrade', 'h1', [
                                'color' => $this->responsive('#ffffff'),
                                'fontSize' => $this->font('42px', '29px'),
                                'textAlign' => $this->responsive('center'),
                            ]),
                            $this->paragraph('Make one selection below. Your saved payment method will be charged securely.', [
                                'color' => $this->responsive(self::ON_DARK),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '34px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->paragraph('ESSENTIAL', ['color' => $this->responsive(self::MUTED), 'fontWeight' => $this->responsive('700')]),
                            $this->heading('Quick-start pack', 'h2', ['color' => $this->responsive(self::INK), 'fontSize' => $this->font('28px', '23px')]),
                            $this->paragraph('The core templates and implementation guide.', ['color' => $this->responsive(self::BODY), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '22px'])]),
                            $this->upsellButton('Add for $37', 'lg', ['borderRadius' => $this->responsive('10px')]),
                        ], style: [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '30px', 'bottom' => '32px', 'left' => '30px']),
                            'borderRadius' => $this->responsive('18px'),
                        ]),
                        $this->column(6, [
                            $this->paragraph('BEST VALUE', ['color' => $this->responsive('#86efac'), 'fontWeight' => $this->responsive('800')]),
                            $this->heading('Complete growth bundle', 'h2', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('28px', '23px')]),
                            $this->paragraph('All templates, training, bonuses and future updates.', ['color' => $this->responsive(self::ON_DARK), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '22px'])]),
                            $this->upsellButton('Add everything for $77', 'lg', [
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'borderRadius' => $this->responsive('10px'),
                            ]),
                        ], style: [
                            'backgroundColor' => $this->responsive(self::INK_SOFT),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '30px', 'bottom' => '32px', 'left' => '30px']),
                            'borderRadius' => $this->responsive('18px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#22c55e', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px']),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->nextStepButton('Skip upgrades and finish my order', 'sm', 'link', [
                                'color' => $this->responsive(self::ON_DARK),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['top' => '26px']),
                            ], 'Decline link'),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderBumpCard(): array
    {
        return $this->block(
            key: 'order-bump-card',
            name: 'Order bump card',
            category: self::CATEGORY_COMMERCE,
            description: 'A compact add-on card to place near checkout, with product imagery, benefits and an editable purchase action.',
            sections: [$this->section(
                style: ['padding' => $this->pad('36px', '24px', '28px')],
                rows: [$this->row([
                    $this->column(4, [
                        $this->image('Order bump product', ['borderRadius' => $this->responsive('14px')]),
                    ]),
                    $this->column(8, [
                        $this->paragraph('ADD THIS TO MY ORDER', ['color' => $this->responsive('#a16207'), 'fontWeight' => $this->responsive('800'), 'letterSpacing' => $this->responsive('0.08em')]),
                        $this->heading('Get the companion workbook for only $19', 'h3', ['color' => $this->responsive(self::INK), 'fontSize' => $this->font('25px', '21px'), 'margin' => $this->responsive(['top' => '8px'])]),
                        $this->paragraph('Turn every lesson into an action plan with prompts, worksheets and printable checklists.', ['color' => $this->responsive(self::BODY), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '18px'])]),
                        $this->upsellButton('Yes, add the workbook', 'md', [
                            'backgroundColor' => $this->responsive('#ca8a04'),
                            'borderRadius' => $this->responsive('9px'),
                        ]),
                    ]),
                ], style: [
                    'backgroundColor' => $this->responsive('#fefce8'),
                    'padding' => $this->responsive(['top' => '22px', 'right' => '22px', 'bottom' => '22px', 'left' => '22px']),
                    'borderRadius' => $this->responsive('18px'),
                    'border' => $this->responsive(['style' => 'dashed', 'color' => '#eab308', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px']),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function leadDeliveryConfirmation(): array
    {
        return $this->block(
            key: 'lead-delivery-thank-you',
            name: 'Lead magnet thank-you',
            category: self::CATEGORY_FUNNEL,
            description: 'A download delivery page with inbox guidance, a primary resource button and a next-offer CTA.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#ecfdf5'),
                    'padding' => $this->pad('80px', '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([$this->column(12, [
                        $this->blockNode(BlockType::Icon, ['name' => 'mail-check', 'size' => '42px'], [
                            'color' => $this->responsive('#ffffff'),
                            'backgroundColor' => $this->responsive(self::SUCCESS),
                            'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px']),
                            'borderRadius' => $this->responsive('999px'),
                            'width' => $this->responsive('74px'),
                            'margin' => $this->responsive(['right' => 'auto', 'bottom' => '20px', 'left' => 'auto']),
                        ]),
                        $this->heading('Your free guide is ready', 'h1', ['color' => $this->responsive(self::INK), 'fontSize' => $this->font('42px', '29px')]),
                        $this->paragraph('We also sent a copy to your inbox. If it is not there in five minutes, check Promotions or Spam.', ['color' => $this->responsive(self::BODY), 'fontSize' => $this->font('18px', '16px'), 'maxWidth' => $this->responsive('680px'), 'margin' => $this->responsive(['top' => '12px', 'right' => 'auto', 'bottom' => '28px', 'left' => 'auto'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Download the guide', 'action' => 'url', 'url' => '#', 'iconRight' => 'download', 'size' => 'lg', 'variant' => 'primary'], ['borderRadius' => $this->responsive('12px')]),
                    ])]),
                    $this->row([$this->column(12, [
                        $this->heading('Want help putting it into action?', 'h2', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('28px', '23px')]),
                        $this->paragraph('See the short training we prepared as your next step.', ['color' => $this->responsive(self::ON_DARK), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '18px'])]),
                        $this->nextStepButton('Watch the free training', 'lg', 'primary', ['backgroundColor' => $this->responsive('#ffffff'), 'color' => $this->responsive(self::INK), 'borderRadius' => $this->responsive('10px')]),
                    ], style: [
                        'backgroundColor' => $this->responsive(self::INK),
                        'padding' => $this->responsive(['top' => '30px', 'right' => '30px', 'bottom' => '30px', 'left' => '30px']),
                        'borderRadius' => $this->responsive('18px'),
                    ])], style: ['margin' => $this->responsive(['top' => '42px'])]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingThankYou(): array
    {
        return $this->block(
            key: 'booking-thank-you',
            name: 'Booking thank-you',
            category: self::CATEGORY_FUNNEL,
            description: 'A post-booking confirmation with preparation steps, calendar guidance and a return-home action.',
            sections: [$this->section(
                style: ['padding' => $this->pad('76px', '24px', '46px')],
                rows: [
                    $this->row([$this->column(12, [
                        $this->paragraph('MEETING CONFIRMED', ['color' => $this->responsive(self::SUCCESS), 'fontWeight' => $this->responsive('800'), 'letterSpacing' => $this->responsive('0.12em'), 'textAlign' => $this->responsive('center')]),
                        $this->heading('You are booked. Here is what happens next.', 'h1', ['color' => $this->responsive(self::INK), 'fontSize' => $this->font('42px', '29px'), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '36px'])]),
                    ])]),
                    $this->row([
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'calendar-check', 'size' => '28px'], ['color' => $this->responsive(self::PRIMARY)]), $this->heading('Save the date', 'h3', ['color' => $this->responsive(self::INK)]), $this->paragraph('Use the calendar link in your confirmation email.', ['color' => $this->responsive(self::BODY)])], style: ['backgroundColor' => $this->responsive(self::SURFACE), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'clipboard-check', 'size' => '28px'], ['color' => $this->responsive(self::PRIMARY)]), $this->heading('Complete the brief', 'h3', ['color' => $this->responsive(self::INK)]), $this->paragraph('Reply with the short details requested before the call.', ['color' => $this->responsive(self::BODY)])], style: ['backgroundColor' => $this->responsive(self::SURFACE), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'video', 'size' => '28px'], ['color' => $this->responsive(self::PRIMARY)]), $this->heading('Join prepared', 'h3', ['color' => $this->responsive(self::INK)]), $this->paragraph('Bring your top goal and any questions you want answered.', ['color' => $this->responsive(self::BODY)])], style: ['backgroundColor' => $this->responsive(self::SURFACE), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
                    ]),
                    $this->row([$this->column(12, [
                        $this->blockNode(BlockType::Button, ['label' => 'Return to the website', 'action' => 'url', 'url' => '/', 'size' => 'md', 'variant' => 'secondary'], ['margin' => $this->responsive(['top' => '34px']), 'textAlign' => $this->responsive('center')]),
                    ])]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buttonDesignShowcase(): array
    {
        return $this->block(
            key: 'button-design-showcase',
            name: 'Button design showcase',
            category: self::CATEGORY_CONVERSION,
            description: 'A polished set of primary, secondary and text-link actions ready to restyle and reuse.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#f1f5f9'),
                    'padding' => $this->pad('48px', '24px', '32px'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Choose the action style that fits', 'h2', ['color' => $this->responsive(self::INK), 'fontSize' => $this->font('30px', '24px'), 'textAlign' => $this->responsive('center')]),
                        $this->paragraph('Every button is independently editable, including its action, destination, icons, size and colours.', ['color' => $this->responsive(self::MUTED), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '26px'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Start now', 'action' => 'url', 'url' => '#', 'iconRight' => 'arrow-right', 'size' => 'lg', 'variant' => 'primary'], ['backgroundColor' => $this->responsive(self::PRIMARY), 'borderRadius' => $this->responsive('999px'), 'boxShadow' => $this->responsive('0 12px 28px rgba(79,70,229,0.28)')]),
                        $this->blockNode(BlockType::Button, ['label' => 'See how it works', 'action' => 'url', 'url' => '#', 'iconLeft' => 'play', 'size' => 'lg', 'variant' => 'secondary'], ['borderRadius' => $this->responsive('999px'), 'margin' => $this->responsive(['top' => '12px'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Not ready? Learn more', 'action' => 'url', 'url' => '#', 'size' => 'sm', 'variant' => 'link'], ['color' => $this->responsive(self::BODY), 'margin' => $this->responsive(['top' => '12px'])]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutCtaPanel(): array
    {
        return $this->block(
            key: 'checkout-cta-panel',
            name: 'Checkout CTA panel',
            category: self::CATEGORY_CONVERSION,
            description: 'A high-contrast full-width purchase CTA with reassurance, guarantee text and payment cues.',
            sections: [$this->section(
                style: ['padding' => $this->pad('48px', '24px', '32px')],
                rows: [$this->row([$this->column(12, [
                    $this->paragraph('30-DAY MONEY-BACK GUARANTEE', ['color' => $this->responsive('#86efac'), 'fontWeight' => $this->responsive('800'), 'letterSpacing' => $this->responsive('0.1em'), 'textAlign' => $this->responsive('center')]),
                    $this->heading('Ready to get the complete system?', 'h2', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('34px', '25px'), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '9px', 'bottom' => '20px'])]),
                    $this->blockNode(BlockType::Button, ['label' => 'Get instant access', 'action' => 'next_step', 'targetStepId' => null, 'iconRight' => 'arrow-right', 'fullWidth' => true, 'size' => 'lg', 'variant' => 'primary'], ['backgroundColor' => $this->responsive('#22c55e'), 'color' => $this->responsive('#052e16'), 'borderRadius' => $this->responsive('12px'), 'boxShadow' => $this->responsive('0 14px 34px rgba(34,197,94,0.28)')]),
                    $this->paragraph('Secure checkout · Instant access · Cancel anytime', ['color' => $this->responsive(self::ON_DARK), 'fontSize' => $this->responsive('13px'), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '14px'])]),
                ], style: [
                    'backgroundColor' => $this->responsive('#052e2f'),
                    'padding' => $this->responsive(['top' => '38px', 'right' => '38px', 'bottom' => '38px', 'left' => '38px']),
                    'borderRadius' => $this->responsive('20px'),
                ])])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stickyCtaBand(): array
    {
        return $this->block(
            key: 'sticky-cta-band',
            name: 'Sticky CTA band',
            category: self::CATEGORY_CONVERSION,
            description: 'A compact sticky conversion bar with offer copy, price and a funnel-aware action button.',
            sections: [$this->section(
                width: 'full',
                sticky: true,
                style: [
                    'backgroundColor' => $this->responsive('#111827'),
                    'padding' => $this->responsive(['top' => '16px', 'right' => '24px', 'bottom' => '16px', 'left' => '24px']),
                    'boxShadow' => $this->responsive('0 -8px 30px rgba(15,23,42,0.18)'),
                ],
                rows: [$this->row([
                    $this->column(8, [
                        $this->heading('Get the complete toolkit for $47', 'h3', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('22px', '19px')]),
                        $this->paragraph('Instant access plus every future update.', ['color' => $this->responsive(self::ON_DARK), 'fontSize' => $this->responsive('14px'), 'margin' => $this->responsive(['top' => '4px'])]),
                    ]),
                    $this->column(4, [
                        $this->nextStepButton('Get instant access', 'lg', 'primary', [
                            'backgroundColor' => $this->responsive('#fbbf24'),
                            'color' => $this->responsive('#422006'),
                            'borderRadius' => $this->responsive('10px'),
                            'textAlign' => $this->responsive('right'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /* -------------------------------------------------------------- builders */

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private function block(
        string $key,
        string $name,
        string $category,
        string $description,
        array $sections,
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            // A fragment, not a whole document: the builder appends these
            // sections to whatever page is already open.
            'document' => ['sections' => $sections],
        ];
    }

    /**
     * @param  array<string, mixed>  $style
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function section(array $rows, array $style = [], string $width = 'boxed', bool $sticky = false): array
    {
        return [
            'id' => (string) Str::ulid(),
            'width' => $width,
            'sticky' => $sticky,
            'style' => $style,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function row(array $columns, array $style = []): array
    {
        return [
            'id' => (string) Str::ulid(),
            // Multi-column rows stack on mobile: three 4-wide columns on a
            // phone would be unreadable.
            'stackBelow' => 'mobile',
            'style' => $style,
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
            'id' => (string) Str::ulid(),
            'span' => ['desktop' => $span, 'tablet' => $span <= 4 ? 6 : 12, 'mobile' => 12],
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
        return [
            'id' => (string) Str::ulid(),
            'type' => $type->value,
            // Registry defaults first, so a block dropped from here has every
            // prop the renderer expects even where this definition is terse.
            'props' => [...BlockRegistry::defaultProps($type), ...$props],
            'style' => $style,
        ];
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function heading(string $text, string $level, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => $level], $style);
    }

    /**
     * `text`, not `html` — the paragraph block reads `text` and sanitises a
     * small inline subset out of it.
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function paragraph(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Paragraph, ['text' => $text], $style);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function button(
        string $label,
        string $size = 'md',
        string $variant = 'primary',
        array $style = [],
    ): array {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            // `scroll_to` keeps a template's buttons useful without inventing
            // a URL that would 404 the moment the page is published.
            'action' => 'scroll_to',
            'size' => $size,
            'variant' => $variant,
        ], $style);
    }

    /**
     * A button that moves the visitor to the next funnel step.
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function nextStepButton(
        string $label,
        string $size = 'md',
        string $variant = 'primary',
        array $style = [],
        ?string $name = null,
    ): array {
        $node = $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'next_step',
            'targetStepId' => null,
            'size' => $size,
            'variant' => $variant,
        ], $style);

        if ($name !== null) {
            $node['name'] = $name;
        }

        return $node;
    }

    /**
     * A one-click upsell button: charges the card saved at checkout for the
     * product chosen in its settings, then moves to the next step. With no
     * product chosen yet it simply moves on.
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function upsellButton(string $label, string $size = 'lg', array $style = []): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'upsell_purchase',
            'productId' => null,
            'targetStepId' => null,
            'size' => $size,
            'variant' => 'primary',
        ], $style);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function image(string $alt, array $style = []): array
    {
        return $this->blockNode(BlockType::Image, ['src' => null, 'alt' => $alt], $style);
    }

    /**
     * Every style value is responsive, so a bare value has to be wrapped.
     *
     * @return array<string, mixed>
     */
    private function responsive(mixed $desktop): array
    {
        return ['desktop' => $desktop];
    }

    /**
     * A responsive font size — the desktop scale is nearly always too large on
     * a phone, and leaving it unset is the most common design-block mistake.
     *
     * @return array<string, mixed>
     */
    private function font(string $desktop, string $mobile): array
    {
        return ['desktop' => $desktop, 'mobile' => $mobile];
    }

    /**
     * Symmetric vertical padding with a tighter mobile tier.
     *
     * @return array<string, mixed>
     */
    private function pad(string $vertical, string $horizontal, string $mobileVertical): array
    {
        return [
            'desktop' => ['top' => $vertical, 'right' => $horizontal, 'bottom' => $vertical, 'left' => $horizontal],
            'mobile' => ['top' => $mobileVertical, 'right' => '20px', 'bottom' => $mobileVertical, 'left' => '20px'],
        ];
    }
}
