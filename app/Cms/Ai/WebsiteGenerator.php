<?php

namespace App\Cms\Ai;

use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\BuildsDocumentNodes;
use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Cms\Builder\SiteChrome;
use App\Cms\Themes\DesignTokens;
use App\Models\Page;
use App\Support\Settings;
use App\Support\Slugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates single pages or full multi-page websites with AI and compiles them
 * into validated visual-builder PageDocuments + global header/footer chrome.
 */
class WebsiteGenerator
{
    use BuildsDocumentNodes;

    private const ALLOWED_ICONS = [
        'zap', 'shield', 'check', 'star', 'heart', 'globe', 'layers', 'users',
        'target', 'compass', 'workflow', 'clock', 'mail', 'phone', 'map-pin',
        'package', 'boxes', 'book-open', 'building-2', 'home', 'play', 'arrow-right',
    ];

    public function __construct(
        private AiClient $ai,
        private DocumentUpgrader $upgrader,
        private DocumentValidator $validator,
        private PageDocuments $documents,
        private SiteChrome $chrome,
        private Settings $settings,
    ) {}

    /**
     * Generates a validated multi-page website, single page, or existing-page redesign preview.
     *
     * @param  array{
     *     prompt: string,
     *     mode: string,
     *     tone?: string,
     *     pages?: list<string>,
     *     brand_name?: ?string,
     *     page_title?: ?string,
     *     target_page_id?: ?int,
     *     current_document?: ?array<string, mixed>,
     * }  $input
     * @return array<string, mixed>
     */
    public function generate(array $input): array
    {
        $mode = (string) ($input['mode'] ?? 'multipage');
        $isMultipage = $mode === 'multipage';
        $targetPage = $mode === 'redesign'
            ? Page::query()->regular()->findOrFail((int) ($input['target_page_id'] ?? 0))
            : null;
        $currentDocument = $targetPage !== null && is_array($input['current_document'] ?? null)
            ? $input['current_document']
            : null;

        $requestedPages = $targetPage !== null
            ? [$targetPage->title]
            : $this->resolveRequestedPages($input, $isMultipage);

        $blueprint = $this->ai->completeJson(
            $this->systemPrompt($isMultipage, $targetPage !== null),
            $this->userPrompt($input, $requestedPages, $isMultipage, $targetPage, $currentDocument),
        );

        return $this->compileBlueprint($blueprint, $requestedPages, $isMultipage, $input, $targetPage);
    }

    /**
     * Persists a generated website plan into pages, global header/footer, and site settings.
     *
     * @param  array<string, mixed>  $plan
     * @param  array{
     *     selected_slugs?: list<string>,
     *     replace_existing?: bool,
     *     publish_immediately?: bool,
     *     update_site_chrome?: bool,
     *     update_site_settings?: bool,
     * }  $options
     * @return array{
     *     pages: list<array{id: int, title: string, slug: string, is_home: bool, is_published: bool, url: string, builder_url: string}>,
     *     header_url: ?string,
     *     footer_url: ?string,
     * }
     */
    public function apply(array $plan, int $userId, array $options = []): array
    {
        $replaceExisting = (bool) ($options['replace_existing'] ?? true);
        $publishImmediately = (bool) ($options['publish_immediately'] ?? true);
        $updateChrome = (bool) ($options['update_site_chrome'] ?? true);
        $updateSettings = (bool) ($options['update_site_settings'] ?? true);
        $selectedSlugs = array_flip(array_map('strval', $options['selected_slugs'] ?? []));

        $pagesInput = array_values(array_filter(
            is_array($plan['pages'] ?? null) ? $plan['pages'] : [],
            fn (mixed $page): bool => is_array($page) && ($selectedSlugs === [] || isset($selectedSlugs[(string) ($page['slug'] ?? '')])),
        ));

        return DB::transaction(function () use ($plan, $pagesInput, $userId, $replaceExisting, $publishImmediately, $updateChrome, $updateSettings): array {
            if ($updateSettings && is_array($plan['brand'] ?? null)) {
                $this->applyBrandSettings($plan['brand']);
            }

            $createdPages = [];

            foreach ($pagesInput as $index => $pageSpec) {
                $createdPages[] = $this->persistPage($pageSpec, $index, $userId, $replaceExisting, $publishImmediately);
            }

            $headerUrl = null;
            $footerUrl = null;

            if ($updateChrome && is_array($plan['header_document'] ?? null)) {
                $header = $this->chrome->page(Page::ROLE_HEADER)->refresh();
                $header = $this->documents->save($header, $plan['header_document'], $header->version, $userId, 'AI generated header');
                if ($publishImmediately) {
                    $this->documents->publish($header, $userId, $header->version);
                }
                $headerUrl = route('admin.pages.site-header');
            }

            if ($updateChrome && is_array($plan['footer_document'] ?? null)) {
                $footer = $this->chrome->page(Page::ROLE_FOOTER)->refresh();
                $footer = $this->documents->save($footer, $plan['footer_document'], $footer->version, $userId, 'AI generated footer');
                if ($publishImmediately) {
                    $this->documents->publish($footer, $userId, $footer->version);
                }
                $footerUrl = route('admin.pages.site-footer');
            }

            return [
                'pages' => $createdPages,
                'header_url' => $headerUrl,
                'footer_url' => $footerUrl,
            ];
        });
    }

