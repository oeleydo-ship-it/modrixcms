<?php

namespace Modrix\SocialMedia\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SocialProfile extends Model
{
    protected $table = 'social_profiles';

    protected $fillable = [
        'platform',
        'label',
        'handle',
        'url',
        'followers',
        'sort_order',
        'is_active',
        'open_new_tab',
        'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'open_new_tab' => 'boolean',
            'clicks_count' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
