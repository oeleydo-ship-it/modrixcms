<?php

namespace App\Http\Controllers;

use App\Cms\Frontend\PostPresenter;
use App\Models\Category;
use App\Models\Post;
use App\Support\Settings;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private Settings $settings) {}

    public function index(): Response
    {
        return $this->list(null);
    }

    public function category(Category $category): Response
    {
        return $this->list($category);
    }

    public function show(Post $post): Response
    {
        abort_unless($post->status() === 'Published', 404);

        $post->load('category', 'author');

        $related = Post::published()
            ->with('category')
            ->whereKeyNot($post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return inertia('Site/BlogPost', [
            'post' => PostPresenter::full($post),
            'related' => $related->map(fn (Post $item) => PostPresenter::card($item))->all(),
            'meta' => $this->postMeta($post),
        ]);
    }

    /**
     * What search engines and social networks read: the post's own SEO fields where it has them,
     * the title, excerpt and cover otherwise.
     *
     * @return array<string, mixed>
     */
    private function postMeta(Post $post): array
    {
        $siteName = (string) $this->settings->get('site_name');
        $description = (string) ($post->meta_description ?: $post->excerpt);
        $image = $post->og_image ?: $post->coverUrl();
        $canonical = $post->canonical_url ?: null;

        if ($canonical !== null) {
            // The SEO plugin would otherwise add the page's own address as well.
            request()->attributes->set('canonical_url', $canonical);
        }

        return array_filter([
            'title' => ($post->meta_title ?: $post->title).' · '.$siteName,
            'description' => $description ?: null,
            'type' => 'article',
            'image' => $image,
            'noindex' => $post->noindex ?: null,
            'canonical' => $canonical,
            'ogTitle' => $post->og_title ?: null,
            'ogDescription' => $post->og_description ?: null,
            'jsonLd' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'description' => $description ?: null,
                'image' => $image ? url($image) : null,
                'datePublished' => $post->published_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
                'publisher' => ['@type' => 'Organization', 'name' => $siteName],
                'mainEntityOfPage' => $canonical ?? route('blog.show', $post),
                'keywords' => $post->focus_keyword ?: null,
            ]),
        ]);
    }

    private function list(?Category $category): Response
    {
        $posts = Post::published()
            ->with('category')
            ->when($category, fn ($q) => $q->whereBelongsTo($category))
            ->latest('published_at')
            ->paginate(9)
            ->through(fn (Post $post) => PostPresenter::card($post));

        $siteName = $this->settings->get('site_name');

        return inertia('Site/BlogIndex', [
            'posts' => $posts,
            'category' => $category ? ['name' => $category->name, 'slug' => $category->slug] : null,
            'categories' => Category::whereHas('posts', fn ($q) => $q->published())->orderBy('name')->get()
                ->map(fn (Category $item) => ['name' => $item->name, 'slug' => $item->slug, 'url' => route('blog.category', $item)])->all(),
            'allUrl' => route('blog.index'),
            'meta' => [
                'title' => ($category ? $category->name.' · ' : '').'Blog · '.$siteName,
                'description' => $category ? 'Posts about '.$category->name.'.' : 'News, guides and updates from '.$siteName.'.',
            ],
        ]);
    }
}