    /**
     * Compiles an AI blueprint JSON response into validated PageDocuments.
     *
     * @param  array<string, mixed>  $blueprint
     * @param  list<string>  $fallbackPages
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function compileBlueprint(array $blueprint, array $fallbackPages, bool $isMultipage, array $input = [], ?Page $targetPage = null): array
    {
        $brand = $this->normaliseBrand($blueprint['brand'] ?? [], $input);
        $rawPages = is_array($blueprint['pages'] ?? null) && $blueprint['pages'] !== []
            ? $blueprint['pages']
            : array_map(fn (string $name): array => ['title' => $name], $fallbackPages);

        if ($targetPage !== null) {
            $first = is_array($rawPages[0] ?? null) ? $rawPages[0] : [];
            $rawPages = [array_merge($first, [
                'title' => trim((string) ($first['title'] ?? '')) ?: $targetPage->title,
                'slug' => $targetPage->slug,
                'is_home' => (bool) $targetPage->is_home,
                'show_in_menu' => (bool) $targetPage->show_in_menu,
                'meta_title' => trim((string) ($first['meta_title'] ?? '')) ?: ($targetPage->meta_title ?? $targetPage->title),
                'meta_description' => trim((string) ($first['meta_description'] ?? '')) ?: ($targetPage->meta_description ?? ''),
            ])];
        } elseif (! $isMultipage) {
            $rawPages = [array_merge(
                is_array($rawPages[0] ?? null) ? $rawPages[0] : [],
                array_filter([
                    'title' => filled($input['page_title'] ?? null) ? (string) $input['page_title'] : null,
                    'is_home' => false,
                ], fn ($v) => $v !== null),
            )];
        }

        $usedSlugs = [];
        $pageMeta = [];

        foreach ($rawPages as $index => $rawPage) {
            if (! is_array($rawPage)) {
                continue;
            }
            $meta = $this->normalisePageMeta($rawPage, $index, $isMultipage, $usedSlugs);
            if ($targetPage !== null && $index === 0) {
                $meta['target_page_id'] = $targetPage->id;
                $meta['slug'] = $targetPage->slug;
                $meta['is_home'] = (bool) $targetPage->is_home;
                $meta['menu_order'] = (int) ($targetPage->menu_order ?? 0);
            }
            $pageMeta[] = $meta;
        }

        if ($isMultipage && $pageMeta !== [] && ! collect($pageMeta)->contains(fn (array $p): bool => $p['is_home'])) {
            $pageMeta[0]['is_home'] = true;
            $pageMeta[0]['slug'] = 'home';
        }

        $existingSlugs = Page::query()->regular()->pluck('slug')->all();
        $validSlugs = array_values(array_unique([
            ...array_map(fn (array $p): string => $p['slug'], $pageMeta),
            ...$existingSlugs,
        ]));
        $compiledPages = [];

        foreach ($pageMeta as $index => $meta) {
            $rawPage = is_array($rawPages[$index] ?? null) ? $rawPages[$index] : [];
            $rawSections = is_array($rawPage['sections'] ?? null) ? $rawPage['sections'] : [];
            $sections = $this->buildSectionsForPage($rawSections, $meta, $brand, $validSlugs);

            $document = $this->upgrader->upgrade([
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'settings' => PageDocuments::themedSettings(),
                'sections' => $sections,
            ]);
            $this->validator->validate($document);

            $compiledPages[] = [
                ...$meta,
                'section_count' => count($sections),
                'outline' => array_map(
                    fn (array $section): string => (string) ($section['name'] ?? 'Section'),
                    $sections,
                ),
                'document' => $document,
            ];
        }

        $headerDoc = $this->compileHeaderDocument(is_array($blueprint['header'] ?? null) ? $blueprint['header'] : [], $compiledPages, $brand);
        $footerDoc = $this->compileFooterDocument(is_array($blueprint['footer'] ?? null) ? $blueprint['footer'] : [], $compiledPages, $brand);

        return [
            'brand' => $brand,
            'pages' => $compiledPages,
            'header_document' => $headerDoc,
            'footer_document' => $footerDoc,
            'header_links' => $this->extractNavLinks($blueprint['header'] ?? [], $compiledPages),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    private function resolveRequestedPages(array $input, bool $isMultipage): array
    {
        if (! $isMultipage) {
            return [trim((string) ($input['page_title'] ?? 'Landing page')) ?: 'Landing page'];
        }

        $pages = array_values(array_filter(array_map(
            fn (mixed $item): string => trim((string) $item),
            is_array($input['pages'] ?? null) ? $input['pages'] : [],
        )));

        return $pages !== [] ? $pages : ['Home', 'About', 'Services', 'Pricing', 'Contact'];
    }

    private function systemPrompt(bool $isMultipage, bool $isRedesign = false): string
    {
        if ($isRedesign) {
            $scope = 'an improved, redesigned version of an existing web page, keeping its authentic business details while upgrading structure, hierarchy, and copy according to the instructions';
        } elseif ($isMultipage) {
            $scope = 'a cohesive multi-page website with shared header navigation linking every page, a footer, and distinct, tailored content per page';
        } else {
            $scope = 'a single high-converting landing page with multiple rich sections';
        }

        return <<<PROMPT
You are a senior web copywriter and UX architect generating {$scope} for a visual CMS builder.
Return ONLY valid JSON matching this schema cleanly:
{
  "brand": {
    "site_name": "string",
    "tagline": "string (1 sentence)",
    "footer_text": "string (e.g. Brand Name · All rights reserved)"
  },
  "header": {
    "announcement_text": "string or null (short top promo bar, optional)",
    "cta_label": "string",
    "cta_slug": "string (one of the page slugs, e.g. contact or pricing)",
    "links": [{"label": "string", "slug": "string"}]
  },
  "footer": {
    "tagline": "string (1 short sentence)",
    "copyright": "string",
    "links": [{"label": "string", "slug": "string"}]
  },
  "pages": [
    {
      "title": "string",
      "slug": "lowercase-kebab-case",
      "is_home": true,
      "show_in_menu": true,
      "meta_title": "string (<= 65 chars)",
      "meta_description": "string (<= 155 chars)",
      "sections": [
        {
          "type": "hero|features|story|stats|process|pricing|testimonials|faq|cta|contact|content",
          "variant": "minimal|split|dark|cards|trio|gradient",
          "eyebrow": "string",
          "heading": "string",
          "subheading": "string",
          "body": "string",
          "image_alt": "string",
          "primary_cta": {"label": "string", "slug": "string"},
          "secondary_cta": {"label": "string", "slug": "string"},
          "bullets": ["string"],
          "paragraphs": ["string"],
          "items": [{"icon": "zap|shield|check|star|heart|globe|layers|users|target|compass|workflow|clock|mail|phone|map-pin|package", "title": "string", "body": "string"}],
          "stats": [{"value": "string", "label": "string"}],
          "plans": [{"name": "string", "price": "string", "interval": "month|year|project", "highlighted": false, "features": ["string"], "button_label": "string", "slug": "string"}],
          "testimonials": [{"quote": "string", "author": "string", "role": "string", "rating": 5}],
          "faqs": [{"question": "string", "answer": "string"}],
          "email": "string",
          "address": "string"
        }
      ]
    }
  ]
}
Rules:
- Keep slugs strictly lowercase URL slugs (home, about, services, pricing, contact, faq, etc.) matching the requested pages.
- Make copy specific, realistic, concise, and tailored to the user's prompt — never output generic Lorem Ipsum.
- Each page should have 3 to 5 varied sections suited to its purpose (e.g. Home: hero + features + stats/story + testimonials + cta; About: hero/story + stats + process + cta; Pricing: pricing + faq + cta; Contact: contact + faq).
- When redesigning or modifying an existing page, cover the FULL page (typically 4 to 7 rich sections preserving untouched existing content/details unless instructed to change or replace them) so the whole page remains complete and editable.
- Only include fields relevant to each section's type.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $requestedPages
     * @param  array<string, mixed>|null  $currentDocument
     */
    private function userPrompt(array $input, array $requestedPages, bool $isMultipage, ?Page $targetPage = null, ?array $currentDocument = null): string
    {
        $prompt = trim((string) $input['prompt']);
        $tone = trim((string) ($input['tone'] ?? 'Modern & clear'));
        $brandName = trim((string) ($input['brand_name'] ?? ''));
        $siteFallback = trim((string) ($this->settings->get('site_name', 'Modrix')));

        $brandLine = $brandName !== '' ? "Preferred brand/business name: {$brandName}." : "If no brand name is in the description, invent a fitting one (current site name: {$siteFallback}).";
        $pageList = implode(', ', $requestedPages);

        if ($targetPage !== null) {
            $existingSummary = $this->summarizeExistingPage($targetPage, $currentDocument);

            return "Task: Modify and redesign the full existing page '{$targetPage->title}' (slug: /{$targetPage->slug}).\n"
                ."Modification & redesign instructions:\n{$prompt}\n\n"
                ."Tone & visual style: {$tone}\n{$brandLine}\n\n"
                ."Current full-page sections & content (preserve key business details and keep untouched parts unless asked to rewrite/remove):\n{$existingSummary}";
        }

        $modeNote = $isMultipage
            ? "Create these pages: {$pageList}. Ensure header links, footer links, and CTA buttons cross-link cleanly to the generated page slugs."
            : "Create a single standalone page titled '{$pageList}' with 4 to 6 rich sections.";

        return "Business / website brief:\n{$prompt}\n\nTone & visual style: {$tone}\n{$brandLine}\n{$modeNote}";
    }

