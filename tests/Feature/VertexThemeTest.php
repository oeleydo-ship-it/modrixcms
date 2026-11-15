<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class VertexThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_vertex_theme_registers_with_enterprise_tokens_and_customizer_settings(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Vertex Enterprise')));

        $this->get(route('admin.themes.design', ['modrix', 'vertex']))->assertOk()
            ->assertInertia(fn ($page) => $page->where('theme.hasOwnSettings', true));

        $this->post(route('admin.themes.activate', ['modrix', 'vertex']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('modrix/vertex', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/vertex/theme.css', false)
            ->assertSee('--site-primary:#006994;', false)
            ->assertSee('--site-dark:#0c2340;', false)
            ->assertSee('--site-secondary:#00a99d;', false)
            ->assertSee('--site-accent:#5b3df5;', false)
            ->assertSee('--site-heading-font:"Plus Jakarta Sans"', false)
            ->assertSee('--site-button-radius:4px;', false)
            ->assertSee('--vertex-hero-end: #165187;', false)
            ->assertSee('--vertex-banner-bg: #005f87;', false)
            ->assertSee('--vertex-stripe-start: #6cc04a;', false)
            ->assertSee('--vertex-stripe-end: #5b3df5;', false)
            ->assertSee('--vertex-utility-display: block;', false);

        $json = file_get_contents(base_path('extensions/themes/modrix/vertex/theme.json'));
        $this->assertNotFalse($json);
        $this->assertCount(6, json_decode($json, true)['demo']['pages']);
        $this->assertStringNotContainsStringIgnoringCase('vmware', $json);
        $this->assertStringNotContainsStringIgnoringCase('broadcom', $json);
    }

    public function test_vertex_demo_imports_all_six_marketing_pages_and_responds_to_design_and_customize(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'vertex']), ['trust' => 1])->assertRedirect();

        $this->post(route('admin.themes.demo', ['modrix', 'vertex']), ['set_home' => true, 'replace' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, '6 new and 0 updated pages'));

        $this->assertSame(6, Page::query()->regular()->where('theme', 'modrix/vertex')->count());
        $this->assertTrue(Page::query()->regular()->where('theme', 'modrix/vertex')->where('slug', 'home')->firstOrFail()->is_home);

        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/vertex')->firstOrFail();
        $headerJson = (string) json_encode($header->published_document, JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('Products & checkout', $headerJson);
        $this->assertStringContainsString('Themes & modules', $headerJson);
        $this->assertStringNotContainsString('MENA', $headerJson);
        $this->assertNotNull(Page::query()->where('role', Page::ROLE_FOOTER)->where('theme', 'modrix/vertex')->first()?->published_document);

        $this->put(route('admin.themes.design.update', ['modrix', 'vertex']), [
            'values' => [
                'primary' => '#0b5cab',
                'heading_font' => 'DM Sans',
                'button_shape' => 'rounded',
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->put(route('admin.themes.customize.update', ['modrix', 'vertex']), [
            'hero_end' => '#1b498c',
            'banner_bg' => '#084f70',
            'stripe_start' => '#5eb944',
            'stripe_end' => '#4f46e5',
            'show_utility_bar' => false,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $homeHtml = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('--site-primary:#0b5cab;', $homeHtml);
        $this->assertStringContainsString('--site-heading-font:"DM Sans"', $homeHtml);
        $this->assertStringContainsString('--site-button-radius:10px;', $homeHtml);
        $this->assertStringContainsString('--vertex-hero-end: #1b498c;', $homeHtml);
        $this->assertStringContainsString('--vertex-banner-bg: #084f70;', $homeHtml);
        $this->assertStringContainsString('--vertex-utility-display: none;', $homeHtml);
        $this->assertStringContainsString('"designCollection":"vertex"', $homeHtml);

        foreach (['platform', 'modules', 'solutions', 'developers', 'demo'] as $slug) {
            $body = (string) $this->get('/'.$slug)->assertOk()->getContent();
            $this->assertStringContainsString('"designCollection":"vertex"', $body);
        }

        $demoBody = (string) $this->get('/demo')->assertOk()->getContent();
        $this->assertStringContainsString('Import the demo', $demoBody);
        $this->assertStringContainsString('Modrix Commerce', $demoBody);
    }
}
