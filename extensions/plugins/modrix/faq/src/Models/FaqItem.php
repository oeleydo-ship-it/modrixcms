<?php

namespace Modrix\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    protected $table = 'faq_items';

    protected $fillable = [
        'category',
        'question',
        'answer_markdown',
        'sort_order',
        'is_published',
        'helpful_yes',
        'helpful_no',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'helpful_yes' => 'integer',
            'helpful_no' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }
}