    /**
     * @param  array<string, mixed>|null  $currentDocument
     */
    private function summarizeExistingPage(Page $page, ?array $currentDocument = null): string
    {
        $parts = [
            "Title: {$page->title}",
            'Route: '.($page->is_home ? '/' : '/'.$page->slug),
        ];

        if (filled($page->meta_title)) {
            $parts[] = 'SEO Title: '.$page->meta_title;
        }
        if (filled($page->meta_description)) {
            $parts[] = 'SEO Description: '.$page->meta_description;
        }

        $sections = is_array($currentDocument['sections'] ?? null)
            ? $currentDocument['sections']
            : ($page->usesBuilder() && is_array($page->document['sections'] ?? null) ? $page->document['sections'] : null);

        if (is_array($sections) && $sections !== []) {
            $parts[] = 'Current sections ('.count($sections).'):';

            foreach (array_slice($sections, 0, 12) as $index => $section) {
                if (! is_array($section)) {
                    continue;
                }
                $snippets = $this->collectTextSnippets($section, 10);
                $label = trim((string) ($section['name'] ?? '')) ?: 'Section '.($index + 1);
                $line = '- Section '.($index + 1)." [{$label}]";
                if ($snippets !== []) {
                    $line .= ': '.implode(' | ', $snippets);
                }
                $parts[] = $line;
            }

            return implode("\n", $parts);
        }

        $snippets = $this->collectTextSnippets($page->blocks ?? [], 40);
        $joined = implode("\n- ", $snippets);

        return implode("\n", $parts).($joined !== '' ? "\nContent blocks:\n- ".$joined : '');
    }

