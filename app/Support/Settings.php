<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Site settings stored in the `settings` table (key → value), cached.
 */
class Settings
{
    private const CACHE_KEY = 'site.settings';

    /** Stored encrypted at rest. */
    private const ENCRYPTED = ['mail_password', 'ai_api_key'];

    public const DEFAULTS = [
        // General
        'site_name' => 'Modrix CMS',
        'tagline' => 'Free, open-source e-commerce you host yourself',
        'contact_email' => null,
        'footer_text' => 'Modrix CMS · MIT License',
        'github_url' => null,
        'marketplace_url' => null,
        'social_x' => null,
        'social_facebook' => null,
        'social_linkedin' => null,
        'social_youtube' => null,

        // AI API (configured under General settings)
        'ai_provider' => 'openai',
        'ai_api_key' => null,
        'ai_model' => 'gpt-4o-mini',
        'ai_base_url' => null,

        // SMTP
        'mail_enabled' => false,
        'mail_host' => null,
        'mail_port' => 587,
        'mail_encryption' => 'tls',
        'mail_username' => null,
        'mail_password' => null,
        'mail_from_address' => null,
        'mail_from_name' => null,

        // Branding
        'logo' => null,
        'favicon' => null,

        // Appearance
        'active_theme' => 'modrix/default',

        // Admin UI & Theme (Reference defaults)
        'admin_sidebar_bg' => '#0a0a0a',
        'admin_sidebar_text' => '#d4d4d4',
        'admin_sidebar_active_bg' => '#2b2b2b',
        'admin_sidebar_active_text' => '#ffffff',
        'admin_sidebar_width' => '240',
        'admin_canvas_bg' => '#ffffff',
        'admin_surface_bg' => '#ffffff',
        'admin_surface_muted' => '#f7f7f7',
        'admin_primary_color' => '#1f1f1f',
        'admin_accent_color' => '#5b3df5',
        'admin_brand_color' => '#22a559',
        'admin_text_color' => '#1a1a1a',
        'admin_muted_color' => '#616161',
        'admin_border_color' => '#e3e3e3',
        'admin_font_family' => 'Inter',
        'admin_heading_font' => 'Inter',
        'admin_font_size' => '13px',
        'admin_heading_weight' => '600',
        'admin_letter_spacing' => '-0.01em',
        'admin_spacing' => 'default',
        'admin_content_padding' => '24px',
        'admin_radius' => '12px',
        'admin_button_radius' => '999px',
        'admin_frame_inset' => '6px',
        // Blank: the sidebar shows the site name and no environment badge.
        'admin_store_handle' => '',
        'admin_store_badge' => '',
        'admin_avatar_color' => '#5b3df5',
        'admin_show_assistant' => true,
        'admin_assistant_label' => 'Work with Sidekick',
    ];

    /** @var array<int, string> */
    public const ADMIN_THEME_KEYS = [
        'admin_sidebar_bg',
        'admin_sidebar_text',
        'admin_sidebar_active_bg',
        'admin_sidebar_active_text',
        'admin_sidebar_width',
        'admin_canvas_bg',
        'admin_surface_bg',
        'admin_surface_muted',
        'admin_primary_color',
        'admin_accent_color',
        'admin_brand_color',
        'admin_text_color',
        'admin_muted_color',
        'admin_border_color',
        'admin_font_family',
        'admin_heading_font',
        'admin_font_size',
        'admin_heading_weight',
        'admin_letter_spacing',
        'admin_spacing',
        'admin_content_padding',
        'admin_radius',
        'admin_button_radius',
        'admin_frame_inset',
        'admin_store_handle',
        'admin_store_badge',
        'admin_avatar_color',
        'admin_show_assistant',
        'admin_assistant_label',
    ];

