<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function credentials(User $user): array
    {
        return ['email' => $user->email, 'password' => 'password'];
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->admin()->active()->create();

        $this->post('/login', $this->credentials($user))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->admin()->suspended()->create();

        $this->post('/login', $this->credentials($user))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->admin()->inactive()->create();

        $this->post('/login', $this->credentials($user))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_blocked_login_reveals_nothing_for_a_wrong_password(): void
    {
        $user = User::factory()->admin()->suspended()->create();

        // A wrong password must fail with the same generic message as an
        // unknown account, never hinting that the account is suspended.
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringNotContainsString(
            'suspended',
            strtolower($response->getSession()->get('errors')->first('email')),
        );
        $this->assertGuest();
    }

    public function test_pre_activation_register_status_can_still_log_in(): void
    {
        $user = User::factory()->patient()->create(['status' => 'Register']);

        $this->post('/login', $this->credentials($user))
            ->assertRedirect(route('patient.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_user_with_two_factor_cannot_reach_the_challenge(): void
    {
        $user = User::factory()->admin()->suspended()->withTwoFactor()->create();

        $this->post('/login', $this->credentials($user))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull(session('login.id'));
    }

    public function test_blocked_user_with_a_live_session_is_logged_out(): void
    {
        $user = User::factory()->patient()->active()->create();

        // Account is suspended after the session was established.
        $user->update(['status' => 'Suspended']);

        $this->actingAs($user->fresh())
            ->get(route('patient.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
