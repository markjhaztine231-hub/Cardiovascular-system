<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminConsoleSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_directory_exposes_role_summary(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->doctor()->create();
        User::factory()->receptionist()->create();
        User::factory()->patient()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.directory'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->where('summary.total', 4)
                ->where('summary.doctors', 1)
                ->where('summary.receptionists', 1)
                ->where('summary.admins', 1)
                ->where('summary.patients', 1)
            );
    }

    public function test_audit_log_exposes_activity_summary(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'Updated user account for doctor@cardiocare.test',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'Deleted user account: old@cardiocare.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-log'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLog')
                ->where('summary.total', 2)
                ->where('summary.today', 2)
                ->where('summary.actors', 1)
                ->where('logs.total', 2)
            );
    }
}
