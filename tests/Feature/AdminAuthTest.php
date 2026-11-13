<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/pages')->assertRedirect(route('admin.login'));
        $this->get(route('admin.login'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Auth/Login'));
    }

    public function test_an_admin_can_sign_in_and_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_user_without_admin_access_cannot_sign_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_signed_in_non_admin_is_signed_out_of_the_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_an_admin_can_change_their_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'name' => 'New Name',
            'email' => $admin->email,
            'current_password' => 'password',
            'password' => 'a-new-long-password',
            'password_confirmation' => 'a-new-long-password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(password_verify('a-new-long-password', $admin->fresh()->password));
        $this->assertSame('New Name', $admin->fresh()->name);
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'current_password' => 'wrong',
            'password' => 'a-new-long-password',
            'password_confirmation' => 'a-new-long-password',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_the_site_admin_command_creates_an_admin(): void
    {
        $this->artisan('site:admin', ['email' => 'owner@example.test'])
            ->expectsQuestion('Password (min. 10 characters)', 'long-enough-password')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.test')->first();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(password_verify('long-enough-password', $user->password));
    }

    public function test_the_site_admin_command_rejects_a_short_password(): void
    {
        $this->artisan('site:admin', ['email' => 'owner@example.test'])
            ->expectsQuestion('Password (min. 10 characters)', 'short')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
    }
}
