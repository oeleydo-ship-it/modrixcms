<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class LarkspurThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_larkspur_theme_registers_with_its_typography_and_palette(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Larkspur Family Health')));

        $this->post(route('admin.themes.activate', ['modrix', 'larkspur']), ['trust' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('modrix/larkspur', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/larkspur/theme.css', false)
            ->assertSee('--site-primary:#1a6083;', false)
            ->assertSee('--site-accent:#ca9352;', false)
            ->assertSee('--site-heading-font:"Instrument Sans"', false)
            ->assertSee('--site-body-font:"Mulish"', false)
            ->assertSee('--site-heading-weight:400;', false)
            ->assertSee('--site-line-height:1.5;', false)
            ->assertSee('family=instrument-sans', false);

        $json = file_get_contents(base_path('extensions/themes/modrix/larkspur/theme.json'));
        $this->assertNotFalse($json);
        $this->assertCount(7, json_decode($json, true)['demo']['pages']);
        $this->assertStringNotContainsStringIgnoringCase('housecall', $json);
    }

    public function test_larkspur_demo_imports_seven_pages_that_follow_theme_settings(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'larkspur']), ['trust' => 1])->assertRedirect();

        $this->post(route('admin.themes.demo', ['modrix', 'larkspur']), ['set_home' => true, 'replace' => true])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '7 new and 0 updated pages'));

        $this->assertSame(7, Page::query()->regular()->where('theme', 'modrix/larkspur')->count());
        $this->assertTrue(Page::query()->regular()->where('theme', 'modrix/larkspur')->where('slug', 'home')->firstOrFail()->is_home);

        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/larkspur')->firstOrFail();
        $this->assertStringContainsString('Fees & insurance', (string) json_encode($header->published_document, JSON_UNESCAPED_SLASHES));
        $this->assertNotNull(Page::query()->where('role', Page::ROLE_FOOTER)->where('theme', 'modrix/larkspur')->first()?->published_document);

        $this->put(route('admin.themes.design.update', ['modrix', 'larkspur']), [
            'values' => [
                'primary' => '#2f6b4f',
                'heading_font' => 'Fraunces',
                'button_shape' => 'rounded',
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $homeHtml = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('--site-primary:#2f6b4f;', $homeHtml);
        $this->assertStringContainsString('--site-heading-font:"Fraunces"', $homeHtml);
        $this->assertStringContainsString('--site-button-radius:10px;', $homeHtml);
        $this->assertStringContainsString('"designCollection":"larkspur"', $homeHtml);

        foreach (['services', 'clinicians', 'locations', 'fees', 'new-patients', 'contact'] as $slug) {
            $this->assertStringContainsString('"designCollection":"larkspur"', (string) $this->get('/'.$slug)->assertOk()->getContent());
        }
    }
}
