<?php

namespace App\Cms\Extensions;

use App\Cms\PluginLoader;
use App\Cms\PluginServiceProvider;
use App\Models\Extension;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Everything that changes installed themes and plugins: upload (new or update),
 * enable / activate (with trust), disable, settings, uninstall / delete.
 */
class ExtensionInstaller
{
    public function __construct(
        private ExtensionRegistry $registry,
        private PackageInspector $inspector,
        private CodeScanner $scanner,
        private PluginLoader $loader,
        private Settings $settings,
    ) {}

    /**
     * Installs an uploaded zip, or updates the package if a lower version is installed.
     * Uploaded packages start disabled (plugins) or inactive (themes) unless it is an update.
     *
     * @return array{package: Package, updated: bool, previous: ?string}
     *
     * @throws InvalidPackage
     */
    public function upload(string $zipPath, string $type, ?User $user): array
    {
        [$manifest, $temp] = $this->inspector->extract($zipPath, $type);

        try {
            if (! $manifest->isCompatible()) {
                throw new InvalidPackage("{$manifest->displayName()} needs CMS version {$manifest->cmsVersion()}; this site runs ".config('cms.version').'.');
            }

            $name = $manifest->name();
            $existing = $this->registry->find($type, $name);
            $previous = $existing?->version();

            if ($existing !== null && $existing->manifest !== null && VersionConstraint::compare($manifest->version(), $previous) <= 0) {
                throw new InvalidPackage("{$manifest->displayName()} {$previous} is already installed. Upload a higher version to update it.");
            }

            $scan = $this->scanner->scan($temp);
            $target = $this->registry->pathFor($type, $name);
            $backup = null;

            if ($existing !== null) {
                $backup = $this->backup($type, $name, $previous);
            }

            File::ensureDirectoryExists(dirname($target));

            if (! File::moveDirectory($temp, $target)) {
                if ($backup) {
                    File::moveDirectory($backup, $target, true);
                }

                throw new InvalidPackage('Could not move the package into place. Check folder permissions on '.dirname($target).'.');
            }
        } finally {
            File::deleteDirectory($temp);
        }

        $record = Extension::query()->firstOrNew(['type' => $type, 'name' => $name]);
        $wasEnabled = $record->exists && $record->enabled;
        $record->fill(['version' => $manifest->version(), 'scan' => $scan, 'error' => null]);

        if ($existing !== null && $record->trusted_at !== null && $user) {
            // Uploading an update of a trusted package is itself trusting the new code.
            $record->fill(['trusted_at' => now(), 'trusted_by' => $user->id]);
        }

        $record->save();
        $package = $this->registry->find($type, $name);
        $this->publishAssets($package);

        if ($type === Extension::PLUGIN && $wasEnabled) {
            try {
                $this->migrate($package);
            } catch (InvalidPackage $e) {
                $this->restore($type, $name, $backup, $previous);

                throw $e;
            }

            $this->pluginsChanged();
        }

        return ['package' => $this->registry->find($type, $name), 'updated' => $existing !== null, 'previous' => $previous];
    }

    /**
     * Turns a plugin on: checks it, records who trusted it, runs its migrations and publishes its assets.
     *
     * @throws InvalidPackage
     */
    public function enablePlugin(string $name, ?User $user, bool $trust): Package
    {
        $package = $this->usablePackage(Extension::PLUGIN, $name);
        $record = $this->trust($package, $user, $trust);

        $this->checkProvider($package);
        $this->migrate($package);
        $this->publishAssets($package);

        $record->fill(['enabled' => true, 'error' => null, 'version' => $package->version()])->save();
        $this->pluginsChanged();

        return $this->registry->find(Extension::PLUGIN, $name);
    }

    public function disablePlugin(string $name): void
    {
        Extension::query()->plugins()->where('name', $name)->update(['enabled' => false]);
        $this->pluginsChanged();
    }

    /**
     * Removes a plugin. With $deleteData its migrations are rolled back (its tables dropped).
     *
     * @throws InvalidPackage
     */
    public function uninstallPlugin(string $name, bool $deleteData): void
    {
        $package = $this->registry->find(Extension::PLUGIN, $name) ?? throw new InvalidPackage("Plugin {$name} is not installed.");

        $this->disablePlugin($name);

        if ($deleteData && $package->manifest !== null && $package->isTrusted() && is_dir($package->path.'/database/migrations')) {
            $this->artisan('migrate:reset', ['--path' => $package->path.'/database/migrations', '--realpath' => true, '--force' => true]);
        }

        $this->remove(Extension::PLUGIN, $name);
        $this->pluginsChanged();
    }

