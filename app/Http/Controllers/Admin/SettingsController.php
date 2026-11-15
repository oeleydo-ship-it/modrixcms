<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Ai\AiClient;
use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
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
        $keys = [
            'site_name', 'tagline', 'contact_email', 'footer_text',
            'github_url', 'marketplace_url',
            'social_x', 'social_facebook', 'social_linkedin', 'social_youtube',
        ];

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

    public function ai(AiClient $ai): Response
    {
        $keys = ['ai_provider', 'ai_model', 'ai_base_url'];

        return Inertia::render('Admin/Settings/Ai', [
            'values' => collect($keys)->mapWithKeys(fn ($key) => [$key => $this->settings->get($key) ?? '']),
            'aiKeySaved' => filled($this->settings->get('ai_api_key')),
            'aiConfigured' => $ai->isConfigured(),
            'aiProviders' => AiClient::PROVIDERS,
            'aiGeneratorUrl' => route('admin.ai.index'),
        ]);
    }

    public function updateAi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ai_provider' => ['required', Rule::in(array_keys(AiClient::PROVIDERS))],
            'ai_api_key' => ['nullable', 'string', 'max:500'],
            'ai_model' => ['nullable', 'string', 'max:150'],
            'ai_base_url' => ['nullable', 'url', 'max:300'],
        ]);

        if ($request->boolean('clear_ai_api_key')) {
            $data['ai_api_key'] = null;
        } elseif (blank($data['ai_api_key'] ?? null)) {
            // Leave blank to preserve the encrypted API key already saved.
            unset($data['ai_api_key']);
        }

        $this->settings->set($data);

        return back()->with('status', 'AI API settings saved.');
    }

    public function testAi(Request $request, AiClient $ai): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'ai_provider' => ['nullable', Rule::in(array_keys(AiClient::PROVIDERS))],
            'ai_api_key' => ['nullable', 'string', 'max:500'],
            'ai_model' => ['nullable', 'string', 'max:150'],
            'ai_base_url' => ['nullable', 'url', 'max:300'],
        ]);

        $overrides = array_filter([
            'provider' => $data['ai_provider'] ?? null,
            'api_key' => filled($data['ai_api_key'] ?? null) ? $data['ai_api_key'] : null,
            'model' => filled($data['ai_model'] ?? null) ? $data['ai_model'] : null,
            'base_url' => filled($data['ai_base_url'] ?? null) ? $data['ai_base_url'] : null,
        ], fn ($value) => $value !== null);

        try {
            $result = $ai->testConnection($overrides);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['ai_api_key' => $e->getMessage()]);
        }

        $message = "Connected to {$result['provider']} ({$result['model']}).";

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'result' => $result]);
        }

        return back()->with('status', $message);
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

    public function appearance(): Response
    {
        return Inertia::render('Admin/Settings/Appearance', [
            'values' => $this->settings->adminTheme(),
            'defaults' => collect(Settings::ADMIN_THEME_KEYS)
                ->mapWithKeys(fn (string $key) => [$key => Settings::DEFAULTS[$key]])
                ->all(),
        ]);
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $hexColor = ['required', 'string', 'regex:/^#[0-9a-fA-F]{3,8}$/'];

        $data = $request->validate([
            'admin_sidebar_bg' => $hexColor,
            'admin_sidebar_text' => $hexColor,
            'admin_sidebar_active_bg' => $hexColor,
            'admin_sidebar_active_text' => $hexColor,
            'admin_sidebar_width' => ['required', 'integer', 'between:180,320'],
            'admin_canvas_bg' => $hexColor,
            'admin_surface_bg' => $hexColor,
            'admin_surface_muted' => $hexColor,
            'admin_primary_color' => $hexColor,
            'admin_accent_color' => $hexColor,
            'admin_brand_color' => $hexColor,
            'admin_text_color' => $hexColor,
            'admin_muted_color' => $hexColor,
            'admin_border_color' => $hexColor,
            'admin_font_family' => ['required', Rule::in(['Inter', 'System UI', 'Plus Jakarta Sans', 'DM Sans', 'Bricolage Grotesque', 'JetBrains Mono'])],
            'admin_heading_font' => ['required', Rule::in(['Inter', 'System UI', 'Plus Jakarta Sans', 'DM Sans', 'Bricolage Grotesque'])],
            'admin_font_size' => ['required', Rule::in(['12px', '13px', '14px', '15px'])],
            'admin_heading_weight' => ['required', Rule::in(['500', '600', '700'])],
            'admin_letter_spacing' => ['required', Rule::in(['-0.02em', '-0.01em', '0em', '0.01em'])],
            'admin_spacing' => ['required', Rule::in(['compact', 'default', 'comfortable', 'spacious'])],
            'admin_content_padding' => ['required', Rule::in(['16px', '20px', '24px', '32px', '40px'])],
            'admin_radius' => ['required', Rule::in(['0px', '6px', '8px', '12px', '16px', '20px'])],
            'admin_button_radius' => ['required', Rule::in(['4px', '8px', '10px', '999px'])],
            'admin_frame_inset' => ['required', Rule::in(['0px', '4px', '6px', '10px'])],
            'admin_store_handle' => ['nullable', 'string', 'max:40'],
            'admin_store_badge' => ['nullable', 'string', 'max:20'],
            'admin_avatar_color' => $hexColor,
            'admin_assistant_label' => ['nullable', 'string', 'max:80'],
        ]);

        $data['admin_sidebar_width'] = (string) $data['admin_sidebar_width'];
        $data['admin_show_assistant'] = $request->boolean('admin_show_assistant');

        $this->settings->set($data);

        return back()->with('status', 'Admin appearance saved.');
    }

    public function resetAppearance(): RedirectResponse
    {
        $defaults = [];

        foreach (Settings::ADMIN_THEME_KEYS as $key) {
            $defaults[$key] = Settings::DEFAULTS[$key];
        }

        $this->settings->set($defaults);

        return back()->with('status', 'Admin appearance reset to reference defaults.');
    }
}
