<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_every_block_type_renders_with_its_defaults(): void
    {
        $blocks = array_map(fn ($type) => PageBuilder::make($type), array_keys(PageBuilder::definitions()));
        Page::factory()->create(['slug' => 'all-blocks', 'blocks' => $blocks]);

        $this->get('/all-blocks')->assertOk()
            ->assertSeeInPage('Launch your store this afternoon.')
            ->assertSeeInPage('Built on a stack you already trust')
            ->assertSeeInPage('Free forever. Read every line.')
            ->assertSeeInPage('Numbers that speak for themselves.')
            ->assertSeeInPage('Start free, scale without limits.')
            ->assertSeeInPage('Why store owners switch to Modrix.')
            ->assertSeeInPage('Everything you need to know.')
            ->assertSeeInPage('Continuous improvements, shipped openly.');

        $editor = PageBuilder::forEditor();
        $this->assertArrayHasKey('categories', $editor);
        $this->assertNotEmpty($editor['presets']);
    }

    public function test_sanitize_drops_unknown_blocks_and_fields_and_unsafe_urls(): void
    {
        $clean = PageBuilder::sanitize([
            ['type' => 'nope', 'data' => []],
            ['id' => 'abc', 'type' => 'cta', 'data' => [
                'title' => str_repeat('x', 1000),
                'primary_url' => 'javascript:alert(1)',
                'secondary_url' => '@marketplace',
                'extra' => 'dropped',
            ]],
            ['type' => 'features', 'data' => ['items' => [['icon' => '<svg onload=x>', 'size' => 'huge', 'chips' => ['a', '', ['x']]]]]],
            ['type' => 'image', 'data' => ['image' => '../../.env']],
        ]);

        $this->assertCount(3, $clean);
        [$cta, $features, $image] = $clean;

        $this->assertSame('abc', $cta['id']);
        $this->assertSame(300, strlen($cta['data']['title']));
        $this->assertSame('', $cta['data']['primary_url']);
        $this->assertSame('@marketplace', $cta['data']['secondary_url']);
        $this->assertArrayNotHasKey('extra', $cta['data']);

        $card = $features['data']['items'][0];
        $this->assertSame('sparkle', $card['icon']);
        $this->assertSame('normal', $card['size']);
        $this->assertSame(['a'], $card['chips']);

        $this->assertSame('', $image['data']['image']);
    }

    public function test_saving_runs_blocks_through_sanitize(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'is_published' => '1',
            'blocks' => json_encode([['type' => 'cta', 'data' => ['title' => 'Hi', 'primary_label' => 'Go', 'primary_url' => 'javascript:alert(1)']]]),
        ])->assertSessionHasNoErrors();

        $this->assertSame('', $page->fresh()->blocks[0]['data']['primary_url']);
        $this->get('/'.$page->slug)->assertDontSeeInPage('javascript:');
    }

    public function test_invalid_blocks_json_is_rejected(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => $page->title, 'slug' => $page->slug, 'blocks' => '{not json',
        ])->assertSessionHasErrors('blocks');
    }

    public function test_only_one_page_is_the_home_page_and_it_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $old = Page::factory()->home()->create();
        $new = Page::factory()->create(['slug' => 'new-home']);

        $this->actingAs($admin)->put(route('admin.pages.update', $new), [
            'title' => 'New home', 'slug' => 'new-home', 'is_published' => '1', 'is_home' => '1',
            'blocks' => json_encode([PageBuilder::make('hero', ['title' => 'Brand new home'])]),
        ])->assertSessionHasNoErrors();

        $this->assertFalse($old->fresh()->is_home);
        $this->assertTrue($new->fresh()->is_home);
        $this->get('/')->assertSeeInPage('Brand new home');

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $new))->assertSessionHasErrors('page');
        $this->assertModelExists($new);
    }

    public function test_the_last_remaining_home_page_can_be_deleted(): void
    {
        $admin = $this->admin();
        $home = Page::factory()->home()->create(['title' => 'Home']);

        $this->actingAs($admin)->get(route('admin.pages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pages.data.0.can_delete', true));

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $home))
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($home);
        $this->get('/')->assertOk();
    }

    public function test_preview_renders_unsaved_blocks(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.preview'), [
            'title' => 'Draft',
            'blocks' => json_encode([PageBuilder::make('page_header', ['title' => 'Unsaved heading'])]),
        ])->assertOk()->assertSeeInPage('Unsaved heading')->assertInertia(fn ($page) => $page->component('Site/Page')->where('preview', true));

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_guests_cannot_preview_or_upload(): void
    {
        $this->post(route('admin.pages.preview'), ['blocks' => '[]'])->assertRedirect(route('admin.login'));
        $this->post(route('admin.builder.images'))->assertRedirect(route('admin.login'));
    }

    public function test_images_upload_and_render_in_an_image_block(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.builder.images'), ['image' => UploadedFile::fake()->image('photo.png', 800, 400)])
            ->assertOk()
            ->assertJsonStructure(['path', 'url']);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);

        Page::factory()->create(['slug' => 'gallery', 'blocks' => [PageBuilder::make('image', ['image' => $path, 'alt' => 'A photo'])]]);
        $this->get('/gallery')->assertSeeInPage(Storage::disk('public')->url($path))->assertSeeInPage('A photo');
    }

    public function test_non_images_are_refused_by_the_upload(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->postJson(route('admin.builder.images'), ['image' => UploadedFile::fake()->create('evil.svg', 2, 'image/svg+xml')])
            ->assertUnprocessable();
    }

    public function test_the_seeder_creates_the_landing_page_as_home(): void
    {
        $this->seed();

        $home = Page::home();
        $this->assertNotNull($home);
        $this->assertSame(
            ['hero', 'stack', 'features', 'marketplace', 'steps', 'comparison', 'latest_posts', 'cta'],
            array_column($home->blocks, 'type'),
        );
        $this->get('/')->assertOk()->assertSeeInPage('Zero platform fees.');
    }
}
