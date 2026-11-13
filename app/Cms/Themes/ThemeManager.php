<?php

namespace App\Cms\Themes;

use App\Cms\Cms;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\Package;
use App\Models\Extension;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

/**
 * The theme the public site is drawn with: the active one (Settings → active_theme),
 * a theme an admin is previewing, or the default theme in safe mode.
 *
 * A theme's compiled scripts register React components (layout, pages, blocks) with
 * window.ModrixCMS; anything it doesn't replace falls back to the core components.
 * Its settings become CSS custom properties and are shared with its components.
 */
class ThemeManager
{
    public const PREVIEW_SESSION_KEY = 'cms.preview_theme';

    private ?Package $theme = null;

    private bool $previewing = false;

    public function __construct(
        private ExtensionRegistry $registry,
        private Settings $siteSettings,
    ) {}

    /** Picks the theme for this request (an admin's preview wins over the active theme). */
    public function apply(Request $request): void
    {
        $this->previewing = false;
        $this->theme = $this->resolve($request);
    }

    public function theme(): ?Package
    {
        return $this->theme ??= $this->resolve(null);
    }

    public function name(): string
    {
        return $this->theme()?->name ?? config('cms.default_theme');
    }

    public function isPreviewing(): bool
    {
        return $this->previewing;
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return $this->theme()?->settings() ?? [];
    }

    /** @return array<int, string> URLs of the theme's compiled scripts */
    public function scripts(): array
    {
        $theme = $this->theme();

        return array_map(
            fn (string $file) => Cms::publicUrl(Extension::THEME, $theme->name, $file).'?v='.$theme->version(),
            $theme?->manifest?->data['scripts'] ?? [],
        );
    }

    /** <link> tags for the theme's styles and a :root block with its CSS custom properties. */
    public function styles(): HtmlString
    {
        $theme = $this->theme();

        if ($theme?->manifest === null) {
            return new HtmlString('');
        }

        $html = '';
        $values = $theme->settings();
        $vars = [];

        foreach ($theme->manifest->settings() as $field) {
            if (isset($field['css_var'])) {
                $vars[] = $field['css_var'].': '.$this->cssValue($field, $values[$field['name']] ?? null).';';
            }
        }

        if ($vars !== []) {
            $html .= '<style id="theme-settings">:root{'.implode('', $vars).'}</style>'.PHP_EOL;
        }

        foreach ($theme->manifest->data['styles'] ?? [] as $file) {
            $html .= '<link rel="stylesheet" href="'.e(Cms::publicUrl(Extension::THEME, $theme->name, $file).'?v='.$theme->version()).'">'.PHP_EOL;
        }

        return new HtmlString($html);
    }

    /** Values are already cleaned by type; this keeps anything that could end the CSS rule out. */
    private function cssValue(array $field, mixed $value): string
    {
        $value = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        if ($field['type'] === 'select' && isset($field['css_values'][$value])) {
            $value = (string) $field['css_values'][$value];
        }

        return preg_replace('/[^A-Za-z0-9#%.,\-\s"\'()]/', '', $value);
    }

    private function resolve(?Request $request): ?Package
    {
        $default = config('cms.default_theme');

        if (Cms::safeMode()) {
            return $this->registry->find(Extension::THEME, $default);
        }

        $preview = $request?->hasSession() ? $request->session()->get(self::PREVIEW_SESSION_KEY) : null;

        if (is_string($preview) && $request->user()?->is_admin) {
            $theme = $this->usable($preview);

            if ($theme !== null) {
                $this->previewing = true;

                return $theme;
            }
        }

        $active = (string) $this->siteSettings->get('active_theme', $default);

        return $this->usable($active) ?? $this->registry->find(Extension::THEME, $default);
    }

    /** A theme can draw the site only if it is valid, compatible and (when it ships scripts) trusted. */
    private function usable(string $name): ?Package
    {
        $theme = $this->registry->find(Extension::THEME, $name);

        if ($theme?->manifest === null || ! $theme->isCompatible()) {
            return null;
        }

        return ! $theme->hasScripts() || $theme->isTrusted() ? $theme : null;
    }
}
