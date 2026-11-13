<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_an_admin_publishes_a_post_with_a_cover(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Hello World',
            'category_id' => $category->id,
            'excerpt' => 'Short',
            'content' => 'Body',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 675),
        ])->assertSessionHasNoErrors();

        $post = Post::firstWhere('slug', 'hello-world');
        $this->assertSame('Published', $post->status());
        $this->assertSame($admin->id, $post->author_id);
        Storage::disk('public')->assertExists($post->cover_image);

        $this->get(route('blog.show', $post))->assertOk()->assertSeeInPage('Hello World')->assertSeeInPage($admin->name);
    }

    public function test_drafts_and_scheduled_posts_stay_off_the_blog(): void
    {
        $live = Post::factory()->create(['title' => 'Live post']);
        $draft = Post::factory()->draft()->create(['title' => 'Draft post']);
        $scheduled = Post::factory()->scheduled()->create(['title' => 'Future post']);

        $this->get(route('blog.index'))->assertOk()->assertSeeInPage('Live post')->assertDontSeeInPage('Draft post')->assertDontSeeInPage('Future post');
        $this->get(route('blog.show', $draft))->assertNotFound();
        $this->get(route('blog.show', $scheduled))->assertNotFound();
        $this->get(route('blog.show', $live))->assertOk();
    }

    public function test_scheduling_needs_a_date(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Later', 'status' => 'scheduled',
        ])->assertSessionHasErrors('published_at');

        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Later', 'status' => 'scheduled', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Scheduled', Post::firstWhere('slug', 'later')->status());
    }

    public function test_resaving_a_published_post_keeps_its_date(): void
    {
        $post = Post::factory()->create(['published_at' => now()->subMonth()]);
        $original = $post->published_at->toDateTimeString();

        $this->actingAs($this->admin())->put(route('admin.posts.update', $post), [
            'title' => 'Edited', 'slug' => $post->slug, 'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertSame($original, $post->fresh()->published_at->toDateTimeString());
        $this->assertSame('Edited', $post->fresh()->title);
    }

    public function test_deleting_a_post_removes_its_cover(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('c.jpg')->store('blog', 'public');
        $post = Post::factory()->create(['cover_image' => $path]);

        $this->actingAs($this->admin())->delete(route('admin.posts.destroy', $post))->assertRedirect();

        $this->assertModelMissing($post);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cover_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Bad cover', 'status' => 'draft',
            'cover_image' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('cover_image');
    }

    public function test_categories_are_managed_and_filter_the_blog(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Release Notes'])->assertSessionHasNoErrors();
        $category = Category::firstWhere('slug', 'release-notes');
        $this->assertNotNull($category);

        Post::factory()->create(['title' => 'In category', 'category_id' => $category->id]);
        Post::factory()->create(['title' => 'Elsewhere']);

        $this->get(route('blog.category', $category))->assertOk()->assertSeeInPage('In category')->assertDontSeeInPage('Elsewhere');

        $this->actingAs($admin)->put(route('admin.categories.update', $category), ['name' => 'Releases', 'slug' => 'releases'])->assertSessionHasNoErrors();
        $this->assertSame('releases', $category->fresh()->slug);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertNull(Post::firstWhere('title', 'In category')->category_id);
    }

    public function test_the_admin_post_screens_load(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Listed post']);

        $this->actingAs($admin)->get(route('admin.posts.index'))->assertOk()->assertSeeInPage('Listed post');
        $this->actingAs($admin)->get(route('admin.posts.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.posts.edit', $post))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
    }

    public function test_the_home_page_shows_the_latest_posts(): void
    {
        Post::factory()->create(['title' => 'Fresh news']);

        $this->get('/')->assertOk()->assertSeeInPage('From the blog')->assertSeeInPage('Fresh news');
    }
}
