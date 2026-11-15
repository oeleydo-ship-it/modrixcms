<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\BuildsDocumentNodes;
use App\Cms\Builder\Schema\TemplatePresentation;

/**
 * Lumikin: a bright kindergarten / early-years collection. White pages with a warm sand
 * band, a deep berry accent, pastel cards, organic photo shapes and Parkinsans type.
 *
 * Every section is ordinary builder blocks, so each one is also a reusable design in the
 * builder's "Insert a section" panel. Sections carry `settings.designCollection = lumikin`
 * for the few finishing rules block styles cannot express (sticky stacking cards, carousel
 * arrows, FAQ cards). Colours and fonts follow Theme settings, with the Lumikin values as
 * fallbacks, so the pages restyle with whichever theme is active.
 */
final class LumikinDesigns
{
    use BuildsDocumentNodes;

    public const COLLECTION = 'lumikin';

    /* Theme-linked colours and fonts (Lumikin values as fallbacks). */
    private const FONT = 'var(--site-body-font, Parkinsans, sans-serif)';

    private const HEADING_FONT = 'var(--site-heading-font, Parkinsans, sans-serif)';

    private const INK = 'var(--site-heading, #111111)';

    private const MUTED = 'var(--site-body, #4d4d4d)';

    private const WHITE = 'var(--site-background, #ffffff)';

    private const SAND = 'var(--site-surface, #f6f2ed)';

    private const BERRY = 'var(--site-primary, #9a233d)';

    private const ON_BERRY = 'var(--site-on-primary, #ffffff)';

    private const DARK = 'var(--site-dark, #151519)';

    /* Fixed pastels, the same in every theme. */
    private const LAVENDER = '#dadaf0';

    private const MINT = '#d8f0e0';

    private const PEACH = '#ffdfcf';

    private const BUTTER = '#ffe2a2';

    private const SKY = '#e9f2fd';

    private const CYAN = '#e2f8ff';

    private const LOGO = '/templates/lumikin/logo.svg';

    private const LOGO_LIGHT = '/templates/lumikin/logo-light.svg';

    private const PHONE = '(555) 318-2040';

    private const EMAIL = 'hello@lumikin.school';

    private const ADDRESS = '22 Willow Bend, Austin, TX 78704';

    /** The content width follows Theme settings → Sections & Cards → Content width. */
    private const WIDTH = 'var(--site-content-width, 1560px)';

    /** Unsplash photo ids (free licence). */
    private const PHOTOS = [
        'hero' => '1544773088-d142e38f5793',
        'story' => '1586694680938-9682c9e1f736',
        'learning' => '1691256257499-25b0717e3f57',
        'studying' => '1758612898788-38a249839c1d',
        'blocks' => '1646148327729-761d4701ef52',
        'circle' => '1786292949404-084cbd10c7b1',
        'reading' => '1759977064094-840dfc694bee',
        'yoga' => '1769095210688-31a7fe984926',
        'art' => '1780751987564-79489283d67b',
        'garden' => '1723750592050-e973050b6ee1',
        'mother' => '1560707854-fb9a10eeaace',
        'teacher' => '1788882679829-fee300e8ae4a',
        'cheer' => '1777421026883-0d55a32e2364',
        'toddler' => '1557452765-5b57d100c1dc',
        'parent' => '1551934262-db2d7dd517f4',
        'toys' => '1637672960996-cdad3e7b0f46',
        'clay' => '1574704530833-d47190a0d92f',
        'field' => '1502086223501-7ea6ecd79368',
        'smile' => '1624272949900-9ae4c56397e8',
        'grass' => '1630864972901-052dfdc7fba4',
        'laugh' => '1517832207067-4db24a2ae47c',
        'room' => '1635625203639-b793c70ba6c9',
        'mural' => '1746343365909-c6b3ce17c462',
        'party' => '1693467064788-d6163ae3b0e5',
        'p1' => '1494790108377-be9c29b29330',
        'p2' => '1568602471122-7832951cc4c5',
        'p3' => '1489278353717-f64c6ee8a4d2',
        'p4' => '1758691737646-79dbce8e25fb',
        'p5' => '1570840934347-4dc56c98b8ef',
        'p6' => '1757744705465-ea08b0ddc38a',
        'p7' => '1548382131-e0ebb1f0cdea',
        'p8' => '1564460576398-ef55d99548b2',
    ];

    /**
     * The page templates (without header and footer, which are the theme's global ones),
     * as ordered design keys.
     *
     * @return list<array{id: string, name: string, slug: string, title: string, description: string, keys: list<string>}>
     */
    public function pages(): array
    {
        return [
            [
                'id' => 'lumikin-home', 'name' => 'Lumikin · Home', 'slug' => 'home', 'title' => 'Home',
                'description' => 'Kindergarten home page: photo hero with feature strip, story, values cards, learning, why us, benefits, stacking activity cards, enrolment journey, parent reviews and stories.',
                'keys' => ['lumikin-hero', 'lumikin-story', 'lumikin-values', 'lumikin-learning', 'lumikin-why-us', 'lumikin-benefits', 'lumikin-activities', 'lumikin-journey', 'lumikin-testimonials', 'lumikin-blog'],
            ],
            [
                'id' => 'lumikin-about', 'name' => 'Lumikin · About', 'slug' => 'about', 'title' => 'About',
                'description' => 'About page: centred hero with photo cards, numbers and reasons, teachers, environment, timeline, parent reviews and stories.',
                'keys' => ['lumikin-about-hero', 'lumikin-about-numbers', 'lumikin-team', 'lumikin-environment', 'lumikin-timeline', 'lumikin-testimonials', 'lumikin-blog'],
            ],
            [
                'id' => 'lumikin-programs', 'name' => 'Lumikin · Programs', 'slug' => 'programs', 'title' => 'Programs',
                'description' => 'Programs page: photo hero, mission highlights, six programs, stacking activity cards and parent reviews.',
                'keys' => ['lumikin-programs-hero', 'lumikin-mission', 'lumikin-programs-grid', 'lumikin-activities', 'lumikin-testimonials'],
            ],
            [
                'id' => 'lumikin-fees', 'name' => 'Lumikin · Fees', 'slug' => 'fees', 'title' => 'Fees',
                'description' => 'Fees page: photo hero, three fee plans, what is included and FAQ with advisors.',
                'keys' => ['lumikin-fees-hero', 'lumikin-fee-plans', 'lumikin-included', 'lumikin-faq'],
            ],
            [
                'id' => 'lumikin-blog', 'name' => 'Lumikin · Blog', 'slug' => 'stories', 'title' => 'Blog',
                'description' => 'Blog page: photo hero and a grid of six stories.',
                'keys' => ['lumikin-blog-hero', 'lumikin-blog-grid'],
            ],
            [
                'id' => 'lumikin-contact', 'name' => 'Lumikin · Contact', 'slug' => 'contact', 'title' => 'Contact',
                'description' => 'Contact page: photo hero, contact cards with message card, map and campus highlights.',
                'keys' => ['lumikin-contact-hero', 'lumikin-contact', 'lumikin-campus'],
            ],
        ];
    }

    /**
     * The finished sections of the given design keys, in order, with fresh ids.
     *
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    public function sections(array $keys): array
    {
        $byKey = array_column($this->all(), 'document', 'key');
        $sections = [];

        foreach ($keys as $key) {
            foreach (TemplatePresentation::document($byKey[$key] ?? ['sections' => []])['sections'] as $section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Every Lumikin design, with fresh ids.
     *
     * @return list<array{key: string, name: string, category: string, description: string, document: array<string, mixed>}>
     */
    public function all(): array
    {
        return [
            $this->header(),
            $this->footer(),
            $this->hero(),
            $this->story(),
            $this->values(),
            $this->learning(),
            $this->whyUs(),
            $this->benefits(),
            $this->activities(),
            $this->journey(),
            $this->testimonials(),
            $this->blog(),
            $this->aboutHero(),
            $this->aboutNumbers(),
            $this->team(),
            $this->environment(),
            $this->timeline(),
            $this->pageHero('lumikin-programs-hero', 'Programs hero', 'programs-hero', 'Our programs', 'Every child deserves joyful learning', 'Play-rich programs for curious little minds, from first steps to school-ready confidence.', 'cheer', 'Enrol your child', '/contact'),
            $this->mission(),
            $this->programsGrid(),
            $this->pageHero('lumikin-fees-hero', 'Fees hero', 'fees-hero', 'Fees', 'Simple, clear and fair fees', 'Quality early learning with no surprises: one honest price that covers every day of play.', 'party', 'See the plans', '#plans'),
            $this->feePlans(),
            $this->included(),
            $this->faq(),
            $this->pageHero('lumikin-blog-hero', 'Blog hero', 'blog-hero', 'News & stories', 'Ideas and stories for growing families', 'Tips, activities and news from our classrooms to help little ones learn at home too.', 'mural', 'Enrol your child', '/contact'),
            $this->blogGrid(),
            $this->pageHero('lumikin-contact-hero', 'Contact hero', 'contact-hero', 'Contact', 'We would love to hear from you', 'Questions, visits or first-day nerves: our team is happy to help, every weekday.', 'room', 'Get in touch', '#message'),
            $this->contact(),
            $this->campus(),
        ];
    }

    /* ------------------------------------------------------- header & footer */

    /** @return array<string, mixed> */
    private function header(): array
    {
        $nav = $this->row([
            [...$this->column(3, [$this->logo(false)], ['justifyContent' => $this->r('center')]), 'span' => ['desktop' => 3, 'tablet' => 5, 'mobile' => 7]],
            [
                ...$this->column(6, [
                    $this->blockNode(BlockType::Navigation, [
                        'items' => [
                            ['label' => 'Home', 'url' => '/'],
                            ['label' => 'About', 'url' => '/about'],
                            ['label' => 'Programs', 'url' => '/programs'],
                            ['label' => 'Fees', 'url' => '/fees'],
                            ['label' => 'Blog', 'url' => '/stories'],
                            ['label' => 'Contact', 'url' => '/contact'],
                        ],
                        'orientation' => 'horizontal',
                        'mobileBehaviour' => 'drawer',
                    ], [
                        'fontFamily' => $this->r(self::FONT),
                        'color' => $this->r(self::INK),
                        'fontSize' => $this->r('14px'),
                        'fontWeight' => $this->r('500'),
                        'gap' => $this->r('36px'),
                        'textAlign' => ['desktop' => 'center', 'mobile' => 'right'],
                        'justifyContent' => ['desktop' => 'center', 'mobile' => 'flex-end'],
                    ]),
                ], ['justifyContent' => $this->r('center')]),
                'span' => ['desktop' => 6, 'tablet' => 7, 'mobile' => 5],
            ],
            [
                ...$this->column(3, [$this->button('Schedule a visit', '/contact', 'berry', ['textAlign' => $this->r('right')])], ['justifyContent' => $this->r('center'), 'alignItems' => $this->r('flex-end')]),
                'span' => ['desktop' => 3, 'tablet' => 12, 'mobile' => 12],
                'visibility' => ['desktop' => true, 'tablet' => false, 'mobile' => false],
            ],
        ], [
            'alignItems' => $this->r('center'),
            'maxWidth' => $this->r(self::WIDTH),
            'gap' => ['desktop' => '24px', 'mobile' => '8px'],
        ]);
        $nav['stackBelow'] = 'none';

        $section = $this->section_('header', 'Header', [$nav], [
            'backgroundColor' => $this->r(self::WHITE),
            'boxShadow' => $this->r('0 10px 50px 0 rgba(83, 67, 67, 0.06)'),
            'padding' => ['desktop' => ['top' => '8px', 'right' => '40px', 'bottom' => '8px', 'left' => '40px'], 'mobile' => ['top' => '10px', 'right' => '16px', 'bottom' => '10px', 'left' => '16px']],
        ]);
        $section['sticky'] = true;

        return $this->design('lumikin-header', 'Lumikin · Header', 'structure', 'Sticky white header with logo, centred links and a berry "Schedule a visit" pill. Lumikin\'s global site header.', [$section]);
    }

