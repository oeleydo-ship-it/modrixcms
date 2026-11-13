<?php

namespace App\Cms;

use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Base class for a plugin's service provider (the "provider" in plugin.json).
 *
 * Put your code in registerPlugin() and bootPlugin(). If either throws, the plugin is
 * disabled and the error shown in Admin → Plugins, instead of taking the site down.
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    /** @var array<string, mixed> the plugin's boot entry (name, path, settings, …) */
    protected array $plugin = [];

    /** @param array<string, mixed> $plugin */
    final public function setPlugin(array $plugin): void
    {
        $this->plugin = $plugin;
    }

    final public function register(): void
    {
        $this->guard(function (): void {
            $views = $this->path('resources/views');

            if (is_dir($views)) {
                $this->loadViewsFrom($views, $this->plugin['slug']);
            }

            $this->registerPlugin();
        });
    }

    final public function boot(): void
    {
        $this->guard(fn () => $this->bootPlugin());
    }

    protected function registerPlugin(): void {}

    protected function bootPlugin(): void {}

    /** "vendor/package" */
    protected function name(): string
    {
        return $this->plugin['name'];
    }

    /** Absolute path of a file in the plugin. */
    protected function path(string $relative = ''): string
    {
        return rtrim($this->plugin['path'].DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);
    }

    /** A value from the plugin's settings. */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->plugin['settings'][$key] ?? $default;
    }

    /**
     * Adds a page-builder block: its fields and default content. $key "form" becomes type
     * "vendor/package:form"; register the React component that draws it under that type in
     * the plugin's script: ModrixCMS.registerPlugin('vendor/package', { blocks: { 'vendor/package:form': Form } }).
     */
    protected function block(string $key, array $definition): void
    {
        $this->app->make(BlockRegistry::class)->register($this->name().':'.$key, $definition);
    }

    /** Prints HTML at an output point (see Hooks::OUTPUTS). */
    protected function hook(string $name, callable $callback, int $priority = 10): void
    {
        Hooks::add($name, $callback, $priority);
    }

    /** Changes a value the core passes through (see Hooks::FILTERS). */
    protected function filter(string $name, callable $callback, int $priority = 10): void
    {
        Hooks::filter($name, $callback, $priority);
    }

    private function guard(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            $this->app->make(PluginLoader::class)->fail($this->plugin['name'] ?? static::class, $e);
        }
    }
}
