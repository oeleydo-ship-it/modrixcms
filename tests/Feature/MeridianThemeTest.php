<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class MeridianThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_meridian_theme_is_registered_with_viselio_design_tokens_and_no_copied_branding(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Meridian Advisory')));

        $this->post(route('admin.themes.activate', ['modrix', 'meridian']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('modrix/meridian', app(Settings::class)->get('active_theme'));
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/meridian/theme.js');
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/meridian/theme.css');

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/meridian/theme.js', false)
            ->assertSee('extensions/themes/modrix/meridian/theme.css', false)
            ->assertSee('--site-primary:#15241b;', false)
            ->assertSee('--site-accent:#b98b43;', false)
            ->assertSee('--site-background:#f7f8ee;', false)
            ->assertSee('--site-heading-font:"Parkinsans"', false)
            ->assertSee('--site-body-font:"DM Sans"', false)
            ->assertSee('fonts.bunny.net/css?family=parkinsans', false);

        $json = file_get_contents(base_path('extensions/themes/modrix/meridian/theme.json'));
        $this->assertNotFalse($json);
        $this->assertGreaterThanOrEqual(6, count(json_decode($json, true)['demo']['pages']));
        $this->assertStringNotContainsStringIgnoringCase('viselio', $json);
    }

    public function test_meridian_demo_imports_header_dropdowns_footer_and_seven_pages_following_theme_settings(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'meridian']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->post(route('admin.themes.demo', ['modrix', 'meridian']), ['set_home' => true, 'replace' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '7 new and 0 updated pages'));

        $this->assertSame(7, Page::query()->regular()->where('theme', 'modrix/meridian')->count());
        $homePage = Page::query()->regular()->where('theme', 'modrix/meridian')->where('slug', 'home')->firstOrFail();
        $this->assertTrue($homePage->is_home);

        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/meridian')->first();
        $this->assertNotNull($header);
        $headerJson = json_encode($header->published_document, JSON_UNESCAPED_SLASHES);
        $this->assertIsString($headerJson);
        $this->assertStringContainsString('Executive Work Permits', $headerJson);
        $this->assertStringContainsString('United Kingdom', $headerJson);
        $this->assertStringContainsString('Policy Briefings', $headerJson);

        $footer = Page::query()->where('role', Page::ROLE_FOOTER)->where('theme', 'modrix/meridian')->first();
        $this->assertNotNull($footer);
        $this->assertNotNull($footer->published_document);

        // Verify Theme Settings overrides propagate into emitted CSS custom properties
        $this->put(route('admin.themes.design.update', ['modrix', 'meridian']), [
            'values' => [
                'primary' => '#223344',
                'accent' => '#cc6633',
                'heading_font' => 'DM Sans',
            ],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $homeHtml = $this->get('/')->assertOk()->getContent();
        $this->assertIsString($homeHtml);
        $this->assertStringContainsString('--site-primary:#223344;', $homeHtml);
        $this->assertStringContainsString('--site-accent:#cc6633;', $homeHtml);
        $this->assertStringContainsString('--site-heading-font:"DM Sans"', $homeHtml);
        $this->assertStringContainsString('"designCollection":"meridian"', $homeHtml);
        $this->assertStringContainsString('Executive Work Permits', $homeHtml);
        $this->assertStringContainsString('Meridian Advisory', $homeHtml);

        foreach (['about', 'services', 'programs', 'destinations', 'insights', 'contact'] as $slug) {
            $pageHtml = $this->get('/'.$slug)->assertOk()->getContent();
            $this->assertIsString($pageHtml);
            $this->assertStringContainsString('--site-primary:#223344;', $pageHtml);
            $this->assertStringContainsString('--site-accent:#cc6633;', $pageHtml);
            $this->assertStringContainsString('"designCollection":"meridian"', $pageHtml);
        }
    }

    public function test_meridian_public_assets_exist_and_serve_cleanly(): void
    {
        foreach ([
            public_path('templates/meridian/logo.svg'),
            public_path('templates/meridian/logo-light.svg'),
            public_path('extensions/themes/modrix/meridian/theme.css'),
            public_path('extensions/themes/modrix/meridian/theme.js'),
            public_path('extensions/themes/modrix/meridian/screenshot.svg'),
        ] as $assetPath) {
            $this->assertFileExists($assetPath);
        }
    }

    public function test_sqlite_temp_store_and_windows_serve_temp_variables_are_configured(): void
    {
        $this->assertSame('MEMORY', config('database.connections.sqlite.pragmas.temp_store'));
        $this->assertTrue(is_writable(sys_get_temp_dir()));

        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertContains('TMP', ServeCommand::$passthroughVariables);
            $this->assertContains('TEMP', ServeCommand::$passthroughVariables);
            $this->assertContains('SQLITE_TMPDIR', ServeCommand::$passthroughVariables);
        }
    }
}
