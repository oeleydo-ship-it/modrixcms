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

        foreach (['admin.settings.general', 'admin.settings.smtp', 'admin.settings.branding', 'admin.account'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
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
