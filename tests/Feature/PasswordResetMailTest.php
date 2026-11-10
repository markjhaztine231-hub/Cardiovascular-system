<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_email_is_sent_to_existing_users(): void
    {
        Mail::fake();

        $user = User::factory()->patient()->active()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && $mail->token !== '';
        });
    }

    public function test_password_reset_email_is_not_sent_to_unknown_addresses(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_mailtrap_mailer_defaults_to_the_transactional_smtp_stream(): void
    {
        $mailer = config('mail.mailers.mailtrap');

        $this->assertSame('smtp', $mailer['transport']);
        $this->assertSame('live.smtp.mailtrap.io', $mailer['host']);
        $this->assertSame(587, $mailer['port']);
        $this->assertSame('api', $mailer['username']);
    }
}
