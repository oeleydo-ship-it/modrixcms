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
            'meta' => array_filter([
                'title' => $post->title.' · '.$this->settings->get('site_name'),
                'description' => (string) ($post->meta_description ?: $post->excerpt) ?: null,
                'type' => 'article',
                'image' => $post->coverUrl(),
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
