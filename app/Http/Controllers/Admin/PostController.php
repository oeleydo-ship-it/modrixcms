<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Ai\AiClient;
use App\Cms\Ai\BlogGenerator;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\Markdown;
use App\Support\Settings;
use App\Support\Slugs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
            'aiConfigured' => app(AiClient::class)->isConfigured(),
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
                // The rich text editor works in HTML: older Markdown posts are converted when opened.
                'content' => $post->content_format === 'html' ? (string) $post->content : Markdown::toHtml($post->content),
                'category_id' => $post->category_id,
                'meta_description' => $post->meta_description,
                'meta_title' => $post->meta_title,
                'focus_keyword' => $post->focus_keyword,
                'canonical_url' => $post->canonical_url,
                'noindex' => (bool) $post->noindex,
                'og_title' => $post->og_title,
                'og_description' => $post->og_description,
                'og_image' => $post->og_image,
                'ai_generated' => (bool) $post->ai_generated,
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
            'siteName' => (string) app(Settings::class)->get('site_name', ''),
            'aiConfigured' => app(AiClient::class)->isConfigured(),
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
            'meta_title' => ['nullable', 'string', 'max:200'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:500'],
            'noindex' => ['nullable', 'boolean'],
            'og_title' => ['nullable', 'string', 'max:200'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'url:http,https', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'required_if:status,scheduled', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        // The editor saves HTML; it is cleaned here and again when the post is shown.
        $data['content'] = HtmlSanitizer::clean($data['content'] ?? '');
        $data['content_format'] = 'html';
        $data['noindex'] = (bool) ($data['noindex'] ?? false);

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

    /** Writes one post from a short brief and returns its fields, for the editor to fill in. */
    public function aiWrite(Request $request, BlogGenerator $blog): JsonResponse
    {
        $brief = $request->validate([
            'topic' => ['required', 'string', 'min:4', 'max:300'],
            'keywords' => ['nullable', 'string', 'max:300'],
            'tone' => ['nullable', 'string', 'max:100'],
            'audience' => ['nullable', 'string', 'max:200'],
            'length' => ['nullable', Rule::in(['short', 'medium', 'long'])],
            'language' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->attempt(fn (): array => $blog->write($brief));
    }

    /** Suggests topic ideas for the auto blog. */
    public function aiTopics(Request $request, BlogGenerator $blog): JsonResponse
    {
        $data = $request->validate([
            'niche' => ['required', 'string', 'min:3', 'max:300'],
            'count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return $this->attempt(fn (): array => ['topics' => $blog->topics($data['niche'], (int) ($data['count'] ?? 8))]);
    }

    /**
     * The auto blog: writes one post and saves it, as a draft or scheduled for a later day.
     * The page calls this once per topic so a long list never hits a request time limit.
     */
    public function aiAuto(Request $request, BlogGenerator $blog): JsonResponse
    {
        $brief = $request->validate([
            'topic' => ['required', 'string', 'min:4', 'max:300'],
            'keywords' => ['nullable', 'string', 'max:300'],
            'tone' => ['nullable', 'string', 'max:100'],
            'audience' => ['nullable', 'string', 'max:200'],
            'length' => ['nullable', Rule::in(['short', 'medium', 'long'])],
            'language' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'publish' => ['required', Rule::in(['draft', 'schedule', 'now'])],
            'publish_at' => ['nullable', 'required_if:publish,schedule', 'date'],
        ]);

        return $this->attempt(function () use ($blog, $brief, $request): array {
            $fields = $blog->write($brief);
            $words = $fields['words'];
            unset($fields['words']);

            $slug = $fields['slug'] ?: Slugs::from($fields['title']);
            $base = $slug;

            for ($i = 2; Post::query()->where('slug', $slug)->exists(); $i++) {
                $slug = $base.'-'.$i;
            }

            $post = Post::create([
                ...$fields,
                'slug' => $slug,
                'category_id' => $brief['category_id'] ?? $fields['category_id'],
                'author_id' => $request->user()->id,
                'content_format' => 'html',
                'ai_generated' => true,
                'published_at' => match ($brief['publish']) {
                    'now' => now(),
                    'schedule' => $brief['publish_at'],
                    default => null,
                },
            ]);

            return [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status(),
                'words' => $words,
                'edit_url' => route('admin.posts.edit', $post),
                'view_url' => route('blog.show', $post),
            ];
        });
    }

    /** @param  callable(): array<string, mixed>  $work */
    private function attempt(callable $work): JsonResponse
    {
        if (function_exists('set_time_limit') && ! app()->runningUnitTests()) {
            @set_time_limit(300);
        }

        try {
            return response()->json(['data' => $work()]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
