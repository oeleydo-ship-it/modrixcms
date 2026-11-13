<?php

namespace App\Cms\Frontend;

use App\Cms\Cms;
use App\Cms\Extensions\Package;
use App\Models\Extension;

/** Themes and plugins as props for the admin screens. */
class PackagePresenter
{
    /** @return array<string, mixed> */
    public static function base(Package $package): array
    {
        [$vendor, $slug] = explode('/', $package->name);
        $manifest = $package->manifest;

        return [
            'name' => $package->name,
            'routeKey' => ['vendor' => $vendor, 'package' => $slug],
            'displayName' => $package->displayName(),
            'version' => $package->version(),
            'author' => $manifest?->author() ?: null,
            'description' => $manifest?->description() ?: null,
            'license' => $manifest->data['license'] ?? null,
            'problem' => $package->problem,
            'compatible' => $package->isCompatible(),
            'usable' => $manifest !== null && $package->isCompatible(),
            'cmsVersion' => $manifest?->cmsVersion(),
            'siteVersion' => Cms::version(),
            'trusted' => $package->isTrusted(),
            'hasSettings' => ($manifest?->settings() ?? []) !== [],
            'scan' => $package->record?->scan,
            'scanSummary' => (object) $package->scanSummary(),
        ];
    }

    /** @return array<string, mixed> */
    public static function theme(Package $theme, string $active): array
    {
        $screenshot = $theme->manifest->data['screenshot'] ?? null;

        return self::base($theme) + [
            'active' => $theme->name === $active,
            'isDefault' => $theme->name === config('cms.default_theme'),
            'needsTrust' => ! $theme->isTrusted() && $theme->hasScripts(),
            'screenshot' => $screenshot && is_file($theme->manifest->file($screenshot))
                ? Cms::publicUrl(Extension::THEME, $theme->name, $screenshot)
                : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function plugin(Package $plugin): array
    {
        $data = $plugin->manifest?->data ?? [];
        $hasMigrations = is_dir($plugin->path.'/database/migrations');

        return self::base($plugin) + [
            'enabled' => $plugin->isEnabled(),
            'error' => $plugin->record?->error,
            'hasMigrations' => $hasMigrations,
            'trustedAt' => $plugin->record?->trusted_at?->toDayDateTimeString(),
            'trustedBy' => $plugin->record?->trustedBy?->name,
            'adds' => array_values(array_filter([
                isset($data['routes']['web']) ? 'public pages' : null,
                isset($data['routes']['admin']) ? 'admin screens' : null,
                ! empty($data['scripts']) ? 'React components' : null,
                $hasMigrations ? 'database tables' : null,
                ! empty($data['settings']) ? 'settings' : null,
            ])),
        ];
    }

    /**
     * Props for the generated settings screen.
     *
     * @return array<string, mixed>
     */
    public static function settingsForm(Package $package, string $title, string $action, string $back): array
    {
        return [
            'title' => $title,
            'subtitle' => $package->name.' · v'.$package->version(),
            'fields' => $package->manifest->settings(),
            'values' => (object) $package->settings(),
            'action' => $action,
            'back' => $back,
        ];
    }
}
