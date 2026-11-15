<?php

namespace App\Cms\Builder;

use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Themes\ThemeManager;
use App\Models\Page;
use Illuminate\Support\Str;

/**
 * The site header and footer for visual-builder pages.
 *
 * Each is a special page (role "header" / "footer") edited in the same builder. Once
 * published, its sections are shown above (header) or below (footer) every builder page
 * that has the matching switch on. They are never pages of their own: their slugs can't
 * be reached and they never count as published pages.
 *
 * Every theme keeps its own header and footer (the page's `theme`), so switching themes
 * switches them too. A theme without its own uses the shared ones (no theme).
 */
class SiteChrome
{
    private const STARTERS = [
        Page::ROLE_HEADER => ['title' => 'Site header', 'slug' => '_site-header', 'design' => 'navbar'],
        Page::ROLE_FOOTER => ['title' => 'Site footer', 'slug' => '_site-footer', 'design' => 'footer-columns'],
    ];

    /** @var array<string, array<int, array<string, mixed>>> published sections, per role, for this request */
    private array $published = [];

    public function __construct(private DesignBlockLibrary $designs, private ThemeManager $themes) {}

    /**
     * The active theme's header or footer page, for editing. Created the first time it is
     * needed: as a copy of the shared one when there is one, else from a starter design.
     */
    public function page(string $role): Page
    {
        $theme = $this->themes->name();

        return $this->forTheme($role, $theme)
            ?? $this->create($role, $theme, $this->forTheme($role, null)?->document);
    }

    /**
     * The header or footer visitors see for a theme: its own once published, else the shared one.
     */
    public function current(string $role, ?string $theme = null): ?Page
    {
        $own = $this->forTheme($role, $theme ?? $this->themes->name());

        return $own?->published_document !== null ? $own : ($this->forTheme($role, null) ?? $own);
    }

    /**
     * Replaces a theme's header or footer with the given document and publishes it.
     *
     * @param  array<string, mixed>  $document
     */
    public function install(string $role, string $theme, array $document, PageDocuments $documents): Page
    {
        $page = $this->forTheme($role, $theme) ?? $this->create($role, $theme, null);
        $page = $documents->save($page->fresh(), $document, $page->fresh()->version, null, 'Theme demo');
        $this->published = [];

        return $documents->publish($page->fresh());
    }

    /**
     * The published sections of the active theme's header or footer (empty until published).
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(string $role): array
    {
        return $this->published[$role] ??= $this->current($role)?->published_document['sections'] ?? [];
    }

    /**
     * A page's published document with the site header and footer around it, as visitors see it.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function wrap(Page $page, array $document): array
    {
        if ($page->role !== null) {
            return $document;
        }

        $document['sections'] = [
            ...($page->show_site_header ? $this->sections(Page::ROLE_HEADER) : []),
            ...($document['sections'] ?? []),
            ...($page->show_site_footer ? $this->sections(Page::ROLE_FOOTER) : []),
        ];

        return $document;
    }

    /**
     * What the builder canvas shows, locked, around the page being edited.
     *
     * @return array<string, array{sections: array<int, array<string, mixed>>, editUrl: string, shown: bool}|null>
     */
    public function forCanvas(Page $page): array
    {
        if ($page->role !== null) {
            return ['header' => null, 'footer' => null];
        }

        return [
            'header' => ['sections' => $this->sections(Page::ROLE_HEADER), 'editUrl' => route('admin.pages.site-header'), 'shown' => (bool) $page->show_site_header],
            'footer' => ['sections' => $this->sections(Page::ROLE_FOOTER), 'editUrl' => route('admin.pages.site-footer'), 'shown' => (bool) $page->show_site_footer],
        ];
    }

    private function forTheme(string $role, ?string $theme): ?Page
    {
        return Page::query()->where('role', $role)
            ->when($theme === null, fn ($query) => $query->whereNull('theme'), fn ($query) => $query->where('theme', $theme))
            ->first();
    }

    /** @param  array<string, mixed>|null  $document */
    private function create(string $role, string $theme, ?array $document): Page
    {
        $starter = self::STARTERS[$role] ?? throw new \InvalidArgumentException("Unknown role {$role}.");

        $page = new Page([
            'title' => $starter['title'],
            'slug' => $starter['slug'].'-'.Str::slug(str_replace('/', '-', $theme)),
            'editor' => Page::EDITOR_BUILDER,
            'blocks' => [],
            'is_published' => false,
        ]);
        $page->forceFill([
            'role' => $role,
            'theme' => $theme,
            'document' => $document ?? [
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'settings' => PageDocuments::themedSettings(),
                'sections' => $this->designs->find($starter['design'])['document']['sections'] ?? [],
            ],
        ])->save();

        return $page;
    }
}
