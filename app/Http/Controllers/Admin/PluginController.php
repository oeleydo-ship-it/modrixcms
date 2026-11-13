<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Extensions\ExtensionInstaller;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Package;
use App\Cms\Extensions\SettingsSchema;
use App\Cms\Frontend\PackagePresenter;
use App\Http\Controllers\Admin\Concerns\HandlesPackages;
use App\Http\Controllers\Controller;
use App\Models\Extension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Admin → Plugins: upload, enable, disable, configure and uninstall plugins. */
class PluginController extends Controller
{
    use HandlesPackages;

    public function __construct(private ExtensionInstaller $installer) {}

    public function index(ExtensionRegistry $registry): Response
    {
        return Inertia::render('Admin/Plugins/Index', [
            'plugins' => $registry->all(Extension::PLUGIN)->map(fn (Package $plugin) => PackagePresenter::plugin($plugin))->values(),
            'uploadsAllowed' => (bool) config('cms.uploads'),
            'maxMb' => config('cms.limits.upload_mb'),
        ]);
    }

    public function show(string $vendor, string $package): Response
    {
        return Inertia::render('Admin/Plugins/Show', ['plugin' => PackagePresenter::plugin($this->package(Extension::PLUGIN, $vendor, $package))]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->ensureUploadsAllowed();
        $request->validate($this->uploadRules());

        try {
            $result = $this->installer->upload($request->file('package')->getRealPath(), Extension::PLUGIN, $request->user());
        } catch (InvalidPackage $e) {
            return back()->withErrors(['package' => $e->getMessage()]);
        }

        $plugin = $result['package'];
        [$vendor, $package] = explode('/', $plugin->name);

        return redirect()->route('admin.plugins.show', [$vendor, $package])->with('status', $result['updated']
            ? "{$plugin->displayName()} updated from {$result['previous']} to {$plugin->version()}."
            : "{$plugin->displayName()} {$plugin->version()} uploaded. Review it below, then enable it.");
    }

    public function enable(Request $request, string $vendor, string $package): RedirectResponse
    {
        $plugin = $this->package(Extension::PLUGIN, $vendor, $package);

        try {
            $this->installer->enablePlugin($plugin->name, $request->user(), $this->trusted($request));
        } catch (InvalidPackage $e) {
            return back()->withErrors(['plugin' => $e->getMessage()]);
        }

        return back()->with('status', "{$plugin->displayName()} is enabled.");
    }

    public function disable(string $vendor, string $package): RedirectResponse
    {
        $plugin = $this->package(Extension::PLUGIN, $vendor, $package);
        $this->installer->disablePlugin($plugin->name);

        return back()->with('status', "{$plugin->displayName()} is disabled.");
    }

    public function settings(string $vendor, string $package): Response
    {
        $plugin = $this->package(Extension::PLUGIN, $vendor, $package);
        abort_if($plugin->manifest === null || $plugin->manifest->settings() === [], 404);

        return Inertia::render('Admin/Extensions/Settings', PackagePresenter::settingsForm(
            $plugin,
            $plugin->displayName().' settings',
            route('admin.plugins.settings.update', [$vendor, $package]),
            route('admin.plugins.index'),
        ));
    }

    public function updateSettings(Request $request, string $vendor, string $package): RedirectResponse
    {
        $plugin = $this->package(Extension::PLUGIN, $vendor, $package);
        abort_if($plugin->manifest === null, 404);

        $this->installer->saveSettings($plugin, $request->validate(SettingsSchema::rules($plugin->manifest->settings())));

        return back()->with('status', 'Plugin settings saved.');
    }

    public function destroy(Request $request, string $vendor, string $package): RedirectResponse
    {
        $plugin = $this->package(Extension::PLUGIN, $vendor, $package);

        try {
            $this->installer->uninstallPlugin($plugin->name, $request->boolean('delete_data'));
        } catch (InvalidPackage $e) {
            return back()->withErrors(['plugin' => $e->getMessage()]);
        }

        return redirect()->route('admin.plugins.index')->with('status', "{$plugin->displayName()} uninstalled.");
    }
}
