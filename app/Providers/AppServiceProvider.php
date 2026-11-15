<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->ensureWritableTempDirectory();
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = $this->app->make(Settings::class);
        $settings->applyMailConfig();

        // For the root templates (app.blade.php / admin.blade.php). Page data goes through Inertia.
        View::share('settings', $settings);
    }

    /**
     * Ensure PHP and SQLite temp directories stay writable under `php artisan serve` on Windows.
     */
    private function ensureWritableTempDirectory(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return;
        }

        foreach (['TMP', 'TEMP', 'TMPDIR', 'LOCALAPPDATA', 'USERPROFILE', 'SQLITE_TMPDIR'] as $variable) {
            if (! in_array($variable, ServeCommand::$passthroughVariables, true)) {
                ServeCommand::$passthroughVariables[] = $variable;
            }
        }

        $currentTemp = @sys_get_temp_dir();

        if (is_string($currentTemp) && $currentTemp !== '' && is_dir($currentTemp) && is_writable($currentTemp)) {
            return;
        }

        $fallback = storage_path('framework/cache/tmp');

        if (! is_dir($fallback)) {
            @mkdir($fallback, 0775, true);
        }

        if (! is_dir($fallback) || ! is_writable($fallback)) {
            return;
        }

        foreach (['TMP', 'TEMP', 'TMPDIR', 'SQLITE_TMPDIR'] as $variable) {
            putenv("{$variable}={$fallback}");
            $_ENV[$variable] = $fallback;
            $_SERVER[$variable] = $fallback;
        }
    }
}
