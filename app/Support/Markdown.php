<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
    /** Markdown to HTML with raw HTML stripped and unsafe links removed. */
    public static function toHtml(?string $text): string
    {
        return Str::markdown($text ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
