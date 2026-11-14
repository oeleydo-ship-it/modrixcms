<?php

namespace App\Cms\Themes;

/**
 * The site's design settings (Appearance → Theme settings): colours, typography, buttons,
 * sections and links. Every theme has the same settings; a theme may ship its own defaults
 * ("design" in theme.json) and each theme keeps its own saved values.
 *
 * They become CSS custom properties (--site-primary, --site-heading-font, …) on the public
 * site, on pages made with the visual builder and on the builder canvas, so pages linked to
 * them (by `var(--site-…)`) follow every change.
 */
class DesignTokens
{
    public const FONTS = [
        'Inter' => 'inter:400,500,600,700,800',
        'Bricolage Grotesque' => 'bricolage-grotesque:500,700,800',
        'DM Sans' => 'dm-sans:400,500,700',
        'Manrope' => 'manrope:400,500,600,700,800',
        'Poppins' => 'poppins:400,500,600,700,800',
        'Space Grotesk' => 'space-grotesk:400,500,600,700',
        'Plus Jakarta Sans' => 'plus-jakarta-sans:400,500,600,700,800',
        'Playfair Display' => 'playfair-display:400,600,700,800',
        'Merriweather' => 'merriweather:400,700',
        'Lora' => 'lora:400,500,600,700',
        'System' => null,
    ];

