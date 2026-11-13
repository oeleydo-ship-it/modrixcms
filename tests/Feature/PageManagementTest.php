<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function blocksJson(array $blocks): string
    {
        return json_encode($blocks);
    }

    public function test_an_admin_creates_a_page_with_a_slug_from_the_title(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pages.store'), [
                'title' => 'Shipping & Returns',
                'blocks' => $this->blocksJson([PageBuilder::make('text', ['content' => "## Our policy\n\nFree returns."])]),
                'is_published' => '1',
                'show_in_menu' => '1',
                'menu_order' => 2,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $page = Page::firstWhere('slug', 'shipping-returns');
        $this->assertNotNull($page);
        $this->assertTrue($page->is_published);
        $this->assertTrue($page->show_in_menu);
        $this->assertSame('text', $page->blocks[0]['type']);

        $this->get('/shipping-returns')->assertOk()->assertSeeInPage('<h2>Our policy</h2>');
    }

    public function test_reserved_and_duplicate_slugs_are_rejected(): void
    {
        $admin = $this->admin();
        Page::factory()->create(['slug' => 'about']);
        $blocks = $this->blocksJson([]);

        $this->actingAs($admin)->post(route('admin.pages.store'), ['title' => 'Blog', 'blocks' => $blocks])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post(route('admin.pages.store'), ['title' => 'x', 'slug' => 'admin', 'blocks' => $blocks])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post(route('admin.pages.store'), ['title' => 'About', 'blocks' => $blocks])
            ->assertSessionHasErrors('slug');
    }

    public function test_an_admin_updates_and_deletes_a_page(): void
    {
        $admin = $this->admin();
        $page = Page::factory()->create(['title' => 'Old', 'slug' => 'old']);

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'New title',
            'slug' => 'old',
            'blocks' => $this->blocksJson([PageBuilder::make('cta')]),
        ])->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('New title', $page->title);
        $this->assertFalse($page->is_published, 'Unchecked box unpublishes');
        $this->assertSame(['cta'], array_column($page->blocks, 'type'));

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $page))->assertRedirect(route('admin.pages.index'));
        $this->assertModelMissing($page);
    }

    public function test_the_admin_lists_and_edits_pages(): void
    {
        $admin = $this->admin();
        $page = Page::factory()->create(['title' => 'Team']);

        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk()->assertSeeInPage('Team');
        $this->actingAs($admin)->get(route('admin.pages.edit', $page))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Pages/Form')->has('builder.blocks.hero'));
        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.create', ['template' => 'landing']))->assertOk()->assertInertia(fn ($page) => $page->where('page.blocks.0.type', 'hero'));
    }

    public function test_published_pages_render_markdown_and_drafts_are_hidden(): void
    {
        Page::factory()->withText("## Questions\n\n**Bold** answer")->create(['slug' => 'faq']);
        Page::factory()->draft()->create(['slug' => 'secret']);

        $this->get('/faq')->assertOk()->assertSeeInPage('<h2>Questions</h2>')->assertSeeInPage('<strong>Bold</strong>');
        $this->get('/secret')->assertNotFound();
        $this->get('/missing-page')->assertNotFound();
    }

    public function test_raw_html_in_text_blocks_is_stripped(): void
    {
        Page::factory()->withText("Hi <script>alert(1)</script>\n\n[x](javascript:alert(1))")->create(['slug' => 'xss']);

        // The rendered HTML is what the page prints as HTML; the raw source is only ever shown as text.
        $html = $this->get('/xss')->assertOk()->viewData('page')['props']['blocks'][0]['data']['content_html'];

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
    }

    public function test_menu_pages_appear_in_the_header(): void
    {
        Page::factory()->create(['title' => 'Pricing', 'slug' => 'pricing', 'show_in_menu' => true]);
        Page::factory()->create(['title' => 'Hidden One', 'slug' => 'hidden-one', 'show_in_menu' => false]);

        $this->get('/')->assertSeeInPage(url('/pricing'))->assertDontSeeInPage('Hidden One');
    }
}
