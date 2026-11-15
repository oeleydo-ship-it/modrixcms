<?php

namespace Modrix\SeoAnalytics\Http\Admin;

use App\Models\Page;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class SeoAuditController
{
    public function index(): Response
    {
        $pages = Page::query()->where('is_published', true)->orderBy('title')->get(['id', 'title', 'slug', 'is_home', 'meta_title', 'meta_description']);
        $posts = Post::published()->latest('published_at')->get(['id', 'title', 'slug', 'excerpt', 'meta_description']);

        $pageChecks = $pages->map(fn (Page $p): array => [
            'id' => $p->id,
            'title' => $p->title,
            'url' => $p->is_home ? route('home') : route('pages.show', $p),
            'edit_url' => route('admin.pages.edit', $p),
            'has_meta_title' => filled($p->meta_title),
            'has_meta_description' => filled($p->meta_description),
            'meta_description_length' => mb_strlen((string) $p->meta_description),
        ])->all();

        $postChecks = $posts->map(fn (Post $p): array => [
            'id' => $p->id,
            'title' => $p->title,
            'url' => route('blog.show', $p),
            'edit_url' => route('admin.posts.edit', $p),
            'has_meta_description' => filled($p->meta_description ?: $p->excerpt),
            'meta_description_length' => mb_strlen((string) ($p->meta_description ?: $p->excerpt)),
        ])->all();

        return Inertia::render('plugin:modrix/seo-analytics/SeoAuditIndex', [
            'sitemapUrl' => route('plugin.modrix.seo-analytics.sitemap'),
            'indexedCount' => count($pageChecks) + count($postChecks) + 2,
            'pageChecks' => $pageChecks,
            'postChecks' => $postChecks,
        ]);
    }
}
