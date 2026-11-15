<?php

namespace Modrix\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $table = 'nl_subscribers';

    protected $fillable = [
        'email',
        'name',
        'status',
        'source',
        'tags',
        'token',
        'confirmed_at',
        'unsubscribed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeSubscribed(Builder $query): void
    {
        $query->where('status', 'subscribed');
    }
}
