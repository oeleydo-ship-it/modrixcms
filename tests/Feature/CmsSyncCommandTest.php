<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class CmsSyncCommandTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    public function test_cms_sync_republishes_theme_assets_after_a_fresh_checkout(): void
    {
        $published = config('cms.public_path').'/themes/modrix/larkspur';

        // A new release folder starts without the git-ignored public/extensions copies.
        File::deleteDirectory(config('cms.public_path'));
        $this->assertDirectoryDoesNotExist($published);

        $this->artisan('cms:sync')->assertSuccessful();

        $this->assertFileExists($published.'/theme.css');
        $this->assertFileExists($published.'/theme.js');
        $this->assertFileExists($published.'/mark-light.svg');
        $this->assertFileExists(config('cms.boot_cache'));
    }

    public function test_cms_sync_is_safe_to_run_twice(): void
    {
        $this->artisan('cms:sync')->assertSuccessful();
        $this->artisan('cms:sync')->assertSuccessful();

        $this->assertFileExists(config('cms.public_path').'/themes/modrix/larkspur/theme.css');
    }
}
