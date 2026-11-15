<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\Schema\Collections\KairoDesigns;
use App\Cms\Builder\Schema\Collections\LarkspurDesigns;
use App\Cms\Builder\Schema\Collections\LumikinDesigns;
use App\Cms\Builder\Schema\Collections\MeridianDesigns;
use App\Cms\Builder\Schema\Collections\PebblewoodDesigns;
use App\Cms\Builder\SiteChrome;
use App\Cms\Extensions\ExtensionInstaller;
use App\Cms\Themes\DesignTokens;
use App\Models\Category;
use App\Models\Extension;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    use BuildsDocumentNodes;

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

    /* Colours come from the theme settings (DesignTokens::ref), so designs follow the site's palette. */

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
            $this->heroMinimalSaas(),
            $this->heroEditorialSplit(),
            $this->heroProductLaunch(),

            /* content */
            $this->featureTrio(),
            $this->featureCards(),
            $this->textWithImage(),
            $this->imageWithText(),
            $this->statsBand(),
            $this->howItWorks(),
            $this->contentBentoShowcase(),
            $this->contentSplitChecklist(),
            $this->contentProcessTimeline(),
            $this->contentComparisonMatrix(),

            /* social proof */
            $this->testimonialRow(),
            $this->testimonialSpotlight(),
            $this->logoStrip(),
            $this->ratingBand(),
            $this->socialTestimonialWall(),
            $this->socialCaseStudySpotlight(),
            $this->socialTrustBadges(),

            /* conversion */
            $this->ctaBanner(),
            $this->conversionGradientCard(),

            /* commerce */
            $this->pricingTable(),
            $this->guaranteeBand(),
            $this->commercePricingPro(),
            $this->commerceBundleSpotlight(),

            /* media */
            $this->videoSection(),
            $this->galleryGrid(),
            $this->mediaShowcaseSplit(),
            $this->mediaBentoGallery(),

            /* scheduling */

            /* structure */
            $this->navbarFloatingPill(),
            $this->navbarAnnouncement(),
            $this->navbarDark(),
            $this->navbar(),
            $this->faqSection(),
            $this->structureFaqTwoColumn(),
            $this->footerColumns(),
            $this->structureFooterMinimal(),

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

            /* Atomos99 AI Agency collection */
            $this->atomos99Navbar(),
            $this->atomos99HeroHome(),
            $this->atomos99LogoStrip(),
            $this->atomos99Services(),
            $this->atomos99CaseStudies(),
            $this->atomos99Process(),
            $this->atomos99Benefits(),
            $this->atomos99Testimonials(),
            $this->atomos99Faq(),
            $this->atomos99Footer(),
            $this->atomos99AboutHero(),
            $this->atomos99AboutMission(),
            $this->atomos99AboutStrategy(),
            $this->atomos99AboutTeam(),
            $this->atomos99ServicesHero(),
            $this->atomos99ServicesStrategy(),
            $this->atomos99BlogGrid(),
            $this->atomos99FaqHub(),

            /* Pebblewood early-learning collection */
            ...(new PebblewoodDesigns)->all(),

            /* Lumikin kindergarten collection */
            ...(new LumikinDesigns)->all(),

            /* Meridian global advisory collection */
            ...(new MeridianDesigns)->all(),

            /* Kairo career SaaS & mobile app collection */
            ...(new KairoDesigns)->all(),

            /* Larkspur family health clinic collection */
            ...(new LarkspurDesigns)->all(),
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
            'cta-banner',
            'footer-columns',
        ];
    }

    /**
     * Assembles a full builder document from an ordered list of design block keys.
     *
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public function assembleDocument(array $keys): array
    {
        $sections = [];
        foreach ($keys as $key) {
            $block = $this->find($key);
            if ($block !== null) {
                foreach ($block['document']['sections'] as $section) {
                    $sections[] = $section;
                }
            }
        }

        return [
            'schema_version' => 1,
            'settings' => [
                'contentWidth' => DesignTokens::ref('content_width'),
            ],
            'sections' => $sections,
        ];
    }

    /**
     * Activates the Atomos99 theme and populates the global header, global footer,
     * Home page (/), and About Us page (/about) with editable Visual Builder blocks.
     *
     * @return array{theme: string, home_id: int, about_id: int, header_id: int, footer_id: int}
     */
    public function applyAtomos99Site(): array
    {
        $installer = app(ExtensionInstaller::class);
        $docs = app(PageDocuments::class);

        Extension::query()->where('name', 'modrix/atomos99')->delete();
        $installer->activateTheme('modrix/nexus-ai', null, true);

        // Nexus AI keeps its own header and footer (see SiteChrome): other themes keep theirs.
        $chrome = app(SiteChrome::class);
        $header = $chrome->install(Page::ROLE_HEADER, 'modrix/nexus-ai', $this->assembleDocument(['atomos99-navbar']), $docs);
        $footer = $chrome->install(Page::ROLE_FOOTER, 'modrix/nexus-ai', $this->assembleDocument(['atomos99-footer']), $docs);

        $home = Page::query()->where('is_home', true)->first() ?? Page::query()->create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 0,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $home->update([
            'title' => 'Home',
            'is_home' => true,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 0,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $home = $docs->save($home->fresh(), $this->assembleDocument([
            'atomos99-hero-home',
            'atomos99-logo-strip',
            'atomos99-services',
            'atomos99-case-studies',
            'atomos99-process',
            'atomos99-benefits',
            'atomos99-testimonials',
            'atomos99-faq',
        ]), $home->fresh()->version, null, 'Atomos99 Home');
        $home = $docs->publish($home->fresh());

        $about = Page::query()->where('slug', 'about')->first() ?? Page::query()->create([
            'title' => 'About Us',
            'slug' => 'about',
            'is_home' => false,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 1,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $about->update([
            'title' => 'About Us',
            'slug' => 'about',
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 1,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $about = $docs->save($about->fresh(), $this->assembleDocument([
            'atomos99-about-hero',
            'atomos99-logo-strip',
            'atomos99-about-mission',
            'atomos99-about-strategy',
            'atomos99-about-team',
            'atomos99-testimonials',
            'atomos99-faq',
        ]), $about->fresh()->version, null, 'Atomos99 About Us');
        $about = $docs->publish($about->fresh());

        $services = Page::query()->where('slug', 'services')->first() ?? Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'is_home' => false,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 2,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $services->update([
            'title' => 'Services',
            'slug' => 'services',
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 2,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $services = $docs->save($services->fresh(), $this->assembleDocument([
            'atomos99-services-hero',
            'atomos99-services-strategy',
            'atomos99-process',
            'atomos99-case-studies',
            'atomos99-faq',
        ]), $services->fresh()->version, null, 'Atomos99 Services');
        $services = $docs->publish($services->fresh());

        $faqs = Page::query()->where('slug', 'faqs')->first() ?? Page::query()->create([
            'title' => 'FAQs',
            'slug' => 'faqs',
            'is_home' => false,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 3,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $faqs->update([
            'title' => 'FAQs',
            'slug' => 'faqs',
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 3,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $faqs = $docs->save($faqs->fresh(), $this->assembleDocument([
            'atomos99-faq-hub',
            'atomos99-faq',
            'atomos99-testimonials',
        ]), $faqs->fresh()->version, null, 'Atomos99 FAQs');
        $faqs = $docs->publish($faqs->fresh());

        $blogs = Page::query()->where('slug', 'blogs')->first() ?? Page::query()->create([
            'title' => 'Blogs',
            'slug' => 'blogs',
            'is_home' => false,
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 4,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $blogs->update([
            'title' => 'Blogs',
            'slug' => 'blogs',
            'editor' => Page::EDITOR_BUILDER,
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 4,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $blogs = $docs->save($blogs->fresh(), $this->assembleDocument([
            'atomos99-blog-grid',
            'atomos99-faq',
        ]), $blogs->fresh()->version, null, 'Atomos99 Blogs');
        $blogs = $docs->publish($blogs->fresh());

        $this->seedAtomos99BlogAndFaqs();

        return [
            'theme' => 'modrix/nexus-ai',
            'home_id' => $home->id,
            'about_id' => $about->id,
            'services_id' => $services->id,
            'faqs_id' => $faqs->id,
            'blogs_id' => $blogs->id,
            'header_id' => $header->id,
            'footer_id' => $footer->id,
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '64px'),
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
                                'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                                'fontSize' => $this->font('19px', '16px'),
                                'maxWidth' => $this->responsive('560px'),
                                'margin' => $this->responsive(['top' => '20px', 'right' => 'auto', 'bottom' => '32px', 'left' => 'auto']),
                            ],
                        ),
                        $this->button('Start free', 'lg'),
                        $this->paragraph('No card needed. Cancel in two clicks.', [
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px')],
                rows: [$this->row([
                    $this->column(7, [
                        $this->heading('Everything that happens after the click', 'h1', [
                            'fontSize' => $this->font('44px', '30px'),
                            'lineHeight' => $this->responsive('1.15'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                        ]),
                        $this->paragraph(
                            'Pages, forms, CRM, email and automation in one place, so a lead never falls through a gap between tools.',
                            [
                                'fontSize' => $this->font('19px', '16px'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Watch it built in four minutes', 'h1', [
                                'fontSize' => $this->font('42px', '28px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('No slides. A real funnel, from blank page to first lead.', [
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                'color' => $this->responsive(DesignTokens::ref('primary')),
            ]),
            $this->heading($heading, 'h3', [
                'fontSize' => $this->responsive('20px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
            ]),
            $this->paragraph($copy, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.6'),
            ]),
        ]);

        return $this->block(
            key: 'feature-trio',
            name: 'Three features',
            category: self::CATEGORY_CONTENT,
            description: 'Three columns of icon, heading and a short paragraph.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Everything in the box', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [$this->row([
                    $this->column(7, [
                        $this->heading('One place for the whole journey', 'h2', [
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                        ]),
                        $this->paragraph(
                            'From the first ad click to the receipt, every step is recorded against the same contact — so your reporting is not a guess.',
                            [
                                'color' => $this->responsive(DesignTokens::ref('body')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                        ]),
                        $this->paragraph(
                            'Every branch is a condition on data you already hold. Open a run and see exactly which path a contact took, and why.',
                            [
                                'color' => $this->responsive(DesignTokens::ref('body')),
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
                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
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
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'letterSpacing' => $this->responsive('0.14em'),
                'textTransform' => $this->responsive('uppercase'),
            ]),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('21px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
            ]),
            $this->paragraph($copy, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.6'),
            ]),
        ]);

        return $this->block(
            key: 'how-it-works',
            name: 'How it works',
            category: self::CATEGORY_CONTENT,
            description: 'Three numbered steps with a heading above them.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Live in three steps', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('Most people finish the first funnel in an afternoon.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px'),
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
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad('56px', '24px', '40px'),
                    'borderRadius' => $this->responsive('16px'),
                ],
                rows: [$this->row([
                    $this->column(8, [
                        $this->heading('Ready to build your first funnel?', 'h2', [
                            'fontSize' => $this->font('30px', '24px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                        ]),
                        $this->paragraph('Free for fourteen days. Your pages stay yours if you leave.', [
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Simple pricing', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('Every plan includes unlimited pages and your own domain.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                            'color' => $this->responsive(DesignTokens::ref('primary')),
                        ]),
                    ]),
                    $this->column(10, [
                        $this->heading('Thirty days, no questions asked', 'h3', [
                            'fontSize' => $this->responsive('20px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                        ]),
                        $this->paragraph(
                            'Try it properly. If it has not earned its price back within a month, reply to your receipt and we refund the whole thing.',
                            [
                                'color' => $this->responsive(DesignTokens::ref('body')),
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
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('See it on a real funnel', 'h2', [
                            'fontSize' => $this->font('32px', '25px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
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
                            'color' => $this->responsive(DesignTokens::ref('heading')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Questions people ask', 'h2', [
                            'textAlign' => $this->responsive('center'),
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
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
                'color' => $this->responsive(DesignTokens::ref('muted')),
                'textTransform' => $this->responsive('uppercase'),
                'letterSpacing' => $this->responsive('0.1em'),
            ]),
            $this->blockNode(BlockType::Navigation, [
                'items' => $items,
                'orientation' => 'vertical',
            ], [
                'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad('64px', '24px', '48px'),
                ],
                rows: [$this->row([
                    $this->column(3, [
                        $this->blockNode(BlockType::Logo, ['url' => '/', 'useWorkspaceBranding' => true]),
                        $this->paragraph('Everything after the click.', [
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
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
                            'color' => $this->responsive(DesignTokens::ref('body')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px'),
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
                                'backgroundColor' => $this->responsive(DesignTokens::ref('accent')),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'padding' => $this->responsive(['top' => '18px', 'right' => '36px', 'bottom' => '18px', 'left' => '36px']),
                                'borderRadius' => $this->responsive('999px'),
                                'boxShadow' => $this->responsive('0 12px 30px rgba(245,158,11,0.35)'),
                            ]),
                            $this->paragraph('🔒 Secure checkout · 30-day money-back guarantee', [
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Here is everything you get today', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Join today', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('One payment. Lifetime access. Every future update included.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                                'color' => $this->responsive(DesignTokens::ref('body')),
                            ]),
                            $this->heading('Wait! Add the done-for-you templates for 60% off', 'h1', [
                                'fontSize' => $this->font('40px', '28px'),
                                'lineHeight' => $this->responsive('1.15'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad('64px', '24px', '40px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [$this->row([
                    $this->column(12, [
                        $this->heading('Not ready for everything? Take the essentials instead', 'h1', [
                            'fontSize' => $this->font('36px', '26px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                            'maxWidth' => $this->responsive('760px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                        ]),
                        $this->paragraph('The 10 most-used funnel templates, on their own, for a one-time $27.', [
                            'color' => $this->responsive(DesignTokens::ref('body')),
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
                            'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('A receipt and your login details are on their way to your inbox.', [
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '42px'),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
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
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'fontSize' => $this->font('24px', '20px'),
                                'margin' => $this->responsive(['top' => '20px', 'bottom' => '18px']),
                            ]),
                            $this->upsellButton('Yes, add this to my order', 'lg', [
                                'backgroundColor' => $this->responsive('#ea580c'),
                                'borderRadius' => $this->responsive('12px'),
                                'boxShadow' => $this->responsive('0 14px 30px rgba(234,88,12,0.28)'),
                            ]),
                            $this->nextStepButton('No thanks, continue without it', 'sm', 'link', [
                                'color' => $this->responsive(DesignTokens::ref('muted')),
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
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '42px'),
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
                                'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '34px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->paragraph('ESSENTIAL', ['color' => $this->responsive(DesignTokens::ref('muted')), 'fontWeight' => $this->responsive('700')]),
                            $this->heading('Quick-start pack', 'h2', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('28px', '23px')]),
                            $this->paragraph('The core templates and implementation guide.', ['color' => $this->responsive(DesignTokens::ref('body')), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '22px'])]),
                            $this->upsellButton('Add for $37', 'lg', ['borderRadius' => $this->responsive('10px')]),
                        ], style: [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '30px', 'bottom' => '32px', 'left' => '30px']),
                            'borderRadius' => $this->responsive('18px'),
                        ]),
                        $this->column(6, [
                            $this->paragraph('BEST VALUE', ['color' => $this->responsive('#86efac'), 'fontWeight' => $this->responsive('800')]),
                            $this->heading('Complete growth bundle', 'h2', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('28px', '23px')]),
                            $this->paragraph('All templates, training, bonuses and future updates.', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted')), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '22px'])]),
                            $this->upsellButton('Add everything for $77', 'lg', [
                                'backgroundColor' => $this->responsive(self::SUCCESS),
                                'borderRadius' => $this->responsive('10px'),
                            ]),
                        ], style: [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '30px', 'bottom' => '32px', 'left' => '30px']),
                            'borderRadius' => $this->responsive('18px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#22c55e', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px']),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->nextStepButton('Skip upgrades and finish my order', 'sm', 'link', [
                                'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
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
                        $this->heading('Get the companion workbook for only $19', 'h3', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('25px', '21px'), 'margin' => $this->responsive(['top' => '8px'])]),
                        $this->paragraph('Turn every lesson into an action plan with prompts, worksheets and printable checklists.', ['color' => $this->responsive(DesignTokens::ref('body')), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '18px'])]),
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
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
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
                        $this->heading('Your free guide is ready', 'h1', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('42px', '29px')]),
                        $this->paragraph('We also sent a copy to your inbox. If it is not there in five minutes, check Promotions or Spam.', ['color' => $this->responsive(DesignTokens::ref('body')), 'fontSize' => $this->font('18px', '16px'), 'maxWidth' => $this->responsive('680px'), 'margin' => $this->responsive(['top' => '12px', 'right' => 'auto', 'bottom' => '28px', 'left' => 'auto'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Download the guide', 'action' => 'url', 'url' => '#', 'iconRight' => 'download', 'size' => 'lg', 'variant' => 'primary'], ['borderRadius' => $this->responsive('12px')]),
                    ])]),
                    $this->row([$this->column(12, [
                        $this->heading('Want help putting it into action?', 'h2', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('28px', '23px')]),
                        $this->paragraph('See the short training we prepared as your next step.', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted')), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '18px'])]),
                        $this->nextStepButton('Watch the free training', 'lg', 'primary', ['backgroundColor' => $this->responsive('#ffffff'), 'color' => $this->responsive(DesignTokens::ref('heading')), 'borderRadius' => $this->responsive('10px')]),
                    ], style: [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
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
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '46px')],
                rows: [
                    $this->row([$this->column(12, [
                        $this->paragraph('MEETING CONFIRMED', ['color' => $this->responsive(self::SUCCESS), 'fontWeight' => $this->responsive('800'), 'letterSpacing' => $this->responsive('0.12em'), 'textAlign' => $this->responsive('center')]),
                        $this->heading('You are booked. Here is what happens next.', 'h1', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('42px', '29px'), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '36px'])]),
                    ])]),
                    $this->row([
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'calendar-check', 'size' => '28px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]), $this->heading('Save the date', 'h3', ['color' => $this->responsive(DesignTokens::ref('heading'))]), $this->paragraph('Use the calendar link in your confirmation email.', ['color' => $this->responsive(DesignTokens::ref('body'))])], style: ['backgroundColor' => $this->responsive(DesignTokens::ref('surface')), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'clipboard-check', 'size' => '28px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]), $this->heading('Complete the brief', 'h3', ['color' => $this->responsive(DesignTokens::ref('heading'))]), $this->paragraph('Reply with the short details requested before the call.', ['color' => $this->responsive(DesignTokens::ref('body'))])], style: ['backgroundColor' => $this->responsive(DesignTokens::ref('surface')), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
                        $this->column(4, [$this->blockNode(BlockType::Icon, ['name' => 'video', 'size' => '28px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]), $this->heading('Join prepared', 'h3', ['color' => $this->responsive(DesignTokens::ref('heading'))]), $this->paragraph('Bring your top goal and any questions you want answered.', ['color' => $this->responsive(DesignTokens::ref('body'))])], style: ['backgroundColor' => $this->responsive(DesignTokens::ref('surface')), 'padding' => $this->responsive(['top' => '26px', 'right' => '24px', 'bottom' => '26px', 'left' => '24px']), 'borderRadius' => $this->responsive('14px')]),
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
                        $this->heading('Choose the action style that fits', 'h2', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('30px', '24px'), 'textAlign' => $this->responsive('center')]),
                        $this->paragraph('Every button is independently editable, including its action, destination, icons, size and colours.', ['color' => $this->responsive(DesignTokens::ref('muted')), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '26px'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Start now', 'action' => 'url', 'url' => '#', 'iconRight' => 'arrow-right', 'size' => 'lg', 'variant' => 'primary'], ['backgroundColor' => $this->responsive(DesignTokens::ref('primary')), 'borderRadius' => $this->responsive('999px'), 'boxShadow' => $this->responsive('0 12px 28px rgba(79,70,229,0.28)')]),
                        $this->blockNode(BlockType::Button, ['label' => 'See how it works', 'action' => 'url', 'url' => '#', 'iconLeft' => 'play', 'size' => 'lg', 'variant' => 'secondary'], ['borderRadius' => $this->responsive('999px'), 'margin' => $this->responsive(['top' => '12px'])]),
                        $this->blockNode(BlockType::Button, ['label' => 'Not ready? Learn more', 'action' => 'url', 'url' => '#', 'size' => 'sm', 'variant' => 'link'], ['color' => $this->responsive(DesignTokens::ref('body')), 'margin' => $this->responsive(['top' => '12px'])]),
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
                    $this->paragraph('Secure checkout · Instant access · Cancel anytime', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted')), 'fontSize' => $this->responsive('13px'), 'textAlign' => $this->responsive('center'), 'margin' => $this->responsive(['top' => '14px'])]),
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
                        $this->paragraph('Instant access plus every future update.', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted')), 'fontSize' => $this->responsive('14px'), 'margin' => $this->responsive(['top' => '4px'])]),
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

    /**
     * @return array<string, mixed>
     */
    private function heroMinimalSaas(): array
    {
        $metric = fn (string $value, string $label): array => $this->column(4, [
            $this->heading($value, 'h3', [
                'fontSize' => $this->font('34px', '26px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'textAlign' => $this->responsive('center'),
            ]),
            $this->paragraph($label, [
                'color' => $this->responsive(DesignTokens::ref('muted')),
                'fontSize' => $this->responsive('14px'),
                'textAlign' => $this->responsive('center'),
                'margin' => $this->responsive(['top' => '4px']),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'padding' => $this->responsive(['top' => '20px', 'right' => '18px', 'bottom' => '20px', 'left' => '18px']),
            'borderRadius' => $this->responsive('14px'),
        ]);

        return $this->block(
            key: 'hero-minimal-saas',
            name: 'Hero · Minimal SaaS',
            category: self::CATEGORY_HERO,
            description: 'Release pill, high-impact headline, dual buttons and a three-card proof row.',
            sections: [$this->section(
                style: [
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px'),
                    'textAlign' => $this->responsive('center'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('NEW RELEASE · ZERO PLATFORM FEES', [
                                'color' => $this->responsive(DesignTokens::ref('primary')),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.12em'),
                            ]),
                            $this->heading('Own your storefront, your customer data and every sale', 'h1', [
                                'fontSize' => $this->font('54px', '34px'),
                                'lineHeight' => $this->responsive('1.1'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'maxWidth' => $this->responsive('820px'),
                                'margin' => $this->responsive(['top' => '14px', 'right' => 'auto', 'bottom' => '20px', 'left' => 'auto']),
                            ]),
                            $this->paragraph('Launch high-converting pages, manage products and scale without paying a percentage of your revenue.', [
                                'fontSize' => $this->font('19px', '16px'),
                                'color' => $this->responsive(DesignTokens::ref('body')),
                                'maxWidth' => $this->responsive('620px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
                            ]),
                            $this->button('Start building free', 'lg'),
                        ]),
                    ]),
                    $this->row([
                        $metric('38ms', 'Median page response'),
                        $metric('0%', 'Platform transaction fee'),
                        $metric('100%', 'Self-hosted data ownership'),
                    ], ['margin' => $this->responsive(['top' => '42px'])]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroEditorialSplit(): array
    {
        return $this->block(
            key: 'hero-editorial-split',
            name: 'Hero · Editorial split',
            category: self::CATEGORY_HERO,
            description: 'Dark contrast two-column hero with a benefit checklist and product preview.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px'),
                ],
                rows: [$this->row([
                    $this->column(7, [
                        $this->paragraph('COMMERCE WITHOUT LOCK-IN', [
                            'color' => $this->responsive('#c6f24e'),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.12em'),
                        ]),
                        $this->heading('Built for brands that outgrow cookie-cutter templates', 'h1', [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('46px', '32px'),
                            'lineHeight' => $this->responsive('1.12'),
                            'margin' => $this->responsive(['top' => '12px', 'bottom' => '18px']),
                        ]),
                        $this->paragraph('Combine visual drag-and-drop speed with clean, extensible code on your own server.', [
                            'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            'fontSize' => $this->font('18px', '16px'),
                            'margin' => $this->responsive(['bottom' => '24px']),
                        ]),
                        $this->button('Explore the platform', 'lg'),
                    ]),
                    $this->column(5, [
                        $this->image('Storefront and admin preview', [
                            'borderRadius' => $this->responsive('18px'),
                            'boxShadow' => $this->responsive('0 24px 60px rgba(0,0,0,0.4)'),
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroProductLaunch(): array
    {
        return $this->block(
            key: 'hero-product-launch',
            name: 'Hero · Product launch',
            category: self::CATEGORY_HERO,
            description: 'Centred launch hero paired with three immediate capability cards underneath.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '52px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Meet the next generation of visual commerce', 'h1', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('48px', '32px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('Design sections live, preview across every device and publish with instant version rollback.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('body')),
                                'fontSize' => $this->font('18px', '16px'),
                                'maxWidth' => $this->responsive('640px'),
                                'margin' => $this->responsive(['top' => '14px', 'right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
                            ]),
                            $this->button('Try the live demo', 'lg', style: ['textAlign' => $this->responsive('center')]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->blockNode(BlockType::FeatureCards, [
                                'columns' => 3,
                                'items' => [
                                    ['icon' => 'zap', 'title' => 'Instant publishing', 'body' => 'Draft safely and push changes live in one click.'],
                                    ['icon' => 'shield', 'title' => 'Version history', 'body' => 'Restore any earlier snapshot of your page at any time.'],
                                    ['icon' => 'globe', 'title' => 'Theme tokens', 'body' => 'Every section automatically inherits your brand palette.'],
                                ],
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
    private function contentBentoShowcase(): array
    {
        return $this->block(
            key: 'content-bento-showcase',
            name: 'Content · Bento showcase',
            category: self::CATEGORY_CONTENT,
            description: 'Asymmetric multi-card bento grid with a dark spotlight card and light feature tiles.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Everything you need, arranged for clarity', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(7, [
                            $this->paragraph('VISUAL ARCHITECTURE', [
                                'color' => $this->responsive('#c6f24e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.1em'),
                            ]),
                            $this->heading('Sections, rows and columns that adapt automatically', 'h3', [
                                'color' => $this->responsive('#ffffff'),
                                'fontSize' => $this->font('28px', '22px'),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '12px']),
                            ]),
                            $this->paragraph('Fine-tune desktop, tablet and mobile spacing independently without writing media queries.', [
                                'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px']),
                            'borderRadius' => $this->responsive('20px'),
                        ]),
                        $this->column(5, [
                            $this->blockNode(BlockType::Icon, ['name' => 'zap', 'size' => '28px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
                            $this->heading('Zero bloat on the storefront', 'h3', [
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'fontSize' => $this->font('24px', '20px'),
                                'margin' => $this->responsive(['top' => '12px', 'bottom' => '10px']),
                            ]),
                            $this->paragraph('Published pages render clean semantic markup that scores 99+ on Lighthouse.', [
                                'color' => $this->responsive(DesignTokens::ref('body')),
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'padding' => $this->responsive(['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px']),
                            'borderRadius' => $this->responsive('20px'),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contentSplitChecklist(): array
    {
        return $this->block(
            key: 'content-split-checklist',
            name: 'Content · Split checklist',
            category: self::CATEGORY_CONTENT,
            description: 'An image on the left paired with a vertical checklist of capabilities on the right.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [$this->row([
                    $this->column(5, [
                        $this->image('Feature workflow preview', ['borderRadius' => $this->responsive('16px')]),
                    ]),
                    $this->column(7, [
                        $this->heading('Designed for daily operations', 'h2', [
                            'fontSize' => $this->font('34px', '26px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                            'margin' => $this->responsive(['bottom' => '16px']),
                        ]),
                        $this->blockNode(BlockType::FeatureCards, [
                            'columns' => 1,
                            'items' => [
                                ['icon' => 'check', 'title' => 'Global header & footer', 'body' => 'Edit your navigation and footer once and share them across every page.'],
                                ['icon' => 'check', 'title' => 'Reusable saved sections', 'body' => 'Save any customized section to your library and drop it onto new pages.'],
                                ['icon' => 'check', 'title' => 'Safe draft & preview', 'body' => 'Test layout changes in private before publishing them to visitors.'],
                            ],
                        ]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contentProcessTimeline(): array
    {
        $milestone = fn (string $step, string $title, string $body): array => $this->column(3, [
            $this->paragraph($step, [
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'fontSize' => $this->responsive('13px'),
                'fontWeight' => $this->responsive('700'),
                'letterSpacing' => $this->responsive('0.1em'),
            ]),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('20px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'margin' => $this->responsive(['top' => '8px', 'bottom' => '8px']),
            ]),
            $this->paragraph($body, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'fontSize' => $this->responsive('15px'),
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'padding' => $this->responsive(['top' => '24px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px']),
            'borderRadius' => $this->responsive('14px'),
        ]);

        return $this->block(
            key: 'content-process-timeline',
            name: 'Content · Four-step timeline',
            category: self::CATEGORY_CONTENT,
            description: 'Four milestone cards showing your onboarding, delivery or product roadmap.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('From idea to live store in four steps', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $milestone('STEP 01', 'Install on your host', 'Run the web installer on any PHP & MySQL server in two minutes.'),
                        $milestone('STEP 02', 'Pick your theme', 'Choose a palette and typography scale that fits your brand.'),
                        $milestone('STEP 03', 'Drop in sections', 'Assemble pages from ready-made hero, pricing and proof blocks.'),
                        $milestone('STEP 04', 'Go live', 'Connect your custom domain and start taking orders with 0% fees.'),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contentComparisonMatrix(): array
    {
        return $this->block(
            key: 'content-comparison-matrix',
            name: 'Content · Us vs. others',
            category: self::CATEGORY_CONTENT,
            description: 'Side-by-side comparison contrasting self-hosted ownership against rented platforms.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Why teams switch to self-hosted commerce', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->paragraph('OUR PLATFORM', ['color' => $this->responsive('#86efac'), 'fontWeight' => $this->responsive('700'), 'fontSize' => $this->responsive('13px')]),
                            $this->heading('100% yours, zero revenue share', 'h3', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('26px', '21px'), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '16px'])]),
                            $this->blockNode(BlockType::FeatureCards, [
                                'columns' => 1,
                                'items' => [
                                    ['icon' => 'check', 'title' => '$0 license & 0% transaction fees', 'body' => 'Keep every dollar of revenue as you scale.'],
                                    ['icon' => 'check', 'title' => 'Full source code & database', 'body' => 'Customize anything and host on any cloud provider.'],
                                ],
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                            'padding' => $this->responsive(['top' => '30px', 'right' => '28px', 'bottom' => '30px', 'left' => '28px']),
                            'borderRadius' => $this->responsive('18px'),
                        ]),
                        $this->column(6, [
                            $this->paragraph('TRADITIONAL SAAS', ['color' => $this->responsive(DesignTokens::ref('muted')), 'fontWeight' => $this->responsive('700'), 'fontSize' => $this->responsive('13px')]),
                            $this->heading('Rented infrastructure & rising fees', 'h3', ['color' => $this->responsive(DesignTokens::ref('heading')), 'fontSize' => $this->font('26px', '21px'), 'margin' => $this->responsive(['top' => '8px', 'bottom' => '16px'])]),
                            $this->blockNode(BlockType::FeatureCards, [
                                'columns' => 1,
                                'items' => [
                                    ['icon' => 'shield', 'title' => '$79–$399/mo plus 2% per order', 'body' => 'Costs compound with every additional staff seat and app.'],
                                    ['icon' => 'shield', 'title' => 'Closed proprietary templates', 'body' => 'Locked into their checkout rules and hosting limits.'],
                                ],
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                            'padding' => $this->responsive(['top' => '30px', 'right' => '28px', 'bottom' => '30px', 'left' => '28px']),
                            'borderRadius' => $this->responsive('18px'),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function socialTestimonialWall(): array
    {
        $card = fn (string $quote, string $name, string $role): array => $this->column(4, [
            $this->blockNode(BlockType::Testimonial, [
                'quote' => $quote,
                'authorName' => $name,
                'authorTitle' => $role,
                'rating' => 5,
                'layout' => 'card',
            ]),
        ]);

        return $this->block(
            key: 'social-testimonial-wall',
            name: 'Social proof · Review wall',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'A six-card customer review wall with heading and five-star ratings.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Loved by merchants and developers alike', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $card('Migrating took a single weekend and cut our monthly software bill by 80%.', 'Elena R.', 'Founder, Studio Nord'),
                        $card('The visual builder lets our marketing team launch campaigns without engineering tickets.', 'Marcus V.', 'Head of Growth'),
                        $card('Cleanest Laravel codebase I have worked with. Extending it is a joy.', 'Priya N.', 'Lead Engineer'),
                    ]),
                    $this->row([
                        $card('Page speed jumped from 68 to 99 on mobile the day we switched.', 'Liam K.', 'E-commerce Director'),
                        $card('Having both structured blocks and a visual canvas covers every page type we need.', 'Hannah S.', 'Creative Director'),
                        $card('Zero transaction fees paid for our dedicated server in the first week.', 'Omar T.', 'Store Owner'),
                    ], ['margin' => $this->responsive(['top' => '20px'])]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function socialCaseStudySpotlight(): array
    {
        return $this->block(
            key: 'social-case-study-spotlight',
            name: 'Social proof · Case study spotlight',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Featured customer outcome paired with key ROI metrics.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [$this->row([
                    $this->column(7, [
                        $this->paragraph('CASE STUDY · ATELIER SUPPLY', [
                            'color' => $this->responsive('#c6f24e'),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.1em'),
                        ]),
                        $this->blockNode(BlockType::Testimonial, [
                            'quote' => 'Switching gave us sub-second page loads across Europe and North America while saving $38,000 a year in platform fees.',
                            'authorName' => 'Claire Beaumont',
                            'authorTitle' => 'Managing Director, Atelier Supply',
                            'rating' => 5,
                            'layout' => 'bare',
                        ], [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('24px', '19px'),
                            'margin' => $this->responsive(['top' => '12px']),
                        ]),
                    ]),
                    $this->column(5, [
                        $this->heading('+44%', 'h3', ['color' => $this->responsive('#c6f24e'), 'fontSize' => $this->font('42px', '32px')]),
                        $this->paragraph('Increase in mobile checkout conversion', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted')), 'margin' => $this->responsive(['bottom' => '20px'])]),
                        $this->heading('2.1x', 'h3', ['color' => $this->responsive('#ffffff'), 'fontSize' => $this->font('42px', '32px')]),
                        $this->paragraph('Faster landing page publishing cadence', ['color' => $this->responsive(DesignTokens::ref('on_dark_muted'))]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function socialTrustBadges(): array
    {
        $badge = fn (string $icon, string $title, string $copy): array => $this->column(3, [
            $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '26px'], ['color' => $this->responsive(DesignTokens::ref('primary'))]),
            $this->heading($title, 'h4', ['fontSize' => $this->responsive('17px'), 'color' => $this->responsive(DesignTokens::ref('heading')), 'margin' => $this->responsive(['top' => '10px', 'bottom' => '4px'])]),
            $this->paragraph($copy, ['color' => $this->responsive(DesignTokens::ref('muted')), 'fontSize' => $this->responsive('14px')]),
        ]);

        return $this->block(
            key: 'social-trust-badges',
            name: 'Social proof · Trust & security strip',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Four reassurance badges for security, uptime, support and ownership.',
            sections: [$this->section(
                style: ['padding' => $this->pad('44px', '24px', '32px')],
                rows: [$this->row([
                    $badge('shield', 'Encrypted & secure', 'TLS 1.3, signed updates and role-based access control.'),
                    $badge('zap', 'Edge-ready speed', 'Built for instant response times under peak load.'),
                    $badge('globe', 'Global multi-currency', 'Sell in any currency and language from one catalog.'),
                    $badge('heart', '30-day guarantee', 'Risk-free onboarding with full data export anytime.'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function conversionGradientCard(): array
    {
        return $this->block(
            key: 'conversion-gradient-card',
            name: 'CTA · Dark contrast card',
            category: self::CATEGORY_CONVERSION,
            description: 'High-contrast rounded callout card with badge, headline and action button.',
            sections: [$this->section(
                style: ['padding' => $this->pad('56px', '24px', '40px')],
                rows: [$this->row([
                    $this->column(12, [
                        $this->paragraph('READY TO LAUNCH', [
                            'color' => $this->responsive('#c6f24e'),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.12em'),
                            'textAlign' => $this->responsive('center'),
                        ]),
                        $this->heading('Start building your store this afternoon', 'h2', [
                            'color' => $this->responsive('#ffffff'),
                            'fontSize' => $this->font('38px', '28px'),
                            'textAlign' => $this->responsive('center'),
                            'margin' => $this->responsive(['top' => '10px', 'bottom' => '14px']),
                        ]),
                        $this->paragraph('Install free on your own server or spin up a managed cloud workspace in 60 seconds.', [
                            'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            'textAlign' => $this->responsive('center'),
                            'maxWidth' => $this->responsive('560px'),
                            'margin' => $this->responsive(['right' => 'auto', 'bottom' => '26px', 'left' => 'auto']),
                        ]),
                        $this->button('Get started free', 'lg', style: ['textAlign' => $this->responsive('center')]),
                    ], [
                        'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                        'padding' => $this->responsive(['top' => '48px', 'right' => '32px', 'bottom' => '48px', 'left' => '32px']),
                        'borderRadius' => $this->responsive('24px'),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function commercePricingPro(): array
    {
        return $this->block(
            key: 'commerce-pricing-pro',
            name: 'Pricing · Three-tier + guarantee',
            category: self::CATEGORY_COMMERCE,
            description: 'Three-tier pricing comparison paired with a 30-day money-back guarantee banner.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '56px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Transparent plans for every stage', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('36px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                            ]),
                            $this->paragraph('Switch plans or export your data at any time.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
                                'margin' => $this->responsive(['top' => '10px', 'bottom' => '32px']),
                            ]),
                            $this->blockNode(BlockType::PricingTable, [
                                'columns' => 3,
                                'plans' => [
                                    [
                                        'name' => 'Community',
                                        'price' => '$0',
                                        'interval' => 'forever',
                                        'features' => ['Self-hosted MIT core', 'Unlimited pages & products', 'Visual & block builders', 'Community support'],
                                        'buttonLabel' => 'Download free',
                                        'buttonUrl' => null,
                                        'highlighted' => false,
                                    ],
                                    [
                                        'name' => 'Pro Cloud',
                                        'price' => '$49',
                                        'interval' => 'month',
                                        'features' => ['Everything in Community', 'Managed global CDN & SSL', 'Automated daily backups', 'Priority 24/7 support'],
                                        'buttonLabel' => 'Start free trial',
                                        'buttonUrl' => null,
                                        'highlighted' => true,
                                    ],
                                    [
                                        'name' => 'Enterprise',
                                        'price' => '$199',
                                        'interval' => 'month',
                                        'features' => ['Dedicated cloud cluster', 'Staging & Git workflows', 'Custom SLA & onboarding', 'Dedicated account engineer'],
                                        'buttonLabel' => 'Contact sales',
                                        'buttonUrl' => null,
                                        'highlighted' => false,
                                    ],
                                ],
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('🔒 Protected by our 30-day no-questions-asked refund guarantee.', [
                                'textAlign' => $this->responsive('center'),
                                'color' => $this->responsive(DesignTokens::ref('muted')),
                                'fontSize' => $this->responsive('14px'),
                                'margin' => $this->responsive(['top' => '24px']),
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
    private function commerceBundleSpotlight(): array
    {
        return $this->block(
            key: 'commerce-bundle-spotlight',
            name: 'Commerce · Featured bundle',
            category: self::CATEGORY_COMMERCE,
            description: 'Two-column product or bundle spotlight with included items, price anchor and CTA.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [$this->row([
                    $this->column(6, [
                        $this->image('Featured bundle preview', ['borderRadius' => $this->responsive('18px')]),
                    ]),
                    $this->column(6, [
                        $this->paragraph('COMPLETE STARTER KIT', [
                            'color' => $this->responsive(DesignTokens::ref('primary')),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.1em'),
                        ]),
                        $this->heading('Everything you need to launch in one bundle', 'h2', [
                            'fontSize' => $this->font('32px', '24px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                            'margin' => $this->responsive(['top' => '8px', 'bottom' => '16px']),
                        ]),
                        $this->blockNode(BlockType::FeatureCards, [
                            'columns' => 1,
                            'items' => [
                                ['icon' => 'check', 'title' => '6 premium storefront themes', 'body' => 'Ready for fashion, electronics, digital goods and B2B.'],
                                ['icon' => 'check', 'title' => '15 essential commerce modules', 'body' => 'Reviews, wishlists, analytics, SEO and payment gateways.'],
                            ],
                        ]),
                        $this->button('Get the bundle', 'lg', style: ['margin' => $this->responsive(['top' => '22px'])]),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaShowcaseSplit(): array
    {
        return $this->block(
            key: 'media-showcase-split',
            name: 'Media · Video & takeaways',
            category: self::CATEGORY_MEDIA,
            description: 'Embedded video player beside a column of key takeaways and a call to action.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [$this->row([
                    $this->column(7, [
                        $this->blockNode(BlockType::Video, [
                            'provider' => 'youtube',
                            'url' => null,
                            'controls' => true,
                            'aspectRatio' => '16:9',
                        ], ['borderRadius' => $this->responsive('16px')]),
                    ]),
                    $this->column(5, [
                        $this->heading('See the workflow in action', 'h2', [
                            'fontSize' => $this->font('30px', '24px'),
                            'color' => $this->responsive(DesignTokens::ref('heading')),
                            'margin' => $this->responsive(['bottom' => '14px']),
                        ]),
                        $this->paragraph('Watch how sections snap into place, inherit theme colours automatically and publish in seconds.', [
                            'color' => $this->responsive(DesignTokens::ref('body')),
                            'margin' => $this->responsive(['bottom' => '20px']),
                        ]),
                        $this->button('Start your own page', 'md'),
                    ]),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaBentoGallery(): array
    {
        return $this->block(
            key: 'media-bento-gallery',
            name: 'Gallery · Curated lookbook',
            category: self::CATEGORY_MEDIA,
            description: 'Four-column visual gallery with heading and supporting description.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                    'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Designed to make your products shine', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '28px']),
                            ]),
                            $this->blockNode(BlockType::Gallery, ['columns' => 4, 'gap' => '16px', 'lightbox' => true]),
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function structureFaqTwoColumn(): array
    {
        return $this->block(
            key: 'structure-faq-two-column',
            name: 'FAQ · Two-column grid',
            category: self::CATEGORY_STRUCTURE,
            description: 'Two side-by-side FAQ accordions for covering product and billing questions.',
            sections: [$this->section(
                style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px')],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('Frequently asked questions', 'h2', [
                                'textAlign' => $this->responsive('center'),
                                'fontSize' => $this->font('34px', '26px'),
                                'color' => $this->responsive(DesignTokens::ref('heading')),
                                'margin' => $this->responsive(['bottom' => '32px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->blockNode(BlockType::Faq, [
                                'items' => [
                                    ['question' => 'How fast can I launch?', 'answer' => 'Most stores install and publish their first pages the same afternoon.'],
                                    ['question' => 'Can I customize the design?', 'answer' => 'Every colour, font, spacing token and section layout is editable live.'],
                                ],
                                'allowMultipleOpen' => true,
                                'emitSchemaOrg' => true,
                            ]),
                        ]),
                        $this->column(6, [
                            $this->blockNode(BlockType::Faq, [
                                'items' => [
                                    ['question' => 'Do I own my database?', 'answer' => 'Yes. Everything runs on your database with full export and backup access.'],
                                    ['question' => 'Are updates included?', 'answer' => 'Yes — one-click core and extension updates with automatic rollback.'],
                                ],
                                'allowMultipleOpen' => true,
                                'emitSchemaOrg' => false,
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
    private function structureFooterMinimal(): array
    {
        return $this->block(
            key: 'structure-footer-minimal',
            name: 'Footer · Minimal bar',
            category: self::CATEGORY_STRUCTURE,
            description: 'Compact single-row footer with brand logo, horizontal navigation links and social icons.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                    'padding' => $this->pad('36px', '24px', '28px'),
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
                                ['label' => 'FAQ', 'url' => '#faq'],
                                ['label' => 'Privacy', 'url' => '#privacy'],
                            ],
                            'orientation' => 'horizontal',
                        ], [
                            'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            'justifyContent' => $this->responsive('center'),
                        ]),
                    ]),
                    $this->column(3, [
                        $this->blockNode(BlockType::SocialIcons, [
                            'networks' => [
                                ['network' => 'x', 'url' => null],
                                ['network' => 'github', 'url' => null],
                                ['network' => 'linkedin', 'url' => null],
                            ],
                            'size' => '18px',
                            'shape' => 'circle',
                        ], ['color' => $this->responsive(DesignTokens::ref('on_dark_muted'))]),
                    ]),
                ], ['alignItems' => $this->responsive('center')])],
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

    /* ---------------------------------------------------- Atomos99 AI Agency */

    /**
     * Inline SVG data URI for the Nexus AI mark + wordmark so it renders cleanly across every environment.
     */
    private function nexusAiLogoDataUri(bool $darkText = true): string
    {
        $textColor = $darkText ? '#09090B' : '#FFFFFF';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="165" height="36" viewBox="0 0 165 36" fill="none">'
            .'<path d="M15.6 4.2C16.7 2.2 19.5 2.2 20.6 4.2L31.8 24.4C32.9 26.4 31.5 28.9 29.2 28.9H23.7C22.4 28.9 21.2 28.2 20.6 27.1L18.1 22.4L15.6 27.1C15.0 28.2 13.8 28.9 12.5 28.9H7.0C4.7 28.9 3.3 26.4 4.4 24.4L15.6 4.2Z" fill="#FF542E"/>'
            .'<path d="M18.1 13.5L22.3 21.4H13.9L18.1 13.5Z" fill="#FFFFFF"/>'
            .'<text x="41" y="24.5" fill="'.$textColor.'" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="21" font-weight="700" letter-spacing="-0.03em">Nexus AI</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Inline vector orbital architecture illustration matching the Process "Build" card.
     */
    private function nexusAiProcessOrbitDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="270" viewBox="0 0 360 270" fill="none">'
            .'<defs><filter id="nodeShadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="3" stdDeviation="5" flood-color="#111111" flood-opacity="0.08"/></filter></defs>'
            .'<circle cx="180" cy="240" r="195" fill="#FCFCFC" stroke="#EFEFEF" stroke-width="1"/>'
            .'<circle cx="180" cy="240" r="148" fill="#F4F4F4" stroke="#EBEBEB" stroke-width="1"/>'
            .'<circle cx="180" cy="240" r="102" fill="#EEEEEE" stroke="#E5E5E5" stroke-width="1"/>'
            .'<g filter="url(#nodeShadow)">'
            .'<circle cx="180" cy="45" r="20" fill="#FFFFFF"/>'
            .'<rect x="172" y="37" width="16" height="10" rx="2.5" stroke="#52525B" stroke-width="1.5"/>'
            .'<path d="M172 45L180 50L188 45" stroke="#52525B" stroke-width="1.5" stroke-linecap="round"/>'
            .'</g>'
            .'<g filter="url(#nodeShadow)">'
            .'<circle cx="152" cy="96" r="20" fill="#FFFFFF"/>'
            .'<circle cx="152" cy="96" r="6" stroke="#52525B" stroke-width="1.5"/>'
            .'<path d="M152 87V89.5M152 102.5V105M143 96H145.5M158.5 96H161M145.6 89.6L147.4 91.4M156.6 100.6L158.4 102.4M158.4 89.6L156.6 91.4M147.4 100.6L145.6 102.4" stroke="#52525B" stroke-width="1.4" stroke-linecap="round"/>'
            .'</g>'
            .'<g filter="url(#nodeShadow)">'
            .'<circle cx="164" cy="140" r="20" fill="#FFFFFF"/>'
            .'<path d="M164 132L166.2 137.8L172 140L166.2 142.2L164 148L161.8 142.2L156 140L161.8 137.8L164 132Z" stroke="#52525B" stroke-width="1.5" stroke-linejoin="round"/>'
            .'</g>'
            .'<g filter="url(#nodeShadow)">'
            .'<circle cx="294" cy="238" r="18" fill="#FFFFFF"/>'
            .'<rect x="288" y="232" width="12" height="12" rx="3" stroke="#52525B" stroke-width="1.5"/>'
            .'</g>'
            .'<g filter="url(#nodeShadow)">'
            .'<circle cx="180" cy="212" r="27" fill="#FFFFFF"/>'
            .'<path d="M177.2 201.5C178.4 199.3 181.6 199.3 182.8 201.5L190.4 215.2C191.6 217.3 190.1 220 187.6 220H183.7C182.6 220 181.6 219.4 181.1 218.4L180 216.2L178.9 218.4C178.4 219.4 177.4 220 176.3 220H172.4C169.9 220 168.4 217.3 169.6 215.2L177.2 201.5Z" fill="#09090B"/>'
            .'<path d="M180 208.2L182.9 213.6H177.1L180 208.2Z" fill="#FFFFFF"/>'
            .'</g>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Builds a Container block node with nested child blocks.
     *
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $style
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function containerNode(array $props, array $style, array $children): array
    {
        return [
            ...$this->blockNode(BlockType::Container, $props, $style),
            'children' => $children,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Navbar(): array
    {
        $navRow = $this->row([
            [
                ...$this->column(3, [
                    $this->blockNode(BlockType::Logo, [
                        'src' => $this->nexusAiLogoDataUri(true),
                        'alt' => 'Nexus AI',
                        'url' => '/',
                        'useWorkspaceBranding' => false,
                    ]),
                ]),
                'span' => ['desktop' => 3, 'tablet' => 3, 'mobile' => 8],
            ],
            [
                ...$this->column(6, [
                    $this->blockNode(BlockType::Navigation, [
                        'items' => [
                            ['label' => 'Home', 'url' => '/'],
                            ['label' => 'About Us', 'url' => '/about'],
                            ['label' => 'Services', 'url' => '/services'],
                            ['label' => 'FAQs', 'url' => '/faqs'],
                            ['label' => 'Blogs', 'url' => '/blogs'],
                        ],
                        'orientation' => 'horizontal',
                        'mobileBehaviour' => 'drawer',
                    ], [
                        'color' => $this->responsive('#09090b'),
                        'fontSize' => $this->responsive('14px'),
                        'fontWeight' => $this->responsive('500'),
                        'justifyContent' => ['desktop' => 'center', 'mobile' => 'flex-end'],
                        'textAlign' => ['desktop' => 'center', 'mobile' => 'right'],
                    ]),
                ]),
                'span' => ['desktop' => 6, 'tablet' => 5, 'mobile' => 4],
            ],
            [
                ...$this->column(3, [
                    $this->blockNode(BlockType::Button, [
                        'label' => 'Book free call',
                        'action' => 'url',
                        'url' => '/about',
                        'iconRight' => 'arrow-right',
                        'size' => 'sm',
                        'variant' => 'primary',
                    ], [
                        'backgroundColor' => $this->responsive('#09090b'),
                        'color' => $this->responsive('#ffffff'),
                        'borderRadius' => $this->responsive('999px'),
                        'fontSize' => $this->responsive('13px'),
                        'padding' => $this->responsive(['top' => '9px', 'right' => '16px', 'bottom' => '9px', 'left' => '16px']),
                        'textAlign' => $this->responsive('right'),
                    ]),
                ]),
                'span' => ['desktop' => 3, 'tablet' => 4, 'mobile' => 12],
                'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => false],
            ],
        ], [
            'alignItems' => $this->responsive('center'),
            'gap' => ['desktop' => '16px', 'mobile' => '8px'],
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('999px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'boxShadow' => $this->responsive('0 10px 30px -12px rgba(9, 9, 11, 0.08)'),
            'padding' => [
                'desktop' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '20px'],
                'mobile' => ['top' => '8px', 'right' => '10px', 'bottom' => '8px', 'left' => '16px'],
            ],
        ]);
        $navRow['stackBelow'] = 'none';

        return $this->block(
            key: 'atomos99-navbar',
            name: 'Nexus AI · Floating Pill Header',
            category: self::CATEGORY_STRUCTURE,
            description: 'Nexus AI sticky glassmorphic pill navigation bar with logo, centered links, and obsidian CTA pill button.',
            sections: [$this->section(
                width: 'boxed',
                sticky: true,
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => [
                        'desktop' => ['top' => '14px', 'right' => '24px', 'bottom' => '8px', 'left' => '24px'],
                        'mobile' => ['top' => '10px', 'right' => '14px', 'bottom' => '6px', 'left' => '14px'],
                    ],
                ],
                rows: [$navRow],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99HeroHome(): array
    {
        $ctaRow = $this->row([
            [
                ...$this->column(6, [
                    $this->blockNode(BlockType::Button, [
                        'label' => 'Book free call',
                        'action' => 'url',
                        'url' => '/about',
                        'iconRight' => 'arrow-right',
                        'size' => 'md',
                        'variant' => 'primary',
                    ], [
                        'backgroundColor' => $this->responsive('#09090b'),
                        'color' => $this->responsive('#ffffff'),
                        'borderRadius' => $this->responsive('999px'),
                        'fontSize' => ['desktop' => '14px', 'mobile' => '13px'],
                        'padding' => [
                            'desktop' => ['top' => '12px', 'right' => '22px', 'bottom' => '12px', 'left' => '22px'],
                            'mobile' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px'],
                        ],
                        'textAlign' => $this->responsive('right'),
                    ]),
                ]),
                'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 6],
            ],
            [
                ...$this->column(6, [
                    $this->blockNode(BlockType::Button, [
                        'label' => 'See Services',
                        'action' => 'url',
                        'url' => '/services',
                        'size' => 'md',
                        'variant' => 'secondary',
                    ], [
                        'backgroundColor' => $this->responsive('#ffffff'),
                        'color' => $this->responsive('#09090b'),
                        'borderRadius' => $this->responsive('999px'),
                        'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                        'fontSize' => ['desktop' => '14px', 'mobile' => '13px'],
                        'padding' => [
                            'desktop' => ['top' => '12px', 'right' => '22px', 'bottom' => '12px', 'left' => '22px'],
                            'mobile' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px'],
                        ],
                        'textAlign' => $this->responsive('left'),
                    ]),
                ]),
                'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 6],
            ],
        ], [
            'alignItems' => $this->responsive('center'),
            'gap' => ['desktop' => '12px', 'mobile' => '8px'],
            'margin' => ['desktop' => ['bottom' => '36px'], 'mobile' => ['bottom' => '24px']],
        ]);
        $ctaRow['stackBelow'] = 'none';

        $workflowPanel = fn (string $leftAlt, string $leftSrc, string $rightAlt, string $rightSrc): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [5, 7],
                'gap' => '24px',
                'stackBelow' => 'mobile',
            ], [
                'alignItems' => $this->responsive('center'),
                'backgroundColor' => $this->responsive('#ffffff'),
                'backgroundGradient' => $this->responsive('linear-gradient(180deg, #ffffff 0%, #fffaf5 68%, #ffe4d6 100%)'),
                'borderRadius' => ['desktop' => '24px', 'mobile' => '18px'],
                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px']),
                'boxShadow' => $this->responsive('0 30px 70px -24px rgba(9, 9, 11, 0.18)'),
                'padding' => [
                    'desktop' => ['top' => '32px', 'right' => '28px', 'bottom' => '32px', 'left' => '28px'],
                    'mobile' => ['top' => '18px', 'right' => '14px', 'bottom' => '18px', 'left' => '14px'],
                ],
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px']),
                    'children' => [
                        $this->image(
                            $leftAlt,
                            [
                                'borderRadius' => $this->responsive('18px'),
                                'boxShadow' => $this->responsive('0 18px 40px -18px rgba(9, 9, 11, 0.14)'),
                            ],
                            $leftSrc
                        ),
                    ],
                ],
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px']),
                    'children' => [
                        $this->image(
                            $rightAlt,
                            ['borderRadius' => $this->responsive('18px')],
                            $rightSrc
                        ),
                    ],
                ],
            ],
        ];

        $workflowTabsBlock = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'initialTab' => 1,
                'tabs' => [
                    ['id' => null, 'label' => '⚡ AI Automation'],
                    ['id' => null, 'label' => '🔥 AI Strategy'],
                    ['id' => null, 'label' => '⌘ AI Development'],
                ],
            ], [
                'gap' => $this->responsive('16px'),
                'custom' => $this->responsive([
                    '--fnl-tablist-bg' => '#f4f4f2',
                    '--fnl-tablist-radius' => '999px',
                    '--fnl-tab-radius' => '999px',
                    '--fnl-tab-active-bg' => '#ffffff',
                    '--fnl-tab-active-border' => '#ff542e',
                    '--fnl-tab-active-shadow' => '0 6px 20px -6px rgba(255, 84, 46, 0.25)',
                ]),
            ]),
            'children' => [
                $workflowPanel(
                    'AI Automation Flow Nodes',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8beb72ffcc8e26c805fc9_Frame%202147259125.svg',
                    'Custom AI Workflow Graph',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e93e1d1384aa972c91f_Workflow%20for%20AI%20Strategy%20(2).svg'
                ),
                $workflowPanel(
                    'Nexus AI Assistant Chatbox',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e098e5eb4e325f42995_Chatbox%20(1).svg',
                    'Workflow for AI Strategy',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e93e1d1384aa972c91f_Workflow%20for%20AI%20Strategy%20(2).svg'
                ),
                $workflowPanel(
                    'AI Strategy Generator UI',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8b17f099df6fbd8aeac5d_Frame%202147259124.svg',
                    'Nexus AI Assistant Chatbox',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e098e5eb4e325f42995_Chatbox%20(1).svg'
                ),
            ],
        ];

        return $this->block(
            key: 'atomos99-hero-home',
            name: 'Nexus AI · Hero & Interactive AI Workflow',
            category: self::CATEGORY_HERO,
            description: 'Centered display hero with dual pill CTAs and a macOS-framed AI Automation / Strategy / Development workflow showcase.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('64px', '24px', '36px'),
                    'custom' => $this->responsive([
                        '--fnl-effect' => 'reveal exit-fade',
                        '--fnl-motion-duration' => '680ms',
                    ]),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// CUSTOM AI AGENCY', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textTransform' => $this->responsive('uppercase'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('We build custom AI solutions for your business', 'h1', [
                                'fontSize' => $this->font('60px', '34px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.08'),
                                'letterSpacing' => $this->responsive('-0.035em'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('780px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '16px', 'left' => 'auto']),
                            ]),
                            $this->paragraph('From strategy to deployment, we help companies automate workflows, unlock data, and scale with AI that actually works.', [
                                'fontSize' => $this->font('18px', '15px'),
                                'lineHeight' => $this->responsive('1.6'),
                                'color' => $this->responsive('#52525b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('600px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '24px', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                    $ctaRow,
                    $this->row([
                        $this->column(12, [
                            $workflowTabsBlock,
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99LogoStrip(): array
    {
        $brandPill = fn (string $name): array => [
            ...$this->column(2, [
                $this->paragraph($name, [
                    'color' => $this->responsive('#18181b'),
                    'fontSize' => ['desktop' => '16px', 'mobile' => '14px'],
                    'fontWeight' => $this->responsive('700'),
                    'letterSpacing' => $this->responsive('-0.02em'),
                    'textAlign' => $this->responsive('center'),
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'padding' => [
                        'desktop' => ['top' => '14px', 'right' => '14px', 'bottom' => '14px', 'left' => '14px'],
                        'mobile' => ['top' => '11px', 'right' => '10px', 'bottom' => '11px', 'left' => '10px'],
                    ],
                    'borderRadius' => $this->responsive('14px'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                ]),
            ]),
            'span' => ['desktop' => 2, 'tablet' => 4, 'mobile' => 6],
        ];

        $logosRow = $this->row([
            $brandPill('◎ circle'),
            $brandPill('⌘ hooks'),
            $brandPill('✦ Light AI'),
            $brandPill('❖ Radiyal'),
            $brandPill('▲ Kanba'),
            $brandPill('▣ goldline'),
        ], ['gap' => ['desktop' => '14px', 'mobile' => '10px']]);
        $logosRow['stackBelow'] = 'none';

        return $this->block(
            key: 'atomos99-logo-strip',
            name: 'Nexus AI · Trusted Brands Marquee',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Trusted by leading brands logo strip featuring Circle, Hooks, Light AI, Radiyal, Kanba, and Goldline.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('36px', '24px', '24px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'fade-in exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('Trusted by leading brands', [
                                'color' => $this->responsive('#71717a'),
                                'fontSize' => $this->responsive('13px'),
                                'fontWeight' => $this->responsive('500'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '20px']),
                            ]),
                        ]),
                    ]),
                    $logosRow,
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Services(): array
    {
        $serviceCard = fn (string $icon, string $title, string $body): array => $this->column(4, [
            $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '22px'], [
                'color' => $this->responsive('#09090b'),
                'backgroundColor' => $this->responsive('#f4f4f2'),
                'padding' => $this->responsive(['top' => '10px', 'right' => '10px', 'bottom' => '10px', 'left' => '10px']),
                'borderRadius' => $this->responsive('12px'),
            ]),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('20px'),
                'fontWeight' => $this->responsive('600'),
                'color' => $this->responsive('#09090b'),
                'margin' => [
                    'desktop' => ['top' => '40px', 'bottom' => '10px'],
                    'mobile' => ['top' => '20px', 'bottom' => '8px'],
                ],
            ]),
            $this->paragraph($body, [
                'color' => $this->responsive('#52525b'),
                'fontSize' => $this->responsive('14px'),
                'lineHeight' => $this->responsive('1.6'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('20px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => [
                'desktop' => ['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px'],
                'mobile' => ['top' => '22px', 'right' => '20px', 'bottom' => '22px', 'left' => '20px'],
            ],
            'custom' => $this->responsive([
                '--fnl-effect' => 'reveal exit-fade lift',
            ]),
        ]);

        return $this->block(
            key: 'atomos99-services',
            name: 'Nexus AI · Everything You Need to Put AI to Work',
            category: self::CATEGORY_CONTENT,
            description: 'Atomos99 Services bento showcase with AI Assistant, Custom Workflows, AI Strategy, Data & Intelligence, and AI Development.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('96px', '24px', '56px'),
                ],
                rows: [
                    $this->row([
                        $this->column(5, [
                            $this->paragraph('// SERVICES', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'margin' => $this->responsive(['bottom' => '12px']),
                            ]),
                            $this->heading('Everything you need to put AI to work', 'h2', [
                                'fontSize' => $this->font('42px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.12'),
                                'letterSpacing' => $this->responsive('-0.03em'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->paragraph('End-to-end AI services designed to solve real business problems and deliver measurable impact.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.65'),
                                'margin' => $this->responsive(['bottom' => '48px']),
                            ]),
                            $this->blockNode(BlockType::Icon, ['name' => 'bot', 'size' => '22px'], [
                                'color' => $this->responsive('#09090b'),
                            ]),
                            $this->heading('AI Assistant', 'h3', [
                                'fontSize' => $this->responsive('20px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['top' => '14px', 'bottom' => '8px']),
                            ]),
                            $this->paragraph('Deploy smart assistants for customer support, internal helpdesks, and sales qualification — available 24/7.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('14px'),
                                'lineHeight' => $this->responsive('1.6'),
                            ]),
                        ], [
                            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                        ]),
                        $this->column(7, [
                            $this->heading('What can I help with?', 'h4', [
                                'fontSize' => $this->responsive('18px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->paragraph('💬 Prompt your business assistant...   ·   ✦ Generate Reports   ·   📊 Analyze Data   ·   ✍️ Draft Content   ·   ⚡ Automate Tasks', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('13px'),
                                'textAlign' => $this->responsive('center'),
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'padding' => $this->responsive(['top' => '16px', 'right' => '20px', 'bottom' => '16px', 'left' => '20px']),
                                'borderRadius' => $this->responsive('16px'),
                                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                                'margin' => $this->responsive(['bottom' => '18px']),
                            ]),
                            $this->image(
                                'Nexus AI Assistant Interface',
                                ['borderRadius' => $this->responsive('16px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e098e5eb4e325f42995_Chatbox%20(1).svg'
                            ),
                        ], [
                            'backgroundColor' => $this->responsive('#f4f4f2'),
                            'borderRadius' => $this->responsive('24px'),
                            'padding' => [
                                'desktop' => ['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px'],
                                'mobile' => ['top' => '20px', 'right' => '16px', 'bottom' => '20px', 'left' => '16px'],
                            ],
                            'custom' => $this->responsive(['--fnl-effect' => 'zoom-in exit-scale']),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('center'),
                        'gap' => $this->responsive('28px'),
                        'margin' => $this->responsive(['bottom' => '28px']),
                    ]),
                    $this->row([
                        $this->column(5, [
                            $this->blockNode(BlockType::Icon, ['name' => 'workflow', 'size' => '22px'], [
                                'color' => $this->responsive('#09090b'),
                            ]),
                            $this->heading('Custom Workflows', 'h3', [
                                'fontSize' => $this->responsive('20px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['top' => '14px', 'bottom' => '8px']),
                            ]),
                            $this->paragraph('Automate repetitive tasks across your tools and teams with tailored AI pipelines that save hours every day.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('14px'),
                                'lineHeight' => $this->responsive('1.6'),
                            ]),
                        ], [
                            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                        ]),
                        $this->column(7, [
                            $this->paragraph('⚡ Trigger Received  →  🛡️ Validate Data  →  🧠 Process Request  →  ✓ Send Email · Update CRM · Notify Team', [
                                'color' => $this->responsive('#09090b'),
                                'fontSize' => $this->responsive('13px'),
                                'fontWeight' => $this->responsive('600'),
                                'textAlign' => $this->responsive('center'),
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'padding' => $this->responsive(['top' => '14px', 'right' => '18px', 'bottom' => '14px', 'left' => '18px']),
                                'borderRadius' => ['desktop' => '999px', 'mobile' => '16px'],
                                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                                'margin' => $this->responsive(['bottom' => '18px']),
                            ]),
                            $this->image(
                                'Custom AI Workflow Graph',
                                ['borderRadius' => $this->responsive('16px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ab87e93e1d1384aa972c91f_Workflow%20for%20AI%20Strategy%20(2).svg'
                            ),
                        ], [
                            'backgroundColor' => $this->responsive('#f4f4f2'),
                            'borderRadius' => $this->responsive('24px'),
                            'padding' => [
                                'desktop' => ['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px'],
                                'mobile' => ['top' => '20px', 'right' => '16px', 'bottom' => '20px', 'left' => '16px'],
                            ],
                            'custom' => $this->responsive(['--fnl-effect' => 'zoom-in exit-scale']),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('center'),
                        'gap' => $this->responsive('28px'),
                        'margin' => $this->responsive(['bottom' => '28px']),
                    ]),
                    $this->row([
                        $serviceCard('compass', 'AI Strategy', 'Identify high-impact opportunities and build a clear roadmap to integrate AI into your operations.'),
                        $serviceCard('bar-chart-3', 'Data & Intelligence', 'Turn raw business data into predictive insights, dashboards, and automated reporting.'),
                        $serviceCard('code-2', 'AI Development', 'Build custom models, fine-tuned LLMs, and production-ready AI products tailored to your stack.'),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99CaseStudies(): array
    {
        $casePanel = fn (string $brand, string $headline, string $metrics, string $photo): array => [
            ...$this->blockNode(BlockType::Container, [
                'layout' => 'columns',
                'columns' => [5, 7],
                'gap' => '24px',
                'stackBelow' => 'mobile',
            ], [
                'alignItems' => $this->responsive('center'),
                'backgroundColor' => $this->responsive('#ffffff'),
                'padding' => [
                    'desktop' => ['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'],
                    'mobile' => ['top' => '18px', 'right' => '16px', 'bottom' => '18px', 'left' => '16px'],
                ],
                'borderRadius' => $this->responsive('24px'),
                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px'], [
                        'padding' => [
                            'desktop' => ['top' => '20px', 'right' => '20px', 'bottom' => '20px', 'left' => '20px'],
                            'mobile' => ['top' => '6px', 'right' => '4px', 'bottom' => '12px', 'left' => '4px'],
                        ],
                    ]),
                    'children' => [
                        $this->paragraph($brand, [
                            'color' => $this->responsive('#09090b'),
                            'fontSize' => $this->responsive('18px'),
                            'fontWeight' => $this->responsive('700'),
                            'margin' => $this->responsive(['bottom' => '8px']),
                        ]),
                        $this->heading($headline, 'h3', [
                            'fontSize' => $this->font('24px', '20px'),
                            'fontWeight' => $this->responsive('600'),
                            'lineHeight' => $this->responsive('1.35'),
                            'color' => $this->responsive('#09090b'),
                            'margin' => $this->responsive(['bottom' => '12px']),
                        ]),
                        $this->paragraph($metrics, [
                            'color' => $this->responsive('#09090b'),
                            'fontSize' => $this->responsive('15px'),
                            'fontWeight' => $this->responsive('700'),
                            'backgroundColor' => $this->responsive('#f4f4f2'),
                            'padding' => $this->responsive(['top' => '14px', 'right' => '18px', 'bottom' => '14px', 'left' => '18px']),
                            'borderRadius' => $this->responsive('14px'),
                            'margin' => $this->responsive(['bottom' => '12px']),
                        ]),
                        $this->blockNode(BlockType::Button, [
                            'label' => 'Read full story',
                            'action' => 'url',
                            'url' => '/about',
                            'iconRight' => 'arrow-right',
                            'size' => 'sm',
                            'variant' => 'primary',
                        ], [
                            'backgroundColor' => $this->responsive('#09090b'),
                            'color' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('999px'),
                        ]),
                    ],
                ],
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px']),
                    'children' => [
                        $this->image(
                            $brand.' Case Study Workspace',
                            ['borderRadius' => $this->responsive('18px')],
                            $photo
                        ),
                    ],
                ],
            ],
        ];

        $tabsBlock = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'initialTab' => 0,
                'tabs' => [
                    ['id' => null, 'label' => '▲ Kanba'],
                    ['id' => null, 'label' => '◎ circle'],
                    ['id' => null, 'label' => '✦ stari'],
                    ['id' => null, 'label' => '❖ Radiyal'],
                ],
            ], [
                'gap' => $this->responsive('16px'),
            ]),
            'children' => [
                $casePanel(
                    '▲ Kanba',
                    'Reduced checkout drop-off by 38% and scaled automated customer care with zero extra headcount.',
                    '3.2x Customer Engagement   ·   85% Queries Automated',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb44112ee5470835f5b40f_Rectangle%20155.webp'
                ),
                $casePanel(
                    '◎ circle',
                    'Automated enterprise onboarding and cut document verification time from 4 days to 12 minutes.',
                    '4.6x Faster Onboarding   ·   94% Accuracy Rate',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb44119b5a419950b45417_Rectangle%20156.webp'
                ),
                $casePanel(
                    '✦ stari',
                    'Built a custom AI lead-scoring engine that lifted qualified pipeline conversion by 42% in 60 days.',
                    '+42% Pipeline Conversion   ·   28 hrs/wk Saved',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc6cc77ea0623643d6f633_Rectangle%20127%20(1).webp'
                ),
                $casePanel(
                    '❖ Radiyal',
                    'Unified 14 data sources into a real-time executive intelligence assistant for 200+ operators.',
                    '10x Reporting Speed   ·   99.4% Team Adoption',
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f386100e0e81ed48cc_Team%20Image%2006.webp'
                ),
            ],
        ];

        return $this->block(
            key: 'atomos99-case-studies',
            name: 'Nexus AI · Trusted by Brands Case Studies',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Warm stone case study showcase with interactive brand selector tabs (Kanba, Circle, Stari, Radiyal), ROI metrics, and photography.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#f4f4f2'),
                    'padding' => $this->pad('96px', '24px', '56px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(7, [
                            $this->paragraph('// CASE STUDIES', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->heading('Trusted by brands across industries', 'h2', [
                                'fontSize' => $this->font('42px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.12'),
                                'letterSpacing' => $this->responsive('-0.03em'),
                                'color' => $this->responsive('#09090b'),
                            ]),
                        ]),
                        $this->column(5, [
                            $this->paragraph('Real results from companies that partnered with us to transform how they work with AI.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.65'),
                            ]),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('end'),
                        'margin' => $this->responsive(['bottom' => '32px']),
                    ]),
                    $this->row([
                        $this->column(12, [
                            $tabsBlock,
                        ]),
                    ]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Process(): array
    {
        $cardImage = fn (string $src, string $alt, array $style = []): array => $this->blockNode(BlockType::Image, [
            'src' => $src,
            'alt' => $alt,
            'objectFit' => 'contain',
        ], $style);

        $stepCard = fn (string $icon, string $title, string $body, array $visualBlock, array $tabletMobileSpan, array $extraColStyle = []): array => [
            ...$this->column(4, [
                $this->containerNode(['layout' => 'stack', 'gap' => '10px'], [
                    'padding' => $this->responsive(['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px']),
                ], [
                    $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '20px'], [
                        'color' => $this->responsive('#09090b'),
                        'margin' => $this->responsive(['bottom' => '6px']),
                    ]),
                    $this->heading($title, 'h3', [
                        'fontSize' => $this->responsive('20px'),
                        'fontWeight' => $this->responsive('500'),
                        'lineHeight' => $this->responsive('1.25'),
                        'letterSpacing' => $this->responsive('-0.02em'),
                        'color' => $this->responsive('#09090b'),
                    ]),
                    $this->paragraph($body, [
                        'color' => $this->responsive('#6d6962'),
                        'fontSize' => $this->responsive('15px'),
                        'lineHeight' => $this->responsive('1.55'),
                    ]),
                ]),
                $visualBlock,
            ], [
                'backgroundColor' => $this->responsive('#f5f3f1'),
                'borderRadius' => $this->responsive('24px'),
                'border' => $this->responsive(['style' => 'solid', 'color' => '#efedec', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'padding' => [
                    'desktop' => ['top' => '36px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px'],
                    'mobile' => ['top' => '26px', 'right' => '22px', 'bottom' => '24px', 'left' => '22px'],
                ],
                'justifyContent' => $this->responsive('space-between'),
                'gap' => $this->responsive('28px'),
                'custom' => ['--fnl-effect' => $this->responsive('reveal exit-fade lift')],
                ...$extraColStyle,
            ]),
            'span' => ['desktop' => 4, ...$tabletMobileSpan],
        ];

        $stepsRow = $this->row([
            $stepCard(
                'search',
                'Discover',
                'We analyze your business to identify where AI saves time and money.',
                $cardImage(
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6aba02e05fd04c4dc1cd1650_meeting-card%20(1).svg',
                    'Discovery meeting schedule UI card',
                    ['borderRadius' => $this->responsive('16px')]
                ),
                ['tablet' => 6, 'mobile' => 12],
            ),
            $stepCard(
                'wand-sparkles',
                'Build',
                'We design and build the right AI solution for your specific goals.',
                $cardImage(
                    $this->nexusAiProcessOrbitDataUri(),
                    'Nexus AI architecture orbital diagram',
                    ['borderRadius' => $this->responsive('16px')]
                ),
                ['tablet' => 6, 'mobile' => 12],
            ),
            $stepCard(
                'rocket',
                'Scale',
                'We launch, monitor, and improve your AI systems as your business grows.',
                $cardImage(
                    'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6aba0386c5c4fb977f915408_Frame%202147259141.svg',
                    'Project scope automation checklist card',
                    ['borderRadius' => $this->responsive('16px')]
                ),
                ['tablet' => 12, 'mobile' => 12],
            ),
        ], ['gap' => $this->responsive('16px')]);
        $stepsRow['stackBelow'] = 'mobile';

        return $this->block(
            key: 'atomos99-process',
            name: 'Nexus AI · 3 Simple Steps Process',
            category: self::CATEGORY_CONTENT,
            description: 'Three visual process cards (Discover, Build, Scale) with UI cards and Nexus AI orbital architecture diagram.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'padding' => $this->pad('88px', '24px', '56px'),
                ],
                rows: [
                    $this->row([
                        $this->column(7, [
                            $this->paragraph('// PROCESS', [
                                'color' => $this->responsive('#e84a27'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->heading('3 simple steps to get started fast', 'h2', [
                                'fontSize' => $this->font('40px', '30px'),
                                'fontWeight' => $this->responsive('500'),
                                'lineHeight' => $this->responsive('1.12'),
                                'letterSpacing' => $this->responsive('-0.03em'),
                                'color' => $this->responsive('#09090b'),
                            ]),
                        ]),
                        $this->column(5, [
                            $this->paragraph('A clear, structured process that takes you from idea to working AI in weeks — not months.', [
                                'color' => $this->responsive('#6d6962'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.6'),
                            ]),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('end'),
                        'margin' => $this->responsive(['bottom' => '36px']),
                    ]),
                    $stepsRow,
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Benefits(): array
    {
        $cardImage = fn (string $src, string $alt, array $style = [], array $visibility = ['desktop' => true, 'tablet' => true, 'mobile' => true]): array => [
            ...$this->blockNode(BlockType::Image, [
                'src' => $src,
                'alt' => $alt,
                'objectFit' => 'contain',
            ], $style),
            'visibility' => $visibility,
        ];

        $bentoCardStyle = fn (array $extra = []): array => [
            'backgroundColor' => $this->responsive('#f5f3f1'),
            'borderRadius' => $this->responsive('24px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#efedec', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => [
                'desktop' => ['top' => '36px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px'],
                'mobile' => ['top' => '24px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px'],
            ],
            'justifyContent' => $this->responsive('space-between'),
            'gap' => $this->responsive('32px'),
            'custom' => ['--fnl-effect' => $this->responsive('reveal exit-fade')],
            ...$extra,
        ];

        $bentoCopy = fn (string $title, string $subtitle, string $titleColor = '#09090b', string $subtitleColor = '#6d6962'): array => $this->containerNode(
            ['layout' => 'stack', 'gap' => '6px'],
            [],
            [
                $this->heading($title, 'h3', [
                    'fontSize' => $this->responsive('18px'),
                    'fontWeight' => $this->responsive('500'),
                    'lineHeight' => $this->responsive('1.3'),
                    'letterSpacing' => $this->responsive('-0.02em'),
                    'color' => $this->responsive($titleColor),
                ]),
                $this->paragraph($subtitle, [
                    'color' => $this->responsive($subtitleColor),
                    'fontSize' => $this->responsive('15px'),
                    'lineHeight' => $this->responsive('1.55'),
                ]),
            ],
        );

        $topRow = $this->row([
            [
                ...$this->column(6, [
                    $cardImage(
                        'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6ac0acfe85edc228ca9e718f_Frame%202147259149.svg',
                        'Connected stack workflow hub illustration',
                        ['width' => $this->responsive('100%')]
                    ),
                    $this->containerNode([
                        'layout' => 'columns',
                        'columns' => [5, 7],
                        'stackBelow' => 'mobile',
                        'gap' => '20px',
                    ], [
                        'alignItems' => $this->responsive('start'),
                    ], [
                        $this->heading('See measurable results', 'h3', [
                            'fontSize' => $this->responsive('18px'),
                            'fontWeight' => $this->responsive('500'),
                            'lineHeight' => $this->responsive('1.3'),
                            'letterSpacing' => $this->responsive('-0.02em'),
                            'color' => $this->responsive('#09090b'),
                        ]),
                        $this->paragraph('Track real ROI with reporting built into every project.', [
                            'color' => $this->responsive('#6d6962'),
                            'fontSize' => $this->responsive('15px'),
                            'lineHeight' => $this->responsive('1.55'),
                        ]),
                    ]),
                ], $bentoCardStyle()),
                'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 12],
            ],
            [
                ...$this->column(6, [
                    $this->containerNode([
                        'layout' => 'stack',
                        'gap' => '0px',
                    ], [
                        'padding' => [
                            'desktop' => ['top' => '0px', 'right' => '28px', 'bottom' => '0px', 'left' => '28px'],
                            'mobile' => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                        ],
                    ], [
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abca685c49a28d8d3663b5d_Group%207095%20(1).svg',
                            'AI Task Draft collaborative card preview',
                            [
                                'maxWidth' => $this->responsive('390px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abca6cac49a28d8d3666666_Group%207096%20(2).svg',
                            'Collaborative AI cursor tag',
                            [
                                'width' => $this->responsive('61px'),
                                'custom' => [
                                    '--fnl-tw' => $this->responsive('pointer-events-none'),
                                    'position' => $this->responsive('absolute'),
                                    'top' => $this->responsive('52px'),
                                    'left' => $this->responsive('0px'),
                                ],
                            ],
                            ['desktop' => true, 'tablet' => false, 'mobile' => false]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abca6caec9c6b2d9adf0e6e_Group%207097%20(2).svg',
                            'Automated task cursor badge',
                            [
                                'width' => $this->responsive('61px'),
                                'custom' => [
                                    '--fnl-tw' => $this->responsive('pointer-events-none'),
                                    'position' => $this->responsive('absolute'),
                                    'top' => $this->responsive('112px'),
                                    'right' => $this->responsive('0px'),
                                ],
                            ],
                            ['desktop' => true, 'tablet' => false, 'mobile' => false]
                        ),
                    ]),
                    $bentoCopy(
                        'AI Task Draft',
                        'Reduce overhead with AI that works around the clock.'
                    ),
                ], $bentoCardStyle()),
                'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 12],
            ],
        ], [
            'gap' => $this->responsive('16px'),
            'margin' => $this->responsive(['bottom' => '16px']),
        ]);
        $topRow['stackBelow'] = 'mobile';

        $bottomRow = $this->row([
            [
                ...$this->column(4, [
                    $this->containerNode(['layout' => 'stack', 'gap' => '10px'], [], [
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb16dcf848f21b1b7904cb_9c2644d7167613f6112b8e47a6147db0_stat-card-ai.svg',
                            'AI Score 92% metric card',
                            [
                                'maxWidth' => $this->responsive('333px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb16dc252649e5eaa7b252_stat-card-time.svg',
                            'Time Saved 12h metric card',
                            [
                                'maxWidth' => $this->responsive('333px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb16dc08b8632626ea0ea9_stat-card-accuracy.svg',
                            'Accuracy 97% metric card',
                            [
                                'maxWidth' => $this->responsive('333px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                    ]),
                    $bentoCopy(
                        'Make faster decisions',
                        'Get clear insights from your data, instantly.'
                    ),
                ], $bentoCardStyle()),
                'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
            ],
            [
                ...$this->column(4, [
                    $this->containerNode(['layout' => 'stack', 'gap' => '2px'], [], [
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb14b4ae8c81a49059e05d_Frame%202147258815.svg',
                            'Trigger: New Lead Captured step',
                            [
                                'maxWidth' => $this->responsive('203px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb14fc509e29f34ee2ead2_Line%20237.svg',
                            'Workflow arrow connector',
                            [
                                'width' => $this->responsive('8px'),
                                'height' => $this->responsive('41px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb14b4f2094eb23a291d58_Frame%202147259117.svg',
                            'AI Agent: Enrich & Segment step',
                            [
                                'maxWidth' => $this->responsive('203px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb14fc509e29f34ee2ead2_Line%20237.svg',
                            'Workflow arrow connector',
                            [
                                'width' => $this->responsive('8px'),
                                'height' => $this->responsive('41px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                        $cardImage(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6acb14b4e9731573a6329756_Frame%202147259129.svg',
                            'Integration: Instant Slack Alert step',
                            [
                                'maxWidth' => $this->responsive('203px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                            ]
                        ),
                    ]),
                    $bentoCopy(
                        'Scale without hiring',
                        'Grow your output without growing your headcount.'
                    ),
                ], $bentoCardStyle()),
                'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
            ],
            [
                ...$this->column(4, [
                    $cardImage(
                        'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6aba08478bc0556737cf33e7_Frame%202147259151%20(1).svg',
                        'Daily automation calendar summary card',
                        [
                            'maxWidth' => $this->responsive('340px'),
                            'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
                        ]
                    ),
                    $bentoCopy(
                        'Save hours every week',
                        "Automate manual tasks and free up your team's time.",
                        '#ffffff',
                        '#f5f3f1'
                    ),
                ], $bentoCardStyle([
                    'backgroundColor' => $this->responsive('#181410'),
                    'backgroundImage' => $this->responsive('https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6aba07f4bc9469648f1f0454_Rectangle%2032.webp'),
                    'backgroundSize' => $this->responsive('cover'),
                    'backgroundPosition' => $this->responsive('center'),
                    'overlayColor' => $this->responsive('rgba(9, 9, 11, 0.34)'),
                    'border' => $this->responsive(['style' => 'solid', 'color' => 'transparent', 'top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px']),
                ])),
                'span' => ['desktop' => 4, 'tablet' => 12, 'mobile' => 12],
            ],
        ], ['gap' => $this->responsive('16px')]);
        $bottomRow['stackBelow'] = 'mobile';

        return $this->block(
            key: 'atomos99-benefits',
            name: 'Nexus AI · AI Services & Benefits Bento',
            category: self::CATEGORY_CONTENT,
            description: 'Five-card bento showcase with integrations hub, AI Task Draft preview, live metric stack, AI agent flow, and calendar summary overlay.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#ffffff'),
                    'padding' => $this->pad('56px', '24px', '88px'),
                ],
                rows: [
                    $this->row([
                        [
                            ...$this->column(8, [
                                $this->paragraph('// BENEFITS', [
                                    'color' => $this->responsive('#e84a27'),
                                    'fontSize' => $this->responsive('12px'),
                                    'fontWeight' => $this->responsive('700'),
                                    'letterSpacing' => $this->responsive('0.14em'),
                                    'margin' => $this->responsive(['bottom' => '10px']),
                                ]),
                                $this->heading('AI services for modern business growth', 'h2', [
                                    'fontSize' => $this->font('40px', '30px'),
                                    'fontWeight' => $this->responsive('500'),
                                    'lineHeight' => $this->responsive('1.12'),
                                    'letterSpacing' => $this->responsive('-0.03em'),
                                    'color' => $this->responsive('#09090b'),
                                ]),
                            ]),
                            'span' => ['desktop' => 8, 'tablet' => 8, 'mobile' => 12],
                        ],
                        [
                            ...$this->column(4, [
                                $this->blockNode(BlockType::Button, [
                                    'label' => 'All services',
                                    'action' => 'url',
                                    'url' => '/services',
                                    'iconRight' => 'arrow-right',
                                    'size' => 'md',
                                    'variant' => 'secondary',
                                ], [
                                    'backgroundColor' => $this->responsive('#ffffff'),
                                    'color' => $this->responsive('#09090b'),
                                    'borderRadius' => $this->responsive('999px'),
                                    'border' => $this->responsive(['style' => 'solid', 'color' => '#efedec', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                                    'textAlign' => ['desktop' => 'right', 'tablet' => 'right', 'mobile' => 'left'],
                                ]),
                            ]),
                            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 12],
                        ],
                    ], [
                        'alignItems' => $this->responsive('end'),
                        'margin' => $this->responsive(['bottom' => '36px']),
                    ]),
                    $topRow,
                    $bottomRow,
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Testimonials(): array
    {
        $darkQuoteCard = fn (string $brand, string $quote, string $author, string $role): array => $this->column(4, [
            $this->paragraph($brand, [
                'color' => $this->responsive('#a1a1aa'),
                'fontSize' => $this->responsive('14px'),
                'fontWeight' => $this->responsive('700'),
                'margin' => $this->responsive(['bottom' => '18px']),
            ]),
            $this->blockNode(BlockType::Testimonial, [
                'quote' => $quote,
                'authorName' => $author,
                'authorTitle' => $role,
                'rating' => 5,
                'layout' => 'card',
            ], [
                'color' => $this->responsive('#ffffff'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#141417'),
            'borderRadius' => $this->responsive('22px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#27272a', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
        ]);

        return $this->block(
            key: 'atomos99-testimonials',
            name: 'Nexus AI · Dark Client Reviews Bento',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Obsidian dark 6-cell bento grid pairing client reviews from Kanba, Goldline, Stari, and LightAI with studio portraits.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive('#09090b'),
                    'padding' => $this->pad('96px', '24px', '56px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// CLIENT REVIEWS', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '12px']),
                            ]),
                            $this->heading('Trusted by thousands clients', 'h2', [
                                'fontSize' => $this->font('44px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#ffffff'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '44px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $darkQuoteCard(
                            '▲ Kanba',
                            'With the new smart workflow, our daily intake and triage runs completely on autopilot. We saved 30+ hours a week in the first month.',
                            'Floyd Miles',
                            'Founder & CEO at Kanba'
                        ),
                        $this->column(4, [
                            $this->image(
                                'Client Executive Portrait',
                                ['borderRadius' => $this->responsive('22px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f386100e0e81ed48cc_Team%20Image%2006.webp'
                            ),
                        ]),
                        $darkQuoteCard(
                            '▣ goldline',
                            'They took our messy internal documentation and turned it into an instant AI knowledge assistant that our support team loves.',
                            'Morgan Lee',
                            'Founder & CEO at Goldline'
                        ),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '20px']),
                    ]),
                    $this->row([
                        $darkQuoteCard(
                            '✦ stari',
                            'Atomos99 delivered a custom lead-scoring pipeline in three weeks. Our sales conversion rate jumped 42% quarter over quarter.',
                            'Lucas Turner',
                            'Head of Growth at Stari'
                        ),
                        $darkQuoteCard(
                            '✦ LightAI',
                            'Unlike agencies that just build demos, Atomos99 shipped production-grade AI that integrated cleanly with our existing stack.',
                            'Henry Collins',
                            'CTO at LightAI'
                        ),
                        $this->column(4, [
                            $this->image(
                                'Operator Working in Studio',
                                ['borderRadius' => $this->responsive('22px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb44112ee5470835f5b40f_Rectangle%20155.webp'
                            ),
                        ]),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99Faq(): array
    {
        return $this->block(
            key: 'atomos99-faq',
            name: 'Nexus AI · Got Questions FAQ',
            category: self::CATEGORY_STRUCTURE,
            description: 'Centered FAQ accordion section matching Atomos99 with 5 expandable AI agency questions.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('88px', '24px', '52px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// FREQUENTLY ASKED', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->heading('Got questions?', 'h2', [
                                'fontSize' => $this->font('42px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->paragraph('Everything you need to know before getting started.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '36px']),
                            ]),
                            $this->blockNode(BlockType::Faq, [
                                'initialOpen' => true,
                                'allowMultipleOpen' => false,
                                'emitSchemaOrg' => true,
                                'items' => [
                                    [
                                        'question' => 'How long does a typical AI project take?',
                                        'answer' => 'Most focused automation and AI assistant projects go from discovery to production in 3 to 6 weeks. Larger custom model deployments typically take 6 to 10 weeks with weekly milestones.',
                                    ],
                                    [
                                        'question' => 'Do we need a large technical team to work with you?',
                                        'answer' => 'Not at all. We handle architecture, development, integration, and deployment end-to-end, and train your team so anyone can operate the system confidently.',
                                    ],
                                    [
                                        'question' => 'How do you keep our company data secure and private?',
                                        'answer' => 'Your data never trains public models. We deploy within private cloud environments or enterprise APIs with strict encryption, role-based access, and audit logging.',
                                    ],
                                    [
                                        'question' => 'Can you integrate with the tools we already use?',
                                        'answer' => 'Yes — we integrate natively with Slack, HubSpot, Salesforce, Notion, Linear, Zendesk, Postgres, and custom internal APIs.',
                                    ],
                                    [
                                        'question' => 'What happens after our AI solution is launched?',
                                        'answer' => 'We provide ongoing monitoring, performance tuning, and support packages to ensure your workflows continue delivering measurable ROI as your business grows.',
                                    ],
                                ],
                            ], [
                                'maxWidth' => $this->responsive('760px'),
                                'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto']),
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
    private function atomos99Footer(): array
    {
        return $this->block(
            key: 'atomos99-footer',
            name: 'Nexus AI · Dark Scale CTA & Footer',
            category: self::CATEGORY_STRUCTURE,
            description: 'Obsidian dark conversion banner ("AI solutions that work at business scale") and 4-column Atomos99 footer.',
            sections: [$this->section(
                width: 'full',
                style: [
                    'backgroundColor' => $this->responsive('#09090b'),
                    'padding' => $this->pad('80px', '24px', '48px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->heading('AI solutions that work at business scale', 'h2', [
                                'fontSize' => $this->font('46px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.12'),
                                'letterSpacing' => $this->responsive('-0.03em'),
                                'color' => $this->responsive('#ffffff'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('640px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '14px', 'left' => 'auto']),
                            ]),
                            $this->paragraph('Book a free discovery call and see how we can automate your workflows in weeks.', [
                                'color' => $this->responsive('#a1a1aa'),
                                'fontSize' => $this->responsive('16px'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('520px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
                            ]),
                            $this->blockNode(BlockType::Button, [
                                'label' => 'Book free call',
                                'action' => 'url',
                                'url' => '/about',
                                'iconRight' => 'arrow-right',
                                'size' => 'lg',
                                'variant' => 'primary',
                            ], [
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'color' => $this->responsive('#09090b'),
                                'borderRadius' => $this->responsive('999px'),
                                'textAlign' => $this->responsive('center'),
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive('#141417'),
                            'backgroundGradient' => $this->responsive('radial-gradient(circle at 50% 0%, rgba(255, 84, 46, 0.24) 0%, rgba(20, 20, 23, 0.96) 68%)'),
                            'borderRadius' => $this->responsive('28px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#27272a', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => [
                                'desktop' => ['top' => '64px', 'right' => '32px', 'bottom' => '64px', 'left' => '32px'],
                                'mobile' => ['top' => '36px', 'right' => '20px', 'bottom' => '36px', 'left' => '20px'],
                            ],
                            'margin' => $this->responsive(['bottom' => '48px']),
                        ]),
                    ]),
                    [
                        ...$this->row([
                            [
                                ...$this->column(5, [
                                    $this->blockNode(BlockType::Logo, [
                                        'src' => $this->nexusAiLogoDataUri(false),
                                        'alt' => 'Nexus AI',
                                        'url' => '/',
                                        'useWorkspaceBranding' => false,
                                    ], [
                                        'margin' => $this->responsive(['bottom' => '12px']),
                                    ]),
                                    $this->paragraph("51-53 New Cavendish Street,\nLondon, United Kingdom, W1G 9TG\n\n+44 20 7946 0921\nhello@nexusai.io", [
                                        'color' => $this->responsive('#a1a1aa'),
                                        'fontSize' => $this->responsive('14px'),
                                        'lineHeight' => $this->responsive('1.7'),
                                    ]),
                                ]),
                                'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                            ],
                            [
                                ...$this->column(2, [
                                    $this->heading('Company', 'h4', [
                                        'color' => $this->responsive('#ffffff'),
                                        'fontSize' => $this->responsive('14px'),
                                        'fontWeight' => $this->responsive('600'),
                                        'margin' => $this->responsive(['bottom' => '14px']),
                                    ]),
                                    $this->blockNode(BlockType::Navigation, [
                                        'orientation' => 'vertical',
                                        'items' => [
                                            ['label' => 'Home', 'url' => '/'],
                                            ['label' => 'About Us', 'url' => '/about'],
                                            ['label' => 'Services', 'url' => '/services'],
                                            ['label' => 'FAQs', 'url' => '/faqs'],
                                            ['label' => 'Blogs', 'url' => '/blogs'],
                                        ],
                                    ], ['color' => $this->responsive('#a1a1aa')]),
                                ]),
                                'span' => ['desktop' => 2, 'tablet' => 4, 'mobile' => 6],
                            ],
                            [
                                ...$this->column(3, [
                                    $this->heading('Services', 'h4', [
                                        'color' => $this->responsive('#ffffff'),
                                        'fontSize' => $this->responsive('14px'),
                                        'fontWeight' => $this->responsive('600'),
                                        'margin' => $this->responsive(['bottom' => '14px']),
                                    ]),
                                    $this->blockNode(BlockType::Navigation, [
                                        'orientation' => 'vertical',
                                        'items' => [
                                            ['label' => 'AI Automation', 'url' => '/services'],
                                            ['label' => 'AI Strategy', 'url' => '/services'],
                                            ['label' => 'AI Development', 'url' => '/services'],
                                            ['label' => 'Data & Intelligence', 'url' => '/services'],
                                        ],
                                    ], ['color' => $this->responsive('#a1a1aa')]),
                                ]),
                                'span' => ['desktop' => 3, 'tablet' => 4, 'mobile' => 6],
                            ],
                            [
                                ...$this->column(2, [
                                    $this->heading('Follow', 'h4', [
                                        'color' => $this->responsive('#ffffff'),
                                        'fontSize' => $this->responsive('14px'),
                                        'fontWeight' => $this->responsive('600'),
                                        'margin' => $this->responsive(['bottom' => '14px']),
                                    ]),
                                    $this->blockNode(BlockType::SocialIcons, [
                                        'networks' => [
                                            ['network' => 'x', 'url' => 'https://x.com'],
                                            ['network' => 'linkedin', 'url' => 'https://linkedin.com'],
                                            ['network' => 'instagram', 'url' => 'https://instagram.com'],
                                        ],
                                        'size' => '18px',
                                        'shape' => 'circle',
                                    ]),
                                ]),
                                'span' => ['desktop' => 2, 'tablet' => 4, 'mobile' => 12],
                            ],
                        ], [
                            'margin' => $this->responsive(['bottom' => '36px']),
                            'gap' => $this->responsive('20px'),
                        ]),
                        'stackBelow' => 'none',
                    ],
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('© Nexus AI. All rights reserved. Built with Modrix Visual Builder.', [
                                'color' => $this->responsive('#71717a'),
                                'fontSize' => $this->responsive('13px'),
                                'textAlign' => $this->responsive('center'),
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
    private function atomos99AboutHero(): array
    {
        return $this->block(
            key: 'atomos99-about-hero',
            name: 'Nexus AI · About Hero & Operator Bento',
            category: self::CATEGORY_HERO,
            description: 'About Us hero ("An AI partner that thinks like an operator, not a vendor") with 3-column bento grid, stats, quote card, and studio photography.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('72px', '24px', '48px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(8, [
                            $this->heading('An AI partner that thinks like an operator, not a vendor', 'h1', [
                                'fontSize' => $this->font('54px', '34px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.08'),
                                'letterSpacing' => $this->responsive('-0.035em'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '18px']),
                            ]),
                            $this->paragraph('We are a team of strategists, engineers, and product builders who help ambitious companies turn AI from a buzzword into a durable competitive advantage.', [
                                'fontSize' => $this->font('17px', '15px'),
                                'lineHeight' => $this->responsive('1.65'),
                                'color' => $this->responsive('#52525b'),
                                'margin' => $this->responsive(['bottom' => '36px']),
                            ]),
                        ]),
                        [
                            ...$this->column(4, []),
                            'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => false],
                        ],
                    ]),
                    $this->row([
                        $this->column(4, [
                            $this->image(
                                'Nexus AI Operator Portrait',
                                ['borderRadius' => $this->responsive('20px'), 'margin' => $this->responsive(['bottom' => '12px'])],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb44119b5a419950b45417_Rectangle%20156.webp'
                            ),
                            $this->paragraph('50+   ·   AI projects delivered', [
                                'color' => $this->responsive('#09090b'),
                                'fontSize' => $this->responsive('16px'),
                                'fontWeight' => $this->responsive('700'),
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'padding' => $this->responsive(['top' => '14px', 'right' => '18px', 'bottom' => '14px', 'left' => '18px']),
                                'borderRadius' => $this->responsive('14px'),
                                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->paragraph('3x   ·   ROI within 12 months', [
                                'color' => $this->responsive('#09090b'),
                                'fontSize' => $this->responsive('16px'),
                                'fontWeight' => $this->responsive('700'),
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'padding' => $this->responsive(['top' => '14px', 'right' => '18px', 'bottom' => '14px', 'left' => '18px']),
                                'borderRadius' => $this->responsive('14px'),
                                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            ]),
                        ]),
                        $this->column(4, [
                            $this->paragraph('▲ Kanba', [
                                'color' => $this->responsive('#09090b'),
                                'fontSize' => $this->responsive('16px'),
                                'fontWeight' => $this->responsive('700'),
                                'margin' => [
                                    'desktop' => ['bottom' => '64px'],
                                    'mobile' => ['bottom' => '24px'],
                                ],
                            ]),
                            $this->heading('"Good strategy is not about doing more things. It’s about making the right decision at the right time."', 'h3', [
                                'fontSize' => $this->responsive('21px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.4'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '24px']),
                            ]),
                            $this->paragraph('Floyd Miles — CEO of Kanba', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('13px'),
                                'fontWeight' => $this->responsive('600'),
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'backgroundGradient' => $this->responsive('linear-gradient(180deg, #f4f4f2 0%, #fff7ed 65%, #fed7aa 100%)'),
                            'borderRadius' => $this->responsive('22px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                        ]),
                        $this->column(4, [
                            $this->image(
                                'Atomos99 Studio Team Collaboration',
                                ['borderRadius' => $this->responsive('22px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb44112ee5470835f5b40f_Rectangle%20155.webp'
                            ),
                        ]),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99AboutMission(): array
    {
        $statBox = fn (string $num, string $title, string $desc): array => [
            ...$this->column(3, [
                $this->heading($num, 'h3', [
                    'fontSize' => $this->font('38px', '28px'),
                    'fontWeight' => $this->responsive('600'),
                    'letterSpacing' => $this->responsive('-0.03em'),
                    'color' => $this->responsive('#09090b'),
                    'margin' => $this->responsive(['bottom' => '14px']),
                ]),
                $this->heading($title, 'h4', [
                    'fontSize' => $this->responsive('16px'),
                    'fontWeight' => $this->responsive('600'),
                    'color' => $this->responsive('#09090b'),
                    'margin' => $this->responsive(['bottom' => '6px']),
                ]),
                $this->paragraph($desc, [
                    'color' => $this->responsive('#52525b'),
                    'fontSize' => $this->responsive('13px'),
                    'lineHeight' => $this->responsive('1.6'),
                ]),
            ], [
                'backgroundColor' => $this->responsive('#ffffff'),
                'borderRadius' => $this->responsive('20px'),
                'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'padding' => $this->responsive(['top' => '24px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px']),
                'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 6],
        ];

        return $this->block(
            key: 'atomos99-about-mission',
            name: 'Nexus AI · About Company Mission & Stats',
            category: self::CATEGORY_CONTENT,
            description: 'Centered editorial mission statement followed by 4 key company metric cards (40,000+, 4.8+, 22+, 99.4%).',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('88px', '24px', '52px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// ABOUT OUR COMPANY', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('Creating secure coverage solutions for the future health protection solutions for the future health protection', 'h2', [
                                'fontSize' => $this->font('36px', '26px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.22'),
                                'letterSpacing' => $this->responsive('-0.025em'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('780px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '48px', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                    [
                        ...$this->row([
                            $statBox('40,000+', 'Happy Customer', 'Trusted by fast-growing teams and enterprise operators worldwide.'),
                            $statBox('4.8+', 'Overall Satisfaction', 'Rated by executives across strategy, engineering, and delivery.'),
                            $statBox('22+', 'Award Winner', 'Recognized for excellence in applied AI and workflow design.'),
                            $statBox('99.4%', 'Client Retention', 'Long-term partnerships built on measurable business outcomes.'),
                        ], ['gap' => $this->responsive('16px')]),
                        'stackBelow' => 'none',
                    ],
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99AboutStrategy(): array
    {
        return $this->block(
            key: 'atomos99-about-strategy',
            name: 'Nexus AI · What a Good Strategy Gets You',
            category: self::CATEGORY_CONTENT,
            description: 'Split workspace photography and 2x2 strategy pillars (Clarity, Prioritization, Confidence, Speed).',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('88px', '24px', '52px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [$this->row([
                    $this->column(5, [
                        $this->image(
                            'Engineers Collaborating at Atomos99',
                            ['borderRadius' => $this->responsive('24px')],
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb50b22ee5470835fa71a9_Rectangle%20127.webp'
                        ),
                    ]),
                    $this->column(7, [
                        $this->paragraph('// BENEFITS', [
                            'color' => $this->responsive('#ff542e'),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.14em'),
                            'margin' => $this->responsive(['bottom' => '10px']),
                        ]),
                        $this->heading('What a good strategy gets you', 'h2', [
                            'fontSize' => $this->font('40px', '28px'),
                            'fontWeight' => $this->responsive('600'),
                            'lineHeight' => $this->responsive('1.12'),
                            'letterSpacing' => $this->responsive('-0.03em'),
                            'color' => $this->responsive('#09090b'),
                            'margin' => $this->responsive(['bottom' => '12px']),
                        ]),
                        $this->paragraph('Before writing a single line of code, we align your leadership and operations around where AI drives real leverage.', [
                            'color' => $this->responsive('#52525b'),
                            'fontSize' => $this->responsive('15px'),
                            'lineHeight' => $this->responsive('1.65'),
                            'margin' => $this->responsive(['bottom' => '28px']),
                        ]),
                        $this->blockNode(BlockType::FeatureCards, [
                            'columns' => 2,
                            'items' => [
                                [
                                    'icon' => 'eye',
                                    'title' => 'Clarity',
                                    'body' => 'Cut through AI hype and see exactly which workflows are worth automating first.',
                                ],
                                [
                                    'icon' => 'layers',
                                    'title' => 'Prioritization',
                                    'body' => 'Rank opportunities by business impact, data readiness, and implementation speed.',
                                ],
                                [
                                    'icon' => 'shield',
                                    'title' => 'Confidence',
                                    'body' => 'Move forward with clear ROI projections, security guardrails, and executive alignment.',
                                ],
                                [
                                    'icon' => 'zap',
                                    'title' => 'Speed',
                                    'body' => 'Go from roadmap to working production prototypes in weeks instead of quarters.',
                                ],
                            ],
                        ]),
                    ]),
                ], [
                    'alignItems' => $this->responsive('center'),
                    'gap' => $this->responsive('36px'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99AboutTeam(): array
    {
        $memberCard = fn (string $name, string $role, string $photo): array => $this->column(4, [
            $this->image(
                $name,
                ['borderRadius' => $this->responsive('18px'), 'margin' => $this->responsive(['bottom' => '14px'])],
                $photo
            ),
            $this->heading($name, 'h3', [
                'fontSize' => $this->responsive('18px'),
                'fontWeight' => $this->responsive('600'),
                'color' => $this->responsive('#09090b'),
                'margin' => $this->responsive(['bottom' => '4px']),
            ]),
            $this->paragraph($role.'   ·   𝕏   in', [
                'color' => $this->responsive('#71717a'),
                'fontSize' => $this->responsive('13px'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('22px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '20px', 'left' => '16px']),
            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
        ]);

        return $this->block(
            key: 'atomos99-about-team',
            name: 'Nexus AI · Success Behind Nexus AI Team',
            category: self::CATEGORY_SOCIAL_PROOF,
            description: 'Six-member leadership and engineering team grid with studio portraits and roles.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('88px', '24px', '52px'),
                ],
                rows: [
                    $this->row([
                        $this->column(7, [
                            $this->paragraph('// OUR EXPERT MEMBER', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'margin' => $this->responsive(['bottom' => '10px']),
                            ]),
                            $this->heading('Success behind Nexus AI', 'h2', [
                                'fontSize' => $this->font('42px', '30px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.12'),
                                'letterSpacing' => $this->responsive('-0.03em'),
                                'color' => $this->responsive('#09090b'),
                            ]),
                        ]),
                        $this->column(5, [
                            $this->paragraph('Engineers, strategists, and designers obsessed with shipping AI systems that deliver real operational value.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.65'),
                            ]),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('end'),
                        'margin' => $this->responsive(['bottom' => '36px']),
                    ]),
                    $this->row([
                        $memberCard('Sophia Martinez', 'CEO, Manager', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f308c237041098e2b2_Team%20Image.webp'),
                        $memberCard('Olivia Chen', 'Chief Operating', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f4e337e1a4848afa2d_Team%20Image%20(2).webp'),
                        $memberCard('Jack Black', 'Brand Manager', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f41a9ffd53e06e1985_Team%20Image%2005.webp'),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '20px']),
                    ]),
                    $this->row([
                        $memberCard('Liam Jonson', 'Design Lead', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f386100e0e81ed48cc_Team%20Image%2006.webp'),
                        $memberCard('Zoe Bennett', 'Art Director', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f320476a9362ac0fc1_Team%20Image%2004.webp'),
                        $memberCard('Victoria Hughes', 'Project Manager', 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abb56f326ed6a1a4ce5393b_Team%20Image%20(1).webp'),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99ServicesHero(): array
    {
        $serviceCard = fn (string $iconUrl, string $title, string $body): array => $this->column(4, [
            $this->image(
                $title.' Icon',
                ['maxWidth' => $this->responsive('44px'), 'margin' => $this->responsive(['bottom' => '36px'])],
                $iconUrl
            ),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('20px'),
                'fontWeight' => $this->responsive('600'),
                'color' => $this->responsive('#09090b'),
                'margin' => $this->responsive(['bottom' => '10px']),
            ]),
            $this->paragraph($body, [
                'color' => $this->responsive('#52525b'),
                'fontSize' => $this->responsive('14px'),
                'lineHeight' => $this->responsive('1.6'),
                'margin' => $this->responsive(['bottom' => '18px']),
            ]),
            $this->blockNode(BlockType::Button, [
                'label' => 'Learn more',
                'action' => 'url',
                'url' => '/faqs',
                'iconRight' => 'arrow-right',
                'size' => 'sm',
                'variant' => 'secondary',
            ], [
                'backgroundColor' => $this->responsive('#fafaf8'),
                'color' => $this->responsive('#09090b'),
                'borderRadius' => $this->responsive('999px'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('22px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
        ]);

        return $this->block(
            key: 'atomos99-services-hero',
            name: 'Nexus AI · Services Hero & 5-Way Grid',
            category: self::CATEGORY_HERO,
            description: 'Atomos99 Services page hero ("Four ways we help you grow with AI") with 2 large illustrated cards (AI Strategy & AI Automation) and 3-column services grid.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('72px', '24px', '48px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// OUR SERVICES', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('Four ways we help you grow with AI', 'h1', [
                                'fontSize' => $this->font('54px', '34px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.08'),
                                'letterSpacing' => $this->responsive('-0.035em'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('760px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '18px', 'left' => 'auto']),
                            ]),
                            $this->paragraph('Whether you need a roadmap, a fully automated workflow, or a custom-built tool, we meet you where you are — and take you further.', [
                                'fontSize' => $this->font('17px', '15px'),
                                'lineHeight' => $this->responsive('1.65'),
                                'color' => $this->responsive('#52525b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('620px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '44px', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->image(
                                'AI Strategy Icon',
                                ['maxWidth' => $this->responsive('44px'), 'margin' => $this->responsive(['bottom' => '16px'])],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8b1a52ffcc8e26c7ed06f_Frame%20-%202026-09-27T120202.574.svg'
                            ),
                            $this->heading('AI Strategy', 'h3', [
                                'fontSize' => $this->responsive('24px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '8px']),
                            ]),
                            $this->paragraph('We map out where AI fits in your business and build a roadmap that gets real results.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.6'),
                                'margin' => $this->responsive(['bottom' => '24px']),
                            ]),
                            $this->image(
                                'AI Strategy Generator UI',
                                ['borderRadius' => $this->responsive('16px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8b17f099df6fbd8aeac5d_Frame%202147259124.svg'
                            ),
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('24px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
                        ]),
                        $this->column(6, [
                            $this->image(
                                'AI Automation Icon',
                                ['maxWidth' => $this->responsive('44px'), 'margin' => $this->responsive(['bottom' => '16px'])],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8bea67a7a91a5640088f4_settings%20(1)%201.svg'
                            ),
                            $this->heading('AI Automation', 'h3', [
                                'fontSize' => $this->responsive('24px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '8px']),
                            ]),
                            $this->paragraph('We automate repetitive tasks so your team can spend time on work that matters.', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'lineHeight' => $this->responsive('1.6'),
                                'margin' => $this->responsive(['bottom' => '24px']),
                            ]),
                            $this->image(
                                'AI Automation Flow Nodes',
                                ['borderRadius' => $this->responsive('16px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8beb72ffcc8e26c805fc9_Frame%202147259125.svg'
                            ),
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('24px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
                        ]),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '20px']),
                    ]),
                    $this->row([
                        $serviceCard(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8c173aafd156787ebad96_Frame%20-%202026-09-27T120144.292.svg',
                            'AI Consulting',
                            'We help businesses harness the power of AI, strategy is driven by intelligent insights.'
                        ),
                        $serviceCard(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8c0e07f5db6dfbced1786_Frame%20-%202026-09-27T120138.116.svg',
                            'Data & Intelligence',
                            'We turn your data into insights you can act on, so every decision is backed by evidence.'
                        ),
                        $serviceCard(
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6ab8c00865637c971cc410cc_Frame%20-%202026-09-27T113610.459.svg',
                            'AI Development',
                            'We design and build custom AI tools that fit your workflow, not the other way around.'
                        ),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99ServicesStrategy(): array
    {
        return $this->block(
            key: 'atomos99-services-strategy',
            name: 'Nexus AI · Services Strategy & Right Photo',
            category: self::CATEGORY_CONTENT,
            description: 'Services page split strategy section ("What a good strategy gets you") with 4 value pillars on the left and studio photography on the right.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('88px', '24px', '52px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [$this->row([
                    $this->column(7, [
                        $this->paragraph('// STRATEGY VALUE', [
                            'color' => $this->responsive('#ff542e'),
                            'fontSize' => $this->responsive('12px'),
                            'fontWeight' => $this->responsive('700'),
                            'letterSpacing' => $this->responsive('0.14em'),
                            'margin' => $this->responsive(['bottom' => '10px']),
                        ]),
                        $this->heading('What a good strategy gets you', 'h2', [
                            'fontSize' => $this->font('40px', '28px'),
                            'fontWeight' => $this->responsive('600'),
                            'lineHeight' => $this->responsive('1.12'),
                            'letterSpacing' => $this->responsive('-0.03em'),
                            'color' => $this->responsive('#09090b'),
                            'margin' => $this->responsive(['bottom' => '12px']),
                        ]),
                        $this->paragraph('We prioritize your peace of mind with reliable coverage. Our team is here to support you.', [
                            'color' => $this->responsive('#52525b'),
                            'fontSize' => $this->responsive('15px'),
                            'lineHeight' => $this->responsive('1.65'),
                            'margin' => $this->responsive(['bottom' => '28px']),
                        ]),
                        $this->blockNode(BlockType::FeatureCards, [
                            'columns' => 2,
                            'items' => [
                                [
                                    'icon' => 'eye',
                                    'title' => 'Clarity',
                                    'body' => "Know exactly where AI helps, and where it doesn't.",
                                ],
                                [
                                    'icon' => 'layers',
                                    'title' => 'Prioritization',
                                    'body' => 'Tackle the highest-impact opportunities first.',
                                ],
                                [
                                    'icon' => 'shield',
                                    'title' => 'Confidence',
                                    'body' => 'Make decisions backed by a plan, not guesswork.',
                                ],
                                [
                                    'icon' => 'zap',
                                    'title' => 'Speed',
                                    'body' => 'Skip months of trial and error.',
                                ],
                            ],
                        ]),
                    ]),
                    $this->column(5, [
                        $this->image(
                            'Atomos99 Strategy Session',
                            ['borderRadius' => $this->responsive('24px')],
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc6cc77ea0623643d6f633_Rectangle%20127%20(1).webp'
                        ),
                    ]),
                ], [
                    'alignItems' => $this->responsive('center'),
                    'gap' => $this->responsive('36px'),
                ])],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99BlogGrid(): array
    {
        $postCard = fn (string $title, string $category, string $date, string $slug, string $thumb): array => $this->column(4, [
            $this->image(
                $title,
                ['borderRadius' => $this->responsive('18px'), 'margin' => $this->responsive(['bottom' => '16px'])],
                $thumb
            ),
            $this->paragraph($category.'   ·   '.$date, [
                'color' => $this->responsive('#ff542e'),
                'fontSize' => $this->responsive('12px'),
                'fontWeight' => $this->responsive('700'),
                'letterSpacing' => $this->responsive('0.06em'),
                'margin' => $this->responsive(['bottom' => '8px']),
            ]),
            $this->heading($title, 'h3', [
                'fontSize' => $this->responsive('19px'),
                'fontWeight' => $this->responsive('600'),
                'lineHeight' => $this->responsive('1.35'),
                'color' => $this->responsive('#09090b'),
                'margin' => $this->responsive(['bottom' => '16px']),
            ]),
            $this->blockNode(BlockType::Button, [
                'label' => 'Read article',
                'action' => 'url',
                'url' => '/blog/'.$slug,
                'iconRight' => 'arrow-right',
                'size' => 'sm',
                'variant' => 'secondary',
            ], [
                'backgroundColor' => $this->responsive('#fafaf8'),
                'color' => $this->responsive('#09090b'),
                'borderRadius' => $this->responsive('999px'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('22px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => $this->responsive(['top' => '16px', 'right' => '16px', 'bottom' => '22px', 'left' => '16px']),
            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
        ]);

        return $this->block(
            key: 'atomos99-blog-grid',
            name: 'Nexus AI · Blogs Hero & 6-Post Grid',
            category: self::CATEGORY_CONTENT,
            description: 'Atomos99 Blogs page with editorial header ("How to know if your business is ready for AI") and 6 blog post cards with categories, dates, and thumbnails.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('72px', '24px', '52px'),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// INSIGHTS & RESEARCH', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('How to know if your business is ready for AI', 'h1', [
                                'fontSize' => $this->font('52px', '32px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.1'),
                                'letterSpacing' => $this->responsive('-0.035em'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('760px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '16px', 'left' => 'auto']),
                            ]),
                            $this->paragraph("Five signs it's time to explore automation — and where to start.", [
                                'fontSize' => $this->font('17px', '15px'),
                                'lineHeight' => $this->responsive('1.6'),
                                'color' => $this->responsive('#52525b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('560px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '44px', 'left' => 'auto']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $postCard(
                            'How to know if your business is ready for AI',
                            'AI Strategy',
                            '29 Sep 2026',
                            'how-to-know-if-your-business-is-ready-for-ai',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb1ee80188e997008f0c1b_Frame%202147258664.webp'
                        ),
                        $postCard(
                            'How custom AI solutions effectively drive real growth',
                            'Company',
                            '29 Sep 2026',
                            'how-custom-ai-solutions-effectively-drive-real-growth',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2a00ad36baa123318f45_Frame%202147259157.webp'
                        ),
                        $postCard(
                            'How small businesses can use AI to grow faster',
                            'Research',
                            '29 Sep 2026',
                            'how-small-businesses-can-use-ai-to-grow-faster',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2a509ca8d1121221f5a0_Frame%202147258664%20(1).webp'
                        ),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '20px']),
                    ]),
                    $this->row([
                        $postCard(
                            'How custom technology solutions create lasting business value',
                            'AI Strategy',
                            '29 Sep 2026',
                            'how-custom-technology-solutions-create-lasting-business-value',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2b1cd341a9c24c3c4f3a_Frame%202147258664%20(2).webp'
                        ),
                        $postCard(
                            'How tailored AI solutions improve business performance',
                            'Company',
                            '29 Sep 2026',
                            'how-tailored-ai-solutions-improve-business-performance',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2b710188e9970092b614_Frame%202147259157%20(1).webp'
                        ),
                        $postCard(
                            'How smart AI systems help businesses achieve real results',
                            'Research',
                            '29 Sep 2026',
                            'how-smart-ai-systems-help-businesses-achieve-real-results',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2bb0c2a29660b0b7cb4e_Frame%202147258664%20(3).webp'
                        ),
                    ], ['gap' => $this->responsive('20px')]),
                ],
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function atomos99FaqHub(): array
    {
        $channelCard = fn (string $label, string $value, string $iconUrl): array => $this->column(4, [
            $this->image(
                $label,
                ['maxWidth' => $this->responsive('36px'), 'margin' => $this->responsive(['bottom' => '16px'])],
                $iconUrl
            ),
            $this->paragraph($label, [
                'color' => $this->responsive('#71717a'),
                'fontSize' => $this->responsive('12px'),
                'fontWeight' => $this->responsive('700'),
                'letterSpacing' => $this->responsive('0.12em'),
                'margin' => $this->responsive(['bottom' => '6px']),
            ]),
            $this->heading($value, 'h3', [
                'fontSize' => $this->responsive('18px'),
                'fontWeight' => $this->responsive('600'),
                'color' => $this->responsive('#09090b'),
            ]),
        ], [
            'backgroundColor' => $this->responsive('#ffffff'),
            'borderRadius' => $this->responsive('20px'),
            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
            'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade lift']),
        ]);

        return $this->block(
            key: 'atomos99-faq-hub',
            name: 'Nexus AI · FAQs & Quick Support Hub',
            category: self::CATEGORY_STRUCTURE,
            description: 'Dedicated FAQs & Support hub with categorized two-column accordions, 3 direct contact channels (Email, WhatsApp, Austin HQ), and LightAI executive spotlight.',
            sections: [$this->section(
                style: [
                    'backgroundColor' => $this->responsive('#fafaf8'),
                    'padding' => $this->pad('72px', '24px', '52px'),
                    'custom' => $this->responsive(['--fnl-effect' => 'reveal exit-fade']),
                ],
                rows: [
                    $this->row([
                        $this->column(12, [
                            $this->paragraph('// HELP CENTER & FAQS', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('Let’s talk about your business plans & questions', 'h1', [
                                'fontSize' => $this->font('52px', '32px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.1'),
                                'letterSpacing' => $this->responsive('-0.035em'),
                                'color' => $this->responsive('#09090b'),
                                'textAlign' => $this->responsive('center'),
                                'maxWidth' => $this->responsive('780px'),
                                'margin' => $this->responsive(['right' => 'auto', 'bottom' => '16px', 'left' => 'auto']),
                            ]),
                            $this->paragraph('✓ No pressure   ·   ✓ Expert advice   ·   ✓ Clear roadmaps & transparent pricing', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('15px'),
                                'fontWeight' => $this->responsive('500'),
                                'textAlign' => $this->responsive('center'),
                                'margin' => $this->responsive(['bottom' => '44px']),
                            ]),
                        ]),
                    ]),
                    $this->row([
                        $this->column(6, [
                            $this->heading('Strategy & Delivery', 'h3', [
                                'fontSize' => $this->responsive('20px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '16px']),
                            ]),
                            $this->blockNode(BlockType::Faq, [
                                'initialOpen' => true,
                                'allowMultipleOpen' => false,
                                'emitSchemaOrg' => true,
                                'items' => [
                                    [
                                        'question' => 'How do we know which AI project to start with?',
                                        'answer' => 'Every engagement starts with a Discovery & ROI Audit. We evaluate your team’s bottlenecks, data readiness, and software stack to rank the highest-return automation opportunities.',
                                    ],
                                    [
                                        'question' => 'What deliverables do we get from an AI Strategy engagement?',
                                        'answer' => 'You receive a prioritized 90-day implementation roadmap, architecture diagrams, tool selection, projected ROI models, and a working proof-of-concept.',
                                    ],
                                    [
                                        'question' => 'Can you train our internal team to manage the workflows?',
                                        'answer' => 'Yes — every delivery includes live workshops, playbooks, and documentation so your operations and engineering teams own the workflows with confidence.',
                                    ],
                                ],
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('22px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                        ]),
                        $this->column(6, [
                            $this->heading('Security, Integrations & Support', 'h3', [
                                'fontSize' => $this->responsive('20px'),
                                'fontWeight' => $this->responsive('600'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '16px']),
                            ]),
                            $this->blockNode(BlockType::Faq, [
                                'initialOpen' => true,
                                'allowMultipleOpen' => false,
                                'emitSchemaOrg' => false,
                                'items' => [
                                    [
                                        'question' => 'Which AI models and platforms do you build with?',
                                        'answer' => 'We are model-agnostic and work with OpenAI, Anthropic Claude, DeepSeek, Google Gemini, and open-weights models hosted in your private cloud.',
                                    ],
                                    [
                                        'question' => 'Is our proprietary data ever used to train public AI models?',
                                        'answer' => 'Never. We configure zero-data-retention enterprise endpoints, VPC isolation, and strict role-based access controls for every client deployment.',
                                    ],
                                    [
                                        'question' => 'How does pricing work for custom AI development?',
                                        'answer' => 'We offer fixed-scope sprint packages for initial builds and optional monthly retainer plans for continuous optimization, monitoring, and new workflow rollouts.',
                                    ],
                                ],
                            ]),
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('22px'),
                            'border' => $this->responsive(['style' => 'solid', 'color' => '#e4e4e7', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                        ]),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '28px']),
                    ]),
                    $this->row([
                        $channelCard(
                            'EMAIL',
                            'hi@nexusai.io',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc8aceb7a1d41586e860c1_email-read%201.svg'
                        ),
                        $channelCard(
                            'WHATSAPP',
                            '+1 911 2231 234',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc8cc7c8b60eb215425228_phone%201%20(1).svg'
                        ),
                        $channelCard(
                            'ADDRESS',
                            '108 Sunridge Blvd, Austin, TX',
                            'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc8cc782b8ecfd7f921acd_location-pin%201%20(1).svg'
                        ),
                    ], [
                        'gap' => $this->responsive('20px'),
                        'margin' => $this->responsive(['bottom' => '28px']),
                    ]),
                    $this->row([
                        $this->column(7, [
                            $this->paragraph('// CLIENT SPOTLIGHT', [
                                'color' => $this->responsive('#ff542e'),
                                'fontSize' => $this->responsive('12px'),
                                'fontWeight' => $this->responsive('700'),
                                'letterSpacing' => $this->responsive('0.14em'),
                                'margin' => $this->responsive(['bottom' => '14px']),
                            ]),
                            $this->heading('"Our industry has some strict compliance hurdles, which made digital transformation challenging. Atomos99 navigated these complex regulatory waters with expertise and skill."', 'h3', [
                                'fontSize' => $this->font('24px', '19px'),
                                'fontWeight' => $this->responsive('600'),
                                'lineHeight' => $this->responsive('1.4'),
                                'color' => $this->responsive('#09090b'),
                                'margin' => $this->responsive(['bottom' => '20px']),
                            ]),
                            $this->paragraph('Henry Collins   ·   Founder & CEO at LightAI', [
                                'color' => $this->responsive('#52525b'),
                                'fontSize' => $this->responsive('14px'),
                                'fontWeight' => $this->responsive('600'),
                            ]),
                        ], [
                            'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                        ]),
                        $this->column(5, [
                            $this->image(
                                'Henry Collins at LightAI',
                                ['borderRadius' => $this->responsive('18px')],
                                'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a17/6abc74c61b070386b5413df4_image%20-%202026-09-30T083212.578.webp'
                            ),
                        ]),
                    ], [
                        'alignItems' => $this->responsive('center'),
                        'backgroundColor' => $this->responsive('#f4f4f2'),
                        'borderRadius' => $this->responsive('24px'),
                        'padding' => $this->responsive(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                    ]),
                ],
            )],
        );
    }

    /**
     * Seeds the 6 Atomos99 blog posts (`posts` & `categories`) and FAQ items (`faq_items`)
     * so `/blog`, `/blog/{slug}`, `/admin/posts`, and the FAQ plugin are populated too.
     */
    private function seedAtomos99BlogAndFaqs(): void
    {
        $categories = [
            'AI Strategy' => Category::query()->firstOrCreate(
                ['slug' => 'ai-strategy'],
                ['name' => 'AI Strategy', 'description' => 'Roadmaps, ROI models, and executive guides for adopting AI.'],
            ),
            'Company' => Category::query()->firstOrCreate(
                ['slug' => 'company'],
                ['name' => 'Company', 'description' => 'Case studies, playbooks, and updates from the Atomos99 team.'],
            ),
            'Research' => Category::query()->firstOrCreate(
                ['slug' => 'research'],
                ['name' => 'Research', 'description' => 'Applied AI benchmarks, workflow architectures, and engineering notes.'],
            ),
        ];

        $posts = [
            [
                'title' => 'How to know if your business is ready for AI',
                'slug' => 'how-to-know-if-your-business-is-ready-for-ai',
                'category' => 'AI Strategy',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb1ee80188e997008f0c1b_Frame%202147258664.webp',
                'excerpt' => "Five signs it's time to explore automation — and where to start without disrupting daily operations.",
                'content' => "## Five Signs Your Operations Are Ready for AI\n\nAdopting AI successfully starts with identifying repeatable bottlenecks where your team spends hours moving information between tools.\n\n### 1. High-Volume Repetitive Triage\nWhen customer inquiries, lead qualification, or support tickets follow predictable patterns, an AI assistant can resolve up to 85% of routine requests immediately.\n\n### 2. Structured Data Across Siloed Tools\nIf your CRM, helpdesk, and billing systems hold valuable context that nobody has time to synthesize, custom AI workflows connect those dots automatically.\n\n### 3. Clear KPIs & Executive Sponsorship\nThe highest-ROI deployments start with one measurable target — such as cutting response time from 4 hours to 30 seconds or saving 20 hours per week per operator.",
            ],
            [
                'title' => 'How custom AI solutions effectively drive real growth',
                'slug' => 'how-custom-ai-solutions-effectively-drive-real-growth',
                'category' => 'Company',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2a00ad36baa123318f45_Frame%202147259157.webp',
                'excerpt' => 'Why tailored pipelines and domain-specific assistants outperform generic off-the-shelf chat tools.',
                'content' => "## Why Custom AI Beats Generic Wrappers\n\nOff-the-shelf AI tools force your team to copy and paste context into a separate browser tab. Custom AI solutions live inside your existing stack.\n\n- **Direct CRM & ERP Integration**: Read and write records safely with validation guardrails.\n- **Domain-Specific Context**: Ground responses in your actual product documentation, pricing rules, and compliance policies.\n- **Measurable Unit Economics**: Track accuracy, latency, and hours saved per workflow.",
            ],
            [
                'title' => 'How small businesses can use AI to grow faster',
                'slug' => 'how-small-businesses-can-use-ai-to-grow-faster',
                'category' => 'Research',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2a509ca8d1121221f5a0_Frame%202147258664%20(1).webp',
                'excerpt' => 'Practical automation playbooks that let lean teams punch above their weight class without adding headcount.',
                'content' => "## Scaling Output Without Scaling Payroll\n\nLean teams gain the biggest advantage from AI automation because every saved hour goes straight back into product and customer relationships.\n\n1. **Automated Lead Enrichment & Follow-Up**\n2. **24/7 Intelligent Customer Onboarding**\n3. **Instant Weekly Executive Reporting**",
            ],
            [
                'title' => 'How custom technology solutions create lasting business value',
                'slug' => 'how-custom-technology-solutions-create-lasting-business-value',
                'category' => 'AI Strategy',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2b1cd341a9c24c3c4f3a_Frame%202147258664%20(2).webp',
                'excerpt' => 'Building proprietary AI workflows that compound in accuracy and defensibility over time.',
                'content' => "## Turning Workflows into Durable Moats\n\nWhen your AI pipelines are trained on your operational feedback loops, your systems get smarter every week — creating a lasting operational edge competitors cannot easily replicate.",
            ],
            [
                'title' => 'How tailored AI solutions improve business performance',
                'slug' => 'how-tailored-ai-solutions-improve-business-performance',
                'category' => 'Company',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2b710188e9970092b614_Frame%202147259157%20(1).webp',
                'excerpt' => 'A look inside how Nexus AI designs, tests, and ships production AI pipelines in 3 to 6 weeks.',
                'content' => "## Inside the Nexus AI 3-Step Delivery Framework\n\nFrom discovery audit to production monitoring, we focus on reliability, observability, and human-in-the-loop controls.",
            ],
            [
                'title' => 'How smart AI systems help businesses achieve real results',
                'slug' => 'how-smart-ai-systems-help-businesses-achieve-real-results',
                'category' => 'Research',
                'cover_image' => 'https://cdn.prod.website-files.com/6ab7252a19eae17e99165a13/6abb2bb0c2a29660b0b7cb4e_Frame%202147258664%20(3).webp',
                'excerpt' => 'Benchmarks and lessons learned from 50+ enterprise and mid-market AI deployments.',
                'content' => "## Lessons from 50+ Production AI Deployments\n\nReal-world AI success comes down to clean data pipelines, scoped agent responsibilities, and clear evaluation benchmarks.",
            ],
        ];

        foreach ($posts as $index => $item) {
            Post::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categories[$item['category']]->id,
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'content' => $item['content'],
                    'cover_image' => $item['cover_image'],
                    'meta_description' => $item['excerpt'],
                    'published_at' => now()->subDays($index + 1),
                ],
            );
        }

        if (Schema::hasTable('faq_items')) {
            $faqs = [
                [
                    'category' => 'General',
                    'question' => 'How long does a typical AI project take?',
                    'answer_markdown' => 'Most focused automation and AI assistant projects go from discovery to production in 3 to 6 weeks. Larger custom model deployments typically take 6 to 10 weeks with weekly milestones.',
                    'sort_order' => 1,
                ],
                [
                    'category' => 'General',
                    'question' => 'Do we need a large technical team to work with you?',
                    'answer_markdown' => 'Not at all. We handle architecture, development, integration, and deployment end-to-end, and train your team so anyone can operate the system confidently.',
                    'sort_order' => 2,
                ],
                [
                    'category' => 'Security',
                    'question' => 'How do you keep our company data secure and private?',
                    'answer_markdown' => 'Your data never trains public models. We deploy within private cloud environments or enterprise APIs with strict encryption, role-based access, and audit logging.',
                    'sort_order' => 3,
                ],
                [
                    'category' => 'Integrations',
                    'question' => 'Can you integrate with the tools we already use?',
                    'answer_markdown' => 'Yes — we integrate natively with Slack, HubSpot, Salesforce, Notion, Linear, Zendesk, Postgres, and custom internal APIs.',
                    'sort_order' => 4,
                ],
                [
                    'category' => 'Support',
                    'question' => 'What happens after our AI solution is launched?',
                    'answer_markdown' => 'We provide ongoing monitoring, performance tuning, and support packages to ensure your workflows continue delivering measurable ROI as your business grows.',
                    'sort_order' => 5,
                ],
            ];

            foreach ($faqs as $faq) {
                DB::table('faq_items')->updateOrInsert(
                    ['question' => $faq['question']],
                    [...$faq, 'is_published' => true, 'updated_at' => now(), 'created_at' => now()],
                );
            }
        }
    }
}
