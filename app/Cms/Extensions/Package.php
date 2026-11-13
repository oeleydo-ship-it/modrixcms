<?php

namespace App\Cms\Extensions;

use App\Models\Extension;

/**
 * A theme or plugin found on disk, with its install state. If its manifest is broken,
 * $manifest is null and $problem says why (it is still listed so the owner can delete it).
 */
final class Package
{
    public function __construct(
        public readonly string $type,
        public readonly string $name,
        public readonly string $path,
        public readonly ?Manifest $manifest,
        public readonly ?Extension $record,
        public readonly ?string $problem = null,
    ) {}

    public function displayName(): string
    {
        return $this->manifest?->displayName() ?? $this->name;
    }

    public function version(): string
    {
        return $this->manifest?->version() ?? ($this->record->version ?? '?');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->record?->enabled;
    }

    public function isTrusted(): bool
    {
        return $this->record?->trusted_at !== null;
    }

    /** True when the package ships JavaScript (React components) that runs in the browser. */
    public function hasScripts(): bool
    {
        return ($this->manifest?->data['scripts'] ?? []) !== [];
    }

    public function isCompatible(): bool
    {
        return $this->manifest?->isCompatible() ?? false;
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $fields = $this->manifest?->settings() ?? [];

        return array_replace(
            SettingsSchema::defaults($fields),
            array_intersect_key((array) ($this->record->settings ?? []), SettingsSchema::defaults($fields)),
        );
    }

    /** @return array<string, int> severity → count */
    public function scanSummary(): array
    {
        return collect($this->record->scan ?? [])->countBy('severity')->all();
    }
}
