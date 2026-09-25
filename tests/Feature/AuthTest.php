<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('auth.login_title'))
            ->assertSee('name="email"', false);
    }

    public function test_register_page_loads(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(__('auth.register_title'))
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('auth.register.store'), [
            'name' => 'Alice Test',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->from(route('register'))
            ->post(route('auth.register.store'), [
                'name' => 'Bob',
                'email' => 'taken@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->from(route('register'))
            ->post(route('auth.register.store'), [
                'name' => 'Carol',
                'email' => 'carol@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('auth.login'), [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $this->from(route('login'))
            ->post(route('auth.login'), [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email', null, 'login');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.login'), [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('auth.login'), [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('auth.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logout_requires_auth(): void
    {
        $this->post(route('auth.logout'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_login_when_authenticated(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_login_page_reachable_when_guest(): void
    {
        $this->get(route('login'))->assertOk();
    }
}
