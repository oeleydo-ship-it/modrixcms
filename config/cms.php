<?php

/*
 * Modrix CMS: themes and plugins. See docs/cms/extension-development.md.
 */
return [

    /* The CMS version packages declare compatibility with ("cms_version"). */
    'version' => '1.0.0',

    /* Where installed packages live: {path}/plugins/{vendor}/{name}, {path}/themes/{vendor}/{name}. */
    'path' => env('CMS_EXTENSIONS_PATH', base_path('extensions')),

    /* Public assets of packages are copied here (inside the web root). */
    'public_path' => env('CMS_PUBLIC_PATH', public_path('extensions')),

    /* Unpacked uploads and backups of replaced versions. */
    'storage_path' => env('CMS_STORAGE_PATH', storage_path('app/cms')),

    /* List of enabled plugins read on every request (rebuilt on every change). */
    'boot_cache' => env('CMS_BOOT_CACHE', base_path('bootstrap/cache/cms-plugins.php')),

    /* The theme used when none is set, in safe mode, or when the active one is missing. */
    'default_theme' => 'modrix/default',

    /* Turn off zip uploads of themes and plugins from the admin. */
    'uploads' => env('CMS_ALLOW_UPLOADS', true),

    /*
     * Safe mode loads no plugins and uses the default theme.
     * Also on while the file storage/app/cms/safe-mode exists (php artisan cms:safe-mode on).
     */
    'safe_mode' => env('CMS_SAFE_MODE', false),

    /*
     * First-run setup: a site with no admin account sends its first visitor to /setup to create one.
     * Set SETUP_TOKEN to also require a secret there (stops anyone claiming the site before you).
     */
    'first_run_setup' => env('CMS_FIRST_RUN_SETUP', true),
    'setup_token' => env('SETUP_TOKEN'),

    /*
     * Media library (Admin → Media and the page builder). The largest file accepted, in MB: the web
     * server and PHP (upload_max_filesize, post_max_size) may set a lower limit of their own.
     * Which file types are accepted is listed in App\Cms\Media\MediaLibrary. SVG is not one of them,
     * because an SVG file can carry scripts.
     */
    'media' => [
        'max_upload_mb' => (int) env('CMS_MEDIA_MAX_MB', 20),
    ],

    'limits' => [
        'upload_mb' => 50,
        'extracted_mb' => 200,
        'files' => 5000,
    ],

];
