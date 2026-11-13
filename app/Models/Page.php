<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'blocks', 'meta_title', 'meta_description', 'is_published', 'is_home', 'show_in_menu', 'menu_order'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $attributes = ['blocks' => '[]'];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_published' => 'boolean',
            'is_home' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
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
