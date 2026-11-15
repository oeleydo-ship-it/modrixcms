<?php

namespace Modrix\SocialMedia\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\SocialMedia\Models\SocialProfile;
use Modrix\SocialMedia\SocialMediaServiceProvider;

class ProfilesController
{
    public function index(): Response
    {
        $profiles = SocialProfile::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SocialProfile $p): array => [
                'id' => $p->id,
                'platform' => $p->platform,
                'label' => $p->label,
                'handle' => $p->handle ?? '',
                'url' => $p->url,
                'followers' => $p->followers ?? '',
                'sort_order' => $p->sort_order,
                'is_active' => $p->is_active,
                'open_new_tab' => $p->open_new_tab,
                'clicks_count' => $p->clicks_count,
            ]);

        return Inertia::render('plugin:modrix/social-media/ProfilesIndex', [
            'profiles' => $profiles,
            'platforms' => SocialMediaServiceProvider::PLATFORMS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) (SocialProfile::query()->max('sort_order') ?? 0) + 1;

        SocialProfile::query()->create($data);

        return back()->with('status', 'Social profile added.');
    }

    public function update(Request $request, SocialProfile $profile): RedirectResponse
    {
        $profile->update($this->validated($request));

        return back()->with('status', 'Social profile updated.');
    }

    public function destroy(SocialProfile $profile): RedirectResponse
    {
        $profile->delete();

        return back()->with('status', 'Social profile deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'platform' => ['required', 'string', Rule::in(array_keys(SocialMediaServiceProvider::PLATFORMS))],
            'label' => ['required', 'string', 'max:80'],
            'handle' => ['nullable', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/|mailto:)/i'],
            'followers' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return $data + [
            'is_active' => $request->boolean('is_active', true),
            'open_new_tab' => $request->boolean('open_new_tab', true),
        ];
    }
}
