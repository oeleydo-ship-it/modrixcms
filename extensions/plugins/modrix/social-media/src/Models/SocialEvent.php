<?php

namespace Modrix\SocialMedia\Models;

use Illuminate\Database\Eloquent\Model;

class SocialEvent extends Model
{
    public $timestamps = false;

    protected $table = 'social_events';

    protected $fillable = [
        'event_type',
        'platform',
        'page_url',
        'page_title',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
