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
                ->where('themes.0.displayName', 'Minimal')
                ->where('themes.0.needsTrust', true)
                ->where('themes.1.displayName', 'Modrix Default')
                ->where('themes.1.active', true));
    }

    public function test_default_theme_settings_become_css_variables(): void
    {
        $this->actingAs($this->admin());

        $this->get('/')->assertOk()->assertSee(':root{--brand: #5b3df5;', false);

        $this->put(route('admin.themes.customize.update', ['modrix', 'default']), ['brand' => '#ff0000', 'corners' => 'square'])
            ->assertSessionHasNoErrors();

        $this->get('/')->assertSee('--brand: #ff0000;', false)->assertSee('--radius: 2px;', false);
    }

    public function test_customize_rejects_invalid_values(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.themes.customize.update', ['modrix', 'default']), ['brand' => 'red;}body{display:none'])
            ->assertSessionHasErrors('brand');
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
