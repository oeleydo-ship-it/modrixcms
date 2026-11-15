<?php

namespace Modrix\SeoAnalytics\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\SeoAnalytics\Models\SeoRedirect;

class RedirectsController
{
    public function index(): Response
    {
        $redirects = SeoRedirect::query()
            ->orderBy('from_path')
            ->paginate(25)
            ->through(fn (SeoRedirect $r): array => [
                'id' => $r->id,
                'from_path' => $r->from_path,
                'to_url' => $r->to_url,
                'status_code' => $r->status_code,
                'hits' => $r->hits,
                'is_active' => $r->is_active,
                'last_hit' => $r->last_hit_at?->diffForHumans(),
            ]);

        return Inertia::render('plugin:modrix/seo-analytics/RedirectsIndex', [
            'redirects' => $redirects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SeoRedirect::query()->create($this->validated($request));
        $this->clearRouteCache();

        return back()->with('status', 'Redirect created.');
    }

    public function update(Request $request, SeoRedirect $redirect): RedirectResponse
    {
        $redirect->update($this->validated($request, $redirect));
        $this->clearRouteCache();

        return back()->with('status', 'Redirect updated.');
    }

    public function destroy(SeoRedirect $redirect): RedirectResponse
    {
        $redirect->delete();
        $this->clearRouteCache();

        return back()->with('status', 'Redirect deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SeoRedirect $redirect = null): array
    {
        $normalizedFrom = '/'.ltrim(trim((string) $request->input('from_path', '')), '/');
        $request->merge(['from_path' => $normalizedFrom]);

        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:300', 'regex:/^\/[A-Za-z0-9\-_\/.]+$/', Rule::unique('seo_redirects', 'from_path')->ignore($redirect)],
            'to_url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/i'],
            'status_code' => ['required', 'in:301,302'],
        ]);

        return [
            'from_path' => $data['from_path'],
            'to_url' => $data['to_url'],
            'status_code' => (int) $data['status_code'],
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function clearRouteCache(): void
    {
        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }
    }
}
