<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->with('category')
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category?->name,
                'status' => $post->status(),
                'date' => $post->published_at?->format('M j, Y H:i'),
                'url' => route('blog.show', $post),
            ]),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('q', 'category'),
        ]);
    }

    public function create(): Response
    {
        return $this->form(new Post);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = $request->user()->id;

        $post = Post::create($data);

        return redirect()->route('admin.posts.edit', $post)->with('status', 'Post created.');
    }

    public function edit(Post $post): Response
    {
        return $this->form($post);
    }

    private function form(Post $post): Response
    {
        return Inertia::render('Admin/Posts/Form', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'category_id' => $post->category_id,
                'meta_description' => $post->meta_description,
                'status' => match ($post->exists ? $post->status() : 'Draft') {
                    'Published' => 'published',
                    'Scheduled' => 'scheduled',
                    default => 'draft',
                },
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'cover' => $post->coverUrl(),
                'url' => $post->exists ? route('blog.show', $post) : null,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'blogUrl' => url('/blog'),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->validated($request, $post));

        return redirect()->route('admin.posts.edit', $post)->with('status', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Post deleted.');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $request->merge(['slug' => Slugs::from($request->input('slug') ?: $request->input('title'))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'max:200', Rule::unique('posts', 'slug')->ignore($post)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:200000'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'required_if:status,scheduled', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        $data['published_at'] = match ($data['status']) {
            'draft' => null,
            'scheduled' => $data['published_at'],
            // Keep the original date when re-saving a published post.
            'published' => $post?->published_at && $post->published_at->isPast() ? $post->published_at : now(),
        };

        if ($request->hasFile('cover_image') || $request->boolean('remove_cover')) {
            if ($post?->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }

            $data['cover_image'] = $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('blog', 'public')
                : null;
        } else {
            unset($data['cover_image']);
        }

        unset($data['status'], $data['remove_cover']);

        return $data;
    }
}
