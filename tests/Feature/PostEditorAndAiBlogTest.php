<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\HtmlSanitizer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostEditorAndAiBlogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function useAi(): void
    {
        app(Settings::class)->set(['ai_provider' => 'deepseek', 'ai_api_key' => 'sk-test', 'ai_model' => 'deepseek-flash']);
    }

    /** @return array<string, mixed> */
    private function articleJson(array $override = []): array
    {
        return [...[
            'title' => 'Five signs you are ready for AI',
            'slug' => 'five-signs-ready-for-ai',
            'excerpt' => 'A short summary.',
            'content' => '<h2>Sign one</h2><p>Automation helps.</p><script>alert(1)</script>',
            'meta_title' => 'Five signs you are ready for AI automation',
            'meta_description' => 'Learn the five signs.',
            'focus_keyword' => 'ai automation',
            'og_title' => 'Ready for AI?',
            'og_description' => 'Find out.',
            'category' => '',
        ], ...$override];
    }

    private function fakeAi(array $article): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode($article)], 'finish_reason' => 'stop']]])]);
    }

    public function test_the_sanitizer_keeps_formatting_and_drops_anything_active(): void
    {
        $clean = HtmlSanitizer::clean('<h2 onclick="x()">Title</h2><p style="color:red">Hi <strong>there</strong> <a href="javascript:alert(1)">bad</a> <a href="https://example.com" target="_blank">ok</a></p><script>alert(1)</script><iframe src="https://evil"></iframe><img src="https://example.com/a.png" alt="A" onerror="x()"><div>plain</div>');

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<strong>there</strong>', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('<img src="https://example.com/a.png" alt="A">', $clean);
        $this->assertStringContainsString('plain', $clean);
        foreach (['onclick', 'style=', 'javascript:', '<script', '<iframe', 'onerror', '<div'] as $bad) {
            $this->assertStringNotContainsString($bad, $clean);
        }
    }

    public function test_the_editor_saves_cleaned_html_and_the_seo_fields(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Rich post',
            'content' => '<p>Hello <strong>world</strong></p><script>alert(1)</script>',
            'status' => 'published',
            'meta_title' => 'Custom SEO title',
            'focus_keyword' => 'rich post',
            'canonical_url' => 'https://example.com/original',
            'noindex' => '1',
            'og_title' => 'Share title',
            'og_description' => 'Share text',
            'og_image' => 'https://example.com/share.png',
        ])->assertSessionHasNoErrors();

        $post = Post::firstWhere('slug', 'rich-post');
        $this->assertSame('html', $post->content_format);
        $this->assertSame('<p>Hello <strong>world</strong></p>', $post->content);
        $this->assertTrue($post->noindex);

        $response = $this->get(route('blog.show', $post))->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/original">', $html);
        $this->assertStringContainsString('property="og:title" content="Share title"', $html);
        $this->assertStringContainsString('property="og:image" content="https://example.com/share.png"', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('Custom SEO title', $html);
    }

    public function test_seo_urls_must_be_web_addresses(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Bad links', 'status' => 'draft', 'canonical_url' => 'javascript:alert(1)', 'og_image' => 'not a url',
        ])->assertSessionHasErrors(['canonical_url', 'og_image']);
    }

    public function test_an_older_markdown_post_opens_in_the_editor_as_html(): void
    {
        $post = Post::factory()->create(['content' => "## Heading\n\nSome **bold** text.", 'content_format' => 'markdown']);

        $this->actingAs($this->admin())->get(route('admin.posts.edit', $post))->assertOk()
            ->assertInertia(fn ($page) => $page->where('post.content', fn ($html) => str_contains($html, '<h2>Heading</h2>') && str_contains($html, '<strong>bold</strong>')));
    }

    public function test_the_ai_writer_returns_a_cleaned_post_using_the_configured_provider(): void
    {
        $this->useAi();
        $category = Category::factory()->create(['name' => 'AI Strategy']);
        $this->fakeAi($this->articleJson(['category' => 'ai strategy']));

        $this->actingAs($this->admin())->postJson(route('admin.posts.ai.write'), ['topic' => 'Signs you are ready for AI'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Five signs you are ready for AI')
            ->assertJsonPath('data.focus_keyword', 'ai automation')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.content', '<h2>Sign one</h2><p>Automation helps.</p>');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.deepseek.com') && str_contains(json_encode($request->data()), 'Signs you are ready for AI'));
    }

    public function test_the_auto_blog_saves_a_draft_marked_as_ai_written(): void
    {
        $this->useAi();
        $this->fakeAi($this->articleJson());
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.posts.ai.auto'), ['topic' => 'Signs you are ready for AI', 'publish' => 'draft'])
            ->assertOk()->assertJsonPath('data.status', 'Draft')->assertJsonStructure(['data' => ['words', 'edit_url', 'view_url']]);

        $post = Post::firstWhere('slug', 'five-signs-ready-for-ai');
        $this->assertTrue($post->ai_generated);
        $this->assertSame('html', $post->content_format);
        $this->assertNull($post->published_at);
        $this->assertSame($admin->id, $post->author_id);

        // A second post on the same title gets its own address.
        $this->actingAs($admin)->postJson(route('admin.posts.ai.auto'), ['topic' => 'Another take', 'publish' => 'now'])->assertOk()->assertJsonPath('data.status', 'Published');
        $this->assertDatabaseHas('posts', ['slug' => 'five-signs-ready-for-ai-2']);
    }

    public function test_a_short_draft_is_expanded_once_and_existing_posts_are_offered_for_internal_links(): void
    {
        $this->useAi();
        $existing = Post::factory()->create(['title' => 'Our guide to workflow automation']);
        $long = '<h2>Key takeaways</h2>'.str_repeat('<p>'.str_repeat('useful practical words ', 20).'</p>', 30);
        Http::fake(['api.deepseek.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => json_encode($this->articleJson())], 'finish_reason' => 'stop']]])
            ->push(['choices' => [['message' => ['content' => json_encode($this->articleJson(['content' => $long]))], 'finish_reason' => 'stop']]])]);

        $response = $this->actingAs($this->admin())->postJson(route('admin.posts.ai.write'), ['topic' => 'Signs you are ready for AI'])->assertOk();

        $this->assertGreaterThan(1000, $response->json('data.words'));
        Http::assertSentCount(2);
        $first = Http::recorded()[0][0]->data();
        $this->assertStringContainsString(route('blog.show', $existing), json_encode($first, JSON_UNESCAPED_SLASHES));
        $this->assertStringContainsString('only', json_encode(Http::recorded()[1][0]->data()));
    }

    public function test_the_ai_blog_reports_a_missing_api_key_instead_of_failing(): void
    {
        app(Settings::class)->set(['ai_provider' => 'openai', 'ai_api_key' => '']);

        $this->actingAs($this->admin())->postJson(route('admin.posts.ai.write'), ['topic' => 'Anything at all'])
            ->assertStatus(422)->assertJsonPath('message', 'AI API key is not configured. Add it in Settings → AI API.');
    }

    public function test_topic_ideas_come_back_as_a_list(): void
    {
        $this->useAi();
        $this->fakeAi(['topics' => ['One idea here', 'Another idea here']]);

        $this->actingAs($this->admin())->postJson(route('admin.posts.ai.topics'), ['niche' => 'AI for small business', 'count' => 2])
            ->assertOk()->assertJsonPath('data.topics.1', 'Another idea here');
    }
}