    /**
     * @return list<string>
     */
    private function collectTextSnippets(mixed $node, int $limit): array
    {
        $snippets = [];

        array_walk_recursive($node, function (mixed $value, mixed $key) use (&$snippets): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }
            if (in_array((string) $key, ['text', 'title', 'subtitle', 'heading', 'body', 'label', 'quote', 'question', 'answer', 'authorName', 'url'], true)) {
                $clean = trim(strip_tags($value));
                if ($clean !== '') {
                    $snippets[] = mb_substr($clean, 0, 160);
                }
            }
        });

        return array_slice(array_values(array_unique($snippets)), 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $rawBrand
     * @param  array<string, mixed>  $input
     * @return array{site_name: string, tagline: string, footer_text: string}
     */
    private function normaliseBrand(mixed $rawBrand, array $input): array
    {
        $brand = is_array($rawBrand) ? $rawBrand : [];
        $fallbackName = trim((string) ($input['brand_name'] ?? '')) ?: (string) $this->settings->get('site_name', 'Modrix Commerce');
        $siteName = trim((string) ($brand['site_name'] ?? '')) ?: $fallbackName;
        $tagline = trim((string) ($brand['tagline'] ?? '')) ?: (string) $this->settings->get('tagline', '');
        $footerText = trim((string) ($brand['footer_text'] ?? '')) ?: "{$siteName} · All rights reserved.";

        return [
            'site_name' => mb_substr($siteName, 0, 100),
            'tagline' => mb_substr($tagline, 0, 200),
            'footer_text' => mb_substr($footerText, 0, 300),
        ];
    }

    /**
     * @param  array<string, mixed>  $rawPage
     * @param  array<string, bool>  $usedSlugs
     * @return array{title: string, slug: string, is_home: bool, show_in_menu: bool, menu_order: int, meta_title: string, meta_description: string}
     */
    private function normalisePageMeta(array $rawPage, int $index, bool $isMultipage, array &$usedSlugs): array
    {
        $title = trim((string) ($rawPage['title'] ?? '')) ?: ($index === 0 && $isMultipage ? 'Home' : 'Page '.($index + 1));
        $baseSlug = Slugs::from((string) ($rawPage['slug'] ?? $title)) ?: 'page-'.($index + 1);

        if (in_array($baseSlug, Slugs::RESERVED, true)) {
            $baseSlug .= '-page';
        }

        $slug = $baseSlug;

        for ($n = 2; isset($usedSlugs[$slug]) || in_array($slug, Slugs::RESERVED, true); $n++) {
            $slug = "{$baseSlug}-{$n}";
        }

        $usedSlugs[$slug] = true;
        $isHome = $isMultipage && ((bool) ($rawPage['is_home'] ?? false) || $slug === 'home' || $index === 0 && strtolower($title) === 'home');

        return [
            'title' => mb_substr($title, 0, 120),
            'slug' => $slug,
            'is_home' => $isHome,
            'show_in_menu' => array_key_exists('show_in_menu', $rawPage) ? (bool) $rawPage['show_in_menu'] : ! $isHome,
            'menu_order' => $index,
            'meta_title' => mb_substr(trim((string) ($rawPage['meta_title'] ?? $title)), 0, 120),
            'meta_description' => mb_substr(trim((string) ($rawPage['meta_description'] ?? '')), 0, 300),
        ];
    }

    /**
     * @param  list<mixed>  $rawSections
     * @param  array{title: string, slug: string, is_home: bool, meta_description: string}  $meta
     * @param  array{site_name: string, tagline: string, footer_text: string}  $brand
     * @param  list<string>  $validSlugs
     * @return list<array<string, mixed>>
     */
    private function buildSectionsForPage(array $rawSections, array $meta, array $brand, array $validSlugs): array
    {
        $sections = [];

        foreach ($rawSections as $spec) {
            if (! is_array($spec)) {
                continue;
            }
            $sections[] = $this->buildSection($spec, $meta, $brand, $validSlugs);
        }

        if ($sections === []) {
            $sections[] = $this->buildHeroSection([
                'heading' => $meta['title'],
                'subheading' => $meta['meta_description'] ?: $brand['tagline'],
                'eyebrow' => $brand['site_name'],
            ], $validSlugs);
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array{title: string, slug: string, is_home: bool, meta_description: string}  $meta
     * @param  array{site_name: string, tagline: string, footer_text: string}  $brand
     * @param  list<string>  $validSlugs
     * @return array<string, mixed>
     */
    private function buildSection(array $spec, array $meta, array $brand, array $validSlugs): array
    {
        $type = strtolower(trim((string) ($spec['type'] ?? 'content')));

        return match ($type) {
            'hero' => $this->buildHeroSection($spec, $validSlugs),
            'features' => $this->buildFeaturesSection($spec),
            'story', 'split' => $this->buildStorySection($spec, $validSlugs),
            'stats' => $this->buildStatsSection($spec),
            'process' => $this->buildProcessSection($spec),
            'pricing' => $this->buildPricingSection($spec, $validSlugs),
            'testimonials' => $this->buildTestimonialsSection($spec),
            'faq' => $this->buildFaqSection($spec),
            'cta' => $this->buildCtaSection($spec, $validSlugs),
            'contact' => $this->buildContactSection($spec, $brand),
            default => $this->buildContentSection($spec, $meta),
        };
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $validSlugs
     * @return array<string, mixed>
     */
    private function buildHeroSection(array $spec, array $validSlugs): array
    {
        $variant = (string) ($spec['variant'] ?? 'minimal');
        $dark = $variant === 'dark';
        $split = $variant === 'split';

        $eyebrow = trim((string) ($spec['eyebrow'] ?? ''));
        $heading = trim((string) ($spec['heading'] ?? 'Built for modern teams'));
        $subheading = trim((string) ($spec['subheading'] ?? ($spec['body'] ?? 'Launch fast, stay flexible, and convert visitors into customers.')));
        $cta = $this->resolveCta($spec['primary_cta'] ?? null, 'Get started', $validSlugs);

        $blocks = [];

        if ($eyebrow !== '') {
            $blocks[] = $this->paragraph(mb_strtoupper($eyebrow), [
                'color' => $this->responsive($dark ? DesignTokens::ref('accent') : DesignTokens::ref('primary')),
                'fontSize' => $this->responsive('12px'),
                'fontWeight' => $this->responsive('700'),
                'letterSpacing' => $this->responsive('0.12em'),
            ]);
        }

        $blocks[] = $this->heading($heading, 'h1', [
            'fontSize' => $this->font('52px', '34px'),
            'lineHeight' => $this->responsive('1.1'),
            'color' => $this->responsive($dark ? '#ffffff' : DesignTokens::ref('heading')),
            'maxWidth' => $split ? [] : $this->responsive('820px'),
            'margin' => $split
                ? $this->responsive(['top' => '12px', 'bottom' => '18px'])
                : $this->responsive(['top' => '12px', 'right' => 'auto', 'bottom' => '18px', 'left' => 'auto']),
        ]);

        if ($subheading !== '') {
            $blocks[] = $this->paragraph($subheading, [
                'fontSize' => $this->font('19px', '16px'),
                'color' => $this->responsive($dark ? DesignTokens::ref('on_dark_muted') : DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.6'),
                'maxWidth' => $split ? [] : $this->responsive('640px'),
                'margin' => $split
                    ? $this->responsive(['bottom' => '24px'])
                    : $this->responsive(['right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
            ]);
        }

        $blocks[] = $this->linkButton($cta['label'], $cta['url'], 'primary', 'lg');

        $rows = [];

        if ($split) {
            $rows[] = $this->row([
                $this->column(7, $blocks),
                $this->column(5, [
                    $this->image(trim((string) ($spec['image_alt'] ?? $heading)), [
                        'borderRadius' => $this->responsive('18px'),
                    ]),
                ]),
            ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('32px')]);
        } else {
            $rows[] = $this->row([$this->column(12, $blocks)]);
        }

        $stats = array_slice(is_array($spec['stats'] ?? null) ? $spec['stats'] : [], 0, 3);

        if ($stats !== []) {
            $statCols = [];
            $span = max(4, (int) floor(12 / count($stats)));

            foreach ($stats as $stat) {
                if (! is_array($stat)) {
                    continue;
                }
                $statCols[] = $this->column($span, [
                    $this->heading((string) ($stat['value'] ?? '99%'), 'h3', [
                        'fontSize' => $this->font('34px', '28px'),
                        'color' => $this->responsive($dark ? '#ffffff' : DesignTokens::ref('heading')),
                        'textAlign' => $this->responsive('center'),
                    ]),
                    $this->paragraph((string) ($stat['label'] ?? ''), [
                        'fontSize' => $this->responsive('14px'),
                        'color' => $this->responsive($dark ? DesignTokens::ref('on_dark_muted') : DesignTokens::ref('muted')),
                        'textAlign' => $this->responsive('center'),
                    ]),
                ], [
                    'backgroundColor' => $this->responsive($dark ? 'rgba(255,255,255,0.06)' : DesignTokens::ref('surface')),
                    'padding' => $this->responsive(['top' => '20px', 'right' => '18px', 'bottom' => '20px', 'left' => '18px']),
                    'borderRadius' => $this->responsive('14px'),
                ]);
            }

            if ($statCols !== []) {
                $rows[] = $this->row($statCols, ['margin' => $this->responsive(['top' => '36px']), 'gap' => $this->responsive('20px')]);
            }
        }

        return $this->namedSection(
            'Hero · '.$heading,
            $rows,
            style: array_filter([
                'backgroundColor' => $dark ? $this->responsive(DesignTokens::ref('dark')) : null,
                'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
                'textAlign' => $split ? null : $this->responsive('center'),
            ]),
            width: $dark ? 'full' : 'boxed',
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function buildFeaturesSection(array $spec): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Everything you need'));
        $subheading = trim((string) ($spec['subheading'] ?? ''));
        $rawItems = is_array($spec['items'] ?? null) ? $spec['items'] : [];
        $items = [];

        foreach (array_slice($rawItems, 0, 6) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $icon = (string) ($item['icon'] ?? 'zap');
            $items[] = [
                'icon' => in_array($icon, self::ALLOWED_ICONS, true) ? $icon : self::ALLOWED_ICONS[$index % count(self::ALLOWED_ICONS)],
                'title' => trim((string) ($item['title'] ?? 'Capability')),
                'body' => trim((string) ($item['body'] ?? '')),
            ];
        }

        if ($items === []) {
            $items = [
                ['icon' => 'zap', 'title' => 'Fast setup', 'body' => 'Ready to publish without extra configuration.'],
                ['icon' => 'shield', 'title' => 'Reliable', 'body' => 'Clean structure that scales across every page.'],
                ['icon' => 'check', 'title' => 'Flexible', 'body' => 'Every headline, button and block stays editable.'],
            ];
        }

        $headerBlocks = [
            $this->heading($heading, 'h2', [
                'textAlign' => $this->responsive('center'),
                'fontSize' => $this->font('36px', '28px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
            ]),
        ];

        if ($subheading !== '') {
            $headerBlocks[] = $this->paragraph($subheading, [
                'textAlign' => $this->responsive('center'),
                'color' => $this->responsive(DesignTokens::ref('body')),
                'maxWidth' => $this->responsive('640px'),
                'margin' => $this->responsive(['top' => '10px', 'right' => 'auto', 'bottom' => '28px', 'left' => 'auto']),
            ]);
        }

        return $this->namedSection(
            'Features · '.$heading,
            [
                $this->row([$this->column(12, $headerBlocks)]),
                $this->row([$this->column(12, [
                    $this->blockNode(BlockType::FeatureCards, [
                        'items' => $items,
                        'columns' => count($items) === 4 ? 2 : 3,
                    ]),
                ])]),
            ],
            style: [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $validSlugs
     * @return array<string, mixed>
     */
    private function buildStorySection(array $spec, array $validSlugs): array
    {
        $eyebrow = trim((string) ($spec['eyebrow'] ?? ''));
        $heading = trim((string) ($spec['heading'] ?? 'Our story'));
        $body = trim((string) ($spec['body'] ?? ($spec['subheading'] ?? '')));
        $bullets = array_filter(array_map('strval', is_array($spec['bullets'] ?? null) ? $spec['bullets'] : []));

        $copyBlocks = [];

        if ($eyebrow !== '') {
            $copyBlocks[] = $this->paragraph(mb_strtoupper($eyebrow), [
                'color' => $this->responsive(DesignTokens::ref('primary')),
                'fontSize' => $this->responsive('12px'),
                'fontWeight' => $this->responsive('700'),
                'letterSpacing' => $this->responsive('0.1em'),
            ]);
        }

        $copyBlocks[] = $this->heading($heading, 'h2', [
            'fontSize' => $this->font('36px', '28px'),
            'color' => $this->responsive(DesignTokens::ref('heading')),
            'margin' => $this->responsive(['top' => '8px', 'bottom' => '14px']),
        ]);

        if ($body !== '') {
            $copyBlocks[] = $this->paragraph($body, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.7'),
                'margin' => $this->responsive(['bottom' => '16px']),
            ]);
        }

        foreach (array_slice($bullets, 0, 5) as $bullet) {
            $copyBlocks[] = $this->paragraph('✓ '.$bullet, [
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'fontWeight' => $this->responsive('500'),
                'margin' => $this->responsive(['bottom' => '6px']),
            ]);
        }

        if (is_array($spec['primary_cta'] ?? null)) {
            $cta = $this->resolveCta($spec['primary_cta'], 'Learn more', $validSlugs);
            $copyBlocks[] = $this->linkButton($cta['label'], $cta['url'], 'secondary', 'md', [
                'margin' => $this->responsive(['top' => '14px']),
            ]);
        }

        $imageCol = $this->column(6, [
            $this->image(trim((string) ($spec['image_alt'] ?? $heading)), [
                'borderRadius' => $this->responsive('16px'),
            ]),
        ]);
        $textCol = $this->column(6, $copyBlocks);

        $columns = ($spec['variant'] ?? '') === 'image_left' ? [$imageCol, $textCol] : [$textCol, $imageCol];

        return $this->namedSection(
            'Split · '.$heading,
            [$this->row($columns, ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('32px')])],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '40px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function buildStatsSection(array $spec): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'By the numbers'));
        $stats = array_slice(is_array($spec['stats'] ?? null) ? $spec['stats'] : [], 0, 4);

        if ($stats === []) {
            $stats = [
                ['value' => '10x', 'label' => 'Faster workflow'],
                ['value' => '99.9%', 'label' => 'Reliability'],
                ['value' => '24/7', 'label' => 'Dedicated support'],
            ];
        }

        $span = max(3, (int) floor(12 / count($stats)));
        $cols = [];

        foreach ($stats as $stat) {
            if (! is_array($stat)) {
                continue;
            }
            $cols[] = $this->column($span, [
                $this->heading((string) ($stat['value'] ?? '100%'), 'h3', [
                    'fontSize' => $this->font('40px', '30px'),
                    'color' => $this->responsive(DesignTokens::ref('primary')),
                    'textAlign' => $this->responsive('center'),
                ]),
                $this->paragraph((string) ($stat['label'] ?? ''), [
                    'color' => $this->responsive(DesignTokens::ref('muted')),
                    'textAlign' => $this->responsive('center'),
                ]),
            ]);
        }

        return $this->namedSection(
            'Stats · '.$heading,
            [
                $this->row([$this->column(12, [
                    $this->heading($heading, 'h2', [
                        'textAlign' => $this->responsive('center'),
                        'fontSize' => $this->font('32px', '24px'),
                        'color' => $this->responsive(DesignTokens::ref('heading')),
                        'margin' => $this->responsive(['bottom' => '24px']),
                    ]),
                ])]),
                $this->row($cols, ['gap' => $this->responsive('20px')]),
            ],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '40px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function buildProcessSection(array $spec): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'How it works'));
        $items = array_slice(is_array($spec['items'] ?? null) ? $spec['items'] : [], 0, 4);

        if ($items === []) {
            $items = [
                ['title' => 'Share your goals', 'body' => 'Tell us what you are building and what matters most.'],
                ['title' => 'Tailor every detail', 'body' => 'Review the plan, adjust priorities, and align timelines.'],
                ['title' => 'Launch & grow', 'body' => 'Go live smoothly with ongoing iteration and support.'],
            ];
        }

        $span = max(3, (int) floor(12 / count($items)));
        $cols = [];

        foreach ($items as $idx => $step) {
            if (! is_array($step)) {
                continue;
            }
            $num = str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
            $cols[] = $this->column($span, [
                $this->paragraph($num, [
                    'color' => $this->responsive(DesignTokens::ref('primary')),
                    'fontWeight' => $this->responsive('700'),
                    'fontSize' => $this->responsive('14px'),
                ]),
                $this->heading(trim((string) ($step['title'] ?? "Step {$num}")), 'h3', [
                    'fontSize' => $this->responsive('20px'),
                    'color' => $this->responsive(DesignTokens::ref('heading')),
                    'margin' => $this->responsive(['top' => '6px', 'bottom' => '8px']),
                ]),
                $this->paragraph(trim((string) ($step['body'] ?? '')), [
                    'color' => $this->responsive(DesignTokens::ref('body')),
                    'lineHeight' => $this->responsive('1.6'),
                ]),
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                'padding' => $this->responsive(['top' => '22px', 'right' => '20px', 'bottom' => '22px', 'left' => '20px']),
                'borderRadius' => $this->responsive('14px'),
            ]);
        }

        return $this->namedSection(
            'Process · '.$heading,
            [
                $this->row([$this->column(12, [
                    $this->heading($heading, 'h2', [
                        'textAlign' => $this->responsive('center'),
                        'fontSize' => $this->font('36px', '28px'),
                        'color' => $this->responsive(DesignTokens::ref('heading')),
                        'margin' => $this->responsive(['bottom' => '28px']),
                    ]),
                ])]),
                $this->row($cols, ['gap' => $this->responsive('20px')]),
            ],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $validSlugs
     * @return array<string, mixed>
     */
    private function buildPricingSection(array $spec, array $validSlugs): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Simple, transparent pricing'));
        $subheading = trim((string) ($spec['subheading'] ?? 'Start with the plan that fits today and scale anytime.'));
        $rawPlans = is_array($spec['plans'] ?? null) ? $spec['plans'] : [];
        $plans = [];

        foreach (array_slice($rawPlans, 0, 4) as $plan) {
            if (! is_array($plan)) {
                continue;
            }
            $slug = $this->resolveSlugUrl((string) ($plan['slug'] ?? 'contact'), $validSlugs);
            $plans[] = [
                'name' => trim((string) ($plan['name'] ?? 'Starter')),
                'price' => trim((string) ($plan['price'] ?? '$29')),
                'interval' => trim((string) ($plan['interval'] ?? 'month')),
                'features' => array_values(array_filter(array_map('strval', is_array($plan['features'] ?? null) ? $plan['features'] : ['Full core features', 'Priority support']))),
                'buttonLabel' => trim((string) ($plan['button_label'] ?? 'Get started')),
                'buttonUrl' => $slug,
                'highlighted' => (bool) ($plan['highlighted'] ?? false),
            ];
        }

        if ($plans === []) {
            $plans = [
                ['name' => 'Starter', 'price' => '$29', 'interval' => 'month', 'features' => ['Core features', 'Email support'], 'buttonLabel' => 'Start Starter', 'buttonUrl' => $this->resolveSlugUrl('contact', $validSlugs), 'highlighted' => false],
                ['name' => 'Growth', 'price' => '$79', 'interval' => 'month', 'features' => ['Everything in Starter', 'Unlimited pages', 'Priority support'], 'buttonLabel' => 'Start Growth', 'buttonUrl' => $this->resolveSlugUrl('contact', $validSlugs), 'highlighted' => true],
            ];
        }

        return $this->namedSection(
            'Pricing · '.$heading,
            [
                $this->row([$this->column(12, [
                    $this->heading($heading, 'h2', [
                        'textAlign' => $this->responsive('center'),
                        'fontSize' => $this->font('36px', '28px'),
                        'color' => $this->responsive(DesignTokens::ref('heading')),
                    ]),
                    $this->paragraph($subheading, [
                        'textAlign' => $this->responsive('center'),
                        'color' => $this->responsive(DesignTokens::ref('body')),
                        'margin' => $this->responsive(['top' => '10px', 'bottom' => '28px']),
                    ]),
                ])]),
                $this->row([$this->column(12, [
                    $this->blockNode(BlockType::PricingTable, [
                        'plans' => $plans,
                        'columns' => min(3, count($plans)),
                    ]),
                ])]),
            ],
            style: [
                'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
                'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function buildTestimonialsSection(array $spec): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Trusted by customers'));
        $raw = array_slice(is_array($spec['testimonials'] ?? null) ? $spec['testimonials'] : [], 0, 3);

        if ($raw === []) {
            $raw = [
                ['quote' => 'A seamless experience from day one. Our team saw results in the first week.', 'author' => 'Alex Rivera', 'role' => 'Operations Lead', 'rating' => 5],
                ['quote' => 'Clear, fast, and thoughtful in every detail. Exactly what we were looking for.', 'author' => 'Maya Chen', 'role' => 'Founder', 'rating' => 5],
            ];
        }

        $span = max(4, (int) floor(12 / count($raw)));
        $cols = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $cols[] = $this->column($span, [
                $this->blockNode(BlockType::Testimonial, [
                    'quote' => trim((string) ($item['quote'] ?? '')),
                    'authorName' => trim((string) ($item['author'] ?? 'Customer')),
                    'authorTitle' => trim((string) ($item['role'] ?? '')),
                    'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
                    'layout' => 'card',
                ]),
            ]);
        }

        return $this->namedSection(
            'Testimonials · '.$heading,
            [
                $this->row([$this->column(12, [
                    $this->heading($heading, 'h2', [
                        'textAlign' => $this->responsive('center'),
                        'fontSize' => $this->font('34px', '26px'),
                        'color' => $this->responsive(DesignTokens::ref('heading')),
                        'margin' => $this->responsive(['bottom' => '26px']),
                    ]),
                ])]),
                $this->row($cols, ['gap' => $this->responsive('20px')]),
            ],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function buildFaqSection(array $spec): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Frequently asked questions'));
        $subheading = trim((string) ($spec['subheading'] ?? ''));
        $rawFaqs = is_array($spec['faqs'] ?? null) ? $spec['faqs'] : [];
        $items = [];

        foreach (array_slice($rawFaqs, 0, 8) as $faq) {
            if (! is_array($faq)) {
                continue;
            }
            $items[] = [
                'question' => trim((string) ($faq['question'] ?? 'Question')),
                'answer' => trim((string) ($faq['answer'] ?? '')),
            ];
        }

        if ($items === []) {
            $items = [
                ['question' => 'How do we get started?', 'answer' => 'Reach out through our contact page and we will respond within one business day.'],
                ['question' => 'Can we customize plans later?', 'answer' => 'Yes — you can update your plan or scope anytime as your needs evolve.'],
            ];
        }

        $headerBlocks = [
            $this->heading($heading, 'h2', [
                'textAlign' => $this->responsive('center'),
                'fontSize' => $this->font('34px', '26px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
            ]),
        ];

        if ($subheading !== '') {
            $headerBlocks[] = $this->paragraph($subheading, [
                'textAlign' => $this->responsive('center'),
                'color' => $this->responsive(DesignTokens::ref('body')),
                'margin' => $this->responsive(['top' => '8px', 'bottom' => '24px']),
            ]);
        }

        return $this->namedSection(
            'FAQ · '.$heading,
            [
                $this->row([$this->column(12, $headerBlocks)]),
                $this->row([$this->column(12, [
                    $this->blockNode(BlockType::Faq, [
                        'initialOpen' => true,
                        'items' => $items,
                        'allowMultipleOpen' => false,
                        'emitSchemaOrg' => true,
                    ]),
                ], ['maxWidth' => $this->responsive('780px'), 'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto'])])]),
            ],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $validSlugs
     * @return array<string, mixed>
     */
    private function buildCtaSection(array $spec, array $validSlugs): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Ready to get started?'));
        $body = trim((string) ($spec['body'] ?? ($spec['subheading'] ?? 'Let us help you launch faster.')));
        $cta = $this->resolveCta($spec['primary_cta'] ?? null, 'Contact us', $validSlugs);

        return $this->namedSection(
            'CTA · '.$heading,
            [
                $this->row([$this->column(12, [
                    $this->heading($heading, 'h2', [
                        'color' => $this->responsive('#ffffff'),
                        'fontSize' => $this->font('38px', '28px'),
                        'textAlign' => $this->responsive('center'),
                    ]),
                    $this->paragraph($body, [
                        'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                        'fontSize' => $this->font('18px', '16px'),
                        'textAlign' => $this->responsive('center'),
                        'maxWidth' => $this->responsive('620px'),
                        'margin' => $this->responsive(['top' => '12px', 'right' => 'auto', 'bottom' => '24px', 'left' => 'auto']),
                    ]),
                    $this->linkButton($cta['label'], $cta['url'], 'primary', 'lg'),
                ], ['textAlign' => $this->responsive('center')])]),
            ],
            style: [
                'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                'padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '48px'),
            ],
            width: 'full',
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array{site_name: string, tagline: string, footer_text: string}  $brand
     * @return array<string, mixed>
     */
    private function buildContactSection(array $spec, array $brand): array
    {
        $heading = trim((string) ($spec['heading'] ?? 'Get in touch'));
        $subheading = trim((string) ($spec['subheading'] ?? ($spec['body'] ?? 'Have a question or project in mind? Send us a message anytime.')));
        $email = trim((string) ($spec['email'] ?? ($this->settings->get('contact_email') ?: 'hello@example.com')));
        $address = trim((string) ($spec['address'] ?? ''));

        $infoBlocks = [
            $this->heading($heading, 'h2', [
                'fontSize' => $this->font('36px', '28px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
            ]),
            $this->paragraph($subheading, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.65'),
                'margin' => $this->responsive(['top' => '12px', 'bottom' => '20px']),
            ]),
            $this->paragraph('Email: '.$email, [
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'fontWeight' => $this->responsive('600'),
            ]),
        ];

        if ($address !== '') {
            $infoBlocks[] = $this->paragraph('Location: '.$address, [
                'color' => $this->responsive(DesignTokens::ref('muted')),
                'margin' => $this->responsive(['top' => '6px']),
            ]);
        }

        $optinCol = $this->column(6, [
            $this->heading('Stay connected', 'h3', [
                'fontSize' => $this->responsive('20px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'margin' => $this->responsive(['bottom' => '12px']),
            ]),
            $this->blockNode(BlockType::EmailOptin, [
                'placeholder' => 'you@example.com',
                'submitLabel' => 'Subscribe',
                'successMessage' => 'Thanks for reaching out to '.$brand['site_name'].'.',
            ]),
        ], [
            'backgroundColor' => $this->responsive(DesignTokens::ref('surface')),
            'padding' => $this->responsive(['top' => '28px', 'right' => '24px', 'bottom' => '28px', 'left' => '24px']),
            'borderRadius' => $this->responsive('16px'),
        ]);

        return $this->namedSection(
            'Contact · '.$heading,
            [$this->row([$this->column(6, $infoBlocks), $optinCol], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('28px')])],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '44px')],
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array{title: string}  $meta
     * @return array<string, mixed>
     */
    private function buildContentSection(array $spec, array $meta): array
    {
        $heading = trim((string) ($spec['heading'] ?? $meta['title']));
        $paragraphs = array_filter(array_map('strval', is_array($spec['paragraphs'] ?? null) ? $spec['paragraphs'] : []));

        if ($paragraphs === [] && filled($spec['body'] ?? null)) {
            $paragraphs = [(string) $spec['body']];
        }

        $blocks = [
            $this->heading($heading, 'h2', [
                'fontSize' => $this->font('34px', '26px'),
                'color' => $this->responsive(DesignTokens::ref('heading')),
                'margin' => $this->responsive(['bottom' => '14px']),
            ]),
        ];

        foreach ($paragraphs as $paragraph) {
            $blocks[] = $this->paragraph($paragraph, [
                'color' => $this->responsive(DesignTokens::ref('body')),
                'lineHeight' => $this->responsive('1.75'),
                'margin' => $this->responsive(['bottom' => '14px']),
            ]);
        }

        return $this->namedSection(
            'Content · '.$heading,
            [$this->row([$this->column(12, $blocks, ['maxWidth' => $this->responsive('820px'), 'margin' => $this->responsive(['right' => 'auto', 'left' => 'auto'])])])],
            style: ['padding' => $this->pad(DesignTokens::ref('section_spacing'), '24px', '40px')],
        );
    }

    /**
     * Builds a strict header-only single section for the shared Site header.
     *
     * @param  array<string, mixed>  $rawHeader
     * @param  list<array<string, mixed>>  $compiledPages
     * @param  array{site_name: string}  $brand
     * @return array<string, mixed>
     */
    private function compileHeaderDocument(array $rawHeader, array $compiledPages, array $brand): array
    {
        $navItems = $this->extractNavLinks($rawHeader, $compiledPages);
        $validSlugs = array_map(fn (array $p): string => (string) $p['slug'], $compiledPages);
        $ctaLabel = trim((string) ($rawHeader['cta_label'] ?? 'Get started')) ?: 'Get started';
        $ctaUrl = $this->resolveSlugUrl((string) ($rawHeader['cta_slug'] ?? ($validSlugs[count($validSlugs) - 1] ?? 'home')), $validSlugs);
        $announcement = trim((string) ($rawHeader['announcement_text'] ?? ''));

        $rows = [];

        if ($announcement !== '') {
            $rows[] = $this->row([
                $this->column(12, [
                    $this->paragraph($announcement, [
                        'color' => $this->responsive(DesignTokens::ref('on_primary')),
                        'fontSize' => $this->responsive('13px'),
                        'fontWeight' => $this->responsive('600'),
                        'textAlign' => $this->responsive('center'),
                    ]),
                ]),
            ], [
                'backgroundColor' => $this->responsive(DesignTokens::ref('primary')),
                'padding' => $this->responsive(['top' => '8px', 'right' => '20px', 'bottom' => '8px', 'left' => '20px']),
            ]);
        }

        $rows[] = $this->row([
            $this->column(3, [
                $this->blockNode(BlockType::Logo, ['url' => '/', 'alt' => $brand['site_name'], 'useWorkspaceBranding' => true]),
            ]),
            $this->column(6, [
                $this->blockNode(BlockType::Navigation, [
                    'items' => $navItems,
                    'orientation' => 'horizontal',
                    'mobileBehaviour' => 'drawer',
                ], ['justifyContent' => $this->responsive('center')]),
            ]),
            $this->column(3, [
                $this->linkButton($ctaLabel, $ctaUrl, 'primary', 'sm', ['textAlign' => $this->responsive('right')]),
            ]),
        ], [
            'alignItems' => $this->responsive('center'),
            'padding' => $this->responsive(['top' => '14px', 'right' => '24px', 'bottom' => '14px', 'left' => '24px']),
        ]);

        $section = $this->namedSection(
            'Navigation · Site header',
            $rows,
            style: [
                'backgroundColor' => $this->responsive(DesignTokens::ref('header_bg')),
                'color' => $this->responsive(DesignTokens::ref('header_text')),
                'border' => $this->responsive(['style' => 'solid', 'color' => DesignTokens::ref('border'), 'bottom' => '1px']),
            ],
            width: 'full',
            sticky: true,
        );

        $doc = $this->upgrader->upgrade([
            'schema_version' => DocumentUpgrader::CURRENT_VERSION,
            'settings' => PageDocuments::themedSettings(),
            'sections' => [$section],
        ]);
        $this->validator->validate($doc);

        return $doc;
    }

    /**
     * @param  array<string, mixed>  $rawFooter
     * @param  list<array<string, mixed>>  $compiledPages
     * @param  array{site_name: string, tagline: string, footer_text: string}  $brand
     * @return array<string, mixed>
     */
    private function compileFooterDocument(array $rawFooter, array $compiledPages, array $brand): array
    {
        $navItems = $this->extractNavLinks($rawFooter, $compiledPages);
        $tagline = trim((string) ($rawFooter['tagline'] ?? $brand['tagline']));
        $copyright = trim((string) ($rawFooter['copyright'] ?? $brand['footer_text']));

        $section = $this->namedSection(
            'Footer · Site footer',
            [
                $this->row([
                    $this->column(5, [
                        $this->blockNode(BlockType::Logo, ['url' => '/', 'alt' => $brand['site_name'], 'useWorkspaceBranding' => true]),
                        $this->paragraph($tagline ?: $brand['site_name'], [
                            'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            'fontSize' => $this->responsive('14px'),
                            'margin' => $this->responsive(['top' => '10px']),
                        ]),
                    ]),
                    $this->column(7, [
                        $this->blockNode(BlockType::Navigation, [
                            'items' => $navItems,
                            'orientation' => 'horizontal',
                            'mobileBehaviour' => 'stack',
                        ], ['color' => $this->responsive(DesignTokens::ref('on_dark')), 'justifyContent' => $this->responsive('flex-end')]),
                    ]),
                ], ['alignItems' => $this->responsive('center'), 'gap' => $this->responsive('24px')]),
                $this->row([
                    $this->column(12, [
                        $this->paragraph($copyright, [
                            'color' => $this->responsive(DesignTokens::ref('on_dark_muted')),
                            'fontSize' => $this->responsive('13px'),
                        ]),
                    ]),
                ], [
                    'margin' => $this->responsive(['top' => '24px']),
                    'padding' => $this->responsive(['top' => '16px']),
                    'border' => $this->responsive(['style' => 'solid', 'color' => 'rgba(255,255,255,0.12)', 'top' => '1px']),
                ]),
            ],
            style: [
                'backgroundColor' => $this->responsive(DesignTokens::ref('dark')),
                'color' => $this->responsive(DesignTokens::ref('on_dark')),
                'padding' => $this->pad('40px', '24px', '32px'),
            ],
            width: 'full',
        );

        $doc = $this->upgrader->upgrade([
            'schema_version' => DocumentUpgrader::CURRENT_VERSION,
            'settings' => PageDocuments::themedSettings(),
            'sections' => [$section],
        ]);
        $this->validator->validate($doc);

        return $doc;
    }

    /**
     * @param  array<string, mixed>  $rawChrome
     * @param  list<array<string, mixed>>  $compiledPages
     * @return list<array{label: string, url: string}>
     */
    private function extractNavLinks(array $rawChrome, array $compiledPages): array
    {
        $bySlug = [];
        foreach ($compiledPages as $page) {
            $bySlug[$page['slug']] = $page['is_home'] ? '/' : '/'.$page['slug'];
        }

        $links = [];

        if (is_array($rawChrome['links'] ?? null)) {
            foreach (array_slice($rawChrome['links'], 0, 7) as $link) {
                if (! is_array($link)) {
                    continue;
                }
                $label = trim((string) ($link['label'] ?? ''));
                $slug = Slugs::from((string) ($link['slug'] ?? ''));
                if ($label !== '') {
                    $links[] = ['label' => $label, 'url' => $bySlug[$slug] ?? ($slug !== '' ? '/'.$slug : '/')];
                }
            }
        }

        if ($links !== []) {
            return $links;
        }

        return array_values(array_map(
            fn (array $page): array => ['label' => $page['title'], 'url' => $page['is_home'] ? '/' : '/'.$page['slug']],
            array_slice($compiledPages, 0, 6),
        ));
    }

    /**
     * @param  array<string, mixed>|null  $cta
     * @param  list<string>  $validSlugs
     * @return array{label: string, url: string}
     */
    private function resolveCta(mixed $cta, string $fallbackLabel, array $validSlugs): array
    {
        $data = is_array($cta) ? $cta : [];
        $label = trim((string) ($data['label'] ?? $fallbackLabel)) ?: $fallbackLabel;
        $slug = (string) ($data['slug'] ?? ($validSlugs[count($validSlugs) - 1] ?? 'home'));

        return ['label' => $label, 'url' => $this->resolveSlugUrl($slug, $validSlugs)];
    }

    /**
     * @param  list<string>  $validSlugs
     */
    private function resolveSlugUrl(string $rawSlug, array $validSlugs): string
    {
        $slug = Slugs::from($rawSlug);

        if ($slug === 'home' || $slug === '') {
            return '/';
        }

        if (in_array($slug, $validSlugs, true)) {
            return '/'.$slug;
        }

        return $validSlugs !== [] && $validSlugs[0] === 'home' && count($validSlugs) > 1
            ? '/'.$validSlugs[1]
            : '/'.($validSlugs[0] ?? '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function namedSection(string $name, array $rows, array $style = [], string $width = 'boxed', bool $sticky = false): array
    {
        $section = $this->section($rows, $style, $width, $sticky);
        $section['name'] = mb_substr($name, 0, 80);

        return $section;
    }

    /**
     * @param  array{site_name?: string, tagline?: string, footer_text?: string}  $brand
     */
    private function applyBrandSettings(array $brand): void
    {
        $updates = array_filter([
            'site_name' => $brand['site_name'] ?? null,
            'tagline' => $brand['tagline'] ?? null,
            'footer_text' => $brand['footer_text'] ?? null,
            'contact_email' => filter_var($brand['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
        ], fn (?string $value): bool => filled($value));

        if ($updates !== []) {
            $this->settings->set($updates);
        }
    }

    /**
     * @param  array<string, mixed>  $pageSpec
     * @return array{id: int, title: string, slug: string, is_home: bool, is_published: bool, url: string, builder_url: string}
     */
    private function persistPage(array $pageSpec, int $index, int $userId, bool $replaceExisting, bool $publishImmediately): array
    {
        $targetPageId = isset($pageSpec['target_page_id']) ? (int) $pageSpec['target_page_id'] : null;
        $isHome = (bool) ($pageSpec['is_home'] ?? false);
        $baseSlug = Slugs::from((string) ($pageSpec['slug'] ?? $pageSpec['title'] ?? 'page')) ?: 'page-'.($index + 1);

        if (in_array($baseSlug, Slugs::RESERVED, true)) {
            $baseSlug .= '-page';
        }

        $page = $targetPageId ? Page::query()->regular()->findOrFail($targetPageId) : null;

        if ($page === null && $replaceExisting) {
            $page = $isHome
                ? Page::query()->regular()->where('is_home', true)->first() ?? Page::query()->regular()->where('slug', $baseSlug)->first()
                : Page::query()->regular()->where('slug', $baseSlug)->first();
        }

        if ($page === null) {
            $slug = $baseSlug;
            for ($n = 2; Page::query()->where('slug', $slug)->exists(); $n++) {
                $slug = "{$baseSlug}-{$n}";
            }

            $page = Page::query()->create([
                'title' => (string) ($pageSpec['title'] ?? Str::title(str_replace('-', ' ', $slug))),
                'slug' => $slug,
                'editor' => Page::EDITOR_BUILDER,
                'blocks' => [],
                'is_published' => false,
                'is_home' => $isHome,
                'show_in_menu' => (bool) ($pageSpec['show_in_menu'] ?? ! $isHome),
                'menu_order' => (int) ($pageSpec['menu_order'] ?? $index),
                'meta_title' => $pageSpec['meta_title'] ?? null,
                'meta_description' => $pageSpec['meta_description'] ?? null,
                'show_site_header' => (bool) ($pageSpec['show_site_header'] ?? true),
                'show_site_footer' => (bool) ($pageSpec['show_site_footer'] ?? true),
            ])->refresh();
        } else {
            $page->forceFill([
                'title' => (string) ($pageSpec['title'] ?? $page->title),
                'editor' => Page::EDITOR_BUILDER,
                'is_home' => $isHome ?: $page->is_home,
                'show_in_menu' => (bool) ($pageSpec['show_in_menu'] ?? $page->show_in_menu),
                'menu_order' => (int) ($pageSpec['menu_order'] ?? $index),
                'meta_title' => $pageSpec['meta_title'] ?? $page->meta_title,
                'meta_description' => $pageSpec['meta_description'] ?? $page->meta_description,
                'show_site_header' => (bool) ($pageSpec['show_site_header'] ?? true),
                'show_site_footer' => (bool) ($pageSpec['show_site_footer'] ?? true),
            ])->save();
        }

        if ($isHome) {
            Page::query()->regular()->where('id', '!=', $page->id)->where('is_home', true)->update(['is_home' => false]);
        }

        $document = is_array($pageSpec['document'] ?? null) ? $pageSpec['document'] : PageDocuments::blank();
        $saved = $this->documents->save($page, $document, $page->version, $userId, 'AI generated page');

        if ($publishImmediately) {
            $saved = $this->documents->publish($saved, $userId, $saved->version);
        }

        return [
            'id' => $saved->id,
            'title' => $saved->title,
            'slug' => $saved->slug,
            'is_home' => (bool) $saved->is_home,
            'is_published' => (bool) $saved->is_published,
            'url' => $saved->is_home ? route('home') : route('pages.show', $saved),
            'builder_url' => route('admin.pages.builder', $saved),
        ];
    }
}
