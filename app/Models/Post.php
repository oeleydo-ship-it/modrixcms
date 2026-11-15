<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use App\Support\Markdown;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'content_format', 'cover_image', 'meta_description', 'meta_title', 'focus_keyword', 'canonical_url', 'noindex', 'og_title', 'og_description', 'og_image', 'ai_generated', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'noindex' => 'boolean', 'ai_generated' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Live posts: a publish date that has passed. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function status(): string
    {
        return match (true) {
            $this->published_at === null => 'Draft',
            $this->published_at->isFuture() => 'Scheduled',
            default => 'Published',
        };
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    public function html(): string
    {
        // Posts written in the rich text editor are HTML; older ones are Markdown.
        return $this->content_format === 'html'
            ? HtmlSanitizer::clean($this->content)
            : Markdown::toHtml($this->content);
    }
}