    /**
     * Makes a theme the site's theme.
     *
     * @throws InvalidPackage
     */
    public function activateTheme(string $name, ?User $user, bool $trust): Package
    {
        $package = $this->usablePackage(Extension::THEME, $name);
        $this->trustTheme($package, $user, $trust);
        $this->publishAssets($package);

        Extension::query()->themes()->update(['enabled' => false]);
        Extension::query()->updateOrCreate(['type' => Extension::THEME, 'name' => $name], ['enabled' => true, 'version' => $package->version()]);
        $this->settings->set(['active_theme' => $name]);

        return $this->registry->find(Extension::THEME, $name);
    }

    /**
     * Gets a theme ready to preview (previewing runs its templates, so it needs trust too).
     *
     * @throws InvalidPackage
     */
    public function prepareThemePreview(string $name, ?User $user, bool $trust): Package
    {
        $package = $this->usablePackage(Extension::THEME, $name);
        $this->trustTheme($package, $user, $trust);
        $this->publishAssets($package);

        return $this->registry->find(Extension::THEME, $name);
    }

    /** @throws InvalidPackage */
    public function deleteTheme(string $name): void
    {
        if ($name === config('cms.default_theme')) {
            throw new InvalidPackage('The default theme can’t be deleted. It is what the site falls back to.');
        }

        if ($name === $this->settings->get('active_theme')) {
            throw new InvalidPackage('Activate another theme before deleting this one.');
        }

        if (! $this->registry->exists(Extension::THEME, $name)) {
            throw new InvalidPackage("Theme {$name} is not installed.");
        }

        $this->remove(Extension::THEME, $name);
    }

    /**
     * Saves settings of a theme or plugin (only the declared fields, cleaned by type).
     *
     * @param  array<string, mixed>  $input
     */
    public function saveSettings(Package $package, array $input): void
    {
        $fields = $package->manifest?->settings() ?? [];

        Extension::query()->updateOrCreate(
            ['type' => $package->type, 'name' => $package->name],
            ['settings' => SettingsSchema::cleanAll($fields, $input), 'version' => $package->version()],
        );

        if ($package->type === Extension::PLUGIN) {
            $this->pluginsChanged();
        }
    }

    /** @throws InvalidPackage */
    private function usablePackage(string $type, string $name): Package
    {
        $package = $this->registry->find($type, $name) ?? throw new InvalidPackage(ucfirst($type)." {$name} is not installed.");

        if ($package->manifest === null) {
            throw new InvalidPackage($package->problem ?? 'The package is broken.');
        }

        if (! $package->isCompatible()) {
            throw new InvalidPackage("{$package->displayName()} needs CMS version {$package->manifest->cmsVersion()}; this site runs ".config('cms.version').'.');
        }

        // Same file rules as an upload, for packages copied onto the server by hand.
        $this->inspector->checkDirectory($package->path, $type);

        return $package;
    }

    /** @throws InvalidPackage */
    private function trust(Package $package, ?User $user, bool $trust): Extension
    {
        $record = Extension::query()->firstOrNew(['type' => $package->type, 'name' => $package->name]);

        if ($record->trusted_at === null) {
            if (! $trust) {
                throw new InvalidPackage("Confirm that you trust {$package->displayName()}: it runs code on your server.");
            }

            $record->fill(['trusted_at' => now(), 'trusted_by' => $user?->id]);
        }

        if (! $record->exists) {
            $record->fill(['version' => $package->version(), 'scan' => $this->scanner->scan($package->path)]);
        }

        $record->save();

        return $record;
    }

    /** Themes without scripts (only CSS and settings) don't run code, so they need no trust. */
    private function trustTheme(Package $package, ?User $user, bool $trust): void
    {
        if ($package->hasScripts()) {
            $this->trust($package, $user, $trust);
        } else {
            Extension::query()->firstOrCreate(['type' => Extension::THEME, 'name' => $package->name], ['version' => $package->version()]);
        }
    }

