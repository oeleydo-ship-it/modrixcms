<?php

namespace App\Http\Controllers;

use App\Cms\Extensions\ExtensionRegistry;
use App\Models\Extension;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a theme's or plugin's public files (screenshot, theme.css, theme.js, fonts…) straight
 * from the package folder when the published copy in public/extensions/ is missing.
 *
 * That copy is git-ignored, so a fresh deploy starts without it (until `php artisan cms:sync`
 * runs), and on hosts with a read-only public folder it can never be written. The web server
 * still serves the copy directly when it exists; this route only answers when it does not.
 */
class ExtensionAssetController extends Controller
{
    private const TYPES = ['themes' => Extension::THEME, 'plugins' => Extension::PLUGIN];

    /** What a package may serve. Never PHP or anything executable. */
    private const MIME = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json',
        'map' => 'application/json',
        'txt' => 'text/plain; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
    ];

    public function __invoke(ExtensionRegistry $registry, string $type, string $vendor, string $name, string $path): BinaryFileResponse
    {
        $kind = self::TYPES[$type] ?? null;
        $package = $vendor.'/'.$name;

        abort_if($kind === null || ! $registry->exists($kind, $package), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        abort_if(! isset(self::MIME[$extension]) || str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0"), 404);

        $root = realpath($registry->pathFor($kind, $package).DIRECTORY_SEPARATOR.'public');
        $file = $root === false ? false : realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));

        abort_if($file === false || ! is_file($file) || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);

        return response()->file($file, [
            'Content-Type' => self::MIME[$extension],
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
