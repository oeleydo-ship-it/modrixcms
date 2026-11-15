<?php

namespace App\Cms\Ai;

use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Cms\Extensions\Package;
use App\Cms\Themes\ThemeDemo;
use App\Cms\Themes\ThemeManager;
use App\Support\Settings;
use App\Support\Slugs;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * AI full-site generation in the active theme's own designs.
 *
 * A theme that ships a demo (Appearance → Import demo) is a design kit: its demo pages,
 * header and footer are made of the theme's sections. Generation runs in three steps, each
 * its own request so the admin sees progress and no request runs too long:
 *
 *  1. plan()        one AI call: brand details, the pages, and for each page which of the
 *                   theme's sections to use, in order. Returns skeleton documents.
 *  2. writePage()   one AI call per page (two for very long pages): every text in the
 *                   page's sections is rewritten for the business; links point at the new
 *                   pages; theme logos give way to the brand name.
 *  3. writeChrome() one AI call: the theme's global header and footer, rewritten the same
 *                   way, with the navigation rebuilt from the generated pages.
 *
 * The result has the same shape as WebsiteGenerator's plan, so the same preview and
 * apply flow saves it (header and footer go to the active theme's own).
 */
class ThemeSiteGenerator
{
    /** Texts sent to the AI in one call. */
    private const SLOTS_PER_CALL = 110;

    public function __construct(
        private AiClient $ai,
        private ThemeManager $themes,
        private ThemeDemo $demo,
        private DocumentUpgrader $upgrader,
        private DocumentValidator $validator,
        private Settings $settings,
    ) {}

    /**
     * What the active theme offers to the generator, or null when it ships no demo.
     *
     * @return array{theme: string, label: string, pages: list<string>, section_count: int}|null
     */
    public function summary(): ?array
    {
        $kit = $this->kit();

        return $kit === null ? null : [
            'theme' => $kit['theme'],
            'label' => $kit['label'],
            'pages' => array_values(array_map(fn (array $page): string => $page['title'], $kit['pages'])),
            'section_count' => count($kit['sections']),
        ];
    }

    /**
     * Step 1: the site plan, with each page's sections taken from the theme.
     *
     * @param  array{prompt: string, tone?: ?string, brand_name?: ?string, pages?: list<string>}  $input
     * @return array<string, mixed>
     */
    public function plan(array $input): array
    {
        $kit = $this->kit() ?? throw new RuntimeException('The active theme has no demo designs to build with.');
        $requested = array_values(array_filter(array_map(fn (mixed $title): string => trim((string) $title), $input['pages'] ?? [])));
        $requested = $requested !== [] ? array_slice($requested, 0, 12) : array_column($kit['pages'], 'title');

        $raw = $this->ai->completeJson($this->planSystemPrompt(), $this->planUserPrompt($input, $requested, $kit));

        return $this->compilePlan($raw, $requested, $input, $kit);
    }

    /**
     * Turns the AI's plan into page skeletons. Public for tests.
     *
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $requested
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>|null  $kit
     * @return array<string, mixed>
     */
    public function compilePlan(array $raw, array $requested, array $input, ?array $kit = null): array
    {
        $kit ??= $this->kit() ?? throw new RuntimeException('The active theme has no demo designs to build with.');
        $brand = $this->brand($raw['brand'] ?? [], $input);
        $rawPages = is_array($raw['pages'] ?? null) && $raw['pages'] !== [] ? $raw['pages'] : array_map(fn (string $title): array => ['title' => $title], $requested);

        $pages = [];
        $used = [];

        foreach (array_values($rawPages) as $index => $rawPage) {
            if (! is_array($rawPage) || count($pages) >= 12) {
                continue;
            }

            $title = mb_substr(trim((string) ($rawPage['title'] ?? '')) ?: ($requested[$index] ?? 'Page '.($index + 1)), 0, 120);
            $slug = $this->uniqueSlug((string) ($rawPage['slug'] ?? $title), $used);
            $isHome = $pages === [] ? true : false;
            $ids = $this->validSectionIds($rawPage['sections'] ?? [], $kit) ?: $this->fallbackSections($title, $kit, $isHome);
            $sections = array_map(fn (string $id): array => $kit['sections'][$id]['section'], $ids);

            $pages[] = [
                'title' => $title,
                'slug' => $isHome ? 'home' : $slug,
                'is_home' => $isHome,
                'show_in_menu' => true,
                'menu_order' => count($pages),
                'nav_label' => mb_substr(trim((string) ($rawPage['nav_label'] ?? '')) ?: $title, 0, 40),
                'meta_title' => mb_substr(trim((string) ($rawPage['meta_title'] ?? '')) ?: $title.' · '.$brand['site_name'], 0, 120),
                'meta_description' => mb_substr(trim((string) ($rawPage['meta_description'] ?? '')), 0, 300),
                'purpose' => mb_substr(trim((string) ($rawPage['purpose'] ?? '')), 0, 300),
                'show_site_header' => ! ($kit['sections'][$ids[0]]['has_header'] ?? false),
                'show_site_footer' => true,
                'section_ids' => $ids,
                'section_count' => count($sections),
                'outline' => array_map(fn (string $id): string => $kit['sections'][$id]['name'], $ids),
                'written' => false,
                'document' => $this->document($sections),
            ];
        }

        $slugs = array_column($pages, 'slug');
        $cta = Slugs::from((string) ($raw['cta_slug'] ?? '')) ?: '';
        if (! in_array($cta, $slugs, true)) {
            $cta = collect($slugs)->first(fn (string $slug): bool => str_contains($slug, 'contact')) ?? ($slugs[count($slugs) - 1] ?? 'home');
        }

        return [
            'brand' => $brand,
            'theme' => ['name' => $kit['theme'], 'label' => $kit['label']],
            'prompt' => mb_substr((string) ($input['prompt'] ?? ''), 0, 4000),
            'tone' => mb_substr((string) ($input['tone'] ?? 'Modern & clear'), 0, 100),
            'cta_slug' => $cta,
            'pages' => $pages,
            'header_document' => $kit['header'] !== null ? $this->document($kit['header']['sections']) : null,
            'footer_document' => $kit['footer'] !== null ? $this->document($kit['footer']['sections']) : null,
            'header_links' => $this->navItems($pages),
            'chrome_written' => false,
        ];
    }

    /**
     * Step 2: rewrites one page's texts for the business.
     *
     * @param  array<string, mixed>  $site  brand, prompt, tone, cta_slug and the pages (title, slug, nav_label, is_home, show_in_menu)
     * @param  array<string, mixed>  $page  the page from the plan, with its skeleton document
     * @return array<string, mixed> the page with its written document
     */
    public function writePage(array $site, array $page): array
    {
        $document = $this->upgrader->upgrade(is_array($page['document'] ?? null) ? $page['document'] : []);
        $this->validator->validate($document);

        $document = $this->rewrite($document, $site, 'the "'.($page['title'] ?? 'Page').'" page'.(filled($page['purpose'] ?? null) ? ' ('.$page['purpose'].')' : ''));
        $document = $this->finish($document, $site);
        $this->validator->validate($document);

        return [...$page, 'document' => $document, 'written' => true];
    }

    /**
     * Step 3: rewrites the theme's global header and footer, with navigation to the new pages.
     *
     * @param  array<string, mixed>  $site
     * @param  array<string, mixed>|null  $header
     * @param  array<string, mixed>|null  $footer
     * @return array{header_document: ?array<string, mixed>, footer_document: ?array<string, mixed>}
     */
    public function writeChrome(array $site, ?array $header, ?array $footer): array
    {
        $combined = ['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => [
            ...($header['sections'] ?? []),
            ...($footer['sections'] ?? []),
        ]];
        $this->validator->validate($combined);
        $headerCount = count($header['sections'] ?? []);

        $combined = $this->finish($this->rewrite($combined, $site, 'the site-wide header and footer'), $site, footerFrom: $headerCount);

        return [
            'header_document' => $header === null ? null : [...$header, 'sections' => array_slice($combined['sections'], 0, $headerCount)],
            'footer_document' => $footer === null ? null : [...$footer, 'sections' => array_slice($combined['sections'], $headerCount)],
        ];
    }

    /* ------------------------------------------------------------------- kit */

    /**
     * The active theme's demo as a catalogue of sections.
     *
     * @return array{theme: string, label: string, pages: array<string, array{title: string, ids: list<string>}>, sections: array<string, array{id: string, name: string, page: string, summary: string, has_header: bool, section: array<string, mixed>}>, header: ?array<string, mixed>, footer: ?array<string, mixed>}|null
     */
    public function kit(): ?array
    {
        $theme = $this->themes->theme();

        if (! $theme instanceof Package || ! $this->demo->has($theme)) {
            return null;
        }

        $demo = $theme->manifest->data['demo'];
        $sections = [];
        $pages = [];
        $seen = [];

        foreach ($demo['pages'] ?? [] as $definition) {
            $document = $this->demo->document($theme, $definition['file']);
            $pageSlug = (string) $definition['slug'];
            $ids = [];

            foreach (array_values($document['sections']) as $index => $section) {
                $texts = $this->texts($section, 4);
                $fingerprint = ($section['settings']['designBlock'] ?? $section['name'] ?? '').'|'.($texts[0] ?? '');

                if (isset($seen[$fingerprint])) {
                    $ids[] = $seen[$fingerprint];

                    continue;
                }

                $id = "{$pageSlug}.{$index}";
                $seen[$fingerprint] = $id;
                $ids[] = $id;
                $sections[$id] = [
                    'id' => $id,
                    'name' => (string) ($section['name'] ?? Str::headline((string) ($section['settings']['designBlock'] ?? 'Section'))),
                    'page' => (string) $definition['title'],
                    'summary' => implode(' | ', $texts),
                    'has_header' => $this->hasSiteNav($section),
                    'section' => $section,
                ];
            }

            $pages[$pageSlug] = ['title' => (string) $definition['title'], 'ids' => $ids];
        }

        return [
            'theme' => $theme->name,
            'label' => $theme->displayName(),
            'pages' => $pages,
            'sections' => $sections,
            'header' => isset($demo['header']) ? $this->demo->document($theme, $demo['header']) : null,
            'footer' => isset($demo['footer']) ? $this->demo->document($theme, $demo['footer']) : null,
        ];
    }

    /* ---------------------------------------------------------------- prompts */

    private function planSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior web strategist planning a complete website that will be built from an existing theme's section designs.
You choose which designed sections each page uses; the copy is written in a later step.
Return ONLY valid JSON:
{
  "brand": {"site_name": "string", "tagline": "string (1 sentence)", "footer_text": "string", "email": "string", "phone": "string", "address": "string"},
  "cta_slug": "slug of the page visitors should end up on (usually contact)",
  "pages": [
    {"title": "string", "slug": "lowercase-kebab-case", "nav_label": "short menu label", "purpose": "one sentence: what this page must achieve",
     "meta_title": "<= 65 chars", "meta_description": "<= 155 chars", "sections": ["section id", "..."]}
  ]
}
Rules:
- Create exactly the requested pages, in that order; the first is the home page.
- Use ONLY section ids from the catalogue. Never use one section twice on the same page.
- Start every page with a section marked HERO. The home page uses 6 to 10 sections; other pages 3 to 7.
- Pick sections whose purpose fits the page (plans or fees for pricing, FAQ sections for questions, contact sections for contact, team or timeline for about).
- Brand details must fit the business brief; invent realistic contact details when none are given.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $requested
     * @param  array<string, mixed>  $kit
     */
    private function planUserPrompt(array $input, array $requested, array $kit): string
    {
        $catalogue = [];
        foreach ($kit['sections'] as $id => $section) {
            $hero = $this->isHeroCandidate($id, $kit) ? ' [HERO]' : '';
            $catalogue[] = "- {$id}{$hero}: {$section['name']} (from the theme's {$section['page']} page) — ".mb_substr($section['summary'], 0, 180);
        }

        $brandName = trim((string) ($input['brand_name'] ?? ''));

        return "Business brief:\n".trim((string) $input['prompt'])."\n\n"
            .'Tone: '.trim((string) ($input['tone'] ?? 'Modern & clear'))."\n"
            .($brandName !== '' ? "Brand name: {$brandName}\n" : "Invent a fitting brand name if the brief has none.\n")
            .'Pages to create, in order: '.implode(', ', $requested)."\n\n"
            ."Section catalogue of the {$kit['label']} theme:\n".implode("\n", $catalogue);
    }

    private function writeSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior website copywriter. An existing, finished page design is being reused for a new business.
Rewrite every text so it is specific, natural and convincing for the new business, keeping the design's structure.
Return ONLY valid JSON: {"texts": {"<id>": "<new text>", ...}} with an entry for every id you were given.
Rules:
- Keep each text about the same length as the original and never longer than its "max" characters.
- Keep the same role: headings stay headings, button labels stay 1 to 4 words, questions stay questions, names stay person names.
- Keep inline HTML tags exactly as they appear (<b>, <i>, <u>, <br>, <a href="...">); you may change the words inside them. Keep "\n" line breaks in headings.
- Replace every mention of the old brand with the new brand. Use the brand's email, phone and address wherever contact details appear.
- Numbers and statistics must stay plausible for the new business; prices keep their currency format.
- Image descriptions ("alt") describe a photo that suits the new business.
- Write in the language of the business brief.
PROMPT;
    }

    /* -------------------------------------------------------------- rewriting */

    /**
     * Sends the document's texts to the AI in batches and writes the answers back.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    private function rewrite(array $document, array $site, string $what): array
    {
        $slots = $this->slots($document);
        $answers = [];

        foreach (array_chunk($slots, self::SLOTS_PER_CALL, true) as $batch) {
            $lines = [];
            foreach ($batch as $id => $slot) {
                $lines[] = json_encode(['id' => $id, 'kind' => $slot['kind'], 'text' => $slot['text'], 'max' => $slot['max']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $reply = $this->ai->completeJson($this->writeSystemPrompt(), $this->siteContext($site)."\nYou are writing {$what}.\n\nTexts (one JSON object per line):\n".implode("\n", $lines), [], 200);
            $answers += is_array($reply['texts'] ?? null) ? $reply['texts'] : [];
        }

        $brand = (string) ($site['brand']['site_name'] ?? '');
        $oldBrand = (string) ($site['theme']['label'] ?? '');

        return $this->apply($document, function (string $id, string $original, string $kind) use ($answers, $slots, $brand, $oldBrand): string {
            $text = is_string($answers[$id] ?? null) && trim($answers[$id]) !== '' ? $answers[$id] : $original;
            $text = $this->clean($text, $kind, $slots[$id]['max'] ?? 400);

            return $oldBrand !== '' && $brand !== '' ? str_replace($oldBrand, $brand, $text) : $text;
        });
    }

    /** @param  array<string, mixed>  $site */
    private function siteContext(array $site): string
    {
        $brand = $site['brand'] ?? [];
        $pages = implode(', ', array_map(fn (array $page): string => ($page['title'] ?? '').' (/'.($page['slug'] ?? '').')', $site['pages'] ?? []));

        return 'Business brief: '.trim((string) ($site['prompt'] ?? ''))."\n"
            .'Tone: '.($site['tone'] ?? 'Modern & clear')."\n"
            .'Brand: '.($brand['site_name'] ?? '').' — '.($brand['tagline'] ?? '')."\n"
            .'Contact: '.implode(' · ', array_filter([$brand['email'] ?? null, $brand['phone'] ?? null, $brand['address'] ?? null]))."\n"
            .'Old brand to replace: '.($site['theme']['label'] ?? '')."\n"
            ."Site pages: {$pages}";
    }

    /**
     * Every rewritable text in the document, in a fixed order.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, array{kind: string, text: string, max: int}>
     */
    private function slots(array $document): array
    {
        $slots = [];
        $this->apply($document, function (string $id, string $text, string $kind) use (&$slots): string {
            $slots[$id] = ['kind' => $kind, 'text' => $text, 'max' => max(24, (int) ceil(mb_strlen(strip_tags($text)) * 1.4))];

            return $text;
        });

        return $slots;
    }

    /**
     * Walks every block in document order and passes each text through $write.
     *
     * @param  array<string, mixed>  $document
     * @param  callable(string, string, string): string  $write  (id, text, kind) => new text
     * @return array<string, mixed>
     */
    private function apply(array $document, callable $write): array
    {
        $counter = 0;
        $visit = function (string $text, string $kind) use (&$counter, $write): string {
            if (! preg_match('/[\pL]/u', strip_tags($text))) {
                return $text;
            }

            return $write('t'.(++$counter), $text, $kind);
        };

        $walk = function (array $block) use (&$walk, $visit): array {
            $props = $block['props'] ?? [];

            switch ($block['type'] ?? '') {
                case 'heading':
                    $props['text'] = $visit((string) ($props['text'] ?? ''), 'heading');
                    break;
                case 'paragraph':
                    $props['text'] = $visit((string) ($props['text'] ?? ''), 'text');
                    break;
                case 'button':
                    $props['label'] = $visit((string) ($props['label'] ?? ''), 'button');
                    break;
                case 'image':
                    if (filled($props['alt'] ?? null)) {
                        $props['alt'] = $visit((string) $props['alt'], 'alt');
                    }
                    break;
                case 'progress_bar':
                    $props['label'] = $visit((string) ($props['label'] ?? ''), 'label');
                    break;
                case 'testimonial':
                    foreach (['quote' => 'quote', 'authorName' => 'name', 'authorTitle' => 'role'] as $key => $kind) {
                        $props[$key] = $visit((string) ($props[$key] ?? ''), $kind);
                    }
                    break;
                case 'faq':
                    foreach ($props['items'] ?? [] as $i => $item) {
                        $props['items'][$i]['question'] = $visit((string) ($item['question'] ?? ''), 'question');
                        $props['items'][$i]['answer'] = $visit((string) ($item['answer'] ?? ''), 'answer');
                    }
                    break;
                case 'tabs':
                    foreach ($props['tabs'] ?? [] as $i => $tab) {
                        $props['tabs'][$i]['label'] = $visit((string) ($tab['label'] ?? ''), 'tab');
                    }
                    break;
                case 'feature_cards':
                    foreach ($props['items'] ?? [] as $i => $item) {
                        $props['items'][$i]['title'] = $visit((string) ($item['title'] ?? ''), 'heading');
                        $props['items'][$i]['body'] = $visit((string) ($item['body'] ?? ''), 'text');
                    }
                    break;
                case 'navigation':
                    foreach ($props['items'] ?? [] as $i => $item) {
                        $props['items'][$i]['label'] = $visit((string) ($item['label'] ?? ''), 'link');
                    }
                    break;
            }

            $block['props'] = $props;
            if (is_array($block['children'] ?? null)) {
                $block['children'] = array_map($walk, $block['children']);
            }

            return $block;
        };

        foreach ($document['sections'] ?? [] as $s => $section) {
            foreach ($section['rows'] ?? [] as $r => $row) {
                foreach ($row['columns'] ?? [] as $c => $column) {
                    $document['sections'][$s]['rows'][$r]['columns'][$c]['blocks'] = array_map($walk, $column['blocks'] ?? []);
                }
            }
        }

        return $document;
    }

    /** Keeps the AI's text to the inline markup a block allows and to a sane length. */
    private function clean(string $text, string $kind, int $max): string
    {
        $allowed = in_array($kind, ['heading', 'text', 'answer', 'quote'], true) ? '<b><i><u><em><strong><a><br>' : '';
        $text = trim(strip_tags($text, $allowed));
        $text = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $text) ?? $text;

        return mb_strlen(strip_tags($text)) > $max * 1.3 ? Str::limit(strip_tags($text), (int) ($max * 1.2), '…') : $text;
    }

    /**
     * Links to the new pages, the brand's contact details, the brand name instead of theme
     * logos, and site navigation listing the generated pages.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    private function finish(array $document, array $site, ?int $footerFrom = null): array
    {
        $navItems = $this->navItems($site['pages'] ?? []);
        $footerNavDone = false;

        $walk = function (array $block, bool $inFooter) use (&$walk, $site, $navItems, &$footerNavDone): array {
            $props = $block['props'] ?? [];

            foreach (['url', 'link'] as $key) {
                if (is_string($props[$key] ?? null)) {
                    $props[$key] = $this->remap($props[$key], $site);
                }
            }

            if (in_array($block['type'] ?? '', ['heading', 'paragraph'], true) && is_string($props['text'] ?? null)) {
                $props['text'] = preg_replace_callback('/href\s*=\s*"([^"]*)"/i', fn (array $m): string => 'href="'.e($this->remap(html_entity_decode($m[1]), $site)).'"', $props['text']) ?? $props['text'];
            }

            if (($block['type'] ?? '') === 'logo') {
                $props['src'] = null;
                $props['alt'] = (string) ($site['brand']['site_name'] ?? $props['alt'] ?? '');
                $props['url'] = '/';
            }

            if (($block['type'] ?? '') === 'navigation') {
                $isSiteNav = ($props['orientation'] ?? 'horizontal') === 'horizontal';
                if ($isSiteNav || ($inFooter && ! $footerNavDone)) {
                    $props['items'] = $navItems;
                    $footerNavDone = $footerNavDone || $inFooter;
                } else {
                    foreach ($props['items'] ?? [] as $i => $item) {
                        $props['items'][$i]['url'] = $this->remap((string) ($item['url'] ?? '/'), $site);
                    }
                }
            }

            $block['props'] = $props;
            if (is_array($block['children'] ?? null)) {
                $block['children'] = array_map(fn (array $child): array => $walk($child, $inFooter), $block['children']);
            }

            return $block;
        };

        foreach ($document['sections'] ?? [] as $s => $section) {
            $inFooter = $footerFrom !== null && $s >= $footerFrom;
            foreach ($section['rows'] ?? [] as $r => $row) {
                foreach ($row['columns'] ?? [] as $c => $column) {
                    $document['sections'][$s]['rows'][$r]['columns'][$c]['blocks'] = array_map(fn (array $block): array => $walk($block, $inFooter), $column['blocks'] ?? []);
                }
            }
        }

        return $document;
    }

    /** @param  array<string, mixed>  $site */
    private function remap(string $url, array $site): string
    {
        $url = trim($url);
        $brand = $site['brand'] ?? [];

        if ($url === '' || str_starts_with($url, '#') || preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_starts_with(strtolower($url), 'mailto:')) {
            return filled($brand['email'] ?? null) ? 'mailto:'.$brand['email'] : $url;
        }

        if (str_starts_with(strtolower($url), 'tel:')) {
            $digits = preg_replace('/[^\d+]/', '', (string) ($brand['phone'] ?? ''));

            return $digits !== '' ? 'tel:'.$digits : $url;
        }

        if (! str_starts_with($url, '/')) {
            return $url;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $pages = $site['pages'] ?? [];
        $slugs = array_column($pages, 'slug');
        $home = collect($pages)->firstWhere('is_home', true)['slug'] ?? 'home';

        if ($path === '' || $path === $home) {
            return '/'.($fragment ? "#{$fragment}" : '');
        }

        if (in_array($path, $slugs, true)) {
            return "/{$path}".($fragment ? "#{$fragment}" : '');
        }

        $cta = (string) ($site['cta_slug'] ?? '');

        return in_array($cta, $slugs, true) && $cta !== $home ? "/{$cta}" : '/';
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array{label: string, url: string}>
     */
    private function navItems(array $pages): array
    {
        $items = [];

        foreach ($pages as $page) {
            if (! ($page['show_in_menu'] ?? true)) {
                continue;
            }
            $label = trim((string) ($page['nav_label'] ?? '')) ?: (string) ($page['title'] ?? '');
            $items[] = ['label' => mb_substr($label, 0, 40), 'url' => ($page['is_home'] ?? false) ? '/' : '/'.($page['slug'] ?? '')];
        }

        return $items;
    }

    /* ---------------------------------------------------------------- helpers */

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private function document(array $sections): array
    {
        return $this->freshIds(['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => array_values($sections)]);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function freshIds(array $node): array
    {
        foreach (['sections', 'rows', 'columns', 'blocks', 'children'] as $key) {
            if (is_array($node[$key] ?? null)) {
                $node[$key] = array_map(function (mixed $child): mixed {
                    if (is_array($child) && isset($child['id'])) {
                        $child['id'] = (string) Str::ulid();
                    }

                    return is_array($child) ? $this->freshIds($child) : $child;
                }, $node[$key]);
            }
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<string>
     */
    private function texts(array $section, int $limit): array
    {
        $texts = [];
        array_walk_recursive($section, function (mixed $value, mixed $key) use (&$texts): void {
            if (in_array((string) $key, ['text', 'label', 'question'], true) && is_string($value) && preg_match('/\pL/u', $value)) {
                $texts[] = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', "\n"], ' ', $value))) ?? ''), 0, 70);
            }
        });

        return array_slice(array_values(array_unique(array_filter($texts))), 0, $limit);
    }

    /** @param  array<string, mixed>  $section */
    private function hasSiteNav(array $section): bool
    {
        $found = false;
        array_walk_recursive($section, function (mixed $value, mixed $key) use (&$found): void {
            $found = $found || ($key === 'type' && $value === 'navigation');
        });

        return $found;
    }

    /** @param  array<string, mixed>  $kit */
    private function isHeroCandidate(string $id, array $kit): bool
    {
        foreach ($kit['pages'] as $page) {
            if (($page['ids'][0] ?? null) === $id) {
                return true;
            }
        }

        return str_contains(strtolower($kit['sections'][$id]['name'] ?? ''), 'hero');
    }

    /**
     * @param  array<string, mixed>  $kit
     * @return list<string>
     */
    private function validSectionIds(mixed $ids, array $kit): array
    {
        $valid = [];
        foreach (is_array($ids) ? $ids : [] as $id) {
            $id = (string) $id;
            if (isset($kit['sections'][$id]) && ! in_array($id, $valid, true)) {
                $valid[] = $id;
            }
        }

        return array_slice($valid, 0, 12);
    }

    /**
     * The theme's own page closest to the title, or its home page.
     *
     * @param  array<string, mixed>  $kit
     * @return list<string>
     */
    private function fallbackSections(string $title, array $kit, bool $isHome): array
    {
        $needle = Str::lower($title);
        $best = null;
        $bestScore = -1;

        foreach ($kit['pages'] as $slug => $page) {
            similar_text($needle, Str::lower($page['title'].' '.$slug), $score);
            if (! $isHome && $score > $bestScore) {
                $best = $page;
                $bestScore = $score;
            }
        }

        $page = $isHome || $best === null ? (array_values($kit['pages'])[0] ?? ['ids' => []]) : $best;

        return array_values(array_unique($page['ids']));
    }

    /** @param  array<string, bool>  $used */
    private function uniqueSlug(string $raw, array &$used): string
    {
        $base = Slugs::from($raw) ?: 'page';
        if (in_array($base, Slugs::RESERVED, true)) {
            $base .= '-page';
        }
        $slug = $base;
        for ($n = 2; isset($used[$slug]); $n++) {
            $slug = "{$base}-{$n}";
        }
        $used[$slug] = true;

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{site_name: string, tagline: string, footer_text: string, email: string, phone: string, address: string}
     */
    private function brand(mixed $raw, array $input): array
    {
        $raw = is_array($raw) ? $raw : [];
        $name = trim((string) ($input['brand_name'] ?? '')) ?: trim((string) ($raw['site_name'] ?? '')) ?: (string) $this->settings->get('site_name', 'My site');

        return [
            'site_name' => mb_substr($name, 0, 100),
            'tagline' => mb_substr(trim((string) ($raw['tagline'] ?? '')), 0, 200),
            'footer_text' => mb_substr(trim((string) ($raw['footer_text'] ?? '')) ?: "{$name} · All rights reserved.", 0, 300),
            'email' => mb_substr(trim((string) ($raw['email'] ?? '')), 0, 120),
            'phone' => mb_substr(trim((string) ($raw['phone'] ?? '')), 0, 40),
            'address' => mb_substr(trim((string) ($raw['address'] ?? '')), 0, 200),
        ];
    }
}
