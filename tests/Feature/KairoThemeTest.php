<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class KairoThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_kairo_theme_is_registered_with_clean_design_tokens_and_no_copied_branding(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Kairo Career')));

        $this->post(route('admin.themes.activate', ['modrix', 'kairo']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('modrix/kairo', app(Settings::class)->get('active_theme'));
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/kairo/theme.js');
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/kairo/theme.css');

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/kairo/theme.js', false)
            ->assertSee('extensions/themes/modrix/kairo/theme.css', false)
            ->assertSee('--site-primary:#0066ff;', false)
            ->assertSee('--site-background:#ffffff;', false)
            ->assertSee('--site-surface:#f7f7f8;', false)
            ->assertSee('--site-heading:#0c0407;', false)
            ->assertSee('--site-heading-font:"Inter"', false)
            ->assertSee('--site-body-font:"Inter"', false)
            ->assertSee('fonts.bunny.net/css?family=inter', false);

        $json = file_get_contents(base_path('extensions/themes/modrix/kairo/theme.json'));
        $this->assertNotFalse($json);
        $this->assertGreaterThanOrEqual(6, count(json_decode($json, true)['demo']['pages']));
        $this->assertStringNotContainsStringIgnoringCase('reako', $json);
        $this->assertStringNotContainsStringIgnoringCase('metaflow', $json);
    }

    public function test_kairo_demo_imports_header_footer_and_six_pages_following_theme_settings(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'kairo']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->post(route('admin.themes.demo', ['modrix', 'kairo']), ['set_home' => true, 'replace' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '6 new and 0 updated pages'));

        $this->assertSame(6, Page::query()->regular()->where('theme', 'modrix/kairo')->count());
        $homePage = Page::query()->regular()->where('theme', 'modrix/kairo')->where('slug', 'home')->firstOrFail();
        $this->assertTrue($homePage->is_home);

        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/kairo')->first();
        $this->assertNotNull($header);
        $headerJson = json_encode($header->published_document, JSON_UNESCAPED_SLASHES);
        $this->assertIsString($headerJson);
        $this->assertStringContainsString('Features', $headerJson);
        $this->assertStringContainsString('Pricing', $headerJson);

        $footer = Page::query()->where('role', Page::ROLE_FOOTER)->where('theme', 'modrix/kairo')->first();
        $this->assertNotNull($footer);
        $this->assertNotNull($footer->published_document);

        // Verify Theme Settings overrides propagate into emitted CSS custom properties
        $this->put(route('admin.themes.design.update', ['modrix', 'kairo']), [
            'values' => [
                'primary' => '#1d4ed8',
                'accent' => '#10b981',
                'heading_font' => 'Plus Jakarta Sans',
                'button_shape' => 'rounded',
            ],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $homeHtml = $this->get('/')->assertOk()->getContent();
        $this->assertIsString($homeHtml);
        $this->assertStringContainsString('--site-primary:#1d4ed8;', $homeHtml);
        $this->assertStringContainsString('--site-accent:#10b981;', $homeHtml);
        $this->assertStringContainsString('--site-heading-font:"Plus Jakarta Sans"', $homeHtml);
        $this->assertStringContainsString('--site-button-radius:10px;', $homeHtml);
        $this->assertStringContainsString('"designCollection":"kairo"', $homeHtml);
        $this->assertStringContainsString('Kairo Recommends', $homeHtml);

        foreach (['features', 'about', 'pricing', 'articles', 'contact'] as $slug) {
            $pageHtml = $this->get('/'.$slug)->assertOk()->getContent();
            $this->assertIsString($pageHtml);
            $this->assertStringContainsString('--site-primary:#1d4ed8;', $pageHtml);
            $this->assertStringContainsString('"designCollection":"kairo"', $pageHtml);
        }
    }
}
