<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slugs
{
    /** URLs the site uses itself; pages can't take them. */
    public const RESERVED = ['admin', 'blog', 'up', 'storage', 'build', 'login', 'logout', 'favicon', 'robots'];

    public static function from(?string $value): string
    {
        return Str::slug((string) $value);
    }
}
