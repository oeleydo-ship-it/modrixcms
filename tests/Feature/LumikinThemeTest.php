<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class LumikinThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    private const PAGE_TEMPLATES = ['lumikin-home', 'lumikin-about', 'lumikin-programs', 'lumikin-fees', 'lumikin-blog', 'lumikin-contact'];

    public function test_every_lumikin_section_is_a_reusable_design(): void
    {
        $this->actingAs($this->admin());

        $keys = collect($this->getJson('/api/v1/design-blocks')->assertOk()->json('data'))->pluck('key');

        foreach (['lumikin-header', 'lumikin-footer', 'lumikin-hero', 'lumikin-activities', 'lumikin-testimonials', 'lumikin-fee-plans', 'lumikin-contact'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_each_lumikin_page_template_installs_with_its_header_and_footer(): void
    {
        $this->actingAs($this->admin());

        $listed = collect($this->getJson('/api/v1/templates?type=page&per_page=100')->assertOk()->json('data'))->pluck('id');
        $this->assertSame(self::PAGE_TEMPLATES, $listed->filter(fn (string $id) => str_starts_with($id, 'lumikin-'))->values()->all());

        foreach (self::PAGE_TEMPLATES as $template) {
            $page = Page::factory()->create(['editor' => Page::EDITOR_BUILDER, 'blocks' => []]);

            $this->postJson("/api/v1/templates/{$template}/install", ['page_id' => $page->id, 'expected_version' => 1])->assertOk();

            $blocks = array_column(array_column($page->fresh()->document['sections'], 'settings'), 'designBlock');
            $this->assertSame('header', $blocks[0], $template);
            $this->assertSame('footer', end($blocks), $template);
        }
    }

    public function test_activating_lumikin_sets_its_design_and_the_demo_imports_pages_header_and_footer(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'lumikin']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->assertSame('modrix/lumikin', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('--site-primary:#9a233d;', false)
            ->assertSee('--site-surface:#f6f2ed;', false)
            ->assertSee('--site-content-width:1560px;', false)
            ->assertSee('extensions/themes/modrix/lumikin/theme.js', false);
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/lumikin/logo.svg');

        $this->post(route('admin.themes.demo', ['modrix', 'lumikin']), ['set_home' => true])
            ->assertSessionHas('status', fn ($status) => str_contains($status, '6 new and 0 updated pages'));

        $pages = Page::query()->regular()->where('theme', 'modrix/lumikin')->orderBy('menu_order')->get();
        $this->assertSame(['home', 'about', 'programs', 'fees', 'stories', 'contact'], $pages->pluck('slug')->all());
        $this->assertTrue($pages->first()->is_home);
        $this->assertSame(2, Page::query()->whereNotNull('role')->where('theme', 'modrix/lumikin')->whereNotNull('published_document')->count());

        $this->get('/fees')->assertOk()
            ->assertSee('"designBlock":"header"', false)
            ->assertSee('Choose the right learning plan')
            ->assertSee('"designBlock":"footer"', false);
    }
}
