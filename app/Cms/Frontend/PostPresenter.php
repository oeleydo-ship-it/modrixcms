<?php

namespace App\Cms\Frontend;

use App\Models\Post;

/** Blog posts as props for the React components. */
class PostPresenter
{
    /** @return array<string, mixed> */
    public static function card(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'url' => route('blog.show', $post),
            'excerpt' => $post->excerpt,
            'cover' => $post->coverUrl(),
            'date' => $post->published_at?->toDateString(),
            'dateLabel' => $post->published_at?->format('M j, Y'),
            'category' => $post->category ? ['name' => $post->category->name, 'url' => route('blog.category', $post->category)] : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function full(Post $post): array
    {
        return self::card($post) + [
            'html' => $post->html(),
            'author' => $post->author?->name,
            'dateLong' => $post->published_at?->format('F j, Y'),
        ];
    }
}
