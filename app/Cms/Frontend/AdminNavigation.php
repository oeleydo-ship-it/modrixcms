<?php

namespace App\Cms\Frontend;

use App\Cms\PluginLoader;

/** The admin sidebar: core screens, then screens added by enabled plugins. */
class AdminNavigation
{
    /** @return array<int, array{label: string, items: array<int, array{title: string, url: string, active: bool, icon: string}>}> */
    public static function groups(): array
    {
        $groups = [
            ['label' => 'Content', 'items' => [
                self::item('Dashboard', 'admin.dashboard', 'admin.dashboard', 'dashboard'),
                self::item('Pages', 'admin.pages.index', 'admin.pages.*', 'page'),
                self::item('AI generator', 'admin.ai.index', 'admin.ai.*', 'sparkles'),
                self::item('Blog posts', 'admin.posts.index', 'admin.posts.*', 'posts'),
                self::item('Categories', 'admin.categories.index', 'admin.categories.*', 'layers'),
                self::item('Media', 'admin.media.index', 'admin.media.*', 'image'),
            ]],
            ['label' => 'Extensions', 'items' => [
                self::item('Appearance', 'admin.themes.index', 'admin.themes.*', 'palette'),
                self::item('Plugins', 'admin.plugins.index', 'admin.plugins.*', 'plug'),
            ]],
            ['label' => 'Settings', 'items' => [
                self::item('General', 'admin.settings.general', 'admin.settings.general*', 'gear'),
                self::item('Admin design', 'admin.settings.appearance', 'admin.settings.appearance*', 'sliders'),
                self::item('SMTP', 'admin.settings.smtp', 'admin.settings.smtp*', 'mail'),
                self::item('Logo & favicon', 'admin.settings.branding', 'admin.settings.branding*', 'image'),
                self::item('AI API', 'admin.settings.ai', 'admin.settings.ai*', 'sparkles'),
                self::item('My account', 'admin.account', 'admin.account*', 'user'),
            ]],
        ];

        $plugins = array_map(
            fn ($item) => self::item($item['title'], $item['route'], preg_replace('/\.[^.]+$/', '.*', $item['route']), 'plug'),
            app(PluginLoader::class)->adminNavigation(),
        );

        if ($plugins !== []) {
            $groups[] = ['label' => 'Plugins', 'items' => $plugins];
        }

        return $groups;
    }

    /** @return array{title: string, url: string, active: bool, icon: string} */
    private static function item(string $title, string $route, string $pattern, string $icon): array
    {
        return ['title' => $title, 'url' => route($route), 'active' => request()->routeIs($pattern), 'icon' => $icon];
    }
}
