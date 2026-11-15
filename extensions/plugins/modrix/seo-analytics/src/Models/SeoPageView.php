<?php

namespace Modrix\SeoAnalytics\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPageView extends Model
{
    protected $table = 'seo_page_views';

    protected $fillable = [
        'path',
        'page_title',
        'referrer_host',
        'device',
        'viewed_date',
        'views',
    ];

    protected function casts(): array
    {
        return [
            'viewed_date' => 'date',
            'views' => 'integer',
        ];
    }
}
