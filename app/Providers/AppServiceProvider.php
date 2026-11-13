<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
}