    /** Loads the plugin's provider class once, so a typo is reported now and not on the next request. */
    private function checkProvider(Package $package): void
    {
        $class = $package->manifest->provider();
        $entry = PluginLoader::entryFor($package);

        if ($class === null) {
            $this->loader->loadOne($entry);

            return;
        }

        $file = null;

        foreach ($entry['autoload'] as $prefix => $dir) {
            if (str_starts_with($class, $prefix)) {
                $file = $dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))).'.php';
            }
        }

        if (! class_exists($class, false) && ($file === null || ! is_file($file))) {
            throw new InvalidPackage("The plugin's provider {$class} was not found (expected ".($file ? basename($file) : 'a file in an autoload folder').').');
        }

        try {
            $this->loader->loadOne($entry);
        } catch (Throwable $e) {
            throw new InvalidPackage("The plugin failed to load: {$e->getMessage()}");
        }

        if (! is_subclass_of($class, PluginServiceProvider::class)) {
            throw new InvalidPackage("{$class} must extend ".PluginServiceProvider::class.'.');
        }

        if (isset($this->loader->failed()[$package->name])) {
            throw new InvalidPackage('The plugin failed to load: '.$this->loader->failed()[$package->name]);
        }
    }

    /** @throws InvalidPackage */
    private function migrate(Package $package): void
    {
        $path = $package->path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        if (is_dir($path)) {
            $this->artisan('migrate', ['--path' => $path, '--realpath' => true, '--force' => true]);
        }
    }

    /** @throws InvalidPackage */
    private function artisan(string $command, array $arguments): void
    {
        try {
            $code = Artisan::call($command, $arguments);
        } catch (Throwable $e) {
            throw new InvalidPackage("Database update failed: {$e->getMessage()}");
        }

        if ($code !== 0) {
            throw new InvalidPackage('Database update failed: '.trim(Artisan::output()));
        }
    }

    /**
     * Copies the public/ folder of every installed theme and plugin to public/extensions/…
     * (git-ignored, so a fresh checkout or release folder starts without them) and rebuilds the
     * plugin boot list. Run after every deploy: `php artisan cms:sync`.
     *
     * @return int number of packages whose assets were published
     */
    public function republishAll(): int
    {
        $count = 0;

        foreach ([Extension::THEME, Extension::PLUGIN] as $type) {
            foreach ($this->registry->all($type) as $package) {
                if ($package->problem === null) {
                    $this->publishAssets($package);
                    $count++;
                }
            }
        }

        $this->loader->rebuildCache();

        return $count;
    }

    /** Copies the package's public/ folder to public/extensions/… (PHP files are refused at upload). */
    private function publishAssets(?Package $package): void
    {
        if ($package === null) {
            return;
        }

        $source = $package->path.DIRECTORY_SEPARATOR.'public';
        $target = $this->registry->publicPathFor($package->type, $package->name);

        File::deleteDirectory($target);

        if (is_dir($source)) {
            File::ensureDirectoryExists(dirname($target));
            File::copyDirectory($source, $target);
        }
    }

    private function remove(string $type, string $name): void
    {
        File::deleteDirectory($this->registry->pathFor($type, $name));
        File::deleteDirectory($this->registry->publicPathFor($type, $name));
        File::deleteDirectory($this->backupRoot($type, $name));
        Extension::query()->where(['type' => $type, 'name' => $name])->delete();

        $vendor = dirname($this->registry->pathFor($type, $name));

        if (is_dir($vendor) && File::isEmptyDirectory($vendor)) {
            File::deleteDirectory($vendor);
        }
    }

    /** Moves the installed version aside; only the latest backup is kept. */
    private function backup(string $type, string $name, ?string $version): string
    {
        $root = $this->backupRoot($type, $name);
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root);

        $backup = $root.DIRECTORY_SEPARATOR.($version ?? 'unknown');
        File::moveDirectory($this->registry->pathFor($type, $name), $backup);

        return $backup;
    }

    private function restore(string $type, string $name, ?string $backup, ?string $version): void
    {
        if ($backup === null || ! is_dir($backup)) {
            return;
        }

        File::moveDirectory($backup, $this->registry->pathFor($type, $name), true);
        Extension::query()->where(['type' => $type, 'name' => $name])->update(['version' => $version]);
        $this->publishAssets($this->registry->find($type, $name));
    }

    private function backupRoot(string $type, string $name): string
    {
        return config('cms.storage_path').'/backups/'.$type.'s/'.$name;
    }

    /** Rebuilds the plugin boot list and drops cached routes so the next request sees the change. */
    private function pluginsChanged(): void
    {
        $this->loader->rebuildCache();

        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }
    }
}
