<?php

namespace App\Providers;

use App\Cms\BlockRegistry;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Hooks;
use App\Cms\PluginLoader;
use App\Cms\Themes\ThemeManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/** Themes and plugins: loads enabled plugins and gives templates @hook and @themeStyles. */
class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Hooks::class);
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(ExtensionRegistry::class);
        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(ThemeManager::class);

        $this->app->make(PluginLoader::class)->load();
    }

    public function boot(): void
    {
        Blade::directive('hook', fn (string $expression) => "<?php echo app(\\App\\Cms\\Hooks::class)->render({$expression}); ?>");
        Blade::directive('themeStyles', fn () => '<?php echo app(\\App\\Cms\\Themes\\ThemeManager::class)->styles(); ?>');
    }
}
