<?php

use App\Cms\PluginLoader;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Builder;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
| The visual page builder's API (session auth, admins only).
*/
Route::prefix('api/v1')->name('builder.')->middleware(['auth', 'admin', 'throttle:240,1'])->group(function () {
    Route::get('pages/{page}', [Builder\PageApiController::class, 'show'])->name('pages.show');
    Route::put('pages/{page}', [Builder\PageApiController::class, 'update'])->name('pages.update');
    Route::post('pages/{page}/publish', [Builder\PageApiController::class, 'publish'])->name('pages.publish');
    Route::get('pages/{page}/versions', [Builder\PageApiController::class, 'versions'])->name('pages.versions');
    Route::post('pages/{page}/versions/{version}/restore', [Builder\PageApiController::class, 'restore'])->name('pages.versions.restore');

    Route::get('design-blocks', [Builder\DesignBlockController::class, 'index'])->name('designs.index');
    Route::get('design-blocks/{key}', [Builder\DesignBlockController::class, 'show'])->name('designs.show');

    Route::get('templates', [Builder\TemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [Builder\TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}', [Builder\TemplateController::class, 'show'])->name('templates.show');
    Route::post('templates/{template}/install', [Builder\TemplateController::class, 'install'])->name('templates.install');
    Route::delete('templates/{template}', [Builder\TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('media', [Builder\MediaController::class, 'index'])->name('media.index');
    Route::post('media', [Builder\MediaController::class, 'store'])->middleware('throttle:30,1')->name('media.store');
    Route::patch('media/{media}', [Builder\MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [Builder\MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('forms', [Builder\FormApiController::class, 'index'])->name('forms.index');
    Route::get('forms/{form}', [Builder\FormApiController::class, 'show'])->name('forms.show');
    Route::post('forms/{form}/fields', [Builder\FormApiController::class, 'storeField'])->name('forms.fields.store');
    Route::patch('form-fields/{field}', [Builder\FormApiController::class, 'updateField'])->name('form-fields.update');
    Route::delete('form-fields/{field}', [Builder\FormApiController::class, 'destroyField'])->name('form-fields.destroy');

    // Funnel-platform pickers the builder still asks for: always empty here.
    foreach (['popups', 'products', 'booking/services', 'booking/staff', 'global-components'] as $unavailable) {
        Route::get($unavailable, Builder\UnavailableFeatureController::class);
    }
});

/*
| Admin
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::post('pages/preview', [Admin\PageController::class, 'preview'])->name('pages.preview');
        Route::get('pages/site-header', [Admin\PageController::class, 'siteHeader'])->name('pages.site-header');
        Route::get('pages/site-footer', [Admin\PageController::class, 'siteFooter'])->name('pages.site-footer');
        Route::get('pages/{page}/builder', [Admin\PageController::class, 'builder'])->name('pages.builder');
        Route::post('pages/{page}/convert-to-builder', [Admin\PageController::class, 'convertToBuilder'])->name('pages.convert');
        Route::post('pages/{page}/switch-to-blocks', [Admin\PageController::class, 'switchToBlocks'])->name('pages.switch-to-blocks');
        Route::post('pages/new-builder-page', [Admin\PageController::class, 'storeBuilderPage'])->name('pages.store-builder');
        Route::resource('pages', Admin\PageController::class)->except('show');

        Route::get('ai-generator', [Admin\AiGeneratorController::class, 'index'])->name('ai.index');
        Route::post('ai-generator/generate', [Admin\AiGeneratorController::class, 'generate'])->middleware('throttle:10,1')->name('ai.generate');
        Route::post('ai-generator/apply', [Admin\AiGeneratorController::class, 'apply'])->name('ai.apply');
        Route::post('ai-generator/site/plan', [Admin\AiGeneratorController::class, 'sitePlan'])->middleware('throttle:10,1')->name('ai.site.plan');
        Route::post('ai-generator/site/page', [Admin\AiGeneratorController::class, 'sitePage'])->middleware('throttle:40,1')->name('ai.site.page');
        Route::post('ai-generator/site/chrome', [Admin\AiGeneratorController::class, 'siteChrome'])->middleware('throttle:10,1')->name('ai.site.chrome');
        Route::post('builder/images', Admin\BuilderImageController::class)->middleware('throttle:30,1')->name('builder.images');
        Route::post('posts/ai/write', [Admin\PostController::class, 'aiWrite'])->middleware('throttle:15,1')->name('posts.ai.write');
        Route::post('posts/ai/topics', [Admin\PostController::class, 'aiTopics'])->middleware('throttle:15,1')->name('posts.ai.topics');
        Route::post('posts/ai/auto', [Admin\PostController::class, 'aiAuto'])->middleware('throttle:40,1')->name('posts.ai.auto');
        Route::resource('posts', Admin\PostController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('settings/general', [Admin\SettingsController::class, 'general'])->name('settings.general');
        Route::put('settings/general', [Admin\SettingsController::class, 'updateGeneral'])->name('settings.general.update');
        Route::get('settings/smtp', [Admin\SettingsController::class, 'smtp'])->name('settings.smtp');
        Route::put('settings/smtp', [Admin\SettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('settings/smtp/test', [Admin\SettingsController::class, 'testSmtp'])->middleware('throttle:5,1')->name('settings.smtp.test');
        Route::get('settings/branding', [Admin\SettingsController::class, 'branding'])->name('settings.branding');
        Route::put('settings/branding', [Admin\SettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::get('settings/appearance', [Admin\SettingsController::class, 'appearance'])->name('settings.appearance');
        Route::put('settings/appearance', [Admin\SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');
        Route::post('settings/appearance/reset', [Admin\SettingsController::class, 'resetAppearance'])->name('settings.appearance.reset');
        Route::get('settings/ai', [Admin\SettingsController::class, 'ai'])->name('settings.ai');
        Route::put('settings/ai', [Admin\SettingsController::class, 'updateAi'])->name('settings.ai.update');
        Route::post('settings/ai/test', [Admin\SettingsController::class, 'testAi'])->middleware('throttle:10,1')->name('settings.ai.test');

        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account');
        Route::put('account', [Admin\AccountController::class, 'update'])->name('account.update');

        Route::get('confirm-password', [Admin\ConfirmPasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [Admin\ConfirmPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.confirm.store');

        /*
        | Themes and plugins. Anything that installs, runs or removes code needs a recent password.
        */
        $package = ['vendor' => '[a-z0-9-]+', 'package' => '[a-z0-9-]+'];

        Route::get('themes', [Admin\ThemeController::class, 'index'])->name('themes.index');
        Route::post('themes/preview/exit', [Admin\ThemeController::class, 'exitPreview'])->name('themes.preview.exit');
        Route::get('theme-settings', [Admin\ThemeController::class, 'activeDesign'])->name('themes.design.active');
        Route::get('themes/{vendor}/{package}/design', [Admin\ThemeController::class, 'design'])->where($package)->name('themes.design');
        Route::put('themes/{vendor}/{package}/design', [Admin\ThemeController::class, 'updateDesign'])->where($package)->name('themes.design.update');
        Route::get('themes/{vendor}/{package}/customize', [Admin\ThemeController::class, 'customize'])->where($package)->name('themes.customize');
        Route::put('themes/{vendor}/{package}/customize', [Admin\ThemeController::class, 'updateCustomize'])->where($package)->name('themes.customize.update');
        Route::post('themes/{vendor}/{package}/demo', [Admin\ThemeController::class, 'importDemo'])->where($package)->middleware('throttle:10,1')->name('themes.demo');

        Route::get('plugins', [Admin\PluginController::class, 'index'])->name('plugins.index');
        Route::get('plugins/{vendor}/{package}', [Admin\PluginController::class, 'show'])->where($package)->name('plugins.show');
        Route::get('plugins/{vendor}/{package}/settings', [Admin\PluginController::class, 'settings'])->where($package)->name('plugins.settings');
        Route::put('plugins/{vendor}/{package}/settings', [Admin\PluginController::class, 'updateSettings'])->where($package)->name('plugins.settings.update');
        Route::post('plugins/{vendor}/{package}/disable', [Admin\PluginController::class, 'disable'])->where($package)->name('plugins.disable');

        Route::middleware('cms.confirmed')->group(function () use ($package) {
            Route::post('themes/upload', [Admin\ThemeController::class, 'upload'])->middleware('throttle:10,1')->name('themes.upload');
            Route::post('themes/{vendor}/{package}/preview', [Admin\ThemeController::class, 'preview'])->where($package)->name('themes.preview');
            Route::post('themes/{vendor}/{package}/activate', [Admin\ThemeController::class, 'activate'])->where($package)->name('themes.activate');
            Route::delete('themes/{vendor}/{package}', [Admin\ThemeController::class, 'destroy'])->where($package)->name('themes.destroy');

            Route::post('plugins/upload', [Admin\PluginController::class, 'upload'])->middleware('throttle:10,1')->name('plugins.upload');
            Route::post('plugins/{vendor}/{package}/enable', [Admin\PluginController::class, 'enable'])->where($package)->name('plugins.enable');
            Route::delete('plugins/{vendor}/{package}', [Admin\PluginController::class, 'destroy'])->where($package)->name('plugins.destroy');
        });
    });
});

/*
| Public site
*/
Route::get('/', HomeController::class)->name('home');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Routes of enabled plugins: after the core routes, before the page catch-all.
app(PluginLoader::class)->routes();

// Capture form submissions from visual builder pages.
Route::post('f/{form}', [Builder\FormApiController::class, 'submit'])->middleware('throttle:20,1')->name('forms.capture');

// Last, so it never shadows the routes above.
Route::get('{page:slug}', [PageController::class, 'show'])
    ->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
