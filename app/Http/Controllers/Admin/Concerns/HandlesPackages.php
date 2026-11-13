<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\Package;
use Illuminate\Http\Request;

/** Shared by the Appearance and Plugins screens. */
trait HandlesPackages
{
    private function package(string $type, string $vendor, string $package): Package
    {
        return app(ExtensionRegistry::class)->find($type, $vendor.'/'.$package) ?? abort(404);
    }

    /** @return array<string, array<int, string>> */
    private function uploadRules(): array
    {
        return ['package' => ['required', 'file', 'mimes:zip', 'max:'.(config('cms.limits.upload_mb') * 1024)]];
    }

    private function ensureUploadsAllowed(): void
    {
        abort_unless(config('cms.uploads'), 403, 'Uploads of themes and plugins are turned off on this site (CMS_ALLOW_UPLOADS).');
    }

    private function trusted(Request $request): bool
    {
        return $request->boolean('trust');
    }
}
