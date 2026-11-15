<?php

namespace Tests\Feature;

use App\Models\Extension;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class CmsThemesTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_appearance_lists_the_bundled_themes(): void
    {
        $this->actingAs($this->admin())->get(route('admin.themes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Themes/Index')
                ->where('themes', function ($themes) {
                    $themes = collect($themes)->keyBy('displayName');

                    return $themes['Minimal']['needsTrust'] === true && $themes['Modrix Default']['active'] === true;
                }));
    }

    public function test_theme_settings_become_css_variables_on_the_site(): void
    {
        $this->actingAs($this->admin());

        // The default theme ships its own design defaults (theme.json "design").
        $this->get('/')->assertOk()
            ->assertSee('--site-primary:#5b3df5;', false)
            ->assertSee('--site-heading-font:"Bricolage Grotesque", system-ui, sans-serif;', false)
            ->assertSee('--brand:var(--site-primary)', false)
            ->assertSee('fonts.bunny.net/css?family=bricolage-grotesque', false);

        $this->put(route('admin.themes.design.update', ['modrix', 'default']), ['values' => [
            'primary' => '#ff0000',
            'header_bg' => '#101828',
            'button_shape' => 'square',
            'button_border_width' => '2px',
            'section_spacing' => 'roomy',
            'section_radius' => 'soft',
            'header_height' => '84',
            'hero_highlight' => 'pill',
            'hero_glow' => false,
            'heading_font' => 'Georgia',
            'heading_tracking' => 'wide',
        ]])->assertSessionHasNoErrors();

        $this->get('/')
            ->assertSee('--site-primary:#ff0000;', false)
            ->assertSee('--site-header-bg:#101828;', false)
            ->assertSee('--site-button-radius:4px;', false)
            ->assertSee('--site-button-border-width:2px;', false)
            ->assertSee('--site-section-padding:128px;', false)
            ->assertSee('--site-section-radius:16px;', false)
            ->assertSee('--site-header-height:84px;', false)
            ->assertSee('--site-hero-glow:0;', false)
            ->assertSee('--site-heading-font:Georgia, "Times New Roman", serif;', false)
            ->assertSee('--site-heading-tracking:0.02em;', false);
    }

    public function test_theme_settings_ignore_unsafe_or_unknown_values(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.themes.design.update', ['modrix', 'default']), ['values' => [
            'primary' => 'red;}body{display:none', 'button_shape' => 'wobbly', 'evil' => 'x',
        ]])->assertSessionHasNoErrors();

        $design = Extension::query()->where('name', 'modrix/default')->value('design');
        // Invalid values keep the current setting (here the theme's own default).
        $this->assertSame('#5b3df5', $design['primary']);
        $this->assertSame('pill', $design['button_shape']);
        $this->assertArrayNotHasKey('evil', $design);
        $this->get('/')->assertDontSee('display:none', false);
    }

    public function test_each_theme_keeps_its_own_settings(): void
    {
        $this->confirmedAdmin();

        $this->get(route('admin.themes.design', ['modrix', 'minimal']))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Themes/Design')
            ->where('values.primary', '#e4572e')
            ->where('values.heading_font', 'Georgia')
            ->has('groups.colors.fields.primary')
            ->has('groups.colors.fields.header_bg')
            ->has('groups.typography.fields.heading_tracking')
            ->has('groups.buttons.fields.button_hover')
            ->has('groups.header.fields.header_height')
            ->has('groups.hero.fields.hero_highlight')
            ->has('groups.sections.fields.card_padding')
            ->has('groups.links.fields.footer_padding'));

        $this->put(route('admin.themes.design.update', ['modrix', 'minimal']), ['values' => ['primary' => '#008080']]);
        $this->put(route('admin.themes.design.update', ['modrix', 'default']), ['values' => ['primary' => '#123456']]);

        $this->get('/')->assertSee('--site-primary:#123456;', false);

        $this->post(route('admin.themes.activate', ['modrix', 'minimal']), ['trust' => 1]);
        $this->get('/')->assertSee('--site-primary:#008080;', false)->assertSee('--site-heading-font:Georgia', false);

        $this->get(route('admin.themes.design.active'))->assertRedirect(route('admin.themes.design', ['modrix', 'minimal']));
    }

    public function test_activating_a_theme_with_scripts_needs_trust_and_then_loads_them(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.activate', ['modrix', 'minimal']))->assertSessionHasErrors('theme');
        $this->assertSame('modrix/default', app(Settings::class)->get('active_theme'));

        $this->post(route('admin.themes.activate', ['modrix', 'minimal']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->assertSame('modrix/minimal', app(Settings::class)->get('active_theme'));

        $this->get('/')->assertOk()
            ->assertSee('extensions/themes/modrix/minimal/theme.js', false)   // its React components
            ->assertSee('extensions/themes/modrix/minimal/theme.css', false)
            ->assertSeeInPage('Built on a stack you already trust')
            ->assertInertia(fn ($page) => $page->where('theme.name', 'modrix/minimal')->where('theme.settings.cta_label', 'Get started'));
        $this->assertFileExists(config('cms.public_path').'/themes/modrix/minimal/theme.js');
    }

    public function test_preview_is_only_for_the_admin_who_started_it(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.preview', ['modrix', 'minimal']), ['trust' => 1])->assertRedirect(route('home'));
        $this->get('/')->assertSee('minimal/theme.js', false)
            ->assertInertia(fn ($page) => $page->where('theme.previewing', true)->where('theme.name', 'modrix/minimal'));
        $this->assertSame('modrix/default', app(Settings::class)->get('active_theme'));

        $this->post(route('admin.themes.preview.exit'))->assertRedirect(route('admin.themes.index'));
    }

    public function test_guests_never_see_a_preview(): void
    {
        $this->withSession(['cms.preview_theme' => 'modrix/minimal'])->get('/')
            ->assertOk()->assertDontSee('minimal/theme.js', false)
            ->assertInertia(fn ($page) => $page->where('theme.previewing', false)->where('theme.name', 'modrix/default'));
    }

    public function test_untrusted_theme_is_never_used_even_if_set_as_active(): void
    {
        app(Settings::class)->set(['active_theme' => 'modrix/minimal']);

        $this->get('/')->assertOk()->assertDontSee('minimal/theme.js', false);
    }

    public function test_safe_mode_uses_the_default_theme(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.themes.activate', ['modrix', 'minimal']), ['trust' => 1]);
        config(['cms.safe_mode' => true]);

        $this->get('/')->assertOk()->assertDontSee('minimal/theme.js', false);
    }

    public function test_theme_upload_rules(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.themes.upload'), ['package' => $this->zipFolder('themes/modrix/minimal', ['name' => 'acme/evil'], ['src/Evil.php' => '<?php'])]);
        $this->assertSessionErrorContains('package', "Themes can't contain PHP");

        $this->post(route('admin.themes.upload'), ['package' => $this->zipFolder('themes/modrix/minimal', ['name' => 'acme/evil'], ['views/admin/dashboard.blade.php' => 'x'])])
            ->assertSessionHasErrors('package');

        $this->post(route('admin.themes.upload'), ['package' => $this->zipFolder('themes/modrix/minimal', ['name' => 'acme/harbor', 'display_name' => 'Harbor'])])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.themes.index'));

        $this->assertFileExists(config('cms.path').'/themes/acme/harbor/theme.json');
        $this->assertFileExists(config('cms.public_path').'/themes/acme/harbor/screenshot.svg');
        $this->get(route('admin.themes.index'))->assertSeeInPage('Harbor');
    }

    public function test_default_and_active_themes_cannot_be_deleted_but_others_can(): void
    {
        $this->confirmedAdmin();

        $this->delete(route('admin.themes.destroy', ['modrix', 'default']))->assertSessionHasErrors('theme');

        $this->post(route('admin.themes.activate', ['modrix', 'minimal']), ['trust' => 1]);
        $this->delete(route('admin.themes.destroy', ['modrix', 'minimal']))->assertSessionHasErrors('theme');

        $this->post(route('admin.themes.activate', ['modrix', 'default']));
        $this->delete(route('admin.themes.destroy', ['modrix', 'minimal']))->assertSessionHasNoErrors();

        $this->assertDirectoryDoesNotExist(config('cms.path').'/themes/modrix/minimal');
        $this->assertSame(0, Extension::query()->where('name', 'modrix/minimal')->count());
    }

    public function test_broken_theme_folder_is_listed_with_its_problem(): void
    {
        File::ensureDirectoryExists(config('cms.path').'/themes/acme/broken');
        File::put(config('cms.path').'/themes/acme/broken/theme.json', '{not json');

        $this->actingAs($this->admin())->get(route('admin.themes.index'))->assertOk()->assertSeeInPage('theme.json is not valid JSON.');
    }

    public function test_cli_activates_and_scaffolds_themes(): void
    {
        $this->artisan('cms:make theme acme/sunny')->assertSuccessful();
        $this->artisan('cms:theme activate acme/sunny')->assertFailed();
        $this->artisan('cms:theme activate acme/sunny --trust')->assertSuccessful();

        $this->get('/')->assertOk()->assertSee('--brand: #0f766e;', false);
    }
}
