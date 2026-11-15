<?php

namespace Modrix\FormBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = 'fb_forms';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'settings',
        'status',
        'submissions_count',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'settings' => 'array',
            'submissions_count' => 'integer',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'form_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
