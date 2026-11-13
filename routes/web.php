<?php

use App\Cms\PluginLoader;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

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
        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::post('builder/images', Admin\BuilderImageController::class)->middleware('throttle:30,1')->name('builder.images');
        Route::resource('posts', Admin\PostController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('settings/general', [Admin\SettingsController::class, 'general'])->name('settings.general');
        Route::put('settings/general', [Admin\SettingsController::class, 'updateGeneral'])->name('settings.general.update');
        Route::get('settings/smtp', [Admin\SettingsController::class, 'smtp'])->name('settings.smtp');
        Route::put('settings/smtp', [Admin\SettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('settings/smtp/test', [Admin\SettingsController::class, 'testSmtp'])->middleware('throttle:5,1')->name('settings.smtp.test');
        Route::get('settings/branding', [Admin\SettingsController::class, 'branding'])->name('settings.branding');
        Route::put('settings/branding', [Admin\SettingsController::class, 'updateBranding'])->name('settings.branding.update');

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
        Route::get('themes/{vendor}/{package}/customize', [Admin\ThemeController::class, 'customize'])->where($package)->name('themes.customize');
        Route::put('themes/{vendor}/{package}/customize', [Admin\ThemeController::class, 'updateCustomize'])->where($package)->name('themes.customize.update');

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

// Last, so it never shadows the routes above.
Route::get('{page:slug}', [PageController::class, 'show'])
    ->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
