<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Emails are stored and matched lowercase, so a user cannot lock themselves
 * out by typing a different capitalisation.
 */
class EmailCaseNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'researchjaime';

    private function admin(): User
    {
        return User::create([
            'name' => 'AI Cardiovascular Management',
            'email' => 'aicardiovascularmanagement@gmail.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => 'admin',
            'status' => 'Active',
        ]);
    }

    public function test_email_is_stored_lowercase_and_trimmed(): void
    {
        $user = User::create([
            'name' => 'Mixed Case',
            'email' => '  Dr.Smith@Example.COM ',
            'password' => Hash::make('secret123'),
            'role' => 'doctor',
            'status' => 'Active',
        ]);

        $this->assertSame('dr.smith@example.com', $user->fresh()->email);
    }

    public function test_login_succeeds_with_a_capitalised_email(): void
    {
        $this->admin();

        $response = $this->post('/login', [
            'email' => 'AICardiovascularManagement@Gmail.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_succeeds_with_surrounding_whitespace(): void
    {
        $this->admin();

        $response = $this->post('/login', [
            'email' => '  aicardiovascularmanagement@gmail.com  ',
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_still_fails_with_a_wrong_password(): void
    {
        $this->admin();

        $response = $this->post('/login', [
            'email' => 'AICardiovascularManagement@Gmail.com',
            'password' => 'not-the-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_where_email_scope_matches_any_casing(): void
    {
        $this->admin();

        $this->assertTrue(User::whereEmail('AIcardiovascularmanagement@GMAIL.com')->exists());
        $this->assertTrue(User::whereEmail('  aicardiovascularmanagement@gmail.com ')->exists());
        $this->assertFalse(User::whereEmail('someone.else@gmail.com')->exists());
    }

    public function test_an_account_stored_with_capitals_can_still_log_in(): void
    {
        // Simulates a row written before normalisation existed.
        User::create([
            'name' => 'Legacy Mixed Case',
            'email' => 'Legacy.User@Example.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => 'admin',
            'status' => 'Active',
        ]);

        // Force the legacy casing back into the column, bypassing the mutator.
        User::query()->where('name', 'Legacy Mixed Case')->update([
            'email' => 'Legacy.User@Example.com',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'Legacy.User@Example.com']);

        $this->post('/login', [
            'email' => 'legacy.user@example.com',
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }
}
