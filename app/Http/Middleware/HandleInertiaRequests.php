<?php

namespace App\Http\Middleware;

use App\Cms\Cms;
use App\Cms\Frontend\AdminNavigation;
use App\Cms\Hooks;
use App\Cms\PluginLoader;
use App\Cms\Themes\ThemeManager;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Throwable;

/**
 * Inertia setup: the admin and the public site use different root templates
 * (admin.blade.php / app.blade.php) and share different data.
 */
class HandleInertiaRequests extends Middleware
{
    public function __construct(private Settings $settings, private ThemeManager $themes) {}

    public function rootView(Request $request): string
    {
        return self::isAdmin($request) ? 'admin' : 'app';
    }

    /**
     * Changes whenever the core build or the set of package scripts changes (a plugin enabled,
     * a theme activated or previewed), so Inertia reloads the page and loads the new scripts.
     */
    public function version(Request $request): ?string
    {
        return md5(implode('|', [
            parent::version($request),
            ...app(PluginLoader::class)->scripts(),
            ...(self::isAdmin($request) ? [] : $this->themes->scripts()),
        ]));
    }

    /** Admin screens (and the first-run setup); the page-builder preview is drawn like the public site. */
    public static function isAdmin(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'setup') && ! $request->routeIs('admin.pages.preview');
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        $shared = [
            ...parent::share($request),
            'flash' => ['status' => fn () => $request->session()->get('status')],
            'auth' => ['user' => $user ? ['name' => $user->name, 'email' => $user->email, 'is_admin' => (bool) $user->is_admin] : null],
            'site' => [
                'name' => $this->settings->get('site_name'),
                'tagline' => $this->settings->get('tagline'),
                'logo' => $this->settings->fileUrl('logo'),
                'favicon' => $this->settings->fileUrl('favicon') ?? asset('favicon.svg'),
            ],
            'cms' => ['version' => Cms::version(), 'safeMode' => Cms::safeMode()],
        ];

        return self::isAdmin($request)
            ? $shared + [
                'nav' => fn () => $user?->is_admin ? AdminNavigation::groups() : [],
                'adminTheme' => fn () => $this->settings->adminTheme(),
            ]
            : $shared + $this->siteProps();
    }

    /** @return array<string, mixed> */
    private function siteProps(): array
    {
        return [
            'links' => [
                'home' => route('home'),
                'blog' => route('blog.index'),
                'github' => $this->settings->link('github'),
                'marketplace' => $this->settings->link('marketplace'),
            ],
            'footer' => [
                'text' => $this->settings->get('footer_text'),
                'email' => $this->settings->get('contact_email'),
                'socials' => array_filter([
                    'X' => $this->settings->get('social_x'),
                    'Facebook' => $this->settings->get('social_facebook'),
                    'LinkedIn' => $this->settings->get('social_linkedin'),
                    'YouTube' => $this->settings->get('social_youtube'),
                ]),
            ],
            'menu' => fn () => $this->menu(),
            'theme' => fn () => [
                'name' => $this->themes->name(),
                'settings' => $this->themes->settings(),
                'previewing' => $this->themes->isPreviewing(),
                'displayName' => $this->themes->theme()?->displayName(),
                'exitPreviewUrl' => route('admin.themes.preview.exit'),
            ],
        ];
    }

    /** @return array<int, array{title: string, url: string, slug: string}> */
    private function menu(): array
    {
        try {
            $pages = Page::query()->inMenu()->get(['title', 'slug']);
        } catch (Throwable) {
            $pages = collect();
        }

        return collect(app(Hooks::class)->apply('menu.pages', $pages))
            ->map(fn ($page) => ['title' => $page->title, 'slug' => $page->slug, 'url' => route('pages.show', $page->slug)])
            ->values()
            ->all();
    }
}
