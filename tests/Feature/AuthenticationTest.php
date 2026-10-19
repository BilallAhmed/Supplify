<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_customer_role_only(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin', // must be ignored
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Customer, $user->role);
    }

    public function test_registration_rejects_weak_password(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Weak Pass',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_register_and_login_pages_include_password_visibility_toggle(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Show password', false)
            ->assertSee('At least 8 characters', false)
            ->assertSee('One uppercase letter', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Show password', false);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $login = $this->post(route('login.store'), [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $login->assertRedirect(route('account.profile.edit'));
        $this->assertAuthenticatedAs($user);

        $logout = $this->post(route('logout'));
        $logout->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard', false);
    }

    public function test_guest_is_redirected_from_profile(): void
    {
        $response = $this->get(route('account.profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'profile@example.com',
        ]);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'profile@example.com',
            'phone' => '555-0100',
        ]);

        $response->assertRedirect(route('account.profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '555-0100',
        ]);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'reset@example.com',
        ]);

        $response->assertSessionHas('flash_success');
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
