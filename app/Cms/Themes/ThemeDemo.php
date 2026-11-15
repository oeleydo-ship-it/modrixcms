<?php

namespace App\Cms\Themes;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\SiteChrome;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Package;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A theme's demo content ("demo" in theme.json): ready-made visual-builder pages plus the
 * theme's own site header and footer, imported in one go from Appearance → Themes.
 *
 * Imported pages remember their theme, so importing again updates them instead of making
 * copies. A page whose address is already used by another page is either given a new
 * address or, when asked, replaced (its old content stays in the page's History).
 */
class ThemeDemo
{
    public function __construct(
        private PageDocuments $documents,
        private SiteChrome $chrome,
    ) {}

    public function has(Package $theme): bool
    {
        return is_array($theme->manifest?->data['demo'] ?? null);
    }

    /**
     * What the demo contains, for the admin screen.
     *
     * @return array{pages: list<string>, header: bool, footer: bool}|null
     */
    public function summary(Package $theme): ?array
    {
        if (! $this->has($theme)) {
            return null;
        }

        $demo = $theme->manifest->data['demo'];

        return [
            'pages' => array_map(fn (array $page): string => $page['title'], $demo['pages'] ?? []),
            'header' => isset($demo['header']),
            'footer' => isset($demo['footer']),
        ];
    }

    /**
     * Imports the demo and publishes every page.
     *
     * @return array{created: int, updated: int, renamed: list<string>, home: ?Page}
     *
     * @throws InvalidPackage when a demo file is missing or not a valid page document
     */
    public function import(Package $theme, bool $setHome = false, bool $replace = false, ?int $userId = null): array
    {
        if (! $this->has($theme)) {
            throw new InvalidPackage("{$theme->displayName()} has no demo content.");
        }

        $demo = $theme->manifest->data['demo'];
        $result = ['created' => 0, 'updated' => 0, 'renamed' => [], 'home' => null];

        // Read every file first, so a broken demo changes nothing.
        $pages = array_map(fn (array $page): array => [...$page, 'document' => $this->document($theme, $page['file'])], $demo['pages'] ?? []);
        $chrome = array_filter([
            Page::ROLE_HEADER => isset($demo['header']) ? $this->document($theme, $demo['header']) : null,
            Page::ROLE_FOOTER => isset($demo['footer']) ? $this->document($theme, $demo['footer']) : null,
        ]);

        try {
            DB::transaction(function () use ($theme, $pages, $chrome, $setHome, $replace, $userId, &$result): void {
                foreach ($chrome as $role => $document) {
                    $this->chrome->install($role, $theme->name, $document, $this->documents);
                }

                foreach ($pages as $order => $definition) {
                    [$page, $created, $renamed] = $this->pageFor($theme, $definition, $replace);
                    $page->fill([
                        'title' => $definition['title'],
                        'editor' => Page::EDITOR_BUILDER,
                        'show_in_menu' => $definition['menu'] ?? true,
                        'menu_order' => $order,
                        'show_site_header' => $definition['site_header'] ?? true,
                        'show_site_footer' => $definition['site_footer'] ?? true,
                    ]);
                    $page->forceFill(['theme' => $theme->name])->save();

                    $page = $this->documents->save($page->fresh(), $definition['document'], $page->fresh()->version, $userId, 'Before '.$theme->displayName().' demo');
                    $page = $this->documents->publish($page->fresh(), $userId);

                    $result[$created ? 'created' : 'updated']++;

                    if ($renamed) {
                        $result['renamed'][] = "{$definition['title']} → /{$page->slug}";
                    }

                    if ($setHome && ($definition['home'] ?? false)) {
                        Page::query()->whereKeyNot($page->id)->where('is_home', true)->update(['is_home' => false]);
                        $page->forceFill(['is_home' => true])->save();
                        $result['home'] = $page;
                    }
                }
            });
        } catch (InvalidDocumentException $e) {
            throw new InvalidPackage("{$theme->displayName()} demo: ".$e->getMessage());
        }

        return $result;
    }

    /**
     * The page to fill: one imported earlier from this theme, one replaced on request, or a
     * new one (with a free address when the demo's is taken).
     *
     * @param  array<string, mixed>  $definition
     * @return array{0: Page, 1: bool, 2: bool} page, created, renamed
     */
    private function pageFor(Package $theme, array $definition, bool $replace): array
    {
        $slug = $definition['slug'];
        $existing = Page::query()->regular()->where('slug', $slug)->first();

        if ($existing !== null && ($existing->theme === $theme->name || $replace)) {
            return [$existing, false, false];
        }

        $free = $slug;
        for ($n = 2; Page::query()->where('slug', $free)->exists(); $n++) {
            $free = "{$slug}-{$n}";
        }

        $page = new Page([
            'slug' => $free,
            'blocks' => [],
            'is_published' => false,
        ]);

        return [$page, true, $free !== $slug];
    }

    /**
     * A demo file as a page document, with fresh ids so imports never share ids.
     *
     * @return array<string, mixed>
     */
    public function document(Package $theme, string $file): array
    {
        $path = $theme->manifest->file($file);
        $document = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($document) || ! is_array($document['sections'] ?? null)) {
            throw new InvalidPackage("{$theme->displayName()} demo: {$file} is missing or is not a page document.");
        }

        $document['schema_version'] ??= 1;
        $document['settings'] ??= [];

        return $this->freshIds($document);
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
                    if (! is_array($child)) {
                        return $child;
                    }

                    if (isset($child['id'])) {
                        $child['id'] = (string) Str::ulid();
                    }

                    return $this->freshIds($child);
                }, $node[$key]);
            }
        }

        return $node;
    }
}