    private ?array $loaded = null;

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $stored = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Setting::query()->pluck('value', 'key')->all(),
            );
        } catch (Throwable) {
            // No database yet (fresh install, before migrations): defaults, not remembered.
            return self::DEFAULTS;
        }

        foreach (self::ENCRYPTED as $key) {
            if (! empty($stored[$key])) {
                try {
                    $stored[$key] = Crypt::decryptString($stored[$key]);
                } catch (DecryptException) {
                    $stored[$key] = null;
                }
            }
        }

        return $this->loaded = array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (in_array($key, self::ENCRYPTED, true) && filled($value)) {
                $value = Crypt::encryptString((string) $value);
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /** "github" or "marketplace": the admin setting, else config/site.php. */
    public function link(string $name): string
    {
        return $this->get("{$name}_url") ?: (string) config("site.links.{$name}", '#');
    }

    public function fileUrl(string $key): ?string
    {
        $path = $this->get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** @return array<string, mixed> */
    public function adminTheme(): array
    {
        $values = [];

        foreach (self::ADMIN_THEME_KEYS as $key) {
            if ($key === 'admin_show_assistant') {
                $values[$key] = $this->bool($key);
            } else {
                $values[$key] = (string) ($this->get($key, self::DEFAULTS[$key] ?? '') ?? '');
            }
        }

        return $values;
    }

    /** Generates inline CSS custom properties for the admin shell. */
    public function adminCssVariables(): string
    {
        $theme = $this->adminTheme();

        $fontMap = [
            'Inter' => '"Inter", -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, sans-serif',
            'System UI' => '-apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, sans-serif',
            'Plus Jakarta Sans' => '"Plus Jakarta Sans", "Inter", system-ui, sans-serif',
            'DM Sans' => '"DM Sans", "Inter", system-ui, sans-serif',
            'Bricolage Grotesque' => '"Bricolage Grotesque", "Inter", system-ui, sans-serif',
            'JetBrains Mono' => '"JetBrains Mono", ui-monospace, monospace',
        ];

        $spacingMap = [
            'compact' => ['nav_py' => '4px', 'card_pad' => '16px', 'gap' => '12px'],
            'default' => ['nav_py' => '6px', 'card_pad' => '20px', 'gap' => '16px'],
            'comfortable' => ['nav_py' => '8px', 'card_pad' => '24px', 'gap' => '20px'],
            'spacious' => ['nav_py' => '10px', 'card_pad' => '28px', 'gap' => '24px'],
        ];

        $density = $spacingMap[$theme['admin_spacing']] ?? $spacingMap['default'];
        $font = $fontMap[$theme['admin_font_family']] ?? $fontMap['Inter'];
        $headingFont = $fontMap[$theme['admin_heading_font']] ?? $fontMap['Inter'];
        $sidebarWidth = preg_replace('/[^0-9]/', '', (string) $theme['admin_sidebar_width']) ?: '240';

        $vars = [
            '--admin-sidebar-bg' => $theme['admin_sidebar_bg'],
            '--admin-sidebar-text' => $theme['admin_sidebar_text'],
            '--admin-sidebar-active-bg' => $theme['admin_sidebar_active_bg'],
            '--admin-sidebar-active-text' => $theme['admin_sidebar_active_text'],
            '--admin-sidebar-width' => $sidebarWidth.'px',
            '--admin-canvas-bg' => $theme['admin_canvas_bg'],
            '--admin-surface' => $theme['admin_surface_bg'],
            '--admin-surface-muted' => $theme['admin_surface_muted'],
            '--admin-primary' => $theme['admin_primary_color'],
            '--admin-accent' => $theme['admin_accent_color'],
            '--admin-brand-icon' => $theme['admin_brand_color'],
            '--ink' => $theme['admin_text_color'],
            '--muted' => $theme['admin_muted_color'],
            '--line' => $theme['admin_border_color'],
            '--bg' => $theme['admin_canvas_bg'],
            '--white' => $theme['admin_surface_bg'],
            '--brand' => $theme['admin_accent_color'],
            '--font' => $font,
            '--display' => $headingFont,
            '--admin-font-size' => $theme['admin_font_size'],
            '--admin-heading-weight' => $theme['admin_heading_weight'],
            '--admin-letter-spacing' => $theme['admin_letter_spacing'],
            '--admin-content-pad' => $theme['admin_content_padding'],
            '--radius' => $theme['admin_radius'],
            '--admin-btn-radius' => $theme['admin_button_radius'],
            '--admin-frame-inset' => $theme['admin_frame_inset'],
            '--admin-nav-py' => $density['nav_py'],
            '--admin-card-pad' => $density['card_pad'],
            '--admin-gap' => $density['gap'],
        ];

        $declarations = [];

        foreach ($vars as $name => $value) {
            $clean = preg_replace('/[;{}<>]/', '', (string) $value);
            $declarations[] = "{$name}: {$clean}";
        }

        return ':root { '.implode('; ', $declarations).'; }';
    }

    /** Points Laravel's mailer at the SMTP server saved in the admin. */
    public function applyMailConfig(): void
    {
        if (! $this->bool('mail_enabled') || blank($this->get('mail_host'))) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $this->get('mail_encryption') === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $this->get('mail_host'),
            'mail.mailers.smtp.port' => (int) $this->get('mail_port', 587),
            'mail.mailers.smtp.username' => $this->get('mail_username'),
            'mail.mailers.smtp.password' => $this->get('mail_password'),
            'mail.from.address' => $this->get('mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => $this->get('mail_from_name') ?: $this->get('site_name'),
        ]);
    }
}
