<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Collections;

use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\BuildsDocumentNodes;
use App\Cms\Builder\Schema\TemplatePresentation;

/**
 * Pebblewood: an early-learning / childcare website collection. Header, footer and
 * page sections for five pages (Home, About, Programs, Blog, Contact), every part
 * made of ordinary builder blocks so it can be edited on the canvas.
 *
 * Each section carries `settings.designCollection = pebblewood`, which the renderer
 * turns into a `data-pebblewood` attribute for the few finishing rules that block
 * styles cannot express (FAQ cards, tab pills, the header laid over the hero).
 */
final class PebblewoodDesigns
{
    use BuildsDocumentNodes;

    public const COLLECTION = 'pebblewood';

    /* Colours and fonts follow Theme settings (--site-*); the Pebblewood values are the fallbacks. */
    private const FONT = 'var(--site-body-font, Parkinsans, sans-serif)';

    private const HEADING_FONT = 'var(--site-heading-font, Parkinsans, sans-serif)';

    private const INK = 'var(--site-heading, #1f1d3d)';

    private const BUTTON = 'var(--site-primary, #1f1d3d)';

    private const ON_BUTTON = 'var(--site-on-primary, #ffffff)';

    private const MUTED = 'var(--site-body, #5a5668)';

    private const CREAM = 'var(--site-background, #fffaf3)';

    private const CARD = 'var(--site-surface, #fbf3e2)';

    private const TEAL = 'var(--site-dark, #1f6b63)';

    private const CORAL = 'var(--site-accent, #ef7d45)';

    private const MINT = '#e6f3cf';

    private const PEACH = '#ffe7da';

    private const BUTTER = '#fbecc7';

    private const SKY = '#dbf0fb';

    private const SAND = '#f3eee6';

    /** Content width follows Theme settings → Sections & Cards → Content width. */
    private const WIDTH = 'var(--site-content-width, 1144px)';

    private const LOGO = '/templates/pebblewood/logo.svg';

    private const LOGO_LIGHT = '/templates/pebblewood/logo-light.svg';

    private const PHONE = '+1 (555) 214-7788';

    private const EMAIL = 'hello@pebblewoodlearning.com';

    private const ADDRESS = '48 Maple Grove Lane, Portland, OR 97205';

