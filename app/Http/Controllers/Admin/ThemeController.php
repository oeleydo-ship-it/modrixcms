<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Extensions\ExtensionInstaller;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Package;
use App\Cms\Extensions\SettingsSchema;
use App\Cms\Frontend\PackagePresenter;
use App\Cms\Themes\DesignTokens;
use App\Cms\Themes\ThemeDemo;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Admin\Concerns\HandlesPackages;
use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Admin → Appearance: upload, preview, activate, customize and delete themes. */
class ThemeController extends Controller
{
    use HandlesPackages;

    public function __construct(private ExtensionInstaller $installer) {}

    public function index(ExtensionRegistry $registry, Settings $settings): Response
    {
        $active = (string) $settings->get('active_theme');

        return Inertia::render('Admin/Themes/Index', [
            'themes' => $registry->all(Extension::THEME)->map(fn (Package $theme) => PackagePresenter::theme($theme, $active))->values(),
            'uploadsAllowed' => (bool) config('cms.uploads'),
            'maxMb' => config('cms.limits.upload_mb'),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->ensureUploadsAllowed();
        $request->validate($this->uploadRules());

        try {
            $result = $this->installer->upload($request->file('package')->getRealPath(), Extension::THEME, $request->user());
        } catch (InvalidPackage $e) {
            return back()->withErrors(['package' => $e->getMessage()]);
        }

        $theme = $result['package'];

        return redirect()->route('admin.themes.index')->with('status', $result['updated']
            ? "{$theme->displayName()} updated from {$result['previous']} to {$theme->version()}."
            : "{$theme->displayName()} {$theme->version()} uploaded. Preview it, then activate it when you're happy.");
    }

    public function activate(Request $request, string $vendor, string $package): RedirectResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);

        try {
            $this->installer->activateTheme($theme->name, $request->user(), $this->trusted($request));
        } catch (InvalidPackage $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }

        $request->session()->forget(ThemeManager::PREVIEW_SESSION_KEY);

        return redirect()->route('admin.themes.index')->with('status', "{$theme->displayName()} is now your site's theme.");
    }

    /** Imports the theme's demo pages, header and footer (Appearance → Import demo). */
    public function importDemo(Request $request, ThemeDemo $demo, string $vendor, string $package): RedirectResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);
        $options = $request->validate([
            'set_home' => ['boolean'],
            'replace' => ['boolean'],
        ]);

        try {
            $result = $demo->import($theme, (bool) ($options['set_home'] ?? false), (bool) ($options['replace'] ?? false), $request->user()->id);
        } catch (InvalidPackage $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }

        $message = "{$theme->displayName()} demo imported: {$result['created']} new and {$result['updated']} updated pages, published";
        $message .= $result['home'] ? ", with “{$result['home']->title}” as your home page." : '.';

        if ($result['renamed'] !== []) {
            $message .= ' These addresses were taken, so the demo pages got new ones: '.implode(', ', $result['renamed']).'.';
        }

        return redirect()->route('admin.themes.index')->with('status', $message);
    }

    /** Opens the public site with this theme, for this admin only. */
    public function preview(Request $request, string $vendor, string $package): HttpResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);

        try {
            $this->installer->prepareThemePreview($theme->name, $request->user(), $this->trusted($request));
        } catch (InvalidPackage $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }

        $request->session()->put(ThemeManager::PREVIEW_SESSION_KEY, $theme->name);

        // A full page load: the public site has its own root template and scripts.
        return Inertia::location(route('home'));
    }

    public function exitPreview(Request $request): RedirectResponse
    {
        $request->session()->forget(ThemeManager::PREVIEW_SESSION_KEY);

        return redirect()->route('admin.themes.index');
    }

    /** Theme settings for the active theme (the admin menu's "Theme settings"). */
    public function activeDesign(Settings $settings): RedirectResponse
    {
        [$vendor, $package] = explode('/', (string) $settings->get('active_theme', config('cms.default_theme')));

        return redirect()->route('admin.themes.design', [$vendor, $package]);
    }

    /** A theme's own design settings: colours, typography, buttons, sections, links. */
    public function design(string $vendor, string $package, ThemeManager $themes, Settings $settings): Response
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);
        abort_if($theme->manifest === null, 404);

        return Inertia::render('Admin/Themes/Design', [
            'theme' => [
                'name' => $theme->name,
                'displayName' => $theme->displayName(),
                'active' => $theme->name === $settings->get('active_theme'),
                'hasOwnSettings' => $theme->manifest->settings() !== [],
                'customizeUrl' => route('admin.themes.customize', [$vendor, $package]),
            ],
            'groups' => DesignTokens::schema(),
            'fonts' => DesignTokens::FONTS,
            'values' => (object) $themes->design($theme),
            'defaults' => (object) $themes->design(new Package($theme->type, $theme->name, $theme->path, $theme->manifest, null)),
            'action' => route('admin.themes.design.update', [$vendor, $package]),
        ]);
    }

    public function updateDesign(Request $request, string $vendor, string $package, ThemeManager $themes): RedirectResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);
        abort_if($theme->manifest === null, 404);

        $values = $request->validate(['values' => ['required', 'array']])['values'];

        Extension::query()->updateOrCreate(
            ['type' => Extension::THEME, 'name' => $theme->name],
            // Settings not sent keep their current value (saved, else the theme's own default).
            ['design' => DesignTokens::clean($values, $themes->design($theme)), 'version' => $theme->version()],
        );

        return back()->with('status', $theme->displayName().' theme settings saved. Pages linked to them update everywhere.');
    }

    public function customize(string $vendor, string $package): Response
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);
        abort_if($theme->manifest === null, 404);

        return Inertia::render('Admin/Extensions/Settings', PackagePresenter::settingsForm(
            $theme,
            'Customize '.$theme->displayName(),
            route('admin.themes.customize.update', [$vendor, $package]),
            route('admin.themes.index'),
        ));
    }

    public function updateCustomize(Request $request, string $vendor, string $package): RedirectResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);
        abort_if($theme->manifest === null, 404);

        $this->installer->saveSettings($theme, $request->validate(SettingsSchema::rules($theme->manifest->settings())));

        return back()->with('status', 'Theme settings saved.');
    }

    public function destroy(string $vendor, string $package): RedirectResponse
    {
        $theme = $this->package(Extension::THEME, $vendor, $package);

        try {
            $this->installer->deleteTheme($theme->name);
        } catch (InvalidPackage $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }

        return redirect()->route('admin.themes.index')->with('status', "{$theme->displayName()} deleted.");
    }
}
