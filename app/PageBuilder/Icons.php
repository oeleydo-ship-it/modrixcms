<?php

namespace App\PageBuilder;

/**
 * Icons a block can use. Admins pick a name; only these fixed SVG paths
 * are ever output, never markup typed in the admin.
 */
class Icons
{
    public const ALL = [
        'layout' => '<rect x="3" y="3" width="18" height="6" rx="1.5"/><rect x="3" y="11" width="8" height="10" rx="1.5"/><rect x="13" y="11" width="8" height="10" rx="1.5"/>',
        'page' => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4M9 12h7M9 16h5"/>',
        'lines' => '<path d="M4 5h16M4 10h10M4 15h16M4 20h8"/>',
        'sparkle' => '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 15l3-3 2 2 3-4"/>',
        'cart' => '<path d="M3 4h2l2.4 11h10.2L20 7H6.2"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
        'receipt' => '<path d="M7 3h10v18l-5-3-5 3z"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
        'shield' => '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'refresh' => '<path d="M4 12a8 8 0 0 1 14-5.3L20 9"/><path d="M20 4v5h-5M20 12a8 8 0 0 1-14 5.3L4 15"/><path d="M4 20v-5h5"/>',
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'heart' => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.6-5 6.5-5s5.5 1.5 6.5 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15c2 .6 3.2 2.2 3.5 5"/>',
        'code' => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M14 4l-4 16"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];

    public static function svg(?string $name): string
    {
        return self::ALL[$name] ?? self::ALL['sparkle'];
    }
}
