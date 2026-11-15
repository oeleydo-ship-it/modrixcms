<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'blocks', 'editor', 'meta_title', 'meta_description', 'is_published', 'is_home', 'show_in_menu', 'menu_order', 'show_site_header', 'show_site_footer'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /** Built with the form-based block editor. */
    public const EDITOR_BLOCKS = 'blocks';

    /** Built with the visual page builder (document / published_document). */
    public const EDITOR_BUILDER = 'builder';

    protected $attributes = ['blocks' => '[]', 'editor' => self::EDITOR_BLOCKS];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'document' => 'array',
            'published_document' => 'array',
            'version' => 'integer',
            'schema_version' => 'integer',
            'saves_since_snapshot' => 'integer',
            'published_version' => 'integer',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'is_home' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'integer',
            'show_site_header' => 'boolean',
            'show_site_footer' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->latest('created_at');
    }

    public function usesBuilder(): bool
    {
        return $this->editor === self::EDITOR_BUILDER;
    }

    /** The draft differs from what visitors see. */
    public function hasUnpublishedChanges(): bool
    {
        return $this->usesBuilder() && $this->published_version !== $this->version;
    }

    /** The site header page (shown on top of visual-builder pages). */
    public const ROLE_HEADER = 'header';

    /** The site footer page (shown below visual-builder pages). */
    public const ROLE_FOOTER = 'footer';

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNull('role');
    }

    /** Ordinary pages: not the site header or footer. */
    public function scopeRegular(Builder $query): Builder
    {
        return $query->whereNull('role');
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->published()->where('show_in_menu', true)->orderBy('menu_order')->orderBy('title');
    }

    /** Blocks shown on the site (hidden ones left out). */
    public function visibleBlocks(): array
    {
        return array_values(array_filter($this->blocks ?? [], fn ($block) => empty($block['hidden'])));
    }

    /** The page used for "/", if one is set and published. */
    public static function home(): ?self
    {
        return static::query()->published()->where('is_home', true)->first();
    }
}
