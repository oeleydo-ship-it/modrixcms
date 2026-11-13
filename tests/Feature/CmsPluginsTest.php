<?php

namespace Tests\Feature;

use App\Cms\PluginLoader;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Extension;
use App\Models\Page;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class CmsPluginsTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_upload_installs_a_plugin_disabled_and_scans_it(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles('acme/hello', '1.0.0', '$x = shell_exec("ls");'))])
            ->assertRedirect(route('admin.plugins.show', ['acme', 'hello']));

        $this->assertFileExists(config('cms.path').'/plugins/acme/hello/plugin.json');

        $record = Extension::query()->where('name', 'acme/hello')->firstOrFail();
        $this->assertFalse($record->enabled);
        $this->assertNull($record->trusted_at);
        $this->assertSame('critical', $record->scan[0]['severity']);
        $this->assertSame('src/PluginProvider.php', $record->scan[0]['file']);
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function badPackages(): array
    {
        $manifest = fn (array $changes = []) => json_encode(array_replace([
            'name' => 'acme/bad', 'type' => 'plugin', 'version' => '1.0.0', 'cms_version' => '^1.0',
        ], $changes));

        return [
            'path traversal' => [['plugin.json' => $manifest(), '../evil.php' => '<?php'], 'Unsafe path'],
            'php in public' => [['plugin.json' => $manifest(), 'public/shell.php' => '<?php'], 'inside public/'],
            'blocked file' => [['plugin.json' => $manifest(), '.htaccess' => 'x'], 'not allowed'],
            'manifest in a folder' => [['bad/plugin.json' => $manifest()], 'root of the zip'],
            'theme sent as plugin' => [['plugin.json' => $manifest(['type' => 'theme'])], '"type" must be "plugin"'],
            'bad name' => [['plugin.json' => $manifest(['name' => 'Acme Bad'])], 'vendor/package'],
            'reserved namespace' => [['plugin.json' => $manifest(['autoload' => ['psr-4' => ['App\\Evil\\' => 'src/']]])], 'reserved'],
            'incompatible' => [['plugin.json' => $manifest(['cms_version' => '^2.0'])], 'needs CMS version'],
        ];
    }

    #[DataProvider('badPackages')]
    public function test_upload_rejects_unsafe_or_invalid_packages(array $files, string $message): void
    {
        $this->confirmedAdmin();

        $this->from(route('admin.plugins.index'))
            ->post(route('admin.plugins.upload'), ['package' => $this->zip($files)])
            ->assertRedirect(route('admin.plugins.index'));
        $this->assertSessionErrorContains('package', $message);

        $this->assertDirectoryDoesNotExist(config('cms.path').'/plugins/acme/bad');
        $this->assertSame(0, Extension::query()->count());
    }

    public function test_upload_needs_a_recently_confirmed_password(): void
    {
        $this->actingAs($this->admin());

        $this->from(route('admin.plugins.index'))
            ->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles())])
            ->assertRedirect(route('admin.password.confirm'));

        $this->post(route('admin.password.confirm.store'), ['password' => 'password'])
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertDirectoryDoesNotExist(config('cms.path').'/plugins/acme/hello');
    }

    public function test_non_admins_and_guests_cannot_reach_plugins(): void
    {
        $this->get(route('admin.plugins.index'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())->get(route('admin.plugins.index'))->assertRedirect(route('admin.login'));
    }

    public function test_uploads_can_be_turned_off(): void
    {
        config(['cms.uploads' => false]);
        $this->confirmedAdmin();

        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles())])->assertForbidden();
    }

    public function test_same_version_is_refused_and_higher_version_updates(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles('acme/hello', '1.0.0'))]);

        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles('acme/hello', '1.0.0'))])
            ->assertSessionHasErrors('package');

        $this->post(route('admin.plugins.enable', ['acme', 'hello']), ['trust' => 1])->assertSessionHasNoErrors();

        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles('acme/hello', '1.1.0'))])
            ->assertSessionHasNoErrors();

        $record = Extension::query()->where('name', 'acme/hello')->firstOrFail();
        $this->assertSame('1.1.0', $record->version);
        $this->assertTrue($record->enabled);
        $this->assertDirectoryExists(config('cms.storage_path').'/backups/plugins/acme/hello/1.0.0');
    }

    public function test_enabling_needs_trust_then_runs_migrations_and_loads_the_plugin(): void
    {
        $this->confirmedAdmin();

        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']))
            ->assertSessionHasErrors(['plugin' => 'Confirm that you trust Contact form: it runs code on your server.']);
        $this->assertFalse(Schema::hasTable('contact_form_messages'));

        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']), ['trust' => 1])->assertSessionHasNoErrors();

        $this->assertTrue(Schema::hasTable('contact_form_messages'));
        $this->assertTrue(app(PluginLoader::class)->isLoaded('modrix/contact-form'));
        $this->assertSame(['modrix/contact-form'], array_column(require config('cms.boot_cache'), 'name'));
        $this->assertFileExists(config('cms.public_path').'/plugins/modrix/contact-form/contact-form.css');
        $this->assertArrayHasKey('modrix/contact-form:form', PageBuilder::definitions());
    }

    public function test_contact_form_plugin_works_end_to_end(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']), ['trust' => 1]);
        $this->loadPluginRoutes();

        Page::factory()->create(['slug' => 'contact', 'blocks' => [PageBuilder::make('modrix/contact-form:form')]]);

        $this->get('/contact')->assertOk()
            ->assertSeeInPage('Get in touch')
            ->assertSeeInPage(route('plugin.modrix.contact-form.send'))
            ->assertInertia(fn ($page) => $page->where('blocks.0.type', 'modrix/contact-form:form'))
            ->assertSee('extensions/plugins/modrix/contact-form/contact-form.css', false)
            ->assertSee('extensions/plugins/modrix/contact-form/contact-form.js', false);

        $this->from('/contact')->post(route('plugin.modrix.contact-form.send'), [
            'name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Hello there',
        ])->assertRedirect('/contact#contact');

        $this->assertDatabaseHas('contact_form_messages', ['email' => 'ada@example.com']);

        $this->get('/contact')->assertInertia(fn ($page) => $page->where('contactForm.sent', true));

        $this->get(route('plugin.modrix.contact-form.messages.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('plugin:modrix/contact-form/Messages'))
            ->assertSeeInPage('Hello there');
        $this->get(route('admin.dashboard'))->assertSeeInPage('Messages');
    }

    public function test_plugin_settings_are_saved_cleaned_and_readable(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']), ['trust' => 1]);

        $this->put(route('admin.plugins.settings.update', ['modrix', 'contact-form']), ['recipient' => 'not-an-email'])
            ->assertSessionHasErrors('recipient');

        $this->put(route('admin.plugins.settings.update', ['modrix', 'contact-form']), [
            'recipient' => 'owner@example.com', 'success_message' => 'Got it!', 'sneaky' => 'x',
        ])->assertSessionHasNoErrors();

        $settings = Extension::query()->where('name', 'modrix/contact-form')->value('settings');
        $this->assertSame(['recipient' => 'owner@example.com', 'success_message' => 'Got it!'], $settings);
        $this->assertSame('Got it!', (require config('cms.boot_cache'))[0]['settings']['success_message']);
    }

    public function test_a_plugin_that_crashes_while_loading_is_disabled_with_its_error(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.upload'), ['package' => $this->zip($this->pluginFiles('acme/crashy', '1.0.0', 'throw new \RuntimeException("boom");'))]);

        $this->post(route('admin.plugins.enable', ['acme', 'crashy']), ['trust' => 1]);
        $this->assertSessionErrorContains('plugin', 'boom');

        $record = Extension::query()->where('name', 'acme/crashy')->firstOrFail();
        $this->assertFalse($record->enabled);
        $this->assertStringContainsString('boom', $record->error);
        $this->assertSame([], require config('cms.boot_cache'));
    }

    public function test_uninstall_can_delete_the_plugins_data(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']), ['trust' => 1]);
        $this->assertTrue(Schema::hasTable('contact_form_messages'));

        $this->delete(route('admin.plugins.destroy', ['modrix', 'contact-form']), ['delete_data' => 1])
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertFalse(Schema::hasTable('contact_form_messages'));
        $this->assertDirectoryDoesNotExist(config('cms.path').'/plugins/modrix/contact-form');
        $this->assertDirectoryDoesNotExist(config('cms.public_path').'/plugins/modrix/contact-form');
        $this->assertSame(0, Extension::query()->count());
    }

    public function test_safe_mode_loads_no_plugins(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'contact-form']), ['trust' => 1]);

        $this->artisan('cms:safe-mode on')->assertSuccessful();

        $loader = new PluginLoader($this->app);
        $loader->load();
        $this->assertSame([], $loader->loaded());

        $this->loadPluginRoutes();
        $this->get(route('admin.dashboard'))->assertInertia(fn ($page) => $page->where('cms.safeMode', true));

        $this->artisan('cms:safe-mode off')->assertSuccessful();
        $loader->load();
        $this->assertTrue($loader->isLoaded('modrix/contact-form'));
    }

    public function test_enabling_a_plugin_changes_the_asset_version_so_pages_reload_its_scripts(): void
    {
        $middleware = app(HandleInertiaRequests::class);
        $request = Request::create('/admin');
        $before = $middleware->version($request);

        $this->artisan('cms:plugin enable modrix/contact-form --trust')->assertSuccessful();

        $this->assertNotSame($before, $middleware->version($request));
    }

    public function test_cli_lists_enables_and_scaffolds(): void
    {
        $this->artisan('cms:plugin enable modrix/contact-form')->assertFailed();
        $this->artisan('cms:plugin enable modrix/contact-form --trust')->assertSuccessful();
        $this->artisan('cms:plugin list')->expectsOutputToContain('enabled')->assertSuccessful();

        $this->artisan('cms:make plugin acme/scaffolded')->assertSuccessful();
        $this->artisan('cms:plugin enable acme/scaffolded --trust')->assertSuccessful();
        $this->assertArrayHasKey('acme/scaffolded:hello', PageBuilder::definitions());
    }
}