    /** Unsplash photo ids (free licence). */
    private const PHOTOS = [
        'hero' => '1777056481869-feac70afe522',
        'infant' => '1515488042361-ee00e0ddd4e4',
        'toddler' => '1759678444870-1f09f0d9e688',
        'explorers' => '1516627145497-ae6968895b74',
        'preschool' => '1780751987564-79489283d67b',
        'prek' => '1599689868384-59cb2b01bb21',
        'afterschool' => '1440288736878-766bd5839edb',
        'teacher' => '1788882679829-fee300e8ae4a',
        'classroom' => '1763310225230-6e15b125935a',
        'art' => '1771765812031-22653b4c70a6',
        'playground' => '1575783970733-1aaedde1db74',
        'reading' => '1759977064094-840dfc694bee',
        'cta' => '1761208663763-c4d30657c910',
        'circle' => '1786292949404-084cbd10c7b1',
        'blocks' => '1484820540004-14229fe36ca4',
        'table' => '1777056491418-d4ff81a4ad92',
        'paint' => '1503454537195-1dcabb73ffb9',
        'readTogether' => '1785780224501-d5befd3e019c',
        'drawing' => '1605627079912-97c3810a11a4',
        'park' => '1763561553595-a60112a2977e',
        'nursery' => '1567746455504-cb3213f8f5b8',
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
     * The five page templates, as ordered design keys.
     *
     * @return list<array{id: string, name: string, description: string, keys: list<string>}>
     */
    public function pages(): array
    {
        return [
            [
                'id' => 'pebblewood-home',
                'name' => 'Pebblewood · Home',
                'description' => 'Childcare home page: photo hero under a transparent header, intro and counters, programs, why us, daily routine, learning spaces tabs, parent reviews, FAQ and footer.',
                'keys' => ['pebblewood-hero', 'pebblewood-intro', 'pebblewood-programs', 'pebblewood-why-us', 'pebblewood-day', 'pebblewood-spaces', 'pebblewood-testimonials', 'pebblewood-faq', 'pebblewood-footer'],
            ],
            [
                'id' => 'pebblewood-about',
                'name' => 'Pebblewood · About us',
                'description' => 'About page: centred title with photos, story and counters, timeline, why families choose us, team and parent reviews.',
                'keys' => ['pebblewood-header-light', 'pebblewood-about-hero', 'pebblewood-about-story', 'pebblewood-about-legacy', 'pebblewood-about-why', 'pebblewood-team', 'pebblewood-testimonials', 'pebblewood-footer'],
            ],
            [
                'id' => 'pebblewood-programs-page',
                'name' => 'Pebblewood · Programs',
                'description' => 'Programs page: six age-group program cards, program benefits, daily routine and FAQ.',
                'keys' => ['pebblewood-header-light', 'pebblewood-programs-list', 'pebblewood-benefits', 'pebblewood-day', 'pebblewood-faq', 'pebblewood-footer'],
            ],
            [
                'id' => 'pebblewood-blog',
                'name' => 'Pebblewood · Blog',
                'description' => 'Blog page: title, featured article and a grid of six article cards.',
                'keys' => ['pebblewood-header-light', 'pebblewood-blog-hero', 'pebblewood-blog-grid', 'pebblewood-footer'],
            ],
            [
                'id' => 'pebblewood-contact',
                'name' => 'Pebblewood · Contact',
                'description' => 'Contact page: contact details card, message form, map and FAQ.',
                'keys' => ['pebblewood-header-light', 'pebblewood-contact', 'pebblewood-faq', 'pebblewood-footer'],
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
     * Every Pebblewood design block, with fresh ids.
     *
     * @return list<array{key: string, name: string, category: string, description: string, document: array<string, mixed>}>
     */
    public function all(): array
    {
        return [
            $this->headerLight(),
            $this->hero(),
            $this->intro(),
            $this->programs(),
            $this->whyUs(),
            $this->day(),
            $this->spaces(),
            $this->testimonials(),
            $this->faq(),
            $this->footer(),
            $this->aboutHero(),
            $this->aboutStory(),
            $this->aboutLegacy(),
            $this->aboutWhy(),
            $this->team(),
            $this->programsList(),
            $this->benefits(),
            $this->blogHero(),
            $this->blogGrid(),
            $this->contact(),
        ];
    }

    /* ------------------------------------------------------------ structure */

    /** @return array<string, mixed> */
    private function headerLight(): array
    {
        return $this->design('pebblewood-header-light', 'Pebblewood · Header', 'structure', 'Announcement bar and a cream header with logo, links, phone number and a dark "Book a visit" button.', [
            $this->pwSection('header-light', 'Header', $this->headerRows(overPhoto: false), [
                'backgroundColor' => $this->responsive(self::CREAM),
                'padding' => $this->responsive(['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0']),
            ]),
        ]);
    }

    /**
     * The announcement bar and the navigation row. The light header uses them on cream; the
     * home hero puts them at the top of its photo, which is how the header sits over the photo
     * in the builder exactly as on the live page.
     *
     * @return list<array<string, mixed>>
     */
    private function headerRows(bool $overPhoto): array
    {
        $text = $overPhoto ? '#ffffff' : self::INK;

        $bar = $this->row([
            $this->column(12, [
                $this->paragraph('Little ones grow big here — <a href="/contact">Book a visit</a>', [
                    'fontFamily' => $this->responsive(self::FONT),
                    'color' => $this->responsive(self::INK),
                    'fontSize' => $this->responsive('14px'),
                    'textAlign' => $this->responsive('center'),
                    'maxWidth' => $this->responsive('none'),
                    'margin' => $this->responsive('0'),
                ]),
            ]),
        ], [
            'backgroundColor' => $this->responsive(self::CORAL),
            'padding' => $this->responsive(['top' => '8px', 'right' => '30px', 'bottom' => '8px', 'left' => '30px']),
            'maxWidth' => $this->responsive('none'),
        ]);

        $nav = $this->row([
            [
                ...$this->column(3, [
                    $this->logo(light: $overPhoto),
                ], ['justifyContent' => $this->responsive('center')]),
                'span' => ['desktop' => 3, 'tablet' => 4, 'mobile' => 8],
            ],
            [
                ...$this->column(5, [
                    $this->blockNode(BlockType::Navigation, [
                        'items' => [
                            ['label' => 'Home', 'url' => '/'],
                            ['label' => 'About us', 'url' => '/about'],
                            ['label' => 'Programs', 'url' => '/programs'],
                            ['label' => 'Blog', 'url' => '/news'],
                            ['label' => 'Contact', 'url' => '/contact'],
                        ],
                        'orientation' => 'horizontal',
                        'mobileBehaviour' => 'drawer',
                    ], [
                        'fontFamily' => $this->responsive(self::FONT),
                        'color' => $this->responsive($overPhoto ? '#ffffff' : '#4b4754'),
                        'fontSize' => $this->responsive('14px'),
                        'fontWeight' => $this->responsive('500'),
                        'gap' => $this->responsive('32px'),
                        'textAlign' => ['desktop' => 'left', 'mobile' => 'right'],
                        'justifyContent' => ['desktop' => 'flex-start', 'mobile' => 'flex-end'],
                    ]),
                ], ['justifyContent' => $this->responsive('center')]),
                'span' => ['desktop' => 5, 'tablet' => 8, 'mobile' => 4],
            ],
            [
                ...$this->column(2, [
                    $this->paragraph('<b>Call us</b><br>'.self::PHONE, [
                        'fontFamily' => $this->responsive(self::FONT),
                        'color' => $this->responsive($text),
                        'fontSize' => $this->responsive('14px'),
                        'lineHeight' => $this->responsive('1.5'),
                        'margin' => $this->responsive('0'),
                    ]),
                ], ['justifyContent' => $this->responsive('center')]),
                'span' => ['desktop' => 2, 'tablet' => 6, 'mobile' => 12],
                'visibility' => ['desktop' => true, 'tablet' => false, 'mobile' => false],
            ],
            [
                ...$this->column(2, [
                    $this->pill('Book a visit', '/contact', $overPhoto ? 'cream' : 'ink', 'calendar-check'),
                ], ['justifyContent' => $this->responsive('center'), 'alignItems' => $this->responsive('flex-end')]),
                'span' => ['desktop' => 2, 'tablet' => 6, 'mobile' => 12],
                'visibility' => ['desktop' => true, 'tablet' => false, 'mobile' => false],
            ],
        ], [
            'alignItems' => $this->responsive('center'),
            'gap' => ['desktop' => '24px', 'mobile' => '12px'],
            'maxWidth' => $this->responsive(self::WIDTH),
            'padding' => [
                'desktop' => ['top' => '20px', 'right' => '0', 'bottom' => '20px', 'left' => '0'],
                'mobile' => ['top' => '14px', 'right' => '20px', 'bottom' => '14px', 'left' => '20px'],
            ],
        ]);
        $nav['stackBelow'] = 'none';

        return [$bar, $nav];
    }

    /** @return array<string, mixed> */
    private function footer(): array
    {
        $linkList = fn (string $title, array $items): array => $this->column(2, [
            $this->text($title, self::INK, '16px', ['fontWeight' => $this->responsive('600'), 'margin' => $this->responsive(['bottom' => '6px'])]),
            $this->blockNode(BlockType::Navigation, [
                'items' => array_map(fn (array $item): array => ['label' => $item[0], 'url' => $item[1]], $items),
                'orientation' => 'vertical',
                'mobileBehaviour' => 'stack',
            ], [
                'fontFamily' => $this->responsive(self::FONT),
                'color' => $this->responsive('#4b4754'),
                'fontSize' => $this->responsive('14px'),
                'gap' => $this->responsive('12px'),
            ]),
        ]);

        $card = $this->row([
            [
                ...$this->column(5, [
                    $this->logo(light: false),
                    $this->text('Pebblewood gives every child a safe, warm and playful place to learn, make friends and grow in confidence.', self::MUTED, '14px', ['maxWidth' => $this->responsive('360px')]),
                    $this->text('Licensed early learning centre', self::INK, '14px', ['fontWeight' => $this->responsive('500')]),
                    $this->blockNode(BlockType::SocialIcons, [
                        'networks' => [
                            ['network' => 'facebook', 'url' => 'https://facebook.com'],
                            ['network' => 'instagram', 'url' => 'https://instagram.com'],
                            ['network' => 'youtube', 'url' => 'https://youtube.com'],
                        ],
                        'size' => '16px',
                        'shape' => 'circle',
                    ], [
                        'color' => $this->responsive(self::CORAL),
                    ]),
                ]),
                'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
            ],
            $linkList('Explore', [['Home', '/'], ['About us', '/about'], ['Programs', '/programs'], ['Blog', '/news'], ['Contact', '/contact']]),
            $linkList('Programs', [['Infant care', '/programs'], ['Toddlers', '/programs'], ['Preschool', '/programs'], ['Pre-K', '/programs']]),
            $linkList('Legal', [['Privacy policy', '/privacy'], ['Terms of use', '/terms'], ['Accessibility', '/accessibility']]),
        ], [
            'backgroundColor' => $this->responsive(self::CARD),
            'borderRadius' => $this->responsive('32px 32px 0 0'),
            'maxWidth' => $this->responsive(self::WIDTH),
            'padding' => [
                'desktop' => ['top' => '62px', 'right' => '60px', 'bottom' => '32px', 'left' => '60px'],
                'mobile' => ['top' => '40px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'],
            ],
            'gap' => ['desktop' => '32px', 'mobile' => '28px'],
        ]);

        $bottom = $this->row([
            $this->column(12, [
                $this->text('© '.date('Y').' Pebblewood Early Learning. All rights reserved.', self::MUTED, '13px', [
                    'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(75,71,84,0.2)', 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                    'padding' => $this->responsive(['top' => '16px']),
                    'maxWidth' => $this->responsive('none'),
                ]),
            ]),
        ], [
            'backgroundColor' => $this->responsive(self::CARD),
            'maxWidth' => $this->responsive(self::WIDTH),
            'padding' => [
                'desktop' => ['top' => '0', 'right' => '60px', 'bottom' => '38px', 'left' => '60px'],
                'mobile' => ['top' => '0', 'right' => '24px', 'bottom' => '28px', 'left' => '24px'],
            ],
        ]);

        $cta = $this->row([
            $this->column(12, [
                $this->h2("Your child's bright future\nstarts right here", '#ffffff', 'center'),
                $this->pill('Book a visit', '/contact', 'cream', 'calendar-check', ['textAlign' => $this->responsive('center')]),
            ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('28px')]),
        ], [
            'padding' => [
                'desktop' => ['top' => '0', 'bottom' => '120px'],
                'mobile' => ['top' => '0', 'bottom' => '72px'],
            ],
        ]);

        return $this->design('pebblewood-footer', 'Pebblewood · Footer with call to action', 'structure', 'Photo call-to-action band and a cream footer card with logo, links, socials and copyright.', [
            $this->pwSection('footer', 'Footer', [$cta, $card, $bottom], [
                'backgroundColor' => $this->responsive(self::INK),
                'backgroundImage' => $this->responsive($this->photo('cta', 1800)),
                'backgroundSize' => $this->responsive('cover'),
                'backgroundPosition' => $this->responsive('center'),
                'overlayColor' => $this->responsive('rgba(31, 29, 61, 0.45)'),
                'padding' => [
                    'desktop' => ['top' => '120px', 'right' => '30px', 'bottom' => '0', 'left' => '30px'],
                    'mobile' => ['top' => '72px', 'right' => '16px', 'bottom' => '0', 'left' => '16px'],
                ],
            ], 'full'),
        ]);
    }

    /* ----------------------------------------------------------------- home */

    /** @return array<string, mixed> */
    private function hero(): array
    {
        $buttons = [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px']),
            'children' => [
                $this->pill('Book a visit', '/contact', 'cream', 'calendar-check'),
                $this->pill('Explore programs', '/programs', 'outline', 'arrow-right'),
            ],
        ];

        $content = $this->row([
            [
                ...$this->column(8, [
                    $this->tag('WHERE LITTLE LEARNERS BLOOM', 'rgba(0, 0, 0, 0.37)', '#ffffff'),
                    $this->h1('Give your child a place to shine', '#ffffff', 'left', ['maxWidth' => $this->responsive('780px')]),
                    $this->text('A safe, caring and playful place where your child can explore, learn and grow in confidence every single day.', '#ffffff', '16px', ['maxWidth' => $this->responsive('480px')]),
                    $buttons,
                ], ['gap' => $this->responsive('22px')]),
                'span' => ['desktop' => 9, 'tablet' => 12, 'mobile' => 12],
            ],
        ], [
            'maxWidth' => $this->responsive(self::WIDTH),
            'margin' => ['desktop' => ['top' => '230px'], 'mobile' => ['top' => '120px']],
            'padding' => ['desktop' => ['bottom' => '84px'], 'mobile' => ['right' => '20px', 'bottom' => '56px', 'left' => '20px']],
        ]);

        return $this->design('pebblewood-hero', 'Pebblewood · Hero with header', 'hero', 'Full-bleed classroom photo with the announcement bar, logo, white links and "Book a visit" on top, then a label, a large headline, short text and two pill buttons. Use it with the site header switched off on the page.', [
            $this->pwSection('hero', 'Hero with header', [...$this->headerRows(overPhoto: true), $content], [
                'backgroundColor' => $this->responsive(self::INK),
                'backgroundImage' => $this->responsive($this->photo('hero', 2000)),
                'backgroundSize' => $this->responsive('cover'),
                'backgroundPosition' => $this->responsive('center'),
                'overlayColor' => $this->responsive('rgba(24, 20, 48, 0.42)'),
                'padding' => $this->responsive(['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0']),
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function intro(): array
    {
        $counter = fn (string $number, string $label, string $color): array => [
            ...$this->column(4, [
                $this->text($number, self::INK, '48px', ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('-1.5px'), 'lineHeight' => $this->responsive('1')]),
                $this->text($label, '#4b4754', '14px'),
            ], [
                'backgroundColor' => $this->responsive($color),
                'borderRadius' => $this->responsive('32px'),
                'minHeight' => ['desktop' => '275px', 'mobile' => '190px'],
                'justifyContent' => $this->responsive('space-between'),
                'padding' => $this->responsive(['top' => '32px', 'right' => '28px', 'bottom' => '32px', 'left' => '28px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 12],
        ];

        return $this->design('pebblewood-intro', 'Pebblewood · Intro statement & counters', 'content', 'Large centred welcome statement, a rating line and three pastel counter cards.', [
            $this->pwSection('intro', 'Intro', [
                $this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::Icon, ['name' => 'heart', 'size' => '40px'], [
                            'color' => $this->responsive(self::CORAL),
                            'textAlign' => $this->responsive('center'),
                        ]),
                        $this->text('Here every child is free to imagine, wonder and discover in a safe and caring place. We believe the best learning happens through joyful play and small everyday adventures.', self::INK, ['desktop' => '32px', 'mobile' => '22px'], [
                            'fontWeight' => $this->responsive('500'),
                            'lineHeight' => $this->responsive('1.4'),
                            'letterSpacing' => $this->responsive('-0.6px'),
                            'textAlign' => $this->responsive('center'),
                            'maxWidth' => $this->responsive('880px'),
                            'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
                        ]),
                        $this->text('★ Rated 4.9 / 5 by 400+ families', self::INK, '13px', ['textAlign' => $this->responsive('center'), 'maxWidth' => $this->responsive('none')]),
                    ], ['gap' => $this->responsive('28px')]),
                ], ['margin' => ['desktop' => ['bottom' => '64px'], 'mobile' => ['bottom' => '40px']]]),
                $this->row([
                    $counter('98%', 'Parent satisfaction rate', self::MINT),
                    $counter('15+', 'Years of early learning', self::PEACH),
                    $counter('400+', 'Happy families served', self::BUTTER),
                ], ['maxWidth' => $this->responsive('920px'), 'gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], $this->creamSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function programs(): array
    {
        $feature = fn (string $icon, string $title, string $body): array => [
            ...$this->column(4, [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '14px'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '22px'], ['color' => $this->responsive(self::BUTTER)]),
                        $this->text($title, '#ffffff', '18px', ['fontWeight' => $this->responsive('600')]),
                    ],
                ],
                $this->text($body, 'rgba(255, 255, 255, 0.75)', '14px'),
            ], [
                'border' => ['desktop' => ['style' => 'solid', 'color' => 'rgba(238, 231, 212, 0.2)', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '1px'], 'mobile' => ['style' => 'solid', 'color' => 'rgba(238, 231, 212, 0.2)', 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']],
                'padding' => ['desktop' => ['left' => '28px'], 'mobile' => ['top' => '20px', 'left' => '0']],
                'gap' => $this->responsive('10px'),
            ]),
            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 12],
        ];

        return $this->design('pebblewood-programs', 'Pebblewood · Programs overview', 'content', 'Deep teal band with three program cards (photo, ages, title, text, link) and three trust points.', [
            $this->pwSection('programs', 'Programs', [
                $this->row([
                    $this->column(12, [
                        $this->h2("Helping little minds\ngrow with confidence", '#ffffff', 'center'),
                    ]),
                ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $this->programCard('toddler', 'Ages 1–2 years', 'Toddler program', 'Little ones explore the world through sensory play, movement, music and warm, caring moments that build trust.'),
                    $this->programCard('preschool', 'Ages 3–4 years', 'Preschool program', 'Children grow creativity, language and early number skills through hands-on projects, play and guided discovery.'),
                    $this->programCard('prek', 'Ages 4–5 years', 'Pre-K program', 'A joyful step towards kindergarten, with activities that build early reading, problem-solving and independence.'),
                ], ['gap' => ['desktop' => '24px', 'mobile' => '20px'], 'margin' => ['desktop' => ['bottom' => '72px'], 'mobile' => ['bottom' => '40px']]]),
                $this->row([
                    $feature('shield', 'Safe classrooms', 'Bright, secure rooms designed for confident exploring.'),
                    $feature('users', 'Qualified teachers', 'Experienced educators who nurture every child.'),
                    $feature('clock', 'Flexible schedule', 'Full-day and half-day options that fit your family.'),
                ], ['gap' => ['desktop' => '24px', 'mobile' => '20px']]),
            ], $this->colourSection(self::TEAL)),
        ]);
    }

    /** @return array<string, mixed> */
    private function programCard(string $photo, string $ages, string $title, string $body, int $span = 4): array
    {
        return [
            ...$this->column($span, [
                $this->image($title, [
                    'height' => ['desktop' => '303px', 'mobile' => '240px'],
                    'borderRadius' => $this->responsive('32px'),
                ], $this->photoUrl($photo, 800)),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                        'padding' => $this->responsive(['top' => '14px', 'right' => '18px', 'bottom' => '0', 'left' => '18px']),
                        'textAlign' => $this->responsive('center'),
                    ]),
                    'children' => [
                        $this->text($ages, self::CORAL, '14px', ['textAlign' => $this->responsive('center'), 'maxWidth' => $this->responsive('none')]),
                        $this->h3($title, self::INK, '24px', 'center'),
                        $this->text($body, self::MUTED, '14px', ['textAlign' => $this->responsive('center')]),
                        $this->blockNode(BlockType::Button, [
                            'label' => 'Learn more',
                            'action' => 'url',
                            'url' => '/programs',
                            'iconRight' => 'arrow-right',
                            'size' => 'sm',
                            'variant' => 'link',
                        ], [
                            'fontFamily' => $this->responsive(self::FONT),
                            'color' => $this->responsive(self::INK),
                            'backgroundColor' => $this->responsive('transparent'),
                            'fontSize' => $this->responsive('14px'),
                            'fontWeight' => $this->responsive('600'),
                            'padding' => $this->responsive(['top' => '6px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                            'textAlign' => $this->responsive('center'),
                        ]),
                    ],
                ],
            ], [
                'backgroundColor' => $this->responsive(self::CARD),
                'borderRadius' => $this->responsive('32px'),
                'padding' => $this->responsive(['top' => '10px', 'right' => '10px', 'bottom' => '35px', 'left' => '10px']),
                'gap' => $this->responsive('8px'),
                'custom' => ['--fnl-effect' => 'reveal lift zoom'],
            ]),
            'span' => ['desktop' => $span, 'tablet' => 6, 'mobile' => 12],
        ];
    }

    /** @return array<string, mixed> */
    private function whyUs(): array
    {
        $point = fn (string $title, string $body, bool $last = false): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                'padding' => $this->responsive(['top' => '20px', 'bottom' => '20px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => '#e9e2d4', 'top' => '0', 'right' => '0', 'bottom' => $last ? '0' : '1px', 'left' => '0']),
            ]),
            'children' => [
                $this->h3($title, self::INK, '20px'),
                $this->text($body, self::MUTED, '15px'),
            ],
        ];

        return $this->design('pebblewood-why-us', 'Pebblewood · Why us with tall photo', 'content', 'Tall rounded photo with a caption chip beside a heading, text and three approach points.', [
            $this->pwSection('why-us', 'Why us', [
                $this->row([
                    [
                        ...$this->column(6, [
                            $this->image('A teacher doing a clay activity with two children', [
                                'height' => ['desktop' => '640px', 'mobile' => '380px'],
                                'borderRadius' => $this->responsive('32px'),
                            ], $this->photoUrl('teacher', 1000)),
                            $this->text('🌱 Explore our safe and caring classrooms', self::INK, '14px', [
                                'backgroundColor' => $this->responsive('#ffffff'),
                                'borderRadius' => $this->responsive('11px'),
                                'padding' => $this->responsive(['top' => '11px', 'right' => '14px', 'bottom' => '9px', 'left' => '14px']),
                                'width' => $this->responsive('fit-content'),
                                'margin' => ['desktop' => ['top' => '-76px', 'left' => '24px'], 'mobile' => ['top' => '-64px', 'left' => '16px']],
                                'boxShadow' => $this->responsive('0 10px 30px -12px rgba(31, 29, 61, 0.25)'),
                            ]),
                        ]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(6, [
                            $this->h2('Helping every child reach their potential', self::INK),
                            $this->text('Every child learns in their own way. Our warm, play-based approach sparks curiosity and builds lasting confidence.', self::MUTED, '16px'),
                            $point('Personal learning', 'Children grow at their own pace. Our teachers plan engaging, age-right activities that support thinking, language and social skills.'),
                            $point('Safe & nurturing spaces', 'Calm rooms and gentle routines help children feel secure, share ideas and build important skills naturally.'),
                            $point('Family partnership', 'Daily updates, open doors and regular check-ins keep you close to every milestone along the way.', true),
                        ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('14px')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['gap' => ['desktop' => '80px', 'mobile' => '40px'], 'alignItems' => $this->responsive('center')]),
            ], $this->creamSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function day(): array
    {
        $time = fn (string $label, string $color): array => [
            ...$this->column(4, [
                $this->text($label, self::INK, '14px', [
                    'backgroundColor' => $this->responsive($color),
                    'borderRadius' => $this->responsive('100px'),
                    'padding' => $this->responsive(['top' => '12px', 'right' => '24px', 'bottom' => '12px', 'left' => '24px']),
                    'width' => $this->responsive('fit-content'),
                    'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
                    'fontWeight' => $this->responsive('500'),
                ]),
            ]),
            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 4],
        ];

        $card = fn (string $title, array $items, string $color): array => [
            ...$this->column(4, [
                $this->h3($title, self::INK, '20px'),
                ...array_map(fn (string $item): array => [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [1, 11], 'gap' => '10px', 'stackBelow' => 'none'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => 'check', 'size' => '16px'], ['color' => $this->responsive(self::TEAL)]),
                        $this->text($item, '#4b4754', '14px'),
                    ],
                ], $items),
            ], [
                'backgroundColor' => $this->responsive($color),
                'borderRadius' => $this->responsive('32px'),
                'padding' => $this->responsive(['top' => '32px', 'right' => '32px', 'bottom' => '32px', 'left' => '32px']),
                'minHeight' => ['desktop' => '274px', 'mobile' => '0'],
                'gap' => $this->responsive('14px'),
                'custom' => ['--fnl-effect' => 'reveal'],
            ]),
            'span' => ['desktop' => 4, 'tablet' => 4, 'mobile' => 12],
        ];

        $times = $this->row([
            $time('Morning', self::SKY),
            $time('Midday', self::BUTTER),
            $time('Afternoon', self::MINT),
        ], [
            'gap' => $this->responsive('24px'),
            'margin' => ['desktop' => ['bottom' => '28px'], 'mobile' => ['bottom' => '20px']],
            'custom' => ['background-image' => 'linear-gradient(rgba(239,239,239,0.9),rgba(239,239,239,0.9))', 'background-size' => '100% 3px', 'background-position' => 'center', 'background-repeat' => 'no-repeat'],
        ]);
        $times['stackBelow'] = 'none';

        return $this->design('pebblewood-day', 'Pebblewood · A day at school', 'content', 'Morning, midday and afternoon pills over three pastel routine cards with check lists and a button.', [
            $this->pwSection('day', 'Daily routine', [
                $this->row([
                    $this->column(12, [$this->h2("A day full of\nlearning and fun", self::INK, 'center')]),
                ], ['margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '28px']]]),
                $times,
                $this->row([
                    $card('Morning hello', ['Warm welcomes and free play', 'Circle time and the day ahead', 'Making friends from the start'], self::MINT),
                    $card('Learn & discover', ['Hands-on learning projects', 'Art, music and story time', 'Early reading and number games'], self::PEACH),
                    $card('Play & grow', ['Outdoor adventures and movement', 'Healthy snacks and quiet time', 'Sharing the day and happy goodbyes'], self::BUTTER),
                ], ['gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $this->column(12, [
                        $this->text('A balanced routine of learning, creativity, outdoor play and rest that supports every part of your child’s growth.', self::MUTED, '15px', [
                            'textAlign' => $this->responsive('center'),
                            'maxWidth' => $this->responsive('520px'),
                            'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
                        ]),
                        $this->pill('Book a visit', '/contact', 'ink', 'calendar-check', ['textAlign' => $this->responsive('center')]),
                    ], ['gap' => $this->responsive('20px')]),
                ]),
            ], [
                ...$this->creamSection(),
                'padding' => $this->pad('120px', '30px', '72px'),
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function spaces(): array
    {
        $panel = fn (string $photo, string $icon, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px']),
            'children' => [
                $this->image($title, [
                    'height' => ['desktop' => '555px', 'mobile' => '300px'],
                    'borderRadius' => $this->responsive('28px'),
                ], $this->photoUrl($photo, 1600)),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '12px'], [
                        'backgroundColor' => $this->responsive('#ffffff'),
                        'borderRadius' => $this->responsive('25px'),
                        'padding' => $this->responsive(['top' => '30px', 'right' => '30px', 'bottom' => '30px', 'left' => '30px']),
                        'maxWidth' => ['desktop' => '437px', 'mobile' => 'none'],
                        'margin' => ['desktop' => ['top' => '-262px', 'right' => '24px', 'bottom' => '24px', 'left' => 'auto'], 'mobile' => ['top' => '16px']],
                        'boxShadow' => $this->responsive('0 20px 40px -24px rgba(31, 29, 61, 0.3)'),
                    ]),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '26px'], [
                            'color' => $this->responsive(self::TEAL),
                            'backgroundColor' => $this->responsive(self::SKY),
                            'borderRadius' => $this->responsive('10px'),
                            'padding' => $this->responsive(['top' => '17px', 'right' => '17px', 'bottom' => '17px', 'left' => '17px']),
                            'width' => $this->responsive('fit-content'),
                        ]),
                        $this->h3($title, self::INK, '22px'),
                        $this->text($body, self::MUTED, '14px'),
                    ],
                ],
            ],
        ];

        $tabs = [
            ...$this->blockNode(BlockType::Tabs, [
                'orientation' => 'horizontal',
                'tabs' => [
                    ['id' => null, 'label' => 'Classrooms'],
                    ['id' => null, 'label' => 'Art studio'],
                    ['id' => null, 'label' => 'Outdoor play'],
                    ['id' => null, 'label' => 'Reading nook'],
                ],
            ], [
                'fontFamily' => $this->responsive(self::FONT),
                'gap' => $this->responsive('28px'),
                'custom' => [
                    '--fnl-tablist-bg' => 'transparent',
                    '--fnl-tablist-border' => 'transparent',
                    '--fnl-tab-radius' => '100px',
                    '--fnl-tab-active-bg' => '#ffffff',
                    '--fnl-tab-active-border' => self::TEAL,
                    '--fnl-tab-active-color' => self::TEAL,
                    '--fnl-tab-inactive-bg' => self::SAND,
                    '--fnl-tab-inactive-border' => self::SAND,
                    '--fnl-tab-inactive-color' => '#222222',
                    '--fnl-font-size' => '14px',
                ],
            ]),
            'children' => [
                $panel('classroom', 'home', 'Bright learning rooms', 'Roomy, age-right classrooms full of light, made to spark curiosity and creativity.'),
                $panel('art', 'star', 'Creative art studio', 'Paint, clay and collage stations where little hands can make a happy mess.'),
                $panel('playground', 'compass', 'Outdoor play garden', 'Safe climbing, sand and garden spaces for fresh air and big movement every day.'),
                $panel('reading', 'book-open', 'Cosy reading nook', 'Soft corners and picture books that grow a lifelong love of stories.'),
            ],
        ];

        return $this->design('pebblewood-spaces', 'Pebblewood · Learning spaces tabs', 'media', 'Teal band with a white card of pill tabs; each tab shows a large photo with an info card.', [
            $this->pwSection('spaces', 'Learning spaces', [
                $this->row([
                    $this->column(12, [$this->h2("Spaces made for safe learning\nand happy growing", '#ffffff', 'center')]),
                ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $this->column(12, [$tabs], [
                        'backgroundColor' => $this->responsive('#ffffff'),
                        'borderRadius' => $this->responsive('32px'),
                        'padding' => ['desktop' => ['top' => '32px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'], 'mobile' => ['top' => '16px', 'right' => '12px', 'bottom' => '16px', 'left' => '12px']],
                    ]),
                ]),
            ], $this->colourSection(self::TEAL)),
        ]);
    }

    /** @return array<string, mixed> */
    private function testimonials(): array
    {
        $card = fn (string $quote, string $name, string $parentOf, string $photo, string $color): array => [
            ...$this->column(4, [
                $this->text($quote, self::INK, '16px', ['lineHeight' => $this->responsive('1.65')]),
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [9, 3], 'gap' => '12px', 'stackBelow' => 'none'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '2px']),
                            'children' => [
                                $this->text($name, self::INK, '15px', ['fontWeight' => $this->responsive('600')]),
                                $this->text($parentOf, self::MUTED, '13px'),
                            ],
                        ],
                        [
                            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px'], ['alignItems' => $this->responsive('flex-end')]),
                            'children' => [
                                $this->image($name, [
                                    'width' => $this->responsive('48px'),
                                    'height' => $this->responsive('48px'),
                                    'borderRadius' => $this->responsive('100px'),
                                ], $this->photoUrl($photo, 120, 120)),
                            ],
                        ],
                    ],
                ],
            ], [
                'backgroundColor' => $this->responsive($color),
                'borderRadius' => $this->responsive('30px'),
                'padding' => $this->responsive(['top' => '30px', 'right' => '30px', 'bottom' => '30px', 'left' => '30px']),
                'justifyContent' => $this->responsive('space-between'),
                'gap' => $this->responsive('40px'),
                'custom' => ['--fnl-effect' => 'reveal'],
            ]),
            'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
        ];

        $rowStyle = ['gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '16px']]];

        return $this->design('pebblewood-testimonials', 'Pebblewood · Parent reviews', 'social_proof', 'Six pastel parent review cards with names, children and round photos.', [
            $this->pwSection('testimonials', 'Parent reviews', [
                $this->row([
                    $this->column(12, [
                        $this->tag('♥ Loved by parents', self::SAND, self::INK, true),
                        $this->h2("The foundation for\nhappy childhoods", self::INK, 'center'),
                    ], ['gap' => $this->responsive('18px')]),
                ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $card('"Choosing Pebblewood was one of our best decisions. From day one the teachers made our daughter feel safe and excited to learn, and her confidence has blossomed."', 'Hannah Clarke', 'Mum of Ava', 'p1', self::MINT),
                    $card('"The team took the time to really know our son. He runs in every morning and tells us about his new friends every evening."', 'Marcus Reed', 'Dad of Leo', 'p2', self::PEACH),
                    $card('"We were nervous about our first time in childcare, but the staff made the move so gentle and easy for all of us."', 'Priya Nair', 'Mum of Isla', 'p3', self::BUTTER),
                ], $rowStyle),
                $this->row([
                    $card('"Our son has grown so much since he started. He is more confident and loves joining in with group activities."', 'Tom Alvarez', 'Dad of Mateo', 'p4', self::CARD),
                    $card('"We had so many worries, but the caring team helped our daughter settle in quickly and happily. The daily updates are lovely."', 'Grace Kim', 'Mum of Mia', 'p5', self::SKY),
                    $card('"Starting childcare was a big step, but the warmth and patience of the teachers made it easy. Our son felt at home in no time."', 'Daniel Osei', 'Dad of Noah', 'p6', self::MINT),
                ], [...$rowStyle, 'margin' => $this->responsive(['bottom' => '0'])]),
            ], $this->creamSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function faq(): array
    {
        return $this->design('pebblewood-faq', 'Pebblewood · FAQ', 'content', 'Centred heading over rounded question cards and a contact prompt.', [
            $this->pwSection('faq', 'FAQ', [
                $this->row([
                    $this->column(12, [$this->h2('Frequently asked questions', self::INK, 'center')]),
                ], ['margin' => ['desktop' => ['bottom' => '48px'], 'mobile' => ['bottom' => '28px']]]),
                $this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::Faq, [
                            'initialOpen' => true,
                            'allowMultipleOpen' => false,
                            'items' => [
                                ['question' => 'What are your opening hours?', 'answer' => 'We are open Monday to Friday from 7:30 AM to 6:00 PM, with full-day and half-day places available.'],
                                ['question' => 'Which ages do you care for?', 'answer' => 'We welcome children from 6 months to 5 years, with an after-school club for ages 5 to 8.'],
                                ['question' => 'Are your teachers trained and certified?', 'answer' => 'Yes. Every teacher holds an early childhood qualification, current first-aid training and a background check.'],
                                ['question' => 'What does a typical day look like?', 'answer' => 'A balanced mix of play, learning projects, creative time, outdoor play, healthy meals and rest, all in a calm routine.'],
                                ['question' => 'How do you keep children safe?', 'answer' => 'Secure entry, careful supervision, age-right activities and clear health and safety routines that we review every term.'],
                            ],
                        ], [
                            'fontFamily' => $this->responsive(self::FONT),
                            'color' => $this->responsive(self::INK),
                            'fontSize' => $this->responsive('15px'),
                        ]),
                        $this->text('Still have questions? <a href="/contact">Talk to our team</a>', self::MUTED, '14px', [
                            'textAlign' => $this->responsive('center'),
                            'margin' => $this->responsive(['top' => '24px', 'left' => 'auto', 'right' => 'auto']),
                        ]),
                    ]),
                ]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '0', 'right' => '30px', 'bottom' => '120px', 'left' => '30px'],
                    'mobile' => ['top' => '0', 'right' => '20px', 'bottom' => '72px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /* ---------------------------------------------------------------- about */

    /** @return array<string, mixed> */
    private function aboutHero(): array
    {
        return $this->design('pebblewood-about-hero', 'Pebblewood · About title & photos', 'hero', 'Huge centred title, short text and two rounded photos side by side.', [
            $this->pwSection('page-hero', 'About title', [
                $this->pageTitle('Growing bright futures', 'At Pebblewood we believe every child deserves the chance to learn, grow and shine.'),
                $this->row([
                    [
                        ...$this->column(8, [$this->image('Teacher with a happy group of children', ['height' => ['desktop' => '540px', 'mobile' => '280px'], 'borderRadius' => $this->responsive('32px')], $this->photoUrl('circle', 1400))]),
                        'span' => ['desktop' => 8, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(4, [$this->image('Child building with colourful blocks', ['height' => ['desktop' => '540px', 'mobile' => '280px'], 'borderRadius' => $this->responsive('32px')], $this->photoUrl('blocks', 800))]),
                        'span' => ['desktop' => 4, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['gap' => ['desktop' => '24px', 'mobile' => '16px']]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '80px', 'right' => '30px', 'bottom' => '60px', 'left' => '30px'],
                    'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '40px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function aboutStory(): array
    {
        $stat = fn (string $label, string $number, string $color): array => [
            ...$this->column(3, [
                $this->text($label, self::INK, '16px', ['maxWidth' => $this->responsive('150px')]),
                $this->text($number, self::INK, ['desktop' => '64px', 'mobile' => '44px'], ['fontWeight' => $this->responsive('600'), 'letterSpacing' => $this->responsive('-2px'), 'lineHeight' => $this->responsive('1')]),
            ], [
                'backgroundColor' => $this->responsive($color),
                'borderRadius' => $this->responsive('32px'),
                'minHeight' => ['desktop' => '260px', 'mobile' => '180px'],
                'justifyContent' => $this->responsive('space-between'),
                'padding' => $this->responsive(['top' => '28px', 'right' => '24px', 'bottom' => '28px', 'left' => '24px']),
                'custom' => ['--fnl-effect' => 'reveal lift'],
            ]),
            'span' => ['desktop' => 3, 'tablet' => 6, 'mobile' => 6],
        ];

        $stats = $this->row([
            $stat('Children enrolled', '140+', self::MINT),
            $stat('Caring educators', '30+', self::PEACH),
            $stat('Years of care', '15+', self::BUTTER),
            $stat('Happy families', '400+', self::SKY),
        ], ['gap' => ['desktop' => '24px', 'mobile' => '12px']]);
        $stats['stackBelow'] = 'none';

        return $this->design('pebblewood-about-story', 'Pebblewood · Story & counters', 'content', 'Heading beside two paragraphs of story, then four pastel counter cards.', [
            $this->pwSection('about-story', 'Our story', [
                $this->row([
                    $this->column(6, [$this->h2('Helping every child learn, grow and thrive', self::INK)]),
                    $this->column(6, [
                        $this->text('At Pebblewood we believe every child deserves a safe, warm and inspiring place where they can reach their full potential. Our play-based approach grows curiosity, creativity and confidence at every stage.', self::MUTED, '15px'),
                        $this->text('Since opening our doors we have partnered with families to create meaningful experiences that build friendships, life skills and a love of learning that lasts.', self::MUTED, '15px'),
                    ]),
                ], ['gap' => ['desktop' => '64px', 'mobile' => '20px'], 'margin' => ['desktop' => ['bottom' => '72px'], 'mobile' => ['bottom' => '40px']]]),
                $stats,
            ], [
                ...$this->creamSection(),
                'padding' => $this->pad('60px', '30px', '40px'),
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function aboutLegacy(): array
    {
        $entry = fn (string $title, string $body, bool $last = false): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '10px'], [
                'padding' => $this->responsive(['top' => '24px', 'bottom' => '24px']),
                'border' => $this->responsive(['style' => 'solid', 'color' => '#e9e2d4', 'top' => '0', 'right' => '0', 'bottom' => $last ? '0' : '1px', 'left' => '0']),
            ]),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px'], ['alignItems' => $this->responsive('center')]),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => 'calendar-check', 'size' => '22px'], ['color' => $this->responsive(self::CORAL)]),
                        $this->h3($title, self::INK, '24px'),
                    ],
                ],
                $this->text($body, self::MUTED, '15px'),
            ],
        ];

        return $this->design('pebblewood-about-legacy', 'Pebblewood · Timeline', 'content', 'Heading and button on the left, a year-by-year timeline on the right.', [
            $this->pwSection('about-legacy', 'Timeline', [
                $this->row([
                    [
                        ...$this->column(5, [
                            $this->h2('A history of care and early learning', self::INK),
                            $this->pill('Book a visit', '/contact', 'ink', 'calendar-check'),
                        ], ['gap' => $this->responsive('28px')]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(7, [
                            $entry('2025 – Today', 'We support children and families with a safe, joyful place where every child can learn, grow and build confidence.'),
                            $entry('2021 – New programs', 'We added infant care and an after-school club, with fresh play-based projects that grow creativity and curiosity.'),
                            $entry('2016 – A bigger home', 'We moved into a light-filled building with a garden, an art studio and room for more friends.'),
                            $entry('2010 – Our first day', 'Pebblewood opened with one classroom and a big idea: childcare that feels like a second home.', true),
                        ]),
                        'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['gap' => ['desktop' => '64px', 'mobile' => '24px']]),
            ], $this->creamSection()),
        ]);
    }

    /** @return array<string, mixed> */
    private function aboutWhy(): array
    {
        $item = fn (string $number, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [1, 11], 'gap' => '18px', 'stackBelow' => 'none']),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px']),
                    'children' => [
                        $this->text($number, self::INK, '15px', [
                            'backgroundColor' => $this->responsive(self::BUTTER),
                            'borderRadius' => $this->responsive('100px'),
                            'width' => $this->responsive('40px'),
                            'height' => $this->responsive('40px'),
                            'lineHeight' => $this->responsive('40px'),
                            'textAlign' => $this->responsive('center'),
                            'fontWeight' => $this->responsive('600'),
                        ]),
                    ],
                ],
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '8px']),
                    'children' => [
                        $this->h3($title, '#ffffff', '20px'),
                        $this->text($body, 'rgba(255, 255, 255, 0.75)', '15px'),
                    ],
                ],
            ],
        ];

        return $this->design('pebblewood-about-why', 'Pebblewood · Why families choose us', 'content', 'Teal band with a heading and photo beside three numbered reasons.', [
            $this->pwSection('about-why', 'Why families choose us', [
                $this->row([
                    [
                        ...$this->column(6, [
                            $this->h2('Why families choose Pebblewood', '#ffffff'),
                            $this->image('Children at a classroom table', ['height' => ['desktop' => '380px', 'mobile' => '240px'], 'borderRadius' => $this->responsive('32px')], $this->photoUrl('table', 1000)),
                        ], ['gap' => $this->responsive('32px')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(6, [
                            $item('1', 'Safe & nurturing spaces', 'Every child learns in a secure, caring place where their happiness, comfort and wellbeing come first.'),
                            $item('2', 'Qualified early educators', 'Experienced teachers plan age-right activities that grow curiosity, confidence and communication.'),
                            $item('3', 'Flexible care & family support', 'From flexible hours to regular progress updates, we work alongside parents to make childcare simple.'),
                        ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('36px')]),
                        'span' => ['desktop' => 6, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['gap' => ['desktop' => '72px', 'mobile' => '40px'], 'alignItems' => $this->responsive('center')]),
            ], $this->colourSection(self::TEAL)),
        ]);
    }

    /** @return array<string, mixed> */
    private function team(): array
    {
        $member = fn (string $photo, string $name, string $role): array => [
            ...$this->column(4, [
                $this->image($name, ['height' => ['desktop' => '360px', 'mobile' => '300px'], 'borderRadius' => $this->responsive('28px')], $this->photoUrl($photo, 700, 820)),
                $this->h3($name, self::INK, '20px'),
                $this->text($role, self::MUTED, '14px', ['margin' => $this->responsive(['top' => '-10px'])]),
            ], ['custom' => ['--fnl-effect' => 'reveal zoom']]),
            'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
        ];
        $rowStyle = ['gap' => ['desktop' => '24px', 'mobile' => '28px'], 'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '28px']]];

        return $this->design('pebblewood-team', 'Pebblewood · Meet the teachers', 'content', 'Centred heading over six team portraits with names and roles.', [
            $this->pwSection('team', 'Team', [
                $this->row([
                    $this->column(12, [$this->h2("Meet the people who\ninspire every child", self::INK, 'center')]),
                ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $member('p7', 'Sofia Martins', 'Lead preschool teacher'),
                    $member('p2', 'Ben Harper', 'Pre-K teacher'),
                    $member('p5', 'Amara Lewis', 'Toddler room lead'),
                ], $rowStyle),
                $this->row([
                    $member('p6', 'Ryan Patel', 'Outdoor learning teacher'),
                    $member('p8', 'Chloe Bennett', 'Centre director'),
                    $member('p4', 'Elliot Grant', 'Education director'),
                ], [...$rowStyle, 'margin' => $this->responsive(['bottom' => '0'])]),
            ], $this->creamSection()),
        ]);
    }

    /* ------------------------------------------------------------- programs */

    /** @return array<string, mixed> */
    private function programsList(): array
    {
        $rowStyle = ['gap' => ['desktop' => '24px', 'mobile' => '20px'], 'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '20px']]];

        return $this->design('pebblewood-programs-list', 'Pebblewood · Programs page title & cards', 'content', 'Huge centred title, an age-group label and six program cards with photos.', [
            $this->pwSection('page-hero', 'Programs', [
                $this->pageTitle('Learning programs', 'Age-right programs that grow curiosity, confidence and a lifelong love of learning.'),
                $this->row([
                    $this->column(12, [$this->tag('Choose an age group', self::SAND, self::INK, true)]),
                ], ['margin' => ['desktop' => ['bottom' => '32px'], 'mobile' => ['bottom' => '20px']]]),
                $this->row([
                    $this->programCard('infant', 'Ages 6–18 months', 'Infant care', 'Gentle routines, cuddles and sensory play help our youngest friends feel safe and curious.'),
                    $this->programCard('toddler', 'Ages 1–2 years', 'Toddler explorers', 'Music, movement, stories and messy play build words, balance and first friendships.'),
                    $this->programCard('explorers', 'Ages 2–3 years', 'Little discoverers', 'Hands-on projects and pretend play grow independence, language and problem-solving.'),
                ], $rowStyle),
                $this->row([
                    $this->programCard('preschool', 'Ages 3–4 years', 'Preschool', 'Creative projects and guided discovery build early reading, numbers and teamwork.'),
                    $this->programCard('prek', 'Ages 4–5 years', 'Pre-K', 'A confident step towards kindergarten with letters, numbers and big questions.'),
                    $this->programCard('afterschool', 'Ages 5–8 years', 'After-school club', 'Homework help, outdoor games and clubs in a calm, friendly space after school.'),
                ], [...$rowStyle, 'margin' => $this->responsive(['bottom' => '0'])]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '80px', 'right' => '30px', 'bottom' => '120px', 'left' => '30px'],
                    'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '72px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function benefits(): array
    {
        $benefit = fn (string $number, string $title, string $body): array => [
            ...$this->column(6, [
                $this->text($number, self::BUTTER, '15px', ['fontWeight' => $this->responsive('600')]),
                $this->h3($title, '#ffffff', '22px'),
                $this->text($body, 'rgba(255, 255, 255, 0.75)', '15px'),
            ], [
                'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(238, 231, 212, 0.25)', 'top' => '1px', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                'padding' => $this->responsive(['top' => '28px']),
                'gap' => $this->responsive('10px'),
            ]),
            'span' => ['desktop' => 6, 'tablet' => 6, 'mobile' => 12],
        ];
        $rowStyle = ['gap' => ['desktop' => '48px', 'mobile' => '24px'], 'margin' => ['desktop' => ['bottom' => '40px'], 'mobile' => ['bottom' => '24px']]];

        return $this->design('pebblewood-benefits', 'Pebblewood · Program benefits', 'content', 'Teal band with a heading and four numbered benefits in two columns.', [
            $this->pwSection('benefits', 'Program benefits', [
                $this->row([
                    $this->column(12, [$this->h2("Why our programs\nwork for your child", '#ffffff', 'center')]),
                ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]),
                $this->row([
                    $benefit('01', 'Safe & supportive spaces', 'Your child explores in a secure, nurturing place where they can try new things with confidence.'),
                    $benefit('02', 'Learning through play', 'Planned projects and free play work together to grow thinking, social and motor skills.'),
                ], $rowStyle),
                $this->row([
                    $benefit('03', 'Experienced, caring teachers', 'Qualified educators give personal guidance that supports every stage of development.'),
                    $benefit('04', 'Close family communication', 'Daily photos, notes and regular meetings keep you part of your child’s learning.'),
                ], [...$rowStyle, 'margin' => $this->responsive(['bottom' => '0'])]),
            ], $this->colourSection(self::TEAL)),
        ]);
    }

    /* ----------------------------------------------------------------- blog */

    /** @return array<string, mixed> */
    private function blogHero(): array
    {
        return $this->design('pebblewood-blog-hero', 'Pebblewood · Blog title & featured post', 'hero', 'Huge centred title and a featured article card with photo, label, title, excerpt and link.', [
            $this->pwSection('page-hero', 'Blog title', [
                $this->pageTitle('Parent corner', 'Ideas, tips and stories to support your child’s early learning at home.'),
                $this->row([
                    [
                        ...$this->column(7, [$this->image('Smiling child with paint on her face', ['height' => ['desktop' => '440px', 'mobile' => '260px'], 'borderRadius' => $this->responsive('28px')], $this->photoUrl('paint', 1400))]),
                        'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(5, [
                            $this->tag('Featured · 6 min read', self::PEACH, self::INK),
                            $this->h2('How joyful early learning shapes future success', self::INK, 'left', ['fontSize' => ['desktop' => '36px', 'mobile' => '28px'], 'letterSpacing' => $this->responsive('-1px')]),
                            $this->text('Discover how rich early experiences help children build the confidence, curiosity and kindness they carry into school and beyond.', self::MUTED, '15px'),
                            $this->pill('Read article', '/news', 'ink', 'arrow-right'),
                        ], ['justifyContent' => $this->responsive('center'), 'gap' => $this->responsive('20px'), 'padding' => ['desktop' => ['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '8px'], 'mobile' => ['top' => '8px', 'right' => '8px', 'bottom' => '16px', 'left' => '8px']]]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], [
                    'backgroundColor' => $this->responsive(self::CARD),
                    'borderRadius' => $this->responsive('32px'),
                    'padding' => $this->responsive(['top' => '10px', 'right' => '10px', 'bottom' => '10px', 'left' => '10px']),
                    'gap' => ['desktop' => '32px', 'mobile' => '16px'],
                ]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '80px', 'right' => '30px', 'bottom' => '40px', 'left' => '30px'],
                    'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '24px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function blogGrid(): array
    {
        $post = fn (string $photo, string $meta, string $title, string $excerpt): array => [
            ...$this->column(4, [
                $this->image($title, ['height' => ['desktop' => '260px', 'mobile' => '220px'], 'borderRadius' => $this->responsive('24px')], $this->photoUrl($photo, 800)),
                $this->text($meta, self::CORAL, '13px', ['fontWeight' => $this->responsive('500')]),
                $this->h3($title, self::INK, '20px'),
                $this->text($excerpt, self::MUTED, '14px'),
                $this->blockNode(BlockType::Button, [
                    'label' => 'Read more',
                    'action' => 'url',
                    'url' => '/news',
                    'iconRight' => 'arrow-right',
                    'size' => 'sm',
                    'variant' => 'link',
                ], [
                    'fontFamily' => $this->responsive(self::FONT),
                    'color' => $this->responsive(self::INK),
                    'backgroundColor' => $this->responsive('transparent'),
                    'fontSize' => $this->responsive('14px'),
                    'fontWeight' => $this->responsive('600'),
                    'padding' => $this->responsive(['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0']),
                ]),
            ], [
                'gap' => $this->responsive('12px'),
                'custom' => ['--fnl-effect' => 'reveal zoom'],
            ]),
            'span' => ['desktop' => 4, 'tablet' => 6, 'mobile' => 12],
        ];
        $rowStyle = ['gap' => ['desktop' => '24px', 'mobile' => '36px'], 'margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '36px']]];

        return $this->design('pebblewood-blog-grid', 'Pebblewood · Article grid', 'content', 'Six article cards with photo, category and read time, title, excerpt and link.', [
            $this->pwSection('blog-grid', 'Articles', [
                $this->row([
                    $post('readTogether', 'Reading · 5 min read', 'Five easy ways to share stories at bedtime', 'Simple habits that turn reading time into the best part of the day.'),
                    $post('park', 'Outdoors · 4 min read', 'Why outdoor play matters so much', 'Fresh air, big movement and how nature builds brave little explorers.'),
                    $post('drawing', 'Creativity · 6 min read', 'Messy art, big learning', 'What painting, gluing and scribbling teach children about the world.'),
                ], $rowStyle),
                $this->row([
                    $post('afterschool', 'Wellbeing · 5 min read', 'Helping your child through big feelings', 'Calm, kind words and routines that help little ones feel understood.'),
                    $post('nursery', 'Getting started · 7 min read', 'Preparing for the first day of childcare', 'A gentle checklist for a happy, tear-free start for you both.'),
                    $post('blocks', 'Play · 4 min read', 'The quiet power of building blocks', 'How stacking and toppling grows maths, patience and teamwork.'),
                ], [...$rowStyle, 'margin' => $this->responsive(['bottom' => '0'])]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '64px', 'right' => '30px', 'bottom' => '120px', 'left' => '30px'],
                    'mobile' => ['top' => '40px', 'right' => '20px', 'bottom' => '72px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /* -------------------------------------------------------------- contact */

    /** @return array<string, mixed> */
    private function contact(): array
    {
        $detail = fn (string $icon, string $title, string $body): array => [
            ...$this->blockNode(BlockType::Container, ['layout' => 'columns', 'columns' => [1, 11], 'gap' => '16px', 'stackBelow' => 'none']),
            'children' => [
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '0px']),
                    'children' => [
                        $this->blockNode(BlockType::Icon, ['name' => $icon, 'size' => '20px'], [
                            'color' => $this->responsive('#ffffff'),
                            'backgroundColor' => $this->responsive(self::CORAL),
                            'borderRadius' => $this->responsive('100px'),
                            'padding' => $this->responsive(['top' => '12px', 'right' => '12px', 'bottom' => '12px', 'left' => '12px']),
                            'width' => $this->responsive('fit-content'),
                        ]),
                    ],
                ],
                [
                    ...$this->blockNode(BlockType::Container, ['layout' => 'stack', 'gap' => '4px'], ['padding' => $this->responsive(['left' => '10px'])]),
                    'children' => [
                        $this->text($title, self::INK, '16px', ['fontWeight' => $this->responsive('600')]),
                        $this->text($body, self::MUTED, '14px'),
                    ],
                ],
            ],
        ];

        return $this->design('pebblewood-contact', 'Pebblewood · Contact details & form', 'content', 'Huge centred title, a contact details card, a message form card and a rounded map.', [
            $this->pwSection('page-hero', 'Contact', [
                $this->pageTitle('Contact us', 'We would love to show you around. Ask us anything or book a visit and we will reply within one working day.'),
                $this->row([
                    [
                        ...$this->column(5, [
                            $this->h3('Contact information', self::INK, '24px'),
                            $detail('clock', 'Monday – Friday', '7:30 AM – 6:00 PM'),
                            $detail('phone', 'Call us', self::PHONE),
                            $detail('mail', 'Email us', self::EMAIL),
                            $detail('map-pin', 'Visit us', self::ADDRESS),
                        ], [
                            'backgroundColor' => $this->responsive(self::CARD),
                            'borderRadius' => $this->responsive('32px'),
                            'padding' => ['desktop' => ['top' => '40px', 'right' => '36px', 'bottom' => '40px', 'left' => '36px'], 'mobile' => ['top' => '28px', 'right' => '22px', 'bottom' => '28px', 'left' => '22px']],
                            'gap' => $this->responsive('24px'),
                        ]),
                        'span' => ['desktop' => 5, 'tablet' => 12, 'mobile' => 12],
                    ],
                    [
                        ...$this->column(7, [
                            $this->h3('Have a question? Send us a message', self::INK, '24px'),
                            $this->text('Tell us a little about your child and the days you need, and we will reply within one working day with availability and a time to visit.', self::MUTED, '15px'),
                            $this->image('Bright nursery room', ['height' => ['desktop' => '200px', 'mobile' => '160px'], 'borderRadius' => $this->responsive('24px')], $this->photoUrl('nursery', 1000)),
                            [
                                ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px']),
                                'children' => [
                                    $this->pill('Email us', 'mailto:'.self::EMAIL, 'ink', 'mail'),
                                    $this->pill('Call us', 'tel:'.preg_replace('/[^\d+]/', '', self::PHONE), 'cream', 'phone', [
                                        'border' => $this->responsive(['style' => 'solid', 'color' => self::INK, 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
                                    ]),
                                ],
                            ],
                        ], [
                            'backgroundColor' => $this->responsive('#ffffff'),
                            'borderRadius' => $this->responsive('32px'),
                            'padding' => ['desktop' => ['top' => '40px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'], 'mobile' => ['top' => '28px', 'right' => '22px', 'bottom' => '28px', 'left' => '22px']],
                            'gap' => $this->responsive('20px'),
                        ]),
                        'span' => ['desktop' => 7, 'tablet' => 12, 'mobile' => 12],
                    ],
                ], ['gap' => ['desktop' => '24px', 'mobile' => '16px'], 'margin' => ['desktop' => ['bottom' => '24px'], 'mobile' => ['bottom' => '16px']]]),
                $this->row([
                    $this->column(12, [
                        $this->blockNode(BlockType::Map, ['address' => self::ADDRESS, 'latitude' => 45.5152, 'longitude' => -122.6784, 'zoom' => 14], [
                            'height' => ['desktop' => '420px', 'mobile' => '300px'],
                            'borderRadius' => $this->responsive('32px'),
                            'custom' => ['overflow' => 'hidden'],
                        ]),
                    ]),
                ]),
            ], [
                ...$this->creamSection(),
                'padding' => [
                    'desktop' => ['top' => '80px', 'right' => '30px', 'bottom' => '120px', 'left' => '30px'],
                    'mobile' => ['top' => '48px', 'right' => '20px', 'bottom' => '72px', 'left' => '20px'],
                ],
            ]),
        ]);
    }

    /* -------------------------------------------------------------- helpers */

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array{key: string, name: string, category: string, description: string, document: array<string, mixed>}
     */
    private function design(string $key, string $name, string $category, string $description, array $sections): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'document' => ['sections' => $sections],
        ];
    }

    /**
     * A section tagged with the collection, so the renderer's finishing rules apply.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function pwSection(string $block, string $name, array $rows, array $style, string $width = 'full'): array
    {
        $section = $this->section($rows, [
            'fontFamily' => $this->responsive(self::FONT),
            'color' => $this->responsive(self::INK),
            ...$style,
        ], $width);
        $section['name'] = $name;
        $section['settings'] = ['designCollection' => self::COLLECTION, 'designBlock' => $block];

        return $section;
    }

    /** @return array<string, mixed> */
    private function creamSection(): array
    {
        return [
            'backgroundColor' => $this->responsive(self::CREAM),
            'padding' => $this->pad('120px', '30px', '72px'),
        ];
    }

    /** @return array<string, mixed> */
    private function colourSection(string $colour): array
    {
        return [
            'backgroundColor' => $this->responsive($colour),
            'padding' => $this->pad('120px', '30px', '72px'),
        ];
    }

    /** @return array<string, mixed> */
    private function pageTitle(string $title, string $intro): array
    {
        return $this->row([
            $this->column(12, [
                $this->h1($title, self::INK, 'center'),
                $this->text($intro, self::MUTED, '16px', [
                    'textAlign' => $this->responsive('center'),
                    'maxWidth' => $this->responsive('560px'),
                    'margin' => $this->responsive(['left' => 'auto', 'right' => 'auto']),
                ]),
            ], ['gap' => $this->responsive('20px')]),
        ], ['margin' => ['desktop' => ['bottom' => '56px'], 'mobile' => ['bottom' => '32px']]]);
    }

    /**
     * The logo image (white version over photos). Replace it with your own in the block's
     * "Logo image" field; with no image, the alt text shows as the logo.
     *
     * @return array<string, mixed>
     */
    private function logo(bool $light): array
    {
        return $this->blockNode(BlockType::Logo, [
            'src' => $light ? self::LOGO_LIGHT : self::LOGO,
            'alt' => 'Pebblewood',
            'url' => '/',
        ], [
            'fontFamily' => $this->responsive(self::HEADING_FONT),
            'color' => $this->responsive($light ? '#ffffff' : self::INK),
            'fontSize' => ['desktop' => '30px', 'mobile' => '26px'],
            'height' => ['desktop' => '40px', 'mobile' => '34px'],
        ]);
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function h1(string $text, string $colour, string $align = 'left', array $extra = []): array
    {
        return $this->heading($text, 'h1', [
            'fontFamily' => $this->responsive(self::HEADING_FONT),
            'color' => $this->responsive($colour),
            'fontSize' => ['desktop' => '90px', 'tablet' => '64px', 'mobile' => '46px'],
            'fontWeight' => $this->responsive('700'),
            'lineHeight' => $this->responsive('1.05'),
            'letterSpacing' => ['desktop' => '-4px', 'mobile' => '-1.5px'],
            'textAlign' => $this->responsive($align),
            ...$extra,
        ]);
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function h2(string $text, string $colour, string $align = 'left', array $extra = []): array
    {
        return $this->heading($text, 'h2', [
            'fontFamily' => $this->responsive(self::HEADING_FONT),
            'color' => $this->responsive($colour),
            'fontSize' => ['desktop' => '48px', 'mobile' => '32px'],
            'fontWeight' => $this->responsive('600'),
            'lineHeight' => $this->responsive('1.2'),
            'letterSpacing' => ['desktop' => '-1.5px', 'mobile' => '-0.8px'],
            'textAlign' => $this->responsive($align),
            ...$extra,
        ]);
    }

    /** @return array<string, mixed> */
    private function h3(string $text, string $colour, string $size, string $align = 'left'): array
    {
        return $this->heading($text, 'h3', [
            'fontFamily' => $this->responsive(self::HEADING_FONT),
            'color' => $this->responsive($colour),
            'fontSize' => $this->responsive($size),
            'fontWeight' => $this->responsive('600'),
            'lineHeight' => $this->responsive('1.3'),
            'letterSpacing' => $this->responsive('-0.4px'),
            'textAlign' => $this->responsive($align),
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
            'fontFamily' => $this->responsive(self::FONT),
            'color' => $this->responsive($colour),
            'fontSize' => is_array($size) ? $size : $this->responsive($size),
            'lineHeight' => $this->responsive('1.7'),
            'margin' => $this->responsive('0'),
            ...$extra,
        ]);
    }

    /** A small rounded label. @return array<string, mixed> */
    private function tag(string $text, string $background, string $colour, bool $centred = false): array
    {
        return $this->text($text, $colour, '13px', [
            'backgroundColor' => $this->responsive($background),
            'borderRadius' => $this->responsive('100px'),
            'padding' => $this->responsive(['top' => '6px', 'right' => '14px', 'bottom' => '5px', 'left' => '14px']),
            'width' => $this->responsive('fit-content'),
            'fontWeight' => $this->responsive('500'),
            'letterSpacing' => $this->responsive('0.02em'),
            'maxWidth' => $this->responsive('none'),
            'margin' => $centred ? $this->responsive(['left' => 'auto', 'right' => 'auto']) : $this->responsive('0'),
        ]);
    }

    /**
     * A pill button: cream, ink, or outline (white border on a photo).
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function pill(string $label, string $url, string $tone, ?string $icon = null, array $extra = []): array
    {
        [$background, $colour, $border] = match ($tone) {
            'ink' => [self::BUTTON, self::ON_BUTTON, self::BUTTON],
            'outline' => ['transparent', '#ffffff', '#ffffff'],
            default => ['#fcf6e7', self::INK, '#fcf6e7'],
        };

        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'iconLeft' => $icon,
            'size' => 'md',
            'variant' => $tone === 'outline' ? 'secondary' : 'primary',
        ], [
            'fontFamily' => $this->responsive(self::FONT),
            'backgroundColor' => $this->responsive($background),
            'color' => $this->responsive($colour),
            'border' => $this->responsive(['style' => 'solid', 'color' => $border, 'top' => '1px', 'right' => '1px', 'bottom' => '1px', 'left' => '1px']),
            'borderRadius' => $this->responsive('100px'),
            'fontSize' => $this->responsive('14px'),
            'fontWeight' => $this->responsive('500'),
            'padding' => $this->responsive(['top' => '15px', 'right' => '24px', 'bottom' => '14px', 'left' => '20px']),
            ...$extra,
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
