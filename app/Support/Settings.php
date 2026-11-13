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
    private const ENCRYPTED = ['mail_password'];

    public const DEFAULTS = [
        // General
        'site_name' => 'Modrix Commerce',
        'tagline' => 'Free, open-source e-commerce you host yourself',
        'contact_email' => null,
        'footer_text' => 'Modrix Commerce · MIT License',
        'github_url' => null,
        'marketplace_url' => null,
        'social_x' => null,
        'social_facebook' => null,
        'social_linkedin' => null,
        'social_youtube' => null,

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
