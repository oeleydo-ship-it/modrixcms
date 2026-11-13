<?php

namespace App\Cms;

use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\Package;
use App\Models\Extension;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Loads enabled, trusted plugins on every request.
 *
 * The list comes from a boot cache (bootstrap/cache/cms-plugins.php) so no database query is
 * needed; it is rebuilt whenever a plugin is enabled, disabled, updated or its settings change.
 * A plugin whose service provider throws is disabled and its error recorded.
 */
class PluginLoader
{
    /** @var array<string, array<string, mixed>> name → boot entry */
    private array $loaded = [];

    /** @var array<string, string> name → error */
    private array $failed = [];

    private bool $autoloaderRegistered = false;

    /** @var array<string, string> namespace prefix → directory */
    private array $autoload = [];

    public function __construct(private Application $app) {}

    public static function cachePath(): string
    {
        return config('cms.boot_cache') ?: base_path('bootstrap/cache/cms-plugins.php');
    }

    /** Registers the service providers of enabled plugins. Called once, while the app registers. */
    public function load(): void
    {
        if (Cms::safeMode()) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $this->loadOne($entry);
        }
    }

    /** @param array<string, mixed> $entry */
    public function loadOne(array $entry): void
    {
        $name = $entry['name'];

        if (isset($this->loaded[$name]) || ! is_dir($entry['path'])) {
            return;
        }

        foreach ($entry['autoload'] as $prefix => $dir) {
            $this->autoload[$prefix] = $dir;
        }

        $this->registerAutoloader();
        $this->loaded[$name] = $entry;

        $class = $entry['provider'];

        if ($class === null) {
            return;
        }

        try {
            if (! class_exists($class) || ! is_subclass_of($class, PluginServiceProvider::class)) {
                throw new \RuntimeException("Provider {$class} was not found or does not extend ".PluginServiceProvider::class.'.');
            }

            /** @var PluginServiceProvider $provider */
            $provider = new $class($this->app);
            $provider->setPlugin($entry);
            $this->app->register($provider);
        } catch (Throwable $e) {
            $this->fail($name, $e);
        }
    }

    /** Disables a plugin that broke while loading, so the next request works again. */
    public function fail(string $name, Throwable $e): void
    {
        unset($this->loaded[$name]);
        $this->failed[$name] = $e->getMessage();

        Log::error("Plugin {$name} failed to load and was disabled: {$e->getMessage()}", ['exception' => $e]);

        try {
            Extension::query()->plugins()->where('name', $name)->update([
                'enabled' => false,
                'error' => mb_substr($e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')', 0, 2000),
            ]);
            $this->rebuildCache();
        } catch (Throwable) {
            // No database: the plugin stays in the boot cache but is skipped for this request.
        }
    }

    /** Registers the public and admin routes of loaded plugins. Called from routes/web.php. */
    public function routes(): void
    {
        foreach ($this->loaded as $name => $entry) {
            $prefix = 'plugin.'.str_replace('/', '.', $name).'.';

            try {
                if ($web = $entry['routes']['web'] ?? null) {
                    Route::middleware('web')->name($prefix)->group($web);
                }

                if ($admin = $entry['routes']['admin'] ?? null) {
                    Route::middleware(['web', 'auth', 'admin'])
                        ->prefix('admin/p/'.$name)
                        ->name($prefix)
                        ->group($admin);
                }
            } catch (Throwable $e) {
                $this->fail($name, $e);
            }
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function loaded(): array
    {
        return $this->loaded;
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loaded[$name]);
    }

    /** @return array<string, string> */
    public function failed(): array
    {
        return $this->failed;
    }

    /** @return array<int, string> URLs of the compiled scripts of loaded plugins */
    public function scripts(): array
    {
        $urls = [];

        foreach ($this->loaded as $name => $entry) {
            foreach ($entry['scripts'] ?? [] as $file) {
                $urls[] = Cms::publicUrl(Extension::PLUGIN, $name, $file).'?v='.$entry['version'];
            }
        }

        return $urls;
    }

    /** @return array<string, mixed> */
    public function settings(string $name): array
    {
        return $this->loaded[$name]['settings'] ?? [];
    }

    /** @return array<int, array{title: string, route: string, plugin: string}> admin menu entries whose route exists */
    public function adminNavigation(): array
    {
        $items = [];

        foreach ($this->loaded as $name => $entry) {
            foreach ($entry['admin_navigation'] as $item) {
                $route = 'plugin.'.str_replace('/', '.', $name).'.'.$item['route'];

                if (Route::has($route)) {
                    $items[] = ['title' => $item['title'], 'route' => $route, 'plugin' => $name];
                }
            }
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    private function entries(): array
    {
        $path = self::cachePath();

        if (is_file($path)) {
            $entries = require $path;

            if (is_array($entries)) {
                return $entries;
            }
        }

        return $this->rebuildCache() ?? [];
    }

    /**
     * Writes the boot cache from the database. Returns null (and writes nothing) when the
     * database can't be read, e.g. before the first migration.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function rebuildCache(): ?array
    {
        try {
            $names = Extension::query()->plugins()->where('enabled', true)->whereNotNull('trusted_at')->pluck('name');
        } catch (Throwable) {
            return null;
        }

        $registry = $this->app->make(ExtensionRegistry::class);
        $entries = [];

        foreach ($names as $name) {
            $package = $registry->find(Extension::PLUGIN, $name);

            if ($package?->manifest === null || ! $package->isCompatible()) {
                continue;
            }

            $entries[] = self::entryFor($package);
        }

        $path = self::cachePath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '<?php return '.var_export($entries, true).';'.PHP_EOL);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }

        return $entries;
    }

    /** @return array<string, mixed> */
    public static function entryFor(Package $package): array
    {
        $manifest = $package->manifest;
        $routes = [];

        foreach (['web', 'admin'] as $kind) {
            if ($file = $manifest->data['routes'][$kind] ?? null) {
                $routes[$kind] = $manifest->file($file);
            }
        }

        return [
            'name' => $manifest->name(),
            'slug' => $manifest->slug(),
            'version' => $manifest->version(),
            'path' => $package->path,
            'autoload' => $manifest->autoload(),
            'provider' => $manifest->provider(),
            'routes' => $routes,
            'admin_navigation' => $manifest->data['admin_navigation'] ?? [],
            'scripts' => $manifest->data['scripts'] ?? [],
            'settings' => $package->settings(),
        ];
    }

    private function registerAutoloader(): void
    {
        if ($this->autoloaderRegistered) {
            return;
        }

        $this->autoloaderRegistered = true;

        spl_autoload_register(function (string $class): void {
            foreach ($this->autoload as $prefix => $dir) {
                if (str_starts_with($class, $prefix)) {
                    $file = $dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))).'.php';

                    if (is_file($file)) {
                        require_once $file;

                        return;
                    }
                }
            }
        });
    }
}
