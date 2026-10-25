<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payloads that would change query semantics if user input were
     * interpolated into SQL instead of bound as a parameter.
     *
     * @var list<string>
     */
    private const PAYLOADS = [
        "' OR '1'='1",
        "' OR 1=1 --",
        "'; DROP TABLE users; --",
        "' UNION SELECT NULL, NULL, NULL --",
    ];

    public function test_login_rejects_sql_injection_payloads_without_corrupting_the_database(): void
    {
        $user = User::factory()->patient()->active()->create([
            'email' => 'patient@example.test',
        ]);

        foreach (self::PAYLOADS as $payload) {
            $this->post('/login', [
                'email' => $payload,
                'password' => $payload,
            ])->assertSessionHasErrors();

            $this->assertGuest();
        }

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admin_user_search_treats_payloads_as_literal_text(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->receptionist()->create(['name' => 'Searchable Receptionist']);

        foreach (self::PAYLOADS as $payload) {
            $this->actingAs($admin)
                ->get(route('admin.users.directory', ['search' => $payload]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/UsersDirectory')
                    ->where('users.total', 0));
        }
    }

    public function test_admin_audit_log_search_treats_payloads_as_literal_text(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create(['user_id' => $admin->id, 'action' => 'Viewed patient chart']);

        foreach (self::PAYLOADS as $payload) {
            $this->actingAs($admin)
                ->get(route('admin.audit-log', ['search' => $payload]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/AuditLog')
                    ->where('logs.total', 0));
        }
    }

    public function test_calendar_scope_rejects_sql_injection_payloads(): void
    {
        $user = User::factory()->patient()->active()->create();
        Patient::factory()->for($user, 'user')->create();

        foreach (self::PAYLOADS as $payload) {
            $this->actingAs($user)
                ->get(route('patient.calendar.download', ['scope' => $payload]))
                ->assertNotFound();
        }
    }
}