    /** @return array<string, mixed> */
    private function footer(): array
    {
        $cta = $this->row([
            $this->column(12, [
                $this->h3("Inspiring young minds through joyful\nlearning, growth and confidence", '#ffffff', '32px', 'center', ['letterSpacing' => $this->r('-2px'), 'lineHeight' => $this->r('1.3')]),
                $this->button('Enrol your child', '/contact', 'white', ['textAlign' => $this->r('center')]),
            ], ['alignItems' => $this->r('center'), 'justifyContent' => $this->r('center'), 'gap' => $this->r('24px')]),
        ], [
            'maxWidth' => $this->r(self::WIDTH),
            'minHeight' => ['desktop' => '387px', 'mobile' => '300px'],
            'borderRadius' => $this->r('20px'),
            'backgroundColor' => $this->r('#151519'),
            'backgroundImage' => $this->r($this->photo('field', 1800)),
            'backgroundSize' => $this->r('cover'),
            'backgroundPosition' => $this->r('center'),
            'overlayColor' => $this->r('rgba(21, 21, 25, 0.45)'),
            'alignItems' => $this->r('center'),
            'padding' => $this->r(['top' => '40px', 'right' => '24px', 'bottom' => '40px', 'left' => '24px']),
            'margin' => ['desktop' => ['bottom' => '72px'], 'mobile' => ['bottom' => '48px']],
            'custom' => ['--fnl-effect' => 'reveal'],
        ]);

        $links = fn (string $title, array $items): array => [
            ...$this->column(2, [
                $this->h3($title, self::INK, '20px'),
                $this->blockNode(BlockType::Navigation, [
                    'items' => array_map(fn (array $item): array => ['label' => $item[0], 'url' => $item[1]], $items),
                    'orientation' => 'vertical',
                    'mobileBehaviour' => 'stack',
                ], ['fontFamily' => $this->r(self::FONT), 'color' => $this->r(self::MUTED), 'fontSize' => $this->r('16px'), 'gap' => $this->r('14px')]),
            ], ['gap' => $this->r('22px')]),
            'span' => ['desktop' => 2, 'tablet' => 4, 'mobile' => 6],
        ];

        $columns = $this->row([
            [
                ...$this->column(4, [
                    $this->logo(false),
                    $this->text('Joyful early years education for curious little minds. We help every child learn, play and grow with confidence.', self::MUTED, '16px', ['maxWidth' => $this->r('330px')]),
                    $this->h3('Follow us', self::INK, '20px'),
                    $this->blockNode(BlockType::SocialIcons, [
                        'networks' => [
                            ['network' => 'facebook', 'url' => 'https://facebook.com'],
                            ['network' => 'instagram', 'url' => 'https://instagram.com'],
                            ['network' => 'x', 'url' => 'https://x.com'],
                            ['network' => 'linkedin', 'url' => 'https://linkedin.com'],
                        ],
                        'size' => '18px',
                        'shape' => 'circle',
                    ], ['color' => $this->r(self::INK)]),
                ], ['gap' => $this->r('18px')]),
                'span' => ['desktop' => 4, 'tablet' => 12, 'mobile' => 12],
            ],
            $links('Quick links', [['Home', '/'], ['About', '/about'], ['Programs', '/programs'], ['Fees', '/fees'], ['Blog', '/stories'], ['Contact', '/contact']]),
            $links('Families', [['Enrolment', '/contact'], ['Fees & plans', '/fees'], ['Our teachers', '/about'], ['Privacy policy', '/privacy']]),
            [
                ...$this->column(3, [
                    $this->h3('Address', self::INK, '20px'),
                    $this->text(self::ADDRESS, self::MUTED, '16px', ['maxWidth' => $this->r('220px')]),
                    $this->h3('Contact', self::INK, '20px', 'left', ['margin' => $this->r(['top' => '14px'])]),
                    $this->text('<a href="tel:+15553182040">'.self::PHONE.'</a><br><a href="mailto:'.self::EMAIL.'">'.self::EMAIL.'</a>', self::MUTED, '16px'),
                ], ['gap' => $this->r('14px')]),
                'span' => ['desktop' => 3, 'tablet' => 4, 'mobile' => 12],
            ],
        ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '32px', 'mobile' => '16px']]);

