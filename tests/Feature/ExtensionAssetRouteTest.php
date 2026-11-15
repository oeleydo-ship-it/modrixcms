<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class ExtensionAssetRouteTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    private function themePublic(): string
    {
        return config('cms.path').'/themes/modrix/larkspur/public';
    }

    public function test_a_theme_screenshot_is_served_even_when_it_was_never_published(): void
    {
        // A fresh deploy has no public/extensions copy.
        File::deleteDirectory(config('cms.public_path'));

        $response = $this->get('/extensions/themes/modrix/larkspur/screenshot.svg')->assertOk();

        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
    }

    public function test_theme_scripts_and_styles_get_their_own_content_types(): void
    {
        $this->get('/extensions/themes/modrix/larkspur/theme.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');
        $this->get('/extensions/themes/modrix/larkspur/theme.js')->assertOk()->assertHeader('Content-Type', 'text/javascript; charset=utf-8');
    }

    public function test_the_response_sets_no_cookies_so_it_stays_cacheable(): void
    {
        $response = $this->get('/extensions/themes/modrix/larkspur/screenshot.svg')->assertOk();

        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_it_refuses_paths_outside_the_packages_public_folder(): void
    {
        // The manifest sits next to public/, not in it.
        $this->get('/extensions/themes/modrix/larkspur/theme.json')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/%2e%2e/theme.json')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/..%5Ctheme.json')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/%2e%2e/%2e%2e/%2e%2e/.env')->assertNotFound();
    }

    public function test_it_never_serves_php_or_other_unlisted_file_types(): void
    {
        File::put($this->themePublic().'/evil.php', '<?php echo "pwned";');
        File::put($this->themePublic().'/notes.exe', 'x');
        File::put($this->themePublic().'/.htaccess', 'x');

        $this->get('/extensions/themes/modrix/larkspur/evil.php')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/notes.exe')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/.htaccess')->assertNotFound();
    }

    public function test_unknown_packages_files_and_types_are_not_found(): void
    {
        $this->get('/extensions/themes/modrix/does-not-exist/theme.css')->assertNotFound();
        $this->get('/extensions/themes/modrix/larkspur/missing.css')->assertNotFound();
        $this->get('/extensions/widgets/modrix/larkspur/theme.css')->assertNotFound();
    }

    public function test_plugin_assets_are_served_too(): void
    {
        $this->get('/extensions/plugins/modrix/faq/faq.js')->assertOk()->assertHeader('Content-Type', 'text/javascript; charset=utf-8');
    }

    public function test_assets_load_while_first_run_setup_is_still_pending(): void
    {
        config(['cms.first_run_setup' => true]);

        $this->get('/extensions/themes/modrix/larkspur/theme.css')->assertOk();
    }
}
