<?php

namespace Tests\Feature;

use App\Cms\Builder\SiteChrome;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Manifest;
use App\Cms\Themes\ThemeManager;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class ThemeDemoImportTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    private function activatePebblewood(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.themes.activate', ['modrix', 'pebblewood']), ['trust' => 1])->assertSessionHasNoErrors();
    }

    public function test_the_themes_screen_offers_the_demo(): void
    {
        $this->activatePebblewood();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)
                ->firstWhere('name', 'modrix/pebblewood')['demo']['pages'] === ['Home', 'About us', 'Programs', 'Blog', 'Contact']));
    }

    public function test_importing_the_demo_publishes_its_pages_header_and_footer(): void
    {
        $this->activatePebblewood();

        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']), ['set_home' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '5 new and 0 updated pages'));

        $pages = Page::query()->regular()->where('theme', 'modrix/pebblewood')->orderBy('menu_order')->get();
        $this->assertSame(['home', 'about', 'programs', 'news', 'contact'], $pages->pluck('slug')->all());
        $this->assertTrue($pages->every(fn (Page $page) => $page->is_published && $page->published_document !== null && $page->show_in_menu));
        $this->assertTrue($pages->first()->is_home);
        $this->assertFalse($pages->first()->show_site_header);

        foreach ([Page::ROLE_HEADER, Page::ROLE_FOOTER] as $role) {
            $chrome = Page::query()->where('role', $role)->where('theme', 'modrix/pebblewood')->firstOrFail();
            $this->assertNotNull($chrome->published_document, $role);
        }

        $this->get('/about')->assertOk()
            ->assertSee('"designCollection":"pebblewood"', false)
            ->assertSee('Helping every child learn, grow and thrive')
            ->assertSee('Little ones grow big here', false);
    }

    public function test_importing_again_updates_the_same_pages(): void
    {
        $this->activatePebblewood();

        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']));
        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '0 new and 5 updated pages'));

        $this->assertSame(5, Page::query()->regular()->where('theme', 'modrix/pebblewood')->count());
        $this->assertSame(2, Page::query()->whereNotNull('role')->where('theme', 'modrix/pebblewood')->count());
    }

    public function test_taken_addresses_get_new_ones_unless_replacing_is_asked_for(): void
    {
        $this->activatePebblewood();
        $mine = Page::factory()->create(['slug' => 'about', 'title' => 'My about page']);

        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']))
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'About us → /about-2'));
        $this->assertSame('My about page', $mine->fresh()->title);

        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']), ['replace' => true]);
        $this->assertSame('About us', $mine->fresh()->title);
        $this->assertSame('modrix/pebblewood', $mine->fresh()->theme);
        $this->assertTrue($mine->fresh()->versions()->exists(), 'the replaced content stays in History');
    }

    public function test_each_theme_shows_its_own_header_and_footer(): void
    {
        $this->activatePebblewood();
        $this->post(route('admin.themes.demo', ['modrix', 'pebblewood']));

        (new Page(['title' => 'Shared header', 'slug' => '_site-header', 'editor' => Page::EDITOR_BUILDER, 'blocks' => []]))
            ->forceFill(['role' => Page::ROLE_HEADER, 'published_document' => ['schema_version' => 1, 'settings' => [], 'sections' => []]])
            ->save();

        $this->assertSame('modrix/pebblewood', app(SiteChrome::class)->current(Page::ROLE_HEADER)->theme);
        $this->assertSame('modrix/pebblewood', app(SiteChrome::class)->current(Page::ROLE_HEADER, 'modrix/pebblewood')->theme);
        $this->assertNull(app(SiteChrome::class)->current(Page::ROLE_HEADER, 'modrix/default')->theme, 'a theme without its own uses the shared header');

        app(Settings::class)->set(['active_theme' => 'modrix/default']);
        $this->app->forgetInstance(ThemeManager::class);
        $this->app->forgetInstance(SiteChrome::class);

        $this->get('/about')->assertOk()->assertDontSee('Little ones grow big here');
    }

    public function test_the_nexus_ai_demo_imports_its_pages_header_and_footer(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.themes.activate', ['modrix', 'nexus-ai']), ['trust' => 1])->assertSessionHasNoErrors();

        $this->post(route('admin.themes.demo', ['modrix', 'nexus-ai']), ['set_home' => true])
            ->assertSessionHas('status', fn ($status) => str_contains($status, '5 new and 0 updated pages'));

        $pages = Page::query()->regular()->where('theme', 'modrix/nexus-ai')->orderBy('menu_order')->get();
        $this->assertSame(['home', 'about', 'services', 'faqs', 'blogs'], $pages->pluck('slug')->all());
        $this->assertTrue($pages->first()->is_home);
        $this->assertSame(2, Page::query()->whereNotNull('role')->where('theme', 'modrix/nexus-ai')->whereNotNull('published_document')->count());

        $this->get('/services')->assertOk()->assertSee('Nexus AI', false);
    }

    public function test_a_demo_file_outside_the_demo_folder_is_rejected(): void
    {
        $folder = $this->cmsRoot.'/extensions/themes/modrix/pebblewood';
        $manifest = json_decode(File::get($folder.'/theme.json'), true);
        $manifest['demo']['pages'][0]['file'] = 'public/theme.js';
        File::put($folder.'/theme.json', json_encode($manifest));

        $this->expectException(InvalidPackage::class);
        $this->expectExceptionMessage('demo.pages.0');

        Manifest::load($folder, 'theme');
    }
}
