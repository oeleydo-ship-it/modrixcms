<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(private Settings $settings) {}

    public function general(): Response
    {
        $keys = ['site_name', 'tagline', 'contact_email', 'footer_text', 'github_url', 'marketplace_url', 'social_x', 'social_facebook', 'social_linkedin', 'social_youtube'];

        return Inertia::render('Admin/Settings/General', [
            'values' => collect($keys)->mapWithKeys(fn ($key) => [$key => $this->settings->get($key) ?? '']),
            'githubPlaceholder' => config('site.links.github'),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $this->settings->set($request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'github_url' => ['nullable', 'url:https', 'max:300'],
            'marketplace_url' => ['nullable', 'url:https', 'max:300'],
            'social_x' => ['nullable', 'url:https', 'max:300'],
            'social_facebook' => ['nullable', 'url:https', 'max:300'],
            'social_linkedin' => ['nullable', 'url:https', 'max:300'],
            'social_youtube' => ['nullable', 'url:https', 'max:300'],
        ]));

        return back()->with('status', 'General settings saved.');
    }

    public function smtp(): Response
    {
        $keys = ['mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name'];

        return Inertia::render('Admin/Settings/Smtp', [
            'values' => collect($keys)->mapWithKeys(fn ($key) => [$key => $this->settings->get($key) ?? ''])
                ->put('mail_enabled', $this->settings->bool('mail_enabled')),
            'passwordSaved' => filled($this->settings->get('mail_password')),
            'siteName' => $this->settings->get('site_name'),
        ]);
    }

    public function updateSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_host' => ['nullable', 'required_if_accepted:mail_enabled', 'string', 'max:200'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'mail_username' => ['nullable', 'string', 'max:200'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_from_address' => ['nullable', 'required_if_accepted:mail_enabled', 'email', 'max:200'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
        ]);

        $data['mail_enabled'] = $request->boolean('mail_enabled');

        // A blank password field keeps the saved one.
        if (blank($data['mail_password'] ?? null)) {
            unset($data['mail_password']);
        }

        $this->settings->set($data);

        return back()->with('status', 'SMTP settings saved.');
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $request->validate(['test_to' => ['required', 'email']]);

        $this->settings->applyMailConfig();

        try {
            Mail::raw(
                'This is a test email from '.$this->settings->get('site_name').'. Your SMTP settings work.',
                fn ($message) => $message->to($request->input('test_to'))->subject('SMTP test'),
            );
        } catch (Throwable $e) {
            return back()->withErrors(['test_to' => 'Sending failed: '.$e->getMessage()]);
        }

        return back()->with('status', 'Test email sent to '.$request->input('test_to').'.');
    }

    public function branding(): Response
    {
        return Inertia::render('Admin/Settings/Branding', [
            'logo' => $this->settings->fileUrl('logo'),
            'favicon' => $this->settings->fileUrl('favicon'),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ]);

        $changes = [];

        foreach (['logo', 'favicon'] as $key) {
            if ($request->hasFile($key) || $request->boolean("remove_{$key}")) {
                if ($old = $this->settings->get($key)) {
                    Storage::disk('public')->delete($old);
                }

                $changes[$key] = $request->hasFile($key)
                    ? $request->file($key)->store('branding', 'public')
                    : null;
            }
        }

        if ($changes) {
            $this->settings->set($changes);
        }

        return back()->with('status', 'Branding saved.');
    }
}
