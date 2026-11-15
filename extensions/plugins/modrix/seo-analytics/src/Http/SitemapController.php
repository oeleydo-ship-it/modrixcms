<?php

namespace Modrix\SeoAnalytics\Http;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $urls = [];
        $urls[] = [
            'loc' => route('home'),
            'lastmod' => now()->toAtomString(),
            'priority' => '1.0',
        ];

        Page::query()->where('is_published', true)->where('is_home', false)->get(['slug', 'updated_at'])
            ->each(function (Page $page) use (&$urls): void {
                $urls[] = [
                    'loc' => route('pages.show', $page),
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'priority' => '0.8',
                ];
            });

        $urls[] = [
            'loc' => route('blog.index'),
            'lastmod' => now()->toAtomString(),
            'priority' => '0.8',
        ];

        Category::query()->whereHas('posts', fn ($q) => $q->published())->get(['slug', 'updated_at'])
            ->each(function (Category $category) use (&$urls): void {
                $urls[] = [
                    'loc' => route('blog.category', $category),
                    'lastmod' => $category->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ];
            });

        Post::published()->latest('published_at')->get(['slug', 'updated_at', 'published_at'])
            ->each(function (Post $post) use (&$urls): void {
                $urls[] = [
                    'loc' => route('blog.show', $post),
                    'lastmod' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
                    'priority' => '0.7',
                ];
            });

        $items = '';

        foreach ($urls as $entry) {
            $items .= '<url><loc>'.e($entry['loc']).'</loc>';

            if (! empty($entry['lastmod'])) {
                $items .= '<lastmod>'.e($entry['lastmod']).'</lastmod>';
            }

            $items .= '<priority>'.e($entry['priority']).'</priority></url>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$items
            .'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