    /**
     * Groups → fields. Each field: label, type (color|select|number|toggle), default, css var,
     * and for selects the options (label) and their CSS values.
     *
     * @return array<string, array{label: string, description: string, fields: array<string, array<string, mixed>>}>
     */
    public static function schema(): array
    {
        $fonts = array_combine(array_keys(self::FONTS), array_keys(self::FONTS));

        return [
            'colors' => ['label' => 'Colours', 'description' => 'Your palette. Pages and designs use these by name, so changing one updates every page.', 'fields' => [
                'primary' => self::color('Primary', '#4f46e5', 'Buttons, highlights and links.'),
                'on_primary' => self::color('Text on primary', '#ffffff', 'Text on primary-coloured buttons.'),
                'secondary' => self::color('Secondary', '#0f172a', 'Second buttons and accents.'),
                'accent' => self::color('Accent', '#f59e0b', 'Badges, ratings and small highlights.'),
                'heading' => self::color('Headings', '#0f172a'),
                'body' => self::color('Body text', '#475569'),
                'muted' => self::color('Muted text', '#64748b', 'Captions, labels and small print.'),
                'background' => self::color('Page background', '#ffffff'),
                'surface' => self::color('Surface', '#f8fafc', 'Cards and light sections.'),
                'dark' => self::color('Dark sections', '#0f172a', 'Footers, banners and dark bands.'),
                'on_dark' => self::color('Text on dark', '#ffffff'),
                'on_dark_muted' => self::color('Muted text on dark', '#cbd5e1'),
                'border' => self::color('Borders', '#e2e8f0'),
            ]],
            'typography' => ['label' => 'Typography', 'description' => 'Fonts and text sizes for headings and body text.', 'fields' => [
                'heading_font' => self::select('Heading font', 'Inter', $fonts, null, 'heading-font'),
                'body_font' => self::select('Body font', 'Inter', $fonts, null, 'body-font'),
                'base_size' => self::select('Base text size', '16', ['15' => 'Small (15px)', '16' => 'Normal (16px)', '17' => 'Large (17px)', '18' => 'Extra large (18px)'], ['15' => '15px', '16' => '16px', '17' => '17px', '18' => '18px'], 'font-size'),
                'heading_weight' => self::select('Heading weight', '700', ['500' => 'Medium', '600' => 'Semibold', '700' => 'Bold', '800' => 'Extra bold'], null, 'heading-weight'),
                'heading_scale' => self::select('Heading size', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'large' => 'Large'], ['compact' => '0.85', 'normal' => '1', 'large' => '1.15'], 'heading-scale'),
                'line_height' => self::select('Line spacing', '1.6', ['1.4' => 'Tight', '1.6' => 'Normal', '1.8' => 'Relaxed'], null, 'line-height'),
            ]],
            'buttons' => ['label' => 'Buttons', 'description' => 'How buttons look across the site.', 'fields' => [
                'button_shape' => self::select('Shape', 'rounded', ['square' => 'Square', 'rounded' => 'Rounded', 'pill' => 'Pill'], ['square' => '4px', 'rounded' => '10px', 'pill' => '999px'], 'button-radius'),
                'button_size' => self::select('Size', 'md', ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'], ['sm' => '8px 16px', 'md' => '12px 22px', 'lg' => '16px 30px'], 'button-padding'),
                'button_style' => self::select('Main button style', 'filled', ['filled' => 'Filled', 'outline' => 'Outline'], null, 'button-style'),
                'button_weight' => self::select('Text weight', '600', ['500' => 'Medium', '600' => 'Semibold', '700' => 'Bold'], null, 'button-weight'),
                'button_uppercase' => ['label' => 'Uppercase text', 'type' => 'toggle', 'default' => false, 'var' => 'button-transform', 'css' => ['1' => 'uppercase', '0' => 'none']],
            ]],
            'sections' => ['label' => 'Sections', 'description' => 'Spacing, width and card style for page sections.', 'fields' => [
                'section_spacing' => self::select('Section spacing', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'roomy' => 'Roomy'], ['compact' => '56px', 'normal' => '88px', 'roomy' => '128px'], 'section-padding'),
                'content_width' => self::select('Content width', '1140', ['960' => 'Narrow (960px)', '1140' => 'Normal (1140px)', '1280' => 'Wide (1280px)', '1440' => 'Extra wide (1440px)'], ['960' => '960px', '1140' => '1140px', '1280' => '1280px', '1440' => '1440px'], 'content-width'),
                'card_radius' => self::select('Card corners', 'soft', ['square' => 'Square', 'soft' => 'Slightly rounded', 'round' => 'Rounded', 'extra' => 'Very rounded'], ['square' => '0px', 'soft' => '8px', 'round' => '16px', 'extra' => '24px'], 'card-radius'),
                'shadow' => self::select('Shadows', 'soft', ['none' => 'None', 'soft' => 'Soft', 'strong' => 'Strong'], ['none' => 'none', 'soft' => '0 10px 30px -12px rgba(15, 23, 42, 0.18)', 'strong' => '0 24px 60px -20px rgba(15, 23, 42, 0.35)'], 'shadow'),
            ]],
            'links' => ['label' => 'Links', 'description' => 'Text links inside paragraphs.', 'fields' => [
                'link' => self::color('Link colour', '#4f46e5'),
                'link_underline' => ['label' => 'Underline links', 'type' => 'toggle', 'default' => true, 'var' => 'link-decoration', 'css' => ['1' => 'underline', '0' => 'none']],
            ]],
        ];
    }

    /** @return array<string, mixed> setting name → default value */
    public static function defaults(): array
    {
        $values = [];

        foreach (self::schema() as $group) {
            foreach ($group['fields'] as $name => $field) {
                $values[$name] = $field['default'];
            }
        }

        return $values;
    }

    /**
     * A reference to a setting, with its default as fallback: what pages and designs store
     * so they follow the theme. `ref('primary')` → `var(--site-primary, #4f46e5)`.
     */
    public static function ref(string $name): string
    {
        $field = self::field($name);
        $fallback = $field === null ? '' : ', '.self::cssValue($field, $field['default']);

        return 'var(--site-'.self::varName($name).$fallback.')';
    }

    /**
     * Cleans submitted values: unknown settings are dropped, colours must be #rrggbb and
     * selects one of their options.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function clean(array $input, array $base = []): array
    {
        $values = array_replace(self::defaults(), array_intersect_key($base, self::defaults()));

        foreach (self::schema() as $group) {
            foreach ($group['fields'] as $name => $field) {
                if (! array_key_exists($name, $input)) {
                    continue;
                }

                $value = $input[$name];
                $values[$name] = match ($field['type']) {
                    'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $values[$name],
                    'select' => is_scalar($value) && array_key_exists((string) $value, $field['options']) ? (string) $value : $values[$name],
                    'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    default => $values[$name],
                };
            }
        }

        return $values;
    }

    /**
     * The CSS custom properties for these values.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string> --site-name → value
     */
    public static function cssVariables(array $values): array
    {
        $values = self::clean($values);
        $vars = [];

        foreach (self::schema() as $group) {
            foreach ($group['fields'] as $name => $field) {
                $vars['--site-'.self::varName($name)] = self::cssValue($field, $values[$name]);
            }
        }

        // Font sizes for headings, scaled by "Heading size".
        $scale = (float) self::cssValue(self::field('heading_scale'), $values['heading_scale']);

        foreach (['h1' => 3.25, 'h2' => 2.4, 'h3' => 1.5, 'h4' => 1.2] as $level => $rem) {
            $vars['--site-'.$level.'-size'] = round($rem * $scale, 3).'rem';
        }

        return $vars;
    }

    /** `:root{--site-…}` plus base styles that make plain elements follow the settings. */
    public static function stylesheet(array $values): string
    {
        $declarations = '';

        foreach (self::cssVariables($values) as $name => $value) {
            $declarations .= $name.':'.$value.';';
        }

        return ':root{'.$declarations.'}';
    }

    /** A fonts.bunny.net stylesheet URL for the chosen fonts (null when only system fonts). */
    public static function fontsUrl(array $values): ?string
    {
        $values = self::clean($values);
        $families = array_filter(array_unique([
            self::FONTS[$values['heading_font']] ?? null,
            self::FONTS[$values['body_font']] ?? null,
        ]));

        return $families === [] ? null : 'https://fonts.bunny.net/css?family='.implode('|', $families).'&display=swap';
    }

    /** @return array<string, mixed>|null */
    private static function field(string $name): ?array
    {
        foreach (self::schema() as $group) {
            if (isset($group['fields'][$name])) {
                return $group['fields'][$name];
            }
        }

        return null;
    }

    private static function varName(string $name): string
    {
        return self::field($name)['var'] ?? str_replace('_', '-', $name);
    }

    private static function cssValue(array $field, mixed $value): string
    {
        $key = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        if ($field['type'] === 'select' && in_array($field['var'] ?? '', ['heading-font', 'body-font'], true)) {
            return $key === 'System'
                ? 'system-ui, -apple-system, "Segoe UI", sans-serif'
                : '"'.$key.'", system-ui, sans-serif';
        }

        return (string) ($field['css'][$key] ?? $key);
    }

    /** @return array<string, mixed> */
    private static function color(string $label, string $default, ?string $help = null): array
    {
        return array_filter(['label' => $label, 'type' => 'color', 'default' => $default, 'help' => $help]);
    }

    /** @return array<string, mixed> */
    private static function select(string $label, string $default, array $options, ?array $css, string $var): array
    {
        return array_filter(['label' => $label, 'type' => 'select', 'default' => $default, 'options' => $options, 'css' => $css, 'var' => $var]);
    }
}