        $bottom = $this->row([
            [...$this->column(6, [$this->text('© '.date('Y').' Lumikin Kindergarten. All rights reserved.', self::MUTED, '15px')]), 'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 12]],
            [...$this->column(6, [$this->text('<a href="/privacy">Privacy</a> &nbsp;·&nbsp; <a href="/terms">Terms</a>', self::MUTED, '15px', ['textAlign' => ['desktop' => 'right', 'mobile' => 'left']])]), 'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 12]],
        ], [
            'maxWidth' => $this->r(self::WIDTH),
            'border' => $this->r(['style' => 'solid', 'color' => 'rgba(0,0,0,0.1)', 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
            'padding' => $this->r(['top' => '20px', 'bottom' => '20px']),
            'margin' => ['desktop' => ['top' => '72px'], 'mobile' => ['top' => '40px']],
        ]);

        return $this->design('lumikin-footer', 'Lumikin · Footer with call to action', 'structure', 'Rounded photo call-to-action band, then logo, about text, socials, link columns, address and contact on warm sand. Lumikin\'s global site footer.', [
            $this->section_('footer', 'Footer', [$cta, $columns, $bottom], [
                'backgroundColor' => $this->r(self::SAND),
                'padding' => ['desktop' => ['top' => '44px', 'right' => '40px', 'bottom' => '0', 'left' => '40px'], 'mobile' => ['top' => '24px', 'right' => '16px', 'bottom' => '0', 'left' => '16px']],
            ]),
        ]);
    }

    /* ------------------------------------------------------------------ home */

    /** @return array<string, mixed> */
    private function hero(): array
    {
        $feature = fn (string $icon, string $bg, string $title, string $body, bool $last = false): array => [
            ...$this->column(3, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 8], 'gap' => '18px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
                    'children' => [
                        $this->iconCircle($icon, $bg, '88px'),
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '4px']),
                            'children' => [$this->h3($title, self::INK, '18px', 'left', ['letterSpacing' => $this->r('-1px')]), $this->text($body, self::MUTED, '16px')],
                        ],
                    ],
                ],
            ], [
                'border' => ['desktop' => ['style' => 'solid', 'color' => 'rgba(0,0,0,0.08)', 'top' => '0', 'right' => $last ? '0' : '1px', 'bottom' => '0', 'left' => '0'], 'mobile' => ['style' => 'solid', 'color' => 'rgba(0,0,0,0.08)', 'top' => '0', 'right' => '0', 'bottom' => $last ? '0' : '1px', 'left' => '0']],
                'padding' => ['desktop' => ['right' => '20px'], 'mobile' => ['bottom' => '18px']],
                'justifyContent' => $this->r('center'),
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        $avatars = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['alignItems' => $this->r('center'), 'justifyContent' => $this->r('center')]),
            'children' => [
                $this->image('Happy children', ['width' => $this->r('136px'), 'height' => $this->r('56px'), 'borderRadius' => $this->r('40px'), 'border' => $this->r(['style' => 'solid', 'color' => '#ffffff', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px'])], $this->photoUrl('laugh', 300, 120)),
                $this->text('<b>480+ happy kids</b><br>Learning and growing every day', self::INK, '15px', ['lineHeight' => $this->r('1.5')]),
            ],
        ];

        return $this->design('lumikin-hero', 'Lumikin · Photo hero with features', 'hero', 'Bright full-width photo, a lavender label, a large centred headline with a highlight, two pill buttons, happy-kids badge and a white four-feature strip.', [
            $this->section_('hero', 'Hero', [
                $this->row([
                    $this->column(12, [
                        $this->tag('CURIOUS MINDS GROW', self::LAVENDER, true),
                        $this->h1("Every child deserves\njoyful learning", self::INK, 'center', $this->highlight(self::MINT, '36%', '30px', '50%', '92%')),
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['justifyContent' => $this->r('center')]),
                            'children' => [$this->button('Enrol your child', '/contact', 'white'), $this->button('Explore programs', '/programs', 'outline')],
                        ],
                        $avatars,
                    ], ['alignItems' => $this->r('center'), 'gap' => $this->r('28px')]),
                ], [
                    'maxWidth' => $this->r('860px'),
                    'padding' => ['desktop' => ['top' => '130px', 'bottom' => '120px'], 'mobile' => ['top' => '72px', 'bottom' => '56px']],
                    'custom' => ['--fnl-effect' => 'blur-in'],
                ]),
                $this->row([
                    $feature('book-open', self::SKY, 'Confident learning', 'Discover books through story time'),
                    $feature('heart', self::PEACH, 'Healthy childhood', 'Stay active with indoor play games'),
                    $feature('star', self::BUTTER, 'Creative learning', 'Learn with guided playful tasks'),
                    $feature('compass', self::MINT, 'Artistic ideas', 'Create colourful art and crafts', true),
                ], [
                    'maxWidth' => $this->r(self::WIDTH),
                    'backgroundColor' => $this->r('#ffffff'),
                    'borderRadius' => $this->r('26px'),
                    'padding' => ['desktop' => ['top' => '40px', 'right' => '36px', 'bottom' => '40px', 'left' => '36px'], 'mobile' => ['top' => '24px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px']],
                    'gap' => ['desktop' => '24px', 'mobile' => '16px'],
                    'boxShadow' => $this->r('0 20px 60px -30px rgba(0, 0, 0, 0.2)'),
                    'custom' => ['--fnl-effect' => 'reveal'],
                ]),
            ], [
                'backgroundColor' => $this->r('#f4efe9'),
                'backgroundImage' => $this->r($this->photo('hero', 2000)),
                'backgroundSize' => $this->r('cover'),
                'backgroundPosition' => $this->r('center right'),
                'overlayColor' => $this->r('rgba(255, 255, 255, 0.58)'),
                'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '24px', 'left' => '16px']],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function story(): array
    {
        $point = fn (string $icon, string $bg, string $title, string $body, bool $last = false): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                'alignItems' => $this->r('center'),
                'border' => ['desktop' => ['style' => 'solid', 'color' => 'rgba(0,0,0,0.08)', 'top' => '0', 'right' => $last ? '0' : '1px', 'bottom' => '0', 'left' => '0'], 'mobile' => ['style' => 'none', 'color' => 'transparent', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0']],
                'padding' => $this->r(['left' => '10px', 'right' => '10px']),
            ]),
            'children' => [
                $this->iconCircle($icon, $bg, '88px', true),
                $this->h3($title, self::INK, '20px', 'center'),
                $this->text($body, self::MUTED, '16px', ['textAlign' => $this->r('center')]),
            ],
        ];

        $points = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [4, 4, 4], 'gap' => '0px', 'stackBelow' => 'mobile']),
            'children' => [
                $point('star', self::BUTTER, 'Creative learning', 'Learning through fun and joyful play.'),
                $point('users', self::PEACH, 'Caring mentors', 'Learn from warm, qualified teachers.'),
                $point('target', self::MINT, 'Future skills', 'Build key habits for young minds.', true),
            ],
        ];

        return $this->design('lumikin-story', 'Lumikin · Our story with organic photo', 'content', 'Organic-shaped photo with a floating "Hands-on learning" card beside a label, heading, text, three icon points and a berry button.', [
            $this->section_('story', 'Our story', [
                $this->row([
                    [
                        ...$this->column(6, [
                            // A true circle: the photo is always square (its height follows its width, see
                            // lumikin.ts) and the frame rounds to 50%, so it stays a circle at any width.
                            $this->image('Girl drawing at a table', [
                                'width' => ['desktop' => '560px', 'mobile' => '320px'],
                                'maxWidth' => $this->r('100%'),
                                'borderRadius' => $this->r('50%'),
                                'border' => $this->r(['style' => 'solid', 'color' => self::BUTTER, 'top' => '10px', 'right' => '10px', 'bottom' => '10px', 'left' => '10px']),
                                'margin' => $this->r(['left' => 'auto', 'right' => 'auto']),
                                'custom' => ['--fnl-effect' => 'reveal'],
                            ], $this->photoUrl('story', 1100, 1100)),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                                    'backgroundColor' => $this->r('#ffffff'),
                                    'borderRadius' => $this->r('25px'),
                                    'boxShadow' => $this->r('0 9px 22px 0 rgba(147, 147, 147, 0.25)'),
                                    'padding' => $this->r(['top' => '21px', 'right' => '14px', 'bottom' => '21px', 'left' => '14px']),
                                    'width' => $this->r('150px'),
                                    'alignItems' => $this->r('center'),
                                    'margin' => ['desktop' => ['top' => '-490px', 'left' => '-10px', 'bottom' => '334px'], 'mobile' => ['top' => '-270px', 'left' => '0', 'bottom' => '114px']],
                                    'custom' => ['--fnl-effect' => 'float'],
                                ]),
                                'children' => [
                                    $this->iconCircle('clipboard-check', self::BUTTER, '56px', true),
                                    $this->text('Hands-on<br>learning', self::INK, '16px', ['textAlign' => $this->r('center'), 'lineHeight' => $this->r('1.5')]),
                                ],
                            ],
                        ]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(6, [
                            $this->tag('OUR STORY', self::LAVENDER),
                            $this->h2('More than just classes, a brighter future for every child', self::INK),
                            $this->text('We blend hands-on play, caring guidance and real-world projects so every child discovers what they love and grows in confidence.', self::MUTED, '16px'),
                            $points,
                            $this->button('Start your child\'s journey', '/contact', 'berry'),
                        ], ['gap' => $this->r('24px'), 'justifyContent' => $this->r('center')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'alignItems' => $this->r('center'), 'gap' => ['desktop' => '64px', 'tablet' => '24px', 'mobile' => '16px']]),
            ], $this->whiteSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function values(): array
    {
        $card = fn (string $icon, string $bg, string $title, string $body, bool $tall): array => [
            ...$this->column(3, [
                $this->iconCircle($icon, '#ffffff', '72px', true),
                $this->h3($title, self::INK, '20px', 'left', ['margin' => $this->r(['top' => 'auto'])]),
                $this->text($body, self::MUTED, '16px'),
                $this->linkButton_('Schedule a visit', '/contact'),
            ], [
                'backgroundColor' => $this->r($bg),
                'borderRadius' => $this->r('20px'),
                'padding' => $this->r(['top' => '32px', 'right' => '28px', 'bottom' => '32px', 'left' => '28px']),
                'minHeight' => ['desktop' => $tall ? '442px' : '414px', 'mobile' => '0'],
                'gap' => $this->r('14px'),
                'margin' => ['desktop' => ['top' => $tall ? '0' : '28px'], 'mobile' => ['top' => '0']],
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-values', 'Lumikin · Four pastel value cards', 'content', 'Centred highlighted heading over four pastel cards (lavender, peach, mint, sky) with white icon circles, text and a visit link.', [
            $this->section_('values', 'Values', [
                $this->row([
                    $this->column(12, [$this->h2("Creating brighter futures for little ones\nthrough early learning", self::INK, 'center', $this->highlight(self::LAVENDER, '24%', '26px', '78%', '90%'))]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '60px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $card('book-open', self::LAVENDER, 'Confident learning', 'We grow early reading skills through fun, interactive play.', false),
                    $card('target', self::PEACH, 'Academic growth', 'We build logical thinking with guided daily activities.', true),
                    $card('heart', self::MINT, 'Healthy childhood', 'We encourage active indoor games and healthy social habits.', true),
                    $card('star', self::SKY, 'Artistic ideas flourish', 'We inspire creative expression with colour and craft.', false),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('start')]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function learning(): array
    {
        $stat = fn (string $icon, string $bg, string $number, string $label): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px'], [
                'backgroundColor' => $this->r('#ffffff'),
                'borderRadius' => $this->r('15px'),
                'padding' => $this->r(['top' => '28px', 'right' => '22px', 'bottom' => '28px', 'left' => '22px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'children' => [
                $this->iconCircle($icon, $bg, '84px'),
                $this->text($number, self::INK, ['desktop' => '48px', 'mobile' => '38px'], ['fontWeight' => $this->r('500'), 'letterSpacing' => $this->r('-2.4px'), 'lineHeight' => $this->r('1.2'), 'fontFamily' => $this->r(self::HEADING_FONT)]),
                $this->text($label, self::MUTED, '20px', ['fontWeight' => $this->r('500'), 'lineHeight' => $this->r('1.4')]),
            ],
        ];

        $check = fn (string $label): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [1, 11], 'gap' => '10px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
            'children' => [
                $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '12px'], ['color' => $this->r('#ffffff'), 'backgroundColor' => $this->r(self::BERRY), 'borderRadius' => $this->r('50%'), 'padding' => $this->r(['top' => '4px', 'right' => '4px', 'bottom' => '4px', 'left' => '4px']), 'width' => $this->r('fit-content')]),
                $this->text($label, self::MUTED, '15px'),
            ],
        ];

        $stats = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '16px', 'stackBelow' => 'none']),
            'children' => [
                $stat('shield', self::MINT, '100%', 'Safe kids classroom'),
                $stat('heart', self::BUTTER, '240+', 'Happy little learners'),
            ],
        ];

        return $this->design('lumikin-learning', 'Lumikin · Learning experience', 'content', 'Sand band: rounded photo beside heading, text and button; then two stat cards and a white card with photo and check list.', [
            $this->section_('learning', 'Learning experience', [
                $this->row([
                    [...$this->column(6, [$this->image('Girl building with blocks', ['height' => ['desktop' => '520px', 'mobile' => '300px'], 'borderRadius' => $this->r('40px 40px 40px 160px'), 'custom' => ['--fnl-effect' => 'reveal zoom']], $this->photoUrl('learning', 1100))]), 'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12]],
                    [
                        ...$this->column(6, [
                            $this->h2('What makes our early learning experience special', self::INK),
                            $this->text('Quality early education in a safe, warm space, so young children can build bright futures one happy day at a time.', self::MUTED, '16px'),
                            $this->button('View all programs', '/programs', 'berry'),
                        ], ['gap' => $this->r('24px'), 'justifyContent' => $this->r('center')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '48px', 'tablet' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('center'), 'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '24px']]]),
                $this->row([
                    [...$this->column(6, [$stats], ['justifyContent' => $this->r('flex-end')]), 'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12]],
                    [
                        ...$this->column(6, [
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [5, 7], 'gap' => '22px', 'stackBelow' => 'mobile'], [
                                    'backgroundColor' => $this->r('#ffffff'),
                                    'borderRadius' => $this->r('20px'),
                                    'padding' => $this->r(['top' => '22px', 'right' => '22px', 'bottom' => '22px', 'left' => '22px']),
                                    'alignItems' => $this->r('center'),
                                    'custom' => ['--fnl-effect' => 'reveal'],
                                ]),
                                'children' => [
                                    $this->image('Boy studying', ['height' => ['desktop' => '318px', 'mobile' => '240px'], 'borderRadius' => $this->r('15px')], $this->photoUrl('studying', 600)),
                                    [
                                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px']),
                                        'children' => [
                                            $this->h3('Helping little learners learn happily every day', self::INK, '20px'),
                                            $check('Early years programs'),
                                            $check('Play-based learning support'),
                                            $check('Positive growth guidance'),
                                            $check('Whole-child development'),
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('end')]),
            ], $this->sandSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function whyUs(): array
    {
        $progress = fn (string $label, int $value, string $colour): array => $this->blockNode(BlockType::ProgressBar, [
            'value' => $value,
            'max' => 100,
            'label' => $label,
            'showPercentage' => true,
            'source' => 'manual',
        ], [
            'backgroundColor' => $this->r($colour),
            'fontFamily' => $this->r(self::FONT),
            'color' => $this->r(self::INK),
            'fontSize' => $this->r('16px'),
            'fontWeight' => $this->r('500'),
            'custom' => ['--fnl-track-color' => '#ebebeb', '--fnl-track-height' => '7px'],
        ]);

        return $this->design('lumikin-why-us', 'Lumikin · Why choose us with progress', 'social_proof', 'Sand band: label and highlighted heading; a mint trust card with rating and photo, a progress card with three bars, and a tall photo.', [
            $this->section_('why-us', 'Why choose us', [
                $this->row([
                    $this->column(12, [
                        $this->tag('WHY CHOOSE US', self::LAVENDER, true),
                        $this->h2("A trusted learning space, where\nchildren thrive every day", self::INK, 'center', $this->highlight(self::LAVENDER, '30%', '26px', '66%', '90%')),
                    ], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    [
                        ...$this->column(6, [
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '22px', 'stackBelow' => 'mobile'], [
                                    'backgroundColor' => $this->r(self::MINT),
                                    'borderRadius' => $this->r('20px'),
                                    'padding' => $this->r(['top' => '22px', 'right' => '22px', 'bottom' => '22px', 'left' => '22px']),
                                    'custom' => ['--fnl-effect' => 'reveal'],
                                ]),
                                'children' => [
                                    [
                                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], ['justifyContent' => $this->r('space-between')]),
                                        'children' => [
                                            $this->text('96%', self::INK, '48px', ['fontWeight' => $this->r('500'), 'letterSpacing' => $this->r('-2.4px'), 'lineHeight' => $this->r('1.2'), 'fontFamily' => $this->r(self::HEADING_FONT)]),
                                            $this->text('★★★★★', '#f5a524', '16px', ['letterSpacing' => $this->r('2px')]),
                                            $this->h3('Trusted by parents', self::INK, '20px'),
                                            $this->text('We help every little child grow their best skills.', self::MUTED, '16px'),
                                            [
                                                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px'], ['backgroundColor' => $this->r('#ffffff'), 'borderRadius' => $this->r('20px'), 'padding' => $this->r(['top' => '20px', 'right' => '22px', 'bottom' => '20px', 'left' => '22px'])]),
                                                'children' => [
                                                    $this->image('Happy children', ['width' => $this->r('136px'), 'height' => $this->r('56px'), 'borderRadius' => $this->r('40px')], $this->photoUrl('grass', 300, 120)),
                                                    $this->text('Nurturing every child with care and guidance.', self::MUTED, '15px'),
                                                ],
                                            ],
                                        ],
                                    ],
                                    $this->image('Boy smiling', ['height' => ['desktop' => '365px', 'mobile' => '260px'], 'borderRadius' => $this->r('20px')], $this->photoUrl('toys', 600)),
                                ],
                            ],
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px'], [
                                    'backgroundColor' => $this->r('#ffffff'),
                                    'borderRadius' => $this->r('20px'),
                                    'padding' => $this->r(['top' => '22px', 'right' => '22px', 'bottom' => '26px', 'left' => '22px']),
                                    'custom' => ['--fnl-effect' => 'reveal'],
                                ]),
                                'children' => [
                                    $this->text('<b>Personal progress</b><br>See how little learners grow', self::INK, '16px'),
                                    $progress('Skills improved', 75, '#00a437'),
                                    $progress('Parent satisfaction', 96, '#197eff'),
                                    $progress('Growth achieved', 87, '#eb5012'),
                                ],
                            ],
                        ], ['gap' => $this->r('24px')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(6, [$this->image('Teacher with a group of children', ['height' => ['desktop' => '781px', 'mobile' => '320px'], 'borderRadius' => $this->r('20px'), 'custom' => ['--fnl-effect' => 'reveal zoom']], $this->photoUrl('circle', 1000))]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], [...$this->sandSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function benefits(): array
    {
        $item = fn (string $icon, string $bg, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [2, 10], 'gap' => '20px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
            'children' => [
                $this->iconCircle($icon, $bg, '91px'),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '6px']),
                    'children' => [$this->h3($title, self::INK, '20px'), $this->text($body, self::MUTED, '16px')],
                ],
            ],
        ];

        return $this->design('lumikin-benefits', 'Lumikin · Benefits with organic photo', 'content', 'Organic-shaped photo on a soft peach shape beside a highlighted heading and three icon benefits.', [
            $this->section_('benefits', 'Benefits', [
                $this->row([
                    [
                        ...$this->column(7, [$this->image('Children reading together', [
                            'height' => ['desktop' => '560px', 'mobile' => '320px'],
                            'borderRadius' => $this->r('46% 54% 40% 60% / 55% 45% 55% 45%'),
                            'boxShadow' => $this->r('-40px 30px 0 0 '.self::PEACH),
                            'custom' => ['--fnl-effect' => 'reveal'],
                        ], $this->photoUrl('reading', 1200))]),
                        'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(5, [
                            $this->h2('A place where learning is joyful and meaningful', self::INK, 'left', $this->highlight(self::BUTTER, '58%', '24px', '30%', '50%')),
                            $item('book-open', self::SKY, 'Confidence through learning', 'Children build strong confidence through joyful daily learning.'),
                            $item('heart', self::PEACH, 'Healthy active childhood', 'Active play keeps children healthy, happy and full of energy.'),
                            $item('home', self::BUTTER, 'Creative learning spaces', 'Inspiring rooms where young minds explore and discover.'),
                        ], ['gap' => $this->r('28px'), 'justifyContent' => $this->r('center')]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '64px', 'tablet' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('center')]),
            ], $this->whiteSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function activities(): array
    {
        $pill = fn (string $label, string $bg): array => $this->text($label, '#232c24', '14px', [
            'backgroundColor' => $this->r($bg),
            'borderRadius' => $this->r('100px'),
            'padding' => $this->r(['top' => '9px', 'right' => '15px', 'bottom' => '9px', 'left' => '15px']),
            'fontWeight' => $this->r('600'),
            'letterSpacing' => $this->r('0.05em'),
            'width' => $this->r('fit-content'),
            'maxWidth' => $this->r('none'),
            'lineHeight' => $this->r('1.4'),
        ]);

        $card = fn (string $number, string $title, string $body, string $photo, string $bg, string $quote, string $name, string $avatar) => $this->row([
            [
                // The white text card, then this card's own full-width button on the coloured band.
                ...$this->column(6, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px'], [
                            'backgroundColor' => $this->r('#ffffff'),
                            'borderRadius' => $this->r('20px'),
                            'padding' => ['desktop' => ['top' => '40px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'], 'mobile' => ['top' => '24px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px']],
                            'minHeight' => ['desktop' => '520px', 'mobile' => '0'],
                        ]),
                        'children' => [
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [10, 2], 'gap' => '12px', 'stackBelow' => 'none']),
                                'children' => [
                                    $this->h3($title, self::INK, ['desktop' => '32px', 'mobile' => '24px'], 'left', ['letterSpacing' => $this->r('-2px')]),
                                    $this->h3($number, self::INK, ['desktop' => '32px', 'mobile' => '24px'], 'right', ['letterSpacing' => $this->r('-2px')]),
                                ],
                            ],
                            $this->text($body, self::MUTED, '16px'),
                            $this->h3('What we offer', self::INK, '18px', 'left', ['letterSpacing' => $this->r('-1px'), 'margin' => $this->r(['top' => 'auto'])]),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '10px']),
                                'children' => [
                                    $pill('Body balance', self::PEACH), $pill('Healthy fun', self::MINT), $pill('Kids dance', self::PEACH),
                                    $pill('Happiness', self::SKY), $pill('Calm mind', self::MINT), $pill('Wellness', self::CYAN), $pill('Happy motion', self::BUTTER),
                                ],
                            ],
                        ],
                    ],
                    $this->button('View all programs', '/programs', 'berry', ['textAlign' => $this->r('center')], ['fullWidth' => true]),
                ], ['gap' => ['desktop' => '20px', 'mobile' => '12px']]),
                'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
            ],
            [
                ...$this->column(6, [
                    [
                        ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                            'backgroundColor' => $this->r('rgba(110, 108, 108, 0.6)'),
                            'border' => $this->r(['style' => 'solid', 'color' => 'rgba(255,255,255,0.8)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                            'borderRadius' => $this->r('20px'),
                            'padding' => $this->r(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px']),
                            'maxWidth' => $this->r('407px'),
                            'margin' => ['desktop' => ['left' => 'auto', 'top' => 'auto'], 'mobile' => ['top' => '180px']],
                        ]),
                        'children' => [
                            $this->text('★★★★★', '#f5a524', '15px', ['letterSpacing' => $this->r('2px')]),
                            $this->text($quote, '#ffffff', '16px'),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [2, 10], 'gap' => '12px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
                                'children' => [
                                    $this->image($name, ['width' => $this->r('48px'), 'height' => $this->r('48px'), 'borderRadius' => $this->r('50%')], $this->photoUrl($avatar, 120, 120)),
                                    $this->text("<b>{$name}</b><br>Parent", '#ffffff', '15px', ['lineHeight' => $this->r('1.5')]),
                                ],
                            ],
                        ],
                    ],
                ], [
                    'backgroundColor' => $this->r('#d9d4cc'),
                    'backgroundImage' => $this->r($this->photo($photo, 1200)),
                    'backgroundSize' => $this->r('cover'),
                    'backgroundPosition' => $this->r('center'),
                    'borderRadius' => $this->r('20px'),
                    'padding' => $this->r(['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px']),
                    'minHeight' => ['desktop' => '520px', 'mobile' => '360px'],
                    'justifyContent' => $this->r('flex-end'),
                ]),
                'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
            ],
        ], [
            'maxWidth' => $this->r(self::WIDTH),
            'backgroundColor' => $this->r($bg),
            'borderRadius' => $this->r('20px'),
            'padding' => ['desktop' => ['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'], 'mobile' => ['top' => '12px', 'right' => '12px', 'bottom' => '12px', 'left' => '12px']],
            'gap' => ['desktop' => '24px', 'mobile' => '12px'],
            'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '20px']],
        ]);

        return $this->design('lumikin-activities', 'Lumikin · Stacking activity cards', 'content', 'Highlighted heading over three coloured activity cards that stack as you scroll, each with a number, text, "What we offer" pills, its own full-width button and a photo with a parent review.', [
            $this->section_('activities', 'Activities', [
                $this->row([
                    $this->column(12, [
                        $this->tag('OUR ACTIVITIES', self::PEACH, true),
                        $this->h2("At Lumikin, every day sparks\nplayful learning adventures", self::INK, 'center', $this->highlight(self::PEACH, '30%', '26px', '62%', '90%')),
                    ], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $card('01', 'Mini yoga & dance', 'A playful mix of gentle stretching, rhythm and dance that helps children feel strong and free every day.', 'yoga', self::BUTTER, 'Yoga mornings gave Leo calm, focus and the biggest smiles.', 'Hannah Moore', 'p1'),
                $card('02', 'Kids art & craft time', 'A creative blend of colour, shape and imagination that builds focus and confidence through fun.', 'art', self::LAVENDER, 'Art time helped Mia stay focused, creative and proud of her work.', 'Sarah Lee', 'p3'),
                $card('03', 'Garden explorers', 'A joyful mix of running, planting and outdoor games that builds energy and confidence in the fresh air.', 'garden', self::MINT, 'Outdoor days made Noah more active, confident and curious.', 'Max Turner', 'p2'),
            ], $this->whiteSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function journey(): array
    {
        $step = fn (string $number, string $numberBg, string $photo, string $title, string $body): array => [
            ...$this->column(3, [
                $this->image($title, ['height' => ['desktop' => '405px', 'mobile' => '300px'], 'borderRadius' => $this->r('20px 20px 0 0')], $this->photoUrl($photo, 700)),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                        'backgroundColor' => $this->r('#ffffff'),
                        'borderRadius' => $this->r('20px'),
                        'boxShadow' => $this->r('0 4px 64px 0 rgba(56, 56, 56, 0.15)'),
                        'padding' => $this->r(['top' => '44px', 'right' => '28px', 'bottom' => '36px', 'left' => '28px']),
                        'margin' => $this->r(['top' => '-120px', 'right' => '12px', 'left' => '12px']),
                        'alignItems' => $this->r('center'),
                        'textAlign' => $this->r('center'),
                    ]),
                    'children' => [
                        $this->text($number, self::INK, '16px', ['backgroundColor' => $this->r($numberBg), 'borderRadius' => $this->r('50%'), 'width' => $this->r('52px'), 'height' => $this->r('52px'), 'lineHeight' => $this->r('52px'), 'textAlign' => $this->r('center'), 'margin' => $this->r(['top' => '-70px', 'left' => 'auto', 'right' => 'auto']), 'maxWidth' => $this->r('none')]),
                        $this->h3($title, self::INK, '18px', 'center', ['letterSpacing' => $this->r('-1px')]),
                        $this->text($body, self::MUTED, '16px', ['textAlign' => $this->r('center')]),
                    ],
                ],
            ], ['gap' => $this->r('0'), 'custom' => ['--fnl-effect' => 'reveal lift']]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-journey', 'Lumikin · Enrolment journey', 'content', 'Heading and intro side by side over four photo cards with overlapping white cards and coloured step numbers.', [
            $this->section_('journey', 'Journey', [
                $this->row([
                    [...$this->column(7, [$this->h2('From a parent\'s arms to a joyful classroom journey', self::INK, 'left', $this->highlight(self::LAVENDER, '40%', '26px', '62%', '20%'))]), 'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12]],
                    [...$this->column(5, [$this->text('Visit our friendly campus, explore the play spaces, meet our teachers and enrol your child today.', self::MUTED, '16px')], ['justifyContent' => $this->r('center')]), 'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12]],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '40px', 'tablet' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $step('01', self::MINT, 'mother', 'Visit & explore', 'Parents and children tour our campus and meet our teachers.'),
                    $step('02', self::LAVENDER, 'teacher', 'Personal connections', 'Families meet our teachers and build lasting trust.'),
                    $step('03', self::PEACH, 'cheer', 'Happy first days', 'Children settle in warmly and make new friends.'),
                    $step('04', self::SKY, 'toddler', 'Grow with confidence', 'Kids build skills and confidence with caring support.'),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function testimonials(): array
    {
        $slide = fn (string $photo, string $quote, string $name, string $child): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [5, 7], 'gap' => '24px', 'stackBelow' => 'mobile']),
            'children' => [
                $this->image($name.' with her child', ['height' => ['desktop' => '551px', 'mobile' => '300px'], 'borderRadius' => $this->r('20px')], $this->photoUrl($photo, 800)),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '16px'], [
                        'backgroundColor' => $this->r('rgba(216, 227, 241, 0.8)'),
                        'borderRadius' => $this->r('20px'),
                        'padding' => ['desktop' => ['top' => '48px', 'right' => '84px', 'bottom' => '48px', 'left' => '80px'], 'mobile' => ['top' => '28px', 'right' => '22px', 'bottom' => '80px', 'left' => '22px']],
                        'minHeight' => ['desktop' => '551px', 'mobile' => '0'],
                    ]),
                    'children' => [
                        $this->text('“', '#8fa9d6', '96px', ['lineHeight' => $this->r('0.8'), 'fontFamily' => $this->r('Georgia, serif'), 'height' => $this->r('56px')]),
                        $this->text('4.9 &nbsp;★★★★★', self::MUTED, '16px'),
                        $this->text($quote, self::INK, ['desktop' => '20px', 'mobile' => '17px'], ['lineHeight' => $this->r('1.6'), 'fontWeight' => $this->r('500')]),
                        $this->text("<b>{$name}</b><br>Parent of {$child}", self::MUTED, '16px', ['margin' => $this->r(['top' => 'auto'])]),
                    ],
                ],
            ],
        ];

        $carousel = [
            ...$this->blockNode(BlockType::Carousel, ['autoplay' => false, 'loop' => true, 'showArrows' => true, 'showDots' => false, 'slidesPerView' => 1]),
            'children' => [
                $slide('parent', 'Lumikin has been a wonderful beginning for our daughter. Every activity grows her curiosity, creativity and kindness, and she has made friendships that fill every day with laughter.', 'Emma Clarke', 'Sophie'),
                $slide('mother', 'Our son loves every single morning here. The teachers notice the little things, celebrate every step and keep us close to his progress all year.', 'Daniel Brooks', 'Henry'),
                $slide('laugh', 'We were nervous about the first day, but the team made it gentle and happy. Ava now talks about her teachers and friends all the way home.', 'Maria Torres', 'Ava'),
            ],
        ];

        return $this->design('lumikin-testimonials', 'Lumikin · Parent reviews slider', 'social_proof', 'Butter label and heading over a slider: rounded photo beside a soft blue review card with rating, quote, name and arrows.', [
            $this->section_('testimonials', 'Parent reviews', [
                $this->row([
                    $this->column(12, [
                        $this->tag('PARENT VOICES', self::BUTTER),
                        $this->h2("See why parents trust us to\nnurture happy learners", self::INK),
                    ], ['gap' => $this->r('18px')]),
                ], ['maxWidth' => $this->r(self::WIDTH), 'margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '28px']]]),
                $this->row([$this->column(12, [$carousel])], ['maxWidth' => $this->r(self::WIDTH)]),
            ], $this->whiteSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function blog(int $count = 3, ?string $key = null): array
    {
        $posts = [
            ['blocks', 'March 2', 'Exploring creativity through play and discovery'],
            ['clay', 'March 6', 'Creative ideas for everyday learning moments'],
            ['field', 'March 9', 'Active play that keeps children happy and healthy'],
            ['reading', 'March 12', 'Circle time: how storytelling builds a classroom'],
            ['studying', 'March 16', 'Getting school-ready: a simple study space guide'],
            ['party', 'March 20', 'Sharing and taking turns: first social skills'],
        ];

        $card = fn (array $post): array => [
            ...$this->column(4, [
                $this->image($post[2], ['height' => ['desktop' => '300px', 'mobile' => '240px'], 'borderRadius' => $this->r('20px')], $this->photoUrl($post[0], 800)),
                $this->text('📅 '.$post[1].', '.date('Y'), self::MUTED, '14px'),
                $this->h3('<a href="/stories">'.$post[2].'</a>', self::INK, '20px'),
            ], ['gap' => $this->r('14px'), 'custom' => ['--fnl-effect' => 'reveal zoom']]),
            'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
        ];

        $rows = [];
        foreach (array_chunk(array_slice($posts, 0, $count), 3) as $chunk) {
            $rows[] = $this->row(array_map($card, $chunk), ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '32px']]]);
        }

        $heading = $this->row([
            $this->column(12, [
                $this->tag('LATEST STORIES', self::BUTTER, true),
                $this->h2("Read helpful stories and tips\nabout childhood learning", self::INK, 'center'),
            ], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')]),
        ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]);

        return $this->design($key ?? 'lumikin-blog', $count > 3 ? 'Lumikin · Story grid' : 'Lumikin · Latest stories', 'content', $count > 3 ? 'Grid of six story cards with photo, date and title.' : 'Label and centred heading over three story cards with photo, date and title.', [
            $this->section_($count > 3 ? 'blog-grid' : 'blog', 'Stories', $count > 3 ? $rows : [$heading, ...$rows], $this->whiteSection()),
        ]);
    }

    /* ----------------------------------------------------------------- about */

    /** @return array<string, mixed> */
    private function aboutHero(): array
    {
        $photo = fn (string $photo, string $height): array => [
            ...$this->column(3, [$this->image('Children at Lumikin', ['height' => ['desktop' => $height, 'mobile' => '260px'], 'borderRadius' => $this->r('20px')], $this->photoUrl($photo, 600))], ['justifyContent' => $this->r('flex-end'), 'custom' => ['--fnl-effect' => 'reveal']]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        $quote = [
            ...$this->column(3, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px'], [
                        'backgroundColor' => $this->r('#d9d4cc'),
                        'backgroundImage' => $this->r($this->photo('toys', 700)),
                        'backgroundSize' => $this->r('cover'),
                        'overlayColor' => $this->r('rgba(21, 21, 25, 0.45)'),
                        'borderRadius' => $this->r('20px'),
                        'padding' => $this->r(['top' => '24px', 'right' => '22px', 'bottom' => '24px', 'left' => '22px']),
                        'minHeight' => ['desktop' => '320px', 'mobile' => '260px'],
                        'justifyContent' => $this->r('flex-end'),
                    ]),
                    'children' => [
                        $this->text('★★★★★', '#f5a524', '15px'),
                        $this->text('Teachers create a warm atmosphere that inspires creativity, curiosity and learning.', '#ffffff', '16px'),
                        $this->text('<b>Richard Hale</b><br>Parent of Lucas', '#ffffff', '15px', ['lineHeight' => $this->r('1.5')]),
                    ],
                ],
            ], ['justifyContent' => $this->r('flex-end')]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-about-hero', 'Lumikin · About hero with photo cards', 'hero', 'Butter label, a centred highlighted headline, text and buttons over a row of four photo and review cards.', [
            $this->section_('about-hero', 'About hero', [
                $this->row([
                    $this->column(12, [
                        $this->tag('WELCOME TO LUMIKIN', self::BUTTER, true),
                        $this->h1("Helping young minds bloom\nwith confidence", self::INK, 'center', $this->highlight(self::LAVENDER, '30%', '30px', '78%', '42%')),
                        $this->text('A joyful kindergarten where learning feels like play and every child grows with confidence, creativity and care.', self::MUTED, '16px', ['textAlign' => $this->r('center'), 'maxWidth' => $this->r('560px'), 'margin' => $this->r(['left' => 'auto', 'right' => 'auto'])]),
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['justifyContent' => $this->r('center')]),
                            'children' => [$this->button('Enrol your child', '/contact', 'berry'), $this->button('Explore programs', '/programs', 'outline')],
                        ],
                    ], ['alignItems' => $this->r('center'), 'gap' => $this->r('24px')]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '64px'], 'mobile' => ['bottom' => '36px']], 'custom' => ['--fnl-effect' => 'blur-in']]),
                $this->row([$photo('smile', '300px'), $quote, $photo('room', '360px'), $photo('grass', '300px')], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('end')]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '100px', 'right' => '40px', 'bottom' => '100px', 'left' => '40px'], 'mobile' => ['top' => '56px', 'right' => '16px', 'bottom' => '56px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function aboutNumbers(): array
    {
        $number = fn (string $value, string $label, string $bg): array => [
            ...$this->column(4, [
                $this->text($value, self::INK, ['desktop' => '48px', 'mobile' => '36px'], ['fontWeight' => $this->r('500'), 'letterSpacing' => $this->r('-2.4px'), 'lineHeight' => $this->r('1.2'), 'fontFamily' => $this->r(self::HEADING_FONT)]),
                $this->text($label, self::MUTED, '16px'),
            ], ['backgroundColor' => $this->r($bg), 'borderRadius' => $this->r('20px'), 'padding' => $this->r(['top' => '28px', 'right' => '24px', 'bottom' => '28px', 'left' => '24px']), 'custom' => ['--fnl-effect' => 'reveal lift']]),
            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 12],
        ];

        $reason = fn (string $icon, string $bg, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [2, 10], 'gap' => '18px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
            'children' => [$this->iconCircle($icon, $bg, '72px'), [...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '4px']), 'children' => [$this->h3($title, self::INK, '20px'), $this->text($body, self::MUTED, '16px')]]],
        ];

        return $this->design('lumikin-about-numbers', 'Lumikin · Numbers and reasons', 'content', 'Three pastel number cards, then a heading with four icon reasons beside a rounded photo.', [
            $this->section_('about-numbers', 'Numbers', [
                $this->row([
                    $number('15+', 'Nurturing years', self::LAVENDER),
                    $number('540+', 'Happy learners', self::PEACH),
                    $number('100%', 'Certified teachers', self::MINT),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '100px'], 'mobile' => ['bottom' => '56px']]]),
                $this->row([
                    [
                        ...$this->column(6, [
                            $this->tag('WHY CHOOSE US', self::LAVENDER),
                            $this->h2('Little minds explore, learn, create and grow here', self::INK),
                            $reason('book-open', self::SKY, 'Confidence through learning', 'Strong confidence grows through joyful daily learning.'),
                            $reason('heart', self::PEACH, 'Healthy active childhood', 'Active play keeps children healthy, happy and energised.'),
                            $reason('home', self::BUTTER, 'Creative learning spaces', 'Inspiring rooms where young minds explore and discover.'),
                            $reason('star', self::MINT, 'Artistic ideas flourish', 'Creative ideas bloom through hands-on art experiences.'),
                        ], ['gap' => $this->r('24px')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [...$this->column(6, [$this->image('Children learning together', ['height' => ['desktop' => '640px', 'mobile' => '320px'], 'borderRadius' => $this->r('20px 160px 20px 20px'), 'custom' => ['--fnl-effect' => 'reveal zoom']], $this->photoUrl('blocks', 1000))]), 'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12]],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '64px', 'tablet' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('center')]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function team(): array
    {
        $member = fn (string $photo, string $name, string $role, string $bg): array => [
            ...$this->column(3, [
                $this->image($name, ['height' => ['desktop' => '360px', 'mobile' => '320px'], 'borderRadius' => $this->r('20px'), 'backgroundColor' => $this->r($bg)], $this->photoUrl($photo, 600, 720)),
                $this->h3($name, self::INK, '20px', 'center'),
                $this->text($role, self::MUTED, '16px', ['textAlign' => $this->r('center')]),
            ], ['gap' => $this->r('10px'), 'custom' => ['--fnl-effect' => 'reveal zoom']]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-team', 'Lumikin · Meet the teachers', 'content', 'Label, centred heading and text over four teacher portraits with names and roles.', [
            $this->section_('team', 'Teachers', [
                $this->row([
                    $this->column(12, [
                        $this->tag('OUR EDUCATORS', self::MINT, true),
                        $this->h2("Meet the hearts behind every\nhappy classroom", self::INK, 'center'),
                        $this->text('Our passionate teachers create safe, joyful moments where every child feels seen and supported.', self::MUTED, '16px', ['textAlign' => $this->r('center'), 'maxWidth' => $this->r('560px'), 'margin' => $this->r(['left' => 'auto', 'right' => 'auto'])]),
                    ], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $member('p7', 'Priya Sharma', 'Early years specialist', self::LAVENDER),
                    $member('p1', 'Olivia Grant', 'Head of school', self::PEACH),
                    $member('p2', 'Daniel Okafor', 'Assistant teacher', self::MINT),
                    $member('p5', 'Lena Fischer', 'Founder & program lead', self::SKY),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function environment(): array
    {
        return $this->design('lumikin-environment', 'Lumikin · Joyful environment', 'content', 'Sand band: heading, text and button beside two rounded photos with captions.', [
            $this->section_('environment', 'Environment', [
                $this->row([
                    [
                        ...$this->column(5, [
                            $this->h2('A joyful environment where kids learn confidently', self::INK),
                            $this->text('A nurturing space where children explore, create and learn every day, with caring teachers by their side.', self::MUTED, '16px'),
                            $this->button('Contact us', '/contact', 'berry'),
                        ], ['gap' => $this->r('24px'), 'justifyContent' => $this->r('center')]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [...$this->column(4, [$this->image('Classroom', ['height' => ['desktop' => '460px', 'mobile' => '280px'], 'borderRadius' => $this->r('20px')], $this->photoUrl('mural', 800)), $this->h3('Building strong foundations for lifelong learning', self::INK, '20px')], ['gap' => $this->r('16px'), 'custom' => ['--fnl-effect' => 'reveal']]), 'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12]],
                    [...$this->column(3, [$this->image('Child playing', ['height' => ['desktop' => '360px', 'mobile' => '280px'], 'borderRadius' => $this->r('20px')], $this->photoUrl('toddler', 700)), $this->h3('Where little learners grow with confidence', self::INK, '20px')], ['gap' => $this->r('16px'), 'justifyContent' => $this->r('flex-end'), 'custom' => ['--fnl-effect' => 'reveal']]), 'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12]],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '32px', 'mobile' => '16px'], 'alignItems' => $this->r('end')]),
            ], $this->sandSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function timeline(): array
    {
        $entry = fn (string $year, string $title, string $body, string $bg): array => [
            ...$this->column(3, [
                $this->text($year, self::INK, '16px', ['backgroundColor' => $this->r($bg), 'borderRadius' => $this->r('100px'), 'padding' => $this->r(['top' => '8px', 'right' => '18px', 'bottom' => '8px', 'left' => '18px']), 'width' => $this->r('fit-content'), 'fontWeight' => $this->r('600'), 'maxWidth' => $this->r('none')]),
                $this->h3($title, self::INK, '20px'),
                $this->text($body, self::MUTED, '16px'),
            ], [
                'gap' => $this->r('12px'),
                'border' => $this->r(['style' => 'solid', 'color' => 'rgba(0,0,0,0.12)', 'top' => '2px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                'padding' => $this->r(['top' => '24px']),
                'custom' => ['--fnl-effect' => 'reveal'],
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-timeline', 'Lumikin · Timeline', 'content', 'Sand band: label and heading over four year cards along a line.', [
            $this->section_('timeline', 'Timeline', [
                $this->row([
                    $this->column(12, [$this->tag('TIMELINE', self::BUTTER, true), $this->h2('A short journey through our story', self::INK, 'center')], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')]),
                ], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $entry('2012', 'The dream begins', 'Lumikin opened with one bright room and a big idea: learning that feels like play.', self::LAVENDER),
                    $entry('2016', 'Growing together', 'More families joined us, and we added our garden and art studio.', self::PEACH),
                    $entry('2021', 'A new chapter', 'New programs for toddlers and pre-schoolers, led by our growing team.', self::MINT),
                    $entry('2025', 'Today and beyond', 'Every day we keep making early learning safe, joyful and inspiring.', self::SKY),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], [...$this->sandSection(), 'padding' => ['desktop' => ['top' => '0', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '0', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /* -------------------------------------------------------- inner pages */

    /**
     * The inner-page hero: a full-width photo fading to white on the left, with a white
     * label, a large headline, text and a button.
     *
     * @return array<string, mixed>
     */
    private function pageHero(string $key, string $name, string $block, string $label, string $title, string $intro, string $photo, string $cta, string $url): array
    {
        return $this->design($key, 'Lumikin · '.$name, 'hero', 'Full-width photo fading to white on the left, a white label, a large headline, text and a berry button.', [
            $this->section_($block, $name, [
                $this->row([
                    [
                        ...$this->column(6, [
                            $this->tag(strtoupper($label), '#ffffff'),
                            $this->h1($title, self::INK, 'left', ['fontSize' => ['desktop' => '64px', 'tablet' => '52px', 'mobile' => '40px']]),
                            $this->text($intro, self::MUTED, '17px', ['maxWidth' => $this->r('480px')]),
                            $this->button($cta, $url, 'berry'),
                        ], ['gap' => $this->r('22px'), 'custom' => ['--fnl-effect' => 'blur-in']]),
                        'span' => ['desktop' => 6, 'tablet' => 9, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'minHeight' => ['desktop' => '560px', 'mobile' => '440px'], 'alignItems' => $this->r('center')]),
            ], [
                'backgroundColor' => $this->r('#f4efe9'),
                'backgroundImage' => $this->r($this->photo($photo, 2000)),
                'backgroundSize' => $this->r('cover'),
                'backgroundPosition' => $this->r('center right'),
                'padding' => ['desktop' => ['top' => '40px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'], 'mobile' => ['top' => '32px', 'right' => '16px', 'bottom' => '32px', 'left' => '16px']],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function mission(): array
    {
        $item = fn (string $icon, string $bg, string $label, string $title): array => [
            ...$this->column(3, [$this->iconCircle($icon, $bg, '72px'), $this->text($label, self::MUTED, '15px'), $this->h3($title, self::INK, '20px')], [
                'backgroundColor' => $this->r('#ffffff'), 'borderRadius' => $this->r('20px'), 'padding' => $this->r(['top' => '28px', 'right' => '24px', 'bottom' => '28px', 'left' => '24px']), 'gap' => $this->r('10px'),
                'border' => $this->r(['style' => 'solid', 'color' => 'rgba(0,0,0,0.08)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-mission', 'Lumikin · Mission highlights', 'content', 'Label and heading over four bordered cards with icon circles.', [
            $this->section_('mission', 'Mission', [
                $this->row([$this->column(12, [$this->tag('OUR MISSION', self::LAVENDER, true), $this->h2("From caring basics to bright beginnings,\nwe help young minds grow", self::INK, 'center')], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')])], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $item('star', self::BUTTER, 'Creative learning', 'Personal growth'),
                    $item('target', self::PEACH, 'Child development', 'Growth through discovery'),
                    $item('compass', self::MINT, 'Curious minds', 'A caring learning space'),
                    $item('heart', self::SKY, 'Caring support', 'Every child, every day'),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], $this->whiteSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function programsGrid(): array
    {
        $program = fn (string $number, string $photo, string $title, string $body, string $bg): array => [
            ...$this->column(4, [
                $this->image($title, ['height' => ['desktop' => '280px', 'mobile' => '240px'], 'borderRadius' => $this->r('16px')], $this->photoUrl($photo, 800)),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [10, 2], 'gap' => '8px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
                    'children' => [$this->h3($title, self::INK, '22px'), $this->h3($number, self::INK, '22px', 'right')],
                ],
                $this->text($body, self::MUTED, '16px'),
            ], ['backgroundColor' => $this->r($bg), 'borderRadius' => $this->r('20px'), 'padding' => $this->r(['top' => '14px', 'right' => '14px', 'bottom' => '26px', 'left' => '14px']), 'gap' => $this->r('14px'), 'custom' => ['--fnl-effect' => 'reveal lift']]),
            'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
        ];
        $rowStyle = ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '16px']]];

        return $this->design('lumikin-programs-grid', 'Lumikin · Six programs', 'content', 'Sand band: label and heading over six pastel program cards with photo, title, number and text.', [
            $this->section_('programs-grid', 'Programs', [
                $this->row([$this->column(12, [$this->tag('OUR PROGRAMS', self::BUTTER, true), $this->h2("Every program inspires curious,\ncreative and confident children", self::INK, 'center')], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')])], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $program('01', 'yoga', 'Music & movement', 'Songs, rhythm and dance that build coordination and joy.', self::LAVENDER),
                    $program('02', 'art', 'Creative hands', 'Painting, clay and craft for focus and imagination.', self::PEACH),
                    $program('03', 'party', 'Group activities', 'Team games that grow sharing, kindness and friendship.', self::MINT),
                ], $rowStyle),
                $this->row([
                    $program('04', 'garden', 'Garden explorers', 'Planting, digging and outdoor discovery every week.', self::SKY),
                    $program('05', 'reading', 'Joyful reading time', 'Stories and picture books that spark a love of words.', self::BUTTER),
                    $program('06', 'clay', 'Little makers', 'Building, sorting and simple science for curious minds.', self::CYAN),
                ], [...$rowStyle, 'margin' => $this->r(['bottom' => '0'])]),
            ], $this->sandSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function feePlans(): array
    {
        $plan = fn (string $name, string $ages, string $price, array $features, string $bg, bool $featured = false): array => [
            ...$this->column(4, [
                $this->h3($name, self::INK, '24px'),
                $this->text($ages, self::MUTED, '15px'),
                $this->text($price, self::INK, ['desktop' => '48px', 'mobile' => '40px'], ['fontWeight' => $this->r('500'), 'letterSpacing' => $this->r('-2.4px'), 'lineHeight' => $this->r('1.1'), 'fontFamily' => $this->r(self::HEADING_FONT), 'margin' => $this->r(['top' => '8px'])]),
                $this->text('per month', self::MUTED, '15px', ['margin' => $this->r(['top' => '-10px'])]),
                $this->h3('What is included:', self::INK, '18px', 'left', ['letterSpacing' => $this->r('-1px')]),
                ...array_map(fn (string $feature): array => [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [1, 11], 'gap' => '10px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '12px'], ['color' => $this->r('#ffffff'), 'backgroundColor' => $this->r(self::BERRY), 'borderRadius' => $this->r('50%'), 'padding' => $this->r(['top' => '4px', 'right' => '4px', 'bottom' => '4px', 'left' => '4px']), 'width' => $this->r('fit-content')]),
                        $this->text($feature, self::MUTED, '15px'),
                    ],
                ], $features),
                $this->button('Enquire now', '/contact', $featured ? 'berry' : 'outline', ['margin' => $this->r(['top' => 'auto'])]),
            ], [
                'backgroundColor' => $this->r($bg),
                'borderRadius' => $this->r('20px'),
                'padding' => $this->r(['top' => '36px', 'right' => '32px', 'bottom' => '36px', 'left' => '32px']),
                'gap' => $this->r('14px'),
                'border' => $this->r(['style' => 'solid', 'color' => $featured ? self::BERRY : 'transparent', 'top' => '2px', 'right' => '2px', 'bottom' => '2px', 'left' => '2px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 4, 'tablet' => 12, 'mobile' => 12],
        ];

        $section = $this->section_('fee-plans', 'Fee plans', [
            $this->row([$this->column(12, [$this->tag('OUR FEE PLANS', self::LAVENDER, true), $this->h2('Choose the right learning plan', self::INK, 'center')], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')])], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
            $this->row([
                $plan('Playgroup', '1.5 – 2.5 years', '$180', ['Play-based learning', 'Early social skills', 'Indoor & outdoor play', 'Parent updates'], self::LAVENDER),
                $plan('Nursery', '2.5 – 3.5 years', '$220', ['Everything in Playgroup', 'Early reading & numbers', 'Art & music sessions', 'Healthy snacks daily'], self::PEACH, true),
                $plan('Pre-K', '3.5 – 5 years', '$260', ['Everything in Nursery', 'School-ready skills', 'Garden & science time', 'Termly progress reports'], self::MINT),
            ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
        ], $this->whiteSection());
        $section['settings']['anchor'] = 'plans';

        return $this->design('lumikin-fee-plans', 'Lumikin · Fee plans', 'commerce', 'Label and heading over three pastel plan cards with age range, monthly price, check list and button; the middle one highlighted.', [$section]);
    }

    /** @return array<string, mixed> */
    private function included(): array
    {
        $item = fn (string $icon, string $bg, string $label): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [2, 10], 'gap' => '14px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center')]),
            'children' => [$this->iconCircle($icon, $bg, '56px'), $this->h3($label, self::INK, '18px', 'left', ['letterSpacing' => $this->r('-1px')])],
        ];

        return $this->design('lumikin-included', 'Lumikin · Everything included', 'content', 'Sand band: rounded photo beside a heading, text, six included items and a button.', [
            $this->section_('included', 'Included', [
                $this->row([
                    [...$this->column(6, [$this->image('Child playing with toys', ['height' => ['desktop' => '560px', 'mobile' => '300px'], 'borderRadius' => $this->r('160px 20px 20px 20px'), 'custom' => ['--fnl-effect' => 'reveal zoom']], $this->photoUrl('toys', 1000))]), 'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12]],
                    [
                        ...$this->column(6, [
                            $this->h2('A complete learning experience for your child', self::INK),
                            $this->text('More than a classroom: a safe, engaging and caring place where children learn, play and grow every day.', self::MUTED, '16px'),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [6, 6], 'gap' => '18px', 'stackBelow' => 'mobile']),
                                'children' => [
                                    [...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px']), 'children' => [$item('shield', self::MINT, 'Safe, child-friendly campus'), $item('users', self::PEACH, 'Caring teachers'), $item('mail', self::SKY, 'Regular parent updates')]],
                                    [...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '18px']), 'children' => [$item('star', self::BUTTER, 'Art, music & movement'), $item('heart', self::LAVENDER, 'Healthy meals & snacks'), $item('compass', self::CYAN, 'Play-based activities')]],
                                ],
                            ],
                            $this->button('Enquire about admission', '/contact', 'berry'),
                        ], ['gap' => $this->r('24px'), 'justifyContent' => $this->r('center')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '64px', 'tablet' => '24px', 'mobile' => '16px'], 'alignItems' => $this->r('center')]),
            ], $this->sandSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function faq(): array
    {
        $advisor = fn (string $photo, string $name, string $role): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [3, 9], 'gap' => '14px', 'stackBelow' => 'none'], ['alignItems' => $this->r('center'), 'backgroundColor' => $this->r(self::SAND), 'borderRadius' => $this->r('16px'), 'padding' => $this->r(['top' => '14px', 'right' => '16px', 'bottom' => '14px', 'left' => '16px'])]),
            'children' => [
                $this->image($name, ['width' => $this->r('56px'), 'height' => $this->r('56px'), 'borderRadius' => $this->r('50%')], $this->photoUrl($photo, 140, 140)),
                $this->text("<b>{$name}</b><br>{$role}", self::MUTED, '15px', ['lineHeight' => $this->r('1.5')]),
            ],
        ];

        return $this->design('lumikin-faq', 'Lumikin · FAQ with advisors', 'content', 'Label, heading and two advisor cards beside rounded question cards.', [
            $this->section_('faq', 'FAQ', [
                $this->row([
                    [
                        ...$this->column(5, [
                            $this->tag('FAQ', self::BUTTER),
                            $this->h2('Got questions? We are happy to help!', self::INK),
                            $this->text('Still wondering about something? Our team answers every message within one school day.', self::MUTED, '16px'),
                            $advisor('p8', 'Sara Mitchell', 'Admissions advisor'),
                            $advisor('p6', 'Ethan Chen', 'Program director'),
                        ], ['gap' => $this->r('20px')]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(7, [
                            $this->blockNode(BlockType::Faq, [
                                'initialOpen' => true,
                                'allowMultipleOpen' => false,
                                'items' => [
                                    ['question' => 'What does a typical school day look like?', 'answer' => 'Our day blends learning, play, meals and rest, guided by caring teachers in a calm routine.'],
                                    ['question' => 'Which activities help children grow?', 'answer' => 'Hands-on art, music, movement, stories, garden time and simple science, all through play.'],
                                    ['question' => 'Do you provide healthy meals and snacks?', 'answer' => 'Yes, nutritious meals and snacks are prepared fresh every day, with allergies always respected.'],
                                    ['question' => 'How do teachers share progress?', 'answer' => 'Through daily notes and photos, termly reports and friendly meetings whenever you need one.'],
                                    ['question' => 'Can we visit before we enrol?', 'answer' => 'Of course. Book a visit any weekday morning to meet the team and explore our rooms.'],
                                ],
                            ], ['fontFamily' => $this->r(self::FONT), 'color' => $this->r(self::INK), 'fontSize' => $this->r('16px')]),
                        ]),
                        'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '64px', 'tablet' => '24px', 'mobile' => '16px']]),
            ], [...$this->whiteSection(), 'padding' => ['desktop' => ['top' => '130px', 'right' => '40px', 'bottom' => '130px', 'left' => '40px'], 'mobile' => ['top' => '72px', 'right' => '16px', 'bottom' => '72px', 'left' => '16px']]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function blogGrid(): array
    {
        return $this->blog(6, 'lumikin-blog-grid');
    }

    /** @return array<string, mixed> */
    private function contact(): array
    {
        $card = fn (string $icon, string $bg, string $title, string $body): array => [
            ...$this->column(3, [$this->iconCircle($icon, $bg, '64px'), $this->h3($title, self::INK, '20px'), $this->text($body, self::MUTED, '16px')], [
                'backgroundColor' => $this->r('#ffffff'), 'borderRadius' => $this->r('20px'), 'padding' => $this->r(['top' => '28px', 'right' => '24px', 'bottom' => '28px', 'left' => '24px']), 'gap' => $this->r('10px'),
                'border' => $this->r(['style' => 'solid', 'color' => 'rgba(0,0,0,0.08)', 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        $section = $this->section_('contact', 'Contact', [
            $this->row([$this->column(12, [$this->tag('CONNECT WITH US', self::LAVENDER, true), $this->h2('Get in touch for joyful learning', self::INK, 'center')], ['alignItems' => $this->r('center'), 'gap' => $this->r('18px')])], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
            $this->row([
                $card('phone', self::MINT, 'Prefer to call?', '<a href="tel:+15553182040">'.self::PHONE.'</a>'),
                $card('mail', self::PEACH, 'Email us', '<a href="mailto:'.self::EMAIL.'">'.self::EMAIL.'</a>'),
                $card('map-pin', self::SKY, 'Visit our school', self::ADDRESS),
                $card('clock', self::BUTTER, 'School hours', 'Mon – Fri, 8:00am – 4:00pm'),
            ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '16px']]]),
            $this->row([
                [
                    ...$this->column(5, [
                        $this->h3('Send us a message', self::INK, ['desktop' => '32px', 'mobile' => '26px'], 'left', ['letterSpacing' => $this->r('-2px')]),
                        $this->text('Tell us your child\'s age and the days you need. We reply within one school day with availability and a time to visit.', self::MUTED, '16px'),
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px']),
                            'children' => [$this->button('Email us', 'mailto:'.self::EMAIL, 'berry'), $this->button('Call us', 'tel:+15553182040', 'outline')],
                        ],
                    ], ['backgroundColor' => $this->r(self::LAVENDER), 'borderRadius' => $this->r('20px'), 'padding' => ['desktop' => ['top' => '44px', 'right' => '40px', 'bottom' => '44px', 'left' => '40px'], 'mobile' => ['top' => '28px', 'right' => '22px', 'bottom' => '28px', 'left' => '22px']], 'gap' => $this->r('18px'), 'justifyContent' => $this->r('center')]),
                    'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                ],
                [
                    ...$this->column(7, [
                        $this->blockNode(BlockType::Map, ['address' => self::ADDRESS, 'latitude' => 30.2486, 'longitude' => -97.7668, 'zoom' => 14], ['height' => ['desktop' => '420px', 'mobile' => '300px'], 'borderRadius' => $this->r('20px'), 'custom' => ['overflow' => 'hidden']]),
                    ]),
                    'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                ],
            ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
        ], $this->whiteSection());
        $section['settings']['anchor'] = 'message';

        return $this->design('lumikin-contact', 'Lumikin · Contact details & map', 'content', 'Label and heading over four contact cards, a lavender message card with email and call buttons, and a rounded map.', [$section]);
    }

    /** @return array<string, mixed> */
    private function campus(): array
    {
        $item = fn (string $icon, string $bg, string $title, string $body): array => [
            ...$this->column(3, [$this->iconCircle($icon, $bg, '72px', true), $this->h3($title, self::INK, '20px', 'center'), $this->text($body, self::MUTED, '16px', ['textAlign' => $this->r('center')])], ['alignItems' => $this->r('center'), 'gap' => $this->r('10px'), 'custom' => ['--fnl-effect' => 'reveal']]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 12],
        ];

        return $this->design('lumikin-campus', 'Lumikin · Campus highlights', 'content', 'Sand band: heading over four centred icon highlights.', [
            $this->section_('campus', 'Campus', [
                $this->row([$this->column(12, [$this->h2('Our campus highlights', self::INK, 'center')])], ['maxWidth' => $this->r('900px'), 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $item('shield', self::MINT, 'Safe & secure', 'A protected, carefully supervised campus.'),
                    $item('home', self::PEACH, 'Green & clean', 'Fresh, bright rooms and a growing garden.'),
                    $item('star', self::SKY, 'Modern facilities', 'Thoughtful spaces for play, rest and learning.'),
                    $item('heart', self::BUTTER, 'Play & grow', 'Indoor and outdoor play every single day.'),
                ], ['maxWidth' => $this->r(self::WIDTH), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], $this->sandSection()),
        ]);
    }

    /* --------------------------------------------------------------- helpers */

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array{key: string, name: string, category: string, description: string, document: array<string, mixed>}
     */
    private function design(string $key, string $name, string $category, string $description, array $sections): array
    {
        return ['key' => $key, 'name' => $name, 'category' => $category, 'description' => $description, 'document' => ['sections' => $sections]];
    }

    /**
     * A full-width section tagged with the collection; content rows set their own width.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function section_(string $block, string $name, array $rows, array $style): array
    {
        $section = $this->section($rows, ['fontFamily' => $this->r(self::FONT), 'color' => $this->r(self::INK), ...$style], 'full');
        $section['name'] = $name;
        $section['settings'] = ['designCollection' => self::COLLECTION, 'designBlock' => $block];

        return $section;
    }

    /** @return array<string, mixed> */
    private function whiteSection(): array
    {
        return ['backgroundColor' => $this->r(self::WHITE), 'padding' => $this->pad('130px', '40px', '72px')];
    }

    /** @return array<string, mixed> */
    private function sandSection(): array
    {
        return ['backgroundColor' => $this->r(self::SAND), 'padding' => $this->pad('130px', '40px', '72px')];
    }

    /** @return array<string, mixed> */
    private function r(mixed $value): array
    {
        return $this->responsive($value);
    }

    /**
     * A soft highlight bar behind part of a heading, drawn as the block's background so it
     * shows the same in the builder and on the page. Position and size are percentages.
     *
     * @return array<string, mixed>
     */
    private function highlight(string $colour, string $width, string $height, string $x, string $y): array
    {
        return ['custom' => [
            'background-image' => "linear-gradient({$colour}, {$colour})",
            'background-size' => "{$width} {$height}",
            'background-position' => "{$x} {$y}",
            'background-repeat' => 'no-repeat',
            '--fnl-effect' => 'blur-in',
        ]];
    }

    /** @return array<string, mixed> */
    private function logo(bool $light): array
    {
        return $this->blockNode(BlockType::Logo, ['src' => $light ? self::LOGO_LIGHT : self::LOGO, 'alt' => 'Lumikin', 'url' => '/'], [
            'fontFamily' => $this->r(self::HEADING_FONT),
            'color' => $this->r($light ? '#ffffff' : self::INK),
            'fontSize' => ['desktop' => '26px', 'mobile' => '22px'],
            'height' => ['desktop' => '34px', 'mobile' => '30px'],
        ]);
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function h1(string $text, string $colour, string $align = 'left', array $extra = []): array
    {
        return $this->heading($text, 'h1', [
            'fontFamily' => $this->r(self::HEADING_FONT),
            'color' => $this->r($colour),
            'fontSize' => ['desktop' => '70px', 'tablet' => '56px', 'mobile' => '40px'],
            'fontWeight' => $this->r('500'),
            'lineHeight' => $this->r('1.14'),
            'letterSpacing' => ['desktop' => '-2px', 'mobile' => '-1.2px'],
            'textAlign' => $this->r($align),
            ...$extra,
        ]);
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function h2(string $text, string $colour, string $align = 'left', array $extra = []): array
    {
        return $this->heading($text, 'h2', [
            'fontFamily' => $this->r(self::HEADING_FONT),
            'color' => $this->r($colour),
            'fontSize' => ['desktop' => '48px', 'tablet' => '40px', 'mobile' => '32px'],
            'fontWeight' => $this->r('500'),
            'lineHeight' => $this->r('1.25'),
            'letterSpacing' => ['desktop' => '-2.4px', 'mobile' => '-1.2px'],
            'textAlign' => $this->r($align),
            ...$extra,
        ]);
    }

    /**
     * @param  string|array<string, string>  $size
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function h3(string $text, string $colour, string|array $size, string $align = 'left', array $extra = []): array
    {
        return $this->heading($text, 'h3', [
            'fontFamily' => $this->r(self::HEADING_FONT),
            'color' => $this->r($colour),
            'fontSize' => is_array($size) ? $size : $this->r($size),
            'fontWeight' => $this->r('500'),
            'lineHeight' => $this->r('1.4'),
            'letterSpacing' => $this->r('-0.5px'),
            'textAlign' => $this->r($align),
            ...$extra,
        ]);
    }

    /**
     * @param  string|array<string, string>  $size
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function text(string $text, string $colour, string|array $size, array $extra = []): array
    {
        return $this->paragraph($text, [
            'fontFamily' => $this->r(self::FONT),
            'color' => $this->r($colour),
            'fontSize' => is_array($size) ? $size : $this->r($size),
            'lineHeight' => $this->r('1.75'),
            'margin' => $this->r('0'),
            ...$extra,
        ]);
    }

    /** A rounded label. @return array<string, mixed> */
    private function tag(string $text, string $background, bool $centred = false): array
    {
        return $this->text($text, self::INK, '14px', [
            'backgroundColor' => $this->r($background),
            'borderRadius' => $this->r('50px'),
            'padding' => $this->r(['top' => '11px', 'right' => '20px', 'bottom' => '9px', 'left' => '20px']),
            'width' => $this->r('fit-content'),
            'fontWeight' => $this->r('500'),
            'letterSpacing' => $this->r('0.04em'),
            'lineHeight' => $this->r('1.4'),
            'maxWidth' => $this->r('none'),
            'margin' => $centred ? $this->r(['left' => 'auto', 'right' => 'auto']) : $this->r('0'),
        ]);
    }

    /** An icon in a pastel circle. @return array<string, mixed> */
    private function iconCircle(string $icon, string $background, string $size, bool $centred = false): array
    {
        $px = (int) $size;

        return $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => ((int) round($px * 0.4)).'px'], [
            'color' => $this->r(self::INK),
            'backgroundColor' => $this->r($background),
            'borderRadius' => $this->r('50%'),
            // A fixed square, so the circle is exact whatever column it sits in.
            'width' => $this->r("{$px}px"),
            'height' => $this->r("{$px}px"),
            'minWidth' => $this->r("{$px}px"),
            'padding' => $this->r('0'),
            'margin' => $centred ? $this->r(['left' => 'auto', 'right' => 'auto']) : $this->r('0'),
        ]);
    }

    /**
     * A pill button: berry (filled), white, or outline.
     *
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function button(string $label, string $url, string $tone, array $extra = [], array $props = []): array
    {
        [$background, $colour, $border] = match ($tone) {
            'berry' => [self::BERRY, self::ON_BERRY, self::BERRY],
            'white' => ['#ffffff', self::INK, '#ffffff'],
            default => ['transparent', self::INK, '#000000'],
        };

        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'iconRight' => 'arrow-right',
            'size' => 'md',
            'variant' => $tone === 'berry' ? 'primary' : 'secondary',
            ...$props,
        ], [
            'fontFamily' => $this->r(self::FONT),
            'backgroundColor' => $this->r($background),
            'color' => $this->r($colour),
            'border' => $this->r(['style' => 'solid', 'color' => $border, 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'borderRadius' => $this->r('50px'),
            'fontSize' => $this->r('14px'),
            'fontWeight' => $this->r('500'),
            'padding' => $this->r(['top' => '14px', 'right' => '20px', 'bottom' => '14px', 'left' => '22px']),
            'custom' => ['--fnl-effect' => 'press'],
            ...$extra,
        ]);
    }

    /** A plain text link with an arrow. @return array<string, mixed> */
    private function linkButton_(string $label, string $url): array
    {
        return $this->blockNode(BlockType::Button, ['label' => $label, 'action' => 'url', 'url' => $url, 'iconRight' => 'arrow-right', 'size' => 'sm', 'variant' => 'link'], [
            'fontFamily' => $this->r(self::FONT),
            'color' => $this->r(self::INK),
            'backgroundColor' => $this->r('transparent'),
            'fontSize' => $this->r('14px'),
            'fontWeight' => $this->r('500'),
            'padding' => $this->r(['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0']),
        ]);
    }

    private function photoUrl(string $photo, int $width, ?int $height = null): string
    {
        return 'https://images.unsplash.com/photo-'.self::PHOTOS[$photo].'?auto=format&fit=crop&w='.$width.($height ? '&h='.$height : '').'&q=80';
    }

    private function photo(string $photo, int $width): string
    {
        return 'url("'.$this->photoUrl($photo, $width).'")';
    }
}
