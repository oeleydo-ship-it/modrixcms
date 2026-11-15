<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_settings_screens_load(): void
    {
        $admin = $this->admin();

        foreach (['admin.settings.general', 'admin.settings.appearance', 'admin.settings.smtp', 'admin.settings.branding', 'admin.settings.ai', 'admin.account'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_admin_appearance_settings_can_be_customized_and_reset(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.appearance.update'), [
            'admin_sidebar_bg' => '#121826',
            'admin_sidebar_text' => '#cbd5e1',
            'admin_sidebar_active_bg' => '#1e293b',
            'admin_sidebar_active_text' => '#ffffff',
            'admin_sidebar_width' => 260,
            'admin_canvas_bg' => '#f8fafc',
            'admin_surface_bg' => '#ffffff',
            'admin_surface_muted' => '#f1f5f9',
            'admin_primary_color' => '#2563eb',
            'admin_accent_color' => '#4f46e5',
            'admin_brand_color' => '#16a34a',
            'admin_text_color' => '#0f172a',
            'admin_muted_color' => '#475569',
            'admin_border_color' => '#e2e8f0',
            'admin_font_family' => 'Plus Jakarta Sans',
            'admin_heading_font' => 'Bricolage Grotesque',
            'admin_font_size' => '14px',
            'admin_heading_weight' => '700',
            'admin_letter_spacing' => '-0.02em',
            'admin_spacing' => 'comfortable',
            'admin_content_padding' => '32px',
            'admin_radius' => '16px',
            'admin_button_radius' => '8px',
            'admin_frame_inset' => '4px',
            'admin_store_handle' => 'modrix-store',
            'admin_store_badge' => 'live',
            'admin_avatar_color' => '#2563eb',
            'admin_show_assistant' => '1',
            'admin_assistant_label' => 'Ask Sidekick',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('--admin-sidebar-bg: #121826', false)
            ->assertSee('--admin-sidebar-width: 260px', false)
            ->assertSee('--admin-font-size: 14px', false)
            ->assertSeeInPage('modrix-store');

        $this->actingAs($admin)->post(route('admin.settings.appearance.reset'))
            ->assertSessionHasNoErrors();

        $this->assertSame('#0a0a0a', app(Settings::class)->get('admin_sidebar_bg'));
        $this->assertSame('13px', app(Settings::class)->get('admin_font_size'));
    }

    public function test_general_settings_show_on_the_site(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.general.update'), [
            'site_name' => 'Acme Commerce',
            'tagline' => 'Sell anything',
            'contact_email' => 'hi@acme.test',
            'footer_text' => 'Acme Inc.',
            'github_url' => 'https://github.com/acme/store',
            'marketplace_url' => 'https://market.acme.test',
            'social_x' => 'https://x.com/acme',
        ])->assertSessionHasNoErrors();

        $this->get('/')
            ->assertSee('Acme Commerce — Sell anything</title>', false)
            ->assertSeeInPage('hi@acme.test')
            ->assertSeeInPage('Acme Inc.')
            ->assertSeeInPage('https://github.com/acme/store')
            ->assertSeeInPage('https://market.acme.test')
            ->assertSeeInPage('https://x.com/acme');
    }

    public function test_general_settings_reject_non_https_links(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.general.update'), [
            'site_name' => 'Acme',
            'social_x' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('social_x');
    }

    public function test_smtp_settings_are_saved_with_an_encrypted_password(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.smtp.update'), [
            'mail_enabled' => '1',
            'mail_host' => 'smtp.mailhost.test',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'user',
            'mail_password' => 's3cret-pass',
            'mail_from_address' => 'noreply@acme.test',
            'mail_from_name' => 'Acme',
        ])->assertSessionHasNoErrors();

        $stored = Setting::firstWhere('key', 'mail_password')->value;
        $this->assertNotSame('s3cret-pass', $stored);

        $settings = app(Settings::class);
        $this->assertSame('s3cret-pass', $settings->get('mail_password'));

        $settings->applyMailConfig();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('smtp.mailhost.test', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('noreply@acme.test', config('mail.from.address'));
    }

    public function test_a_blank_password_keeps_the_saved_one(): void
    {
        $admin = $this->admin();
        $payload = [
            'mail_host' => 'smtp.mailhost.test', 'mail_port' => 587, 'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@acme.test',
        ];

        $this->actingAs($admin)->put(route('admin.settings.smtp.update'), $payload + ['mail_password' => 'first-pass']);
        $this->actingAs($admin)->put(route('admin.settings.smtp.update'), $payload + ['mail_password' => '']);

        $this->assertSame('first-pass', app(Settings::class)->get('mail_password'));
    }

    public function test_enabling_smtp_requires_a_host_and_sender(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.smtp.update'), [
            'mail_enabled' => '1', 'mail_port' => 587, 'mail_encryption' => 'tls',
        ])->assertSessionHasErrors(['mail_host', 'mail_from_address']);
    }

    public function test_a_test_email_can_be_sent(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('admin.settings.smtp.test'), ['test_to' => 'me@acme.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    public function test_a_logo_and_favicon_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.branding.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 100),
            'favicon' => UploadedFile::fake()->image('icon.png', 64, 64),
        ])->assertSessionHasNoErrors();

        $settings = app(Settings::class);
        $logo = $settings->get('logo');
        Storage::disk('public')->assertExists($logo);
        Storage::disk('public')->assertExists($settings->get('favicon'));

        $this->get('/')->assertSeeInPage(Storage::disk('public')->url($logo));
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSeeInPage(Storage::disk('public')->url($logo));

        $this->actingAs($admin)->put(route('admin.settings.branding.update'), ['remove_logo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($logo);
        $this->assertNull(app(Settings::class)->get('logo'));
    }

    public function test_svg_logos_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('admin.settings.branding.update'), [
            'logo' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_guests_cannot_change_settings(): void
    {
        $this->put(route('admin.settings.general.update'), ['site_name' => 'Hacked'])->assertRedirect(route('admin.login'));
        $this->assertDatabaseMissing('settings', ['key' => 'site_name']);
    }
}
