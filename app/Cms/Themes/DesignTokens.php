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
        'Geist' => 'geist:400,500,600,700',
        'Bricolage Grotesque' => 'bricolage-grotesque:500,700,800',
        'DM Sans' => 'dm-sans:400,500,700',
        'Manrope' => 'manrope:400,500,600,700,800',
        'Outfit' => 'outfit:400,500,600,700,800',
        'Poppins' => 'poppins:400,500,600,700,800',
        'Sora' => 'sora:400,500,600,700,800',
        'Parkinsans' => 'parkinsans:400,500,600,700',
        'Space Grotesk' => 'space-grotesk:400,500,600,700',
        'Plus Jakarta Sans' => 'plus-jakarta-sans:400,500,600,700,800',
        'Work Sans' => 'work-sans:400,500,600,700',
        'Instrument Sans' => 'instrument-sans:400,500,600,700',
        'Mulish' => 'mulish:400,500,600,700',
        'Playfair Display' => 'playfair-display:400,600,700,800',
        'Fraunces' => 'fraunces:400,600,700,800',
        'Merriweather' => 'merriweather:400,700',
        'Lora' => 'lora:400,500,600,700',
        'Georgia' => null,
        'System' => null,
    ];

    /** Fonts already on visitors' devices (nothing to download). */
    private const SYSTEM_FONTS = [
        'Georgia' => 'Georgia, "Times New Roman", serif',
        'System' => 'system-ui, -apple-system, "Segoe UI", sans-serif',
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
                'on_primary' => self::color('Text on primary', '#ffffff', 'Text on primary-coloured buttons and banners.'),
                'primary_soft' => self::color('Primary highlight', '#6366f1', 'Gradient pair and soft brand accents.'),
                'secondary' => self::color('Secondary', '#0f172a', 'Second buttons and dark accents.'),
                'accent' => self::color('Accent', '#f59e0b', 'Badges, marker highlights and small accents.'),
                'heading' => self::color('Headings', '#0f172a'),
                'body' => self::color('Body text', '#475569'),
                'muted' => self::color('Muted text', '#64748b', 'Captions, labels and small print.'),
                'background' => self::color('Page background', '#ffffff'),
                'surface' => self::color('Surface', '#f8fafc', 'Cards and light sections.'),
                'dark' => self::color('Dark sections', '#0f172a', 'Footers, banners and dark bands.'),
                'on_dark' => self::color('Text on dark', '#ffffff'),
                'on_dark_muted' => self::color('Muted text on dark', '#cbd5e1'),
                'border' => self::color('Borders', '#e2e8f0'),
                'header_bg' => self::color('Header background', '#ffffff', 'Top navigation bar background.'),
                'header_text' => self::color('Header text', '#0f172a', 'Brand title and navigation links.'),
                'footer_bg' => self::color('Footer background', '#ffffff', 'Site footer background.'),
                'footer_text' => self::color('Footer text', '#64748b', 'Footer links and copyright text.'),
            ]],
            'typography' => ['label' => 'Typography', 'description' => 'Fonts, sizes and spacing for headings and body text.', 'fields' => [
                'heading_font' => self::select('Heading font', 'Inter', $fonts, null, 'heading-font'),
                'body_font' => self::select('Body font', 'Inter', $fonts, null, 'body-font'),
                'base_size' => self::select('Base text size', '16', ['15' => 'Small (15px)', '16' => 'Normal (16px)', '17' => 'Large (17px)', '18' => 'Extra large (18px)'], ['15' => '15px', '16' => '16px', '17' => '17px', '18' => '18px'], 'font-size'),
                'heading_weight' => self::select('Heading weight', '700', ['400' => 'Regular', '500' => 'Medium', '600' => 'Semibold', '700' => 'Bold', '800' => 'Extra bold'], null, 'heading-weight'),
                'heading_scale' => self::select('Heading size', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'large' => 'Large'], ['compact' => '0.85', 'normal' => '1', 'large' => '1.15'], 'heading-scale'),
                'heading_tracking' => self::select('Heading letter spacing', 'normal', ['tight' => 'Tight (-0.03em)', 'normal' => 'Normal (-0.02em)', 'balanced' => 'Balanced (-0.01em)', 'wide' => 'Wide (0.02em)'], ['tight' => '-0.03em', 'normal' => '-0.02em', 'balanced' => '-0.01em', 'wide' => '0.02em'], 'heading-tracking'),
                'heading_line_height' => self::select('Heading line height', '1.08', ['1.05' => 'Ultra tight (1.05)', '1.08' => 'Tight (1.08)', '1.2' => 'Normal (1.2)', '1.3' => 'Relaxed (1.3)'], null, 'heading-line-height'),
                'line_height' => self::select('Body line spacing', '1.6', ['1.4' => 'Tight', '1.5' => 'Comfortable', '1.6' => 'Normal', '1.8' => 'Relaxed'], null, 'line-height'),
                'eyebrow_style' => self::select('Eyebrow label case', 'uppercase', ['uppercase' => 'Uppercase', 'none' => 'Sentence case'], null, 'eyebrow-transform'),
            ]],
            'buttons' => ['label' => 'Buttons', 'description' => 'How buttons look and behave on hover across the site.', 'fields' => [
                'button_shape' => self::select('Shape', 'rounded', ['square' => 'Square', 'rounded' => 'Rounded', 'pill' => 'Pill'], ['square' => '4px', 'rounded' => '10px', 'pill' => '999px'], 'button-radius'),
                'button_size' => self::select('Size', 'md', ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'], ['sm' => '8px 16px', 'md' => '12px 22px', 'lg' => '16px 30px'], 'button-padding'),
                'button_style' => self::select('Main button style', 'filled', ['filled' => 'Filled', 'outline' => 'Outline'], null, 'button-style'),
                'button_weight' => self::select('Text weight', '600', ['500' => 'Medium', '600' => 'Semibold', '700' => 'Bold'], null, 'button-weight'),
                'button_border_width' => self::select('Border thickness', '1.5px', ['1px' => 'Thin (1px)', '1.5px' => 'Medium (1.5px)', '2px' => 'Thick (2px)'], null, 'button-border-width'),
                'button_hover' => self::select('Hover effect', 'glow', ['none' => 'None', 'lift' => 'Subtle lift', 'glow' => 'Lift with glow', 'scale' => 'Gentle zoom'], null, 'button-hover'),
                'button_uppercase' => ['label' => 'Uppercase text', 'type' => 'toggle', 'default' => false, 'var' => 'button-transform', 'css' => ['1' => 'uppercase', '0' => 'none']],
            ]],
            'header' => ['label' => 'Header', 'description' => 'Height, sticky behaviour and link style for the top navigation bar.', 'fields' => [
                'header_height' => self::select('Header height', '68', ['56' => 'Compact (56px)', '68' => 'Normal (68px)', '84' => 'Tall (84px)'], ['56' => '56px', '68' => '68px', '84' => '84px'], 'header-height'),
                'header_sticky' => ['label' => 'Sticky navigation bar', 'type' => 'toggle', 'default' => true, 'var' => 'header-position', 'css' => ['1' => 'sticky', '0' => 'relative']],
                'header_blur' => ['label' => 'Frosted glass backdrop', 'type' => 'toggle', 'default' => true, 'var' => 'header-blur', 'css' => ['1' => 'saturate(160%) blur(12px)', '0' => 'none']],
                'header_border' => ['label' => 'Always show bottom border', 'type' => 'toggle', 'default' => false, 'var' => 'header-border', 'css' => ['1' => '1px solid var(--site-border)', '0' => '1px solid transparent']],
                'nav_weight' => self::select('Menu link weight', '500', ['400' => 'Regular', '500' => 'Medium', '600' => 'Semibold'], null, 'nav-weight'),
                'nav_gap' => self::select('Menu link spacing', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'wide' => 'Wide'], ['compact' => '1.1rem', 'normal' => '1.75rem', 'wide' => '2.5rem'], 'nav-gap'),
            ]],
            'hero' => ['label' => 'Frontpage & Hero', 'description' => 'Hero section spacing, decorative highlights and badge shapes.', 'fields' => [
                'hero_padding' => self::select('Hero vertical spacing', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'tall' => 'Spacious'], ['compact' => '3rem 0 2.5rem', 'normal' => '5rem 0 4rem', 'tall' => '7.5rem 0 5.5rem'], 'hero-padding'),
                'hero_highlight' => self::select('Headline highlight style', 'underline', ['underline' => 'Marker underline', 'pill' => 'Accent pill', 'plain' => 'Accent text only'], null, 'hero-highlight'),
                'badge_radius' => self::select('Badges & pills shape', 'pill', ['square' => 'Square (4px)', 'rounded' => 'Rounded (10px)', 'pill' => 'Pill (999px)'], ['square' => '4px', 'rounded' => '10px', 'pill' => '999px'], 'badge-radius'),
                'hero_glow' => ['label' => 'Ambient hero background glow', 'type' => 'toggle', 'default' => true, 'var' => 'hero-glow', 'css' => ['1' => '1', '0' => '0']],
            ]],
            'sections' => ['label' => 'Sections & Cards', 'description' => 'Spacing, width, shadows and card styling for page sections.', 'fields' => [
                'section_spacing' => self::select('Section spacing', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'roomy' => 'Roomy'], ['compact' => '56px', 'normal' => '88px', 'roomy' => '128px'], 'section-padding'),
                'content_width' => self::select('Content width', '1140', ['960' => 'Narrow (960px)', '1140' => 'Normal (1140px)', '1280' => 'Wide (1280px)', '1440' => 'Extra wide (1440px)', '1560' => 'Spacious (1560px)', 'full' => 'Full width (edge to edge)'], ['960' => '960px', '1140' => '1140px', '1280' => '1280px', '1440' => '1440px', '1560' => '1560px', 'full' => '100%'], 'content-width'),
                'grid_gap' => self::select('Grid column spacing', 'normal', ['tight' => 'Tight (12px)', 'normal' => 'Normal (16px)', 'wide' => 'Wide (24px)'], ['tight' => '12px', 'normal' => '16px', 'wide' => '24px'], 'grid-gap'),
                'card_radius' => self::select('Card corners', 'soft', ['square' => 'Square', 'soft' => 'Slightly rounded', 'round' => 'Rounded', 'extra' => 'Very rounded'], ['square' => '0px', 'soft' => '8px', 'round' => '16px', 'extra' => '24px'], 'card-radius'),
                'card_padding' => self::select('Card inner padding', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'roomy' => 'Roomy'], ['compact' => '1.15rem', 'normal' => '1.6rem', 'roomy' => '2.2rem'], 'card-padding'),
                'section_radius' => self::select('Featured band corners', 'round', ['none' => 'Square (0px)', 'soft' => 'Soft (16px)', 'round' => 'Rounded (36px)'], ['none' => '0px', 'soft' => '16px', 'round' => '36px'], 'section-radius'),
                'shadow' => self::select('Shadows', 'soft', ['none' => 'None', 'soft' => 'Soft', 'strong' => 'Strong'], ['none' => 'none', 'soft' => '0 10px 30px -12px rgba(15, 23, 42, 0.18)', 'strong' => '0 24px 60px -20px rgba(15, 23, 42, 0.35)'], 'shadow'),
                'card_hover' => ['label' => 'Lift cards on hover', 'type' => 'toggle', 'default' => true, 'var' => 'card-hover-transform', 'css' => ['1' => 'translateY(-4px)', '0' => 'none']],
            ]],
            'links' => ['label' => 'Links & Footer', 'description' => 'Text link styling and footer layout.', 'fields' => [
                'link' => self::color('Link colour', '#4f46e5'),
                'link_hover' => self::color('Link hover colour', '#3a22c9'),
                'link_underline' => ['label' => 'Underline links', 'type' => 'toggle', 'default' => true, 'var' => 'link-decoration', 'css' => ['1' => 'underline', '0' => 'none']],
                'link_underline_offset' => self::select('Underline offset', '3px', ['2px' => 'Tight (2px)', '3px' => 'Normal (3px)', '5px' => 'Offset (5px)'], null, 'link-underline-offset'),
                'footer_padding' => self::select('Footer spacing', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'roomy' => 'Roomy'], ['compact' => '1.4rem 0', 'normal' => '2.2rem 0', 'roomy' => '3.5rem 0'], 'footer-padding'),
                'footer_border' => ['label' => 'Show footer top border', 'type' => 'toggle', 'default' => true, 'var' => 'footer-border', 'css' => ['1' => '1px solid var(--site-border)', '0' => '0']],
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

        // The main button's colours, from "Main button style" and "Hover effect".
        $outline = $values['button_style'] === 'outline';
        $vars['--site-button-bg'] = $outline ? 'transparent' : 'var(--site-primary)';
        $vars['--site-button-fg'] = $outline ? 'var(--site-primary)' : 'var(--site-on-primary)';
        $vars['--site-button-border'] = $outline
            ? 'var(--site-button-border-width) solid var(--site-primary)'
            : 'var(--site-button-border-width) solid transparent';

        [$hoverTransform, $hoverShadow] = match ($values['button_hover']) {
            'none' => ['none', 'none'],
            'lift' => ['translateY(-2px)', 'none'],
            'scale' => ['scale(1.03)', 'none'],
            default => ['translateY(-2px)', '0 12px 24px -10px var(--site-primary)'],
        };
        $vars['--site-button-hover-transform'] = $hoverTransform;
        $vars['--site-button-hover-shadow'] = $hoverShadow;

        [$emBg, $emPadding, $emRadius] = match ($values['hero_highlight']) {
            'pill' => ['color-mix(in srgb, var(--site-accent) 45%, transparent)', '0 .25em', '.2em'],
            'plain' => ['none', '0', '0'],
            default => ['linear-gradient(transparent 62%, var(--site-accent) 62%, var(--site-accent) 92%, transparent 92%)', '0 .1em', '0'],
        };
        $vars['--site-hero-em-bg'] = $emBg;
        $vars['--site-hero-em-padding'] = $emPadding;
        $vars['--site-hero-em-radius'] = $emRadius;

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

    /**
     * The theme colours offered in the builder's colour pickers. Picking one stores `value`
     * (a linked reference), so the block follows later changes to the theme.
     *
     * @return list<array{name: string, label: string, color: string, value: string}>
     */
    public static function swatches(array $values): array
    {
        $values = self::clean($values);
        $swatches = [];

        foreach (self::schema()['colors']['fields'] as $name => $field) {
            $swatches[] = ['name' => $name, 'label' => $field['label'], 'color' => $values[$name], 'value' => self::ref($name)];
        }

        return $swatches;
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
            return self::SYSTEM_FONTS[$key] ?? '"'.$key.'", system-ui, sans-serif';
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
