<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class SableThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_sable_theme_registers_with_geist_tokens_and_customizer_settings(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Sable Systems')));

        $this->get(route('admin.themes.design', ['modrix', 'sable']))->assertOk()
            ->assertInertia(fn ($page) => $page->where('theme.hasOwnSettings', true));

        $this->post(route('admin.themes.activate', ['modrix', 'sable']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('modrix/sable', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/sable/theme.css', false)
            ->assertSee('geist:400,500,600,700', false)
            ->assertSee('--site-primary:#131314;', false)
            ->assertSee('--site-accent:#eb812c;', false)
            ->assertSee('--site-background:#fdfcfc;', false)
            ->assertSee('--site-surface:#f5f3f1;', false)
            ->assertSee('--site-border:#edebe9;', false)
            ->assertSee('--site-heading-font:"Geist"', false)
            ->assertSee('--site-button-radius:999px;', false)
            ->assertSee('--sable-accent: #eb812c;', false)
            ->assertSee('--sable-surface: #f5f3f1;', false)
            ->assertSee('--sable-grid-color: #edebe9;', false)
            ->assertSee('--sable-crosshair-display: flex;', false)
            ->assertSee('--sable-watermark-display: block;', false);

        $json = file_get_contents(base_path('extensions/themes/modrix/sable/theme.json'));
        $this->assertNotFalse($json);
        $this->assertCount(7, json_decode($json, true)['demo']['pages']);
        $this->assertStringNotContainsStringIgnoringCase('ninelabs', $json);
        $this->assertStringNotContainsStringIgnoringCase('webflow', $json);
    }

    public function test_sable_demo_imports_all_seven_pages_and_responds_to_design_and_customize(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'sable']), ['trust' => 1])->assertRedirect();

        $this->post(route('admin.themes.demo', ['modrix', 'sable']), ['set_home' => true, 'replace' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, '7 new and 0 updated pages'));

        $this->assertSame(7, Page::query()->regular()->where('theme', 'modrix/sable')->count());
        $this->assertTrue(Page::query()->regular()->where('theme', 'modrix/sable')->where('slug', 'home')->firstOrFail()->is_home);

        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/sable')->firstOrFail();
        $headerJson = (string) json_encode($header->published_document, JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('Case studies', $headerJson);
        $this->assertStringContainsString('Pricing', $headerJson);
        $this->assertStringContainsString('Start a project', $headerJson);
        $this->assertNotNull(Page::query()->where('role', Page::ROLE_FOOTER)->where('theme', 'modrix/sable')->first()?->published_document);

        $this->put(route('admin.themes.design.update', ['modrix', 'sable']), [
            'values' => [
                'accent' => '#d9661a',
                'heading_font' => 'DM Sans',
                'button_shape' => 'rounded',
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->put(route('admin.themes.customize.update', ['modrix', 'sable']), [
            'accent_color' => '#d9661a',
            'surface_color' => '#efeae4',
            'grid_color' => '#e3ded8',
            'show_crosshairs' => false,
            'show_watermark' => false,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $homeHtml = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('--site-accent:#d9661a;', $homeHtml);
        $this->assertStringContainsString('--site-heading-font:"DM Sans"', $homeHtml);
        $this->assertStringContainsString('--site-button-radius:10px;', $homeHtml);
        $this->assertStringContainsString('--sable-accent: #d9661a;', $homeHtml);
        $this->assertStringContainsString('--sable-surface: #efeae4;', $homeHtml);
        $this->assertStringContainsString('--sable-crosshair-display: none;', $homeHtml);
        $this->assertStringContainsString('--sable-watermark-display: none;', $homeHtml);
        $this->assertStringContainsString('"designCollection":"sable"', $homeHtml);

        foreach (['about', 'services', 'case-studies', 'pricing', 'journal', 'contact'] as $slug) {
            $body = (string) $this->get('/'.$slug)->assertOk()->getContent();
            $this->assertStringContainsString('"designCollection":"sable"', $body);
        }
    }
}
