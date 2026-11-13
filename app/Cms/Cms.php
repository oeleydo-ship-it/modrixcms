<?php

namespace App\Cms;

use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Themes\ThemeManager;
use App\Models\Extension;

/**
 * Helpers for plugin and theme code.
 *
 *   Cms::setting('acme/contact-form', 'recipient')
 *   Cms::asset('acme/contact-form', 'form.css')
 *   Cms::themeSetting('brand')
 *   Cms::themeAsset('theme.css')
 */
class Cms
{
    public static function version(): string
    {
        return config('cms.version');
    }

    public static function safeMode(): bool
    {
        return (bool) config('cms.safe_mode') || is_file(self::safeModeFile());
    }

    /** While this file exists, safe mode is on (php artisan cms:safe-mode on|off). */
    public static function safeModeFile(): string
    {
        return config('cms.storage_path').DIRECTORY_SEPARATOR.'safe-mode';
    }

    /** A plugin's setting (Admin → Plugins → Settings), or its declared default. */
    public static function setting(string $plugin, string $key, mixed $default = null): mixed
    {
        return app(PluginLoader::class)->settings($plugin)[$key] ?? $default;
    }

    /** URL of a file in a plugin's public/ folder. */
    public static function asset(string $plugin, string $path): string
    {
        return self::publicUrl(Extension::PLUGIN, $plugin, $path);
    }

    /** A setting of the active theme (Appearance → Customize), or its declared default. */
    public static function themeSetting(string $key, mixed $default = null): mixed
    {
        return app(ThemeManager::class)->settings()[$key] ?? $default;
    }

    /** URL of a file in the active theme's public/ folder. */
    public static function themeAsset(string $path): string
    {
        return self::publicUrl(Extension::THEME, app(ThemeManager::class)->name(), $path);
    }

    public static function publicUrl(string $type, string $name, string $path): string
    {
        $path = ltrim(preg_replace('#^public/#', '', $path), '/');

        return asset('extensions/'.($type === Extension::THEME ? 'themes' : 'plugins').'/'.$name.'/'.$path);
    }

    public static function registry(): ExtensionRegistry
    {
        return app(ExtensionRegistry::class);
    }
}
