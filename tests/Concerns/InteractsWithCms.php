<?php

namespace Tests\Concerns;

use App\Cms\PluginLoader;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Runs CMS tests against a throwaway copy of extensions/ (with the bundled themes and plugin),
 * never the real folder.
 */
trait InteractsWithCms
{
    protected string $cmsRoot;

    protected function setUpInteractsWithCms(): void
    {
        // One folder per test process, so parallel test runs never delete each other's copy.
        $this->cmsRoot = storage_path('framework/testing/cms-'.getmypid());
        File::deleteDirectory($this->cmsRoot);
        File::copyDirectory(base_path('extensions'), $this->cmsRoot.'/extensions');

        config([
            'cms.path' => $this->cmsRoot.'/extensions',
            'cms.public_path' => $this->cmsRoot.'/public',
            'cms.boot_cache' => $this->cmsRoot.'/plugins-cache.php',
            'cms.storage_path' => $this->cmsRoot.'/storage',
            'cms.uploads' => true,
            'cms.safe_mode' => false,
        ]);
    }

    protected function tearDownInteractsWithCms(): void
    {
        File::deleteDirectory($this->cmsRoot);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** Signed-in admin who confirmed their password just now. */
    protected function confirmedAdmin(): User
    {
        $admin = $this->admin();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

        return $admin;
    }

    /**
     * @param  array<string, string>  $files  path in zip → contents
     */
    protected function zip(array $files, string $name = 'package.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cms').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $entry => $contents) {
            $zip->addFromString($entry, $contents);
        }

        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }

    /** Zips a package folder from the bundled extensions (optionally changing its manifest). */
    protected function zipFolder(string $folder, array $manifestChanges = [], array $extraFiles = []): UploadedFile
    {
        $files = [];

        foreach (File::allFiles(base_path('extensions/'.$folder)) as $file) {
            $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
        }

        $manifestFile = isset($files['plugin.json']) ? 'plugin.json' : 'theme.json';
        $manifest = array_replace_recursive(json_decode($files[$manifestFile], true), $manifestChanges);
        $files[$manifestFile] = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->zip(array_replace($files, $extraFiles));
    }

    /** A minimal plugin package as zip entries. */
    protected function pluginFiles(string $name = 'acme/hello', string $version = '1.0.0', string $bootCode = ''): array
    {
        // PHP can't unload classes, so each plugin name gets its own namespace.
        $namespace = 'Acme\\'.Str::studly(explode('/', $name)[1]);

        return [
            'plugin.json' => json_encode([
                'name' => $name,
                'type' => 'plugin',
                'display_name' => 'Hello',
                'version' => $version,
                'cms_version' => '^1.0',
                'autoload' => ['psr-4' => [$namespace.'\\' => 'src/']],
                'provider' => $namespace.'\\PluginProvider',
            ]),
            'src/PluginProvider.php' => "<?php\nnamespace {$namespace};\nclass PluginProvider extends \\App\\Cms\\PluginServiceProvider\n{\n    protected function bootPlugin(): void\n    {\n        {$bootCode}\n    }\n}\n",
        ];
    }

    protected function assertSessionErrorContains(string $key, string $text): void
    {
        // Inertia keeps the error bags in the session as arrays: [bag => ['messages' => [field => [...]]]].
        $errors = session('errors');
        $error = is_array($errors)
            ? collect($errors)->map(fn ($bag) => $bag['messages'][$key][0] ?? null)->filter()->first()
            : $errors?->first($key);

        $this->assertNotNull($error, "No session error for \"{$key}\".");
        $this->assertStringContainsString($text, $error);
    }

    /** Registers the routes of plugins loaded during this test (a real request does this at boot). */
    protected function loadPluginRoutes(): void
    {
        app(PluginLoader::class)->routes();
        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }
}
