<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class PebblewoodThemeTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_the_pebblewood_theme_is_listed_and_activates_with_its_assets_and_design(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('themes', fn ($themes) => collect($themes)->contains('displayName', 'Pebblewood')));

        $this->post(route('admin.themes.activate', ['modrix', 'pebblewood']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->assertSame('modrix/pebblewood', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/pebblewood/theme.js', false)
            ->assertSee('extensions/themes/modrix/pebblewood/theme.css', false)
            ->assertSee('--site-primary:#1f1d3d;', false)
            ->assertSee('--site-background:#fffaf3;', false)
            ->assertSee('--site-heading-font:"Parkinsans"', false)
            ->assertSee('fonts.bunny.net/css?family=parkinsans', false)
            ->assertInertia(fn ($page) => $page
                ->where('theme.name', 'modrix/pebblewood')
                ->where('theme.settings.cta_label', 'Book a visit')
                ->where('theme.settings.show_announcement', true));
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/pebblewood/theme.js');
    }

    public function test_pebblewood_theme_settings_keep_their_own_values(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.themes.activate', ['modrix', 'pebblewood']), ['trust' => 1]);

        $this->put(route('admin.themes.design.update', ['modrix', 'pebblewood']), ['values' => ['accent' => '#ff3366']]);

        $this->get('/')->assertSee('--site-accent:#ff3366;', false)->assertSee('--site-primary:#1f1d3d;', false);
    }
}
