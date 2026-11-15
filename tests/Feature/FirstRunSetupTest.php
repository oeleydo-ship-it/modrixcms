<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The suite runs with the redirect off; these tests are about it.
        config(['cms.first_run_setup' => true]);
    }

    /** @return array<string, mixed> */
    private function details(array $overrides = []): array
    {
        return [
            'site_name' => 'Acme Studio',
            'name' => 'Ada Admin',
            'email' => 'ada@example.com',
            'password' => 'a-long-password-1',
            'password_confirmation' => 'a-long-password-1',
            'starter_content' => true,
            ...$overrides,
        ];
    }

    public function test_every_page_leads_to_setup_while_the_site_has_no_admin(): void
    {
        $this->get('/')->assertRedirect(route('setup'));
        $this->get('/blog')->assertRedirect(route('setup'));
        $this->get(route('admin.login'))->assertRedirect(route('setup'));
        $this->getJson('/')->assertStatus(503);
    }

    public function test_the_setup_screen_offers_the_rebranded_default_site_name(): void
    {
        $this->get(route('setup'))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Setup')
                ->where('requiresToken', false)
                ->where('defaults.site_name', 'Modrix CMS'));
    }

    public function test_setup_creates_the_admin_signs_them_in_and_closes_itself(): void
    {
        $this->post(route('setup.store'), $this->details())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $admin = User::query()->where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertSame('Ada Admin', $admin->name);
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('Acme Studio', app(Settings::class)->get('site_name'));
        $this->assertTrue(Page::query()->where('is_home', true)->exists());

        $this->get(route('setup'))->assertRedirect(route('admin.login'));
        $this->get('/')->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_starter_content_is_optional(): void
    {
        $this->post(route('setup.store'), $this->details(['starter_content' => false]))->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, Page::query()->count());
    }

    public function test_the_password_must_be_long_enough_and_confirmed(): void
    {
        $this->post(route('setup.store'), $this->details(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');
        $this->post(route('setup.store'), $this->details(['password_confirmation' => 'something-else-123']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::query()->count());
    }

    public function test_a_setup_token_is_required_when_one_is_configured(): void
    {
        config(['cms.setup_token' => 'correct-horse']);

        $this->get(route('setup'))->assertInertia(fn ($page) => $page->where('requiresToken', true));

        $this->post(route('setup.store'), $this->details())->assertSessionHasErrors('setup_token');
        $this->post(route('setup.store'), $this->details(['setup_token' => 'wrong']))->assertSessionHasErrors('setup_token');
        $this->assertSame(0, User::query()->count());

        $this->post(route('setup.store'), $this->details(['setup_token' => 'correct-horse']))
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(1, User::query()->where('is_admin', true)->count());
    }

    public function test_setup_is_closed_once_an_admin_exists(): void
    {
        User::factory()->admin()->create();

        $this->get(route('setup'))->assertRedirect(route('admin.login'));
        $this->post(route('setup.store'), $this->details())->assertRedirect(route('admin.login'));
        $this->get('/')->assertOk();

        $this->assertSame(1, User::query()->count());
    }

    public function test_the_redirect_can_be_switched_off(): void
    {
        config(['cms.first_run_setup' => false]);

        $this->get('/')->assertOk();
        $this->get(route('admin.login'))->assertOk();
        $this->get(route('setup'))->assertRedirect(route('admin.login'));
    }
}
