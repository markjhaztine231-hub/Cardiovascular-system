<?php

namespace Tests\Feature\Auth;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested()
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(ForgotPasswordMail::class);
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(ForgotPasswordMail::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertOk();

            return true;
        });
    }

    public function test_reset_password_screen_receives_the_account_email()
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(ForgotPasswordMail::class, function ($notification) use ($user) {
            $this->get(route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]))->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', $notification->token)
                ->where('email', $user->email));

            return true;
        });
    }

    public function test_reset_email_links_to_the_registered_reset_route()
    {
        $user = User::factory()->create();
        $token = 'known-test-token-123';

        $html = (new ForgotPasswordMail($user, $token))->render();

        // The link must be a path-based URL (reset-password/{token}) that
        // matches routes/web.php, not the old query-string form that 404'd.
        $this->assertStringContainsString(
            '/reset-password/'.$token.'?email='.urlencode($user->email),
            $html,
        );
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(ForgotPasswordMail::class, function ($notification) use ($user) {
            $response = $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword!2024',
                'password_confirmation' => 'NewPassword!2024',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword!2024',
            'password_confirmation' => 'NewPassword!2024',
        ]);

        $response->assertSessionHasErrors('token');
    }
}
