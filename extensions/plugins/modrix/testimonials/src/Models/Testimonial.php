<?php

namespace Modrix\Testimonials\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = [
        'author_name',
        'author_role',
        'company',
        'author_email',
        'rating',
        'quote',
        'status',
        'is_featured',
        'verified',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_featured' => 'boolean',
            'verified' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }
}
