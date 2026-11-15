<?php

namespace Modrix\PromoBars\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PromoCampaign extends Model
{
    protected $table = 'promo_campaigns';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'title',
        'body',
        'badge_text',
        'cta_label',
        'cta_url',
        'coupon_code',
        'bg_color',
        'text_color',
        'accent_color',
        'trigger_type',
        'trigger_value',
        'ends_at',
        'is_active',
        'impressions',
        'clicks',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ends_at' => 'datetime',
            'trigger_value' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    /** @return array<string, mixed> */
    public function toPublicPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'badge_text' => $this->badge_text,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'coupon_code' => $this->coupon_code,
            'bg_color' => $this->bg_color,
            'text_color' => $this->text_color,
            'accent_color' => $this->accent_color,
            'trigger_type' => $this->trigger_type,
            'trigger_value' => $this->trigger_value,
            'ends_at' => $this->ends_at?->toIso8601String(),
            'track_url' => route('plugin.modrix.promo-bars.track', $this),
        ];
    }
}
