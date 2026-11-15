<?php

namespace Modrix\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $table = 'nl_campaigns';

    protected $fillable = [
        'subject',
        'preview_text',
        'body_markdown',
        'target_tag',
        'status',
        'recipients_count',
        'failed_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }
}
