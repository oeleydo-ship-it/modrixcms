<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Install state of a theme or plugin. The package itself lives on disk under extensions/.
 *
 * @property string $type plugin|theme
 * @property string $name vendor/package
 */
#[Fillable(['type', 'name', 'version', 'enabled', 'trusted_at', 'trusted_by', 'settings', 'design', 'scan', 'error'])]
class Extension extends Model
{
    public const PLUGIN = 'plugin';

    public const THEME = 'theme';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'trusted_at' => 'datetime',
            'settings' => 'array',
            'design' => 'array',
            'scan' => 'array',
        ];
    }

    public function trustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trusted_by');
    }

    public function scopePlugins(Builder $query): void
    {
        $query->where('type', self::PLUGIN);
    }

    public function scopeThemes(Builder $query): void
    {
        $query->where('type', self::THEME);
    }
}
