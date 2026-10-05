<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUsersSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_and_audit_log_search_are_case_insensitive()
    {
        $admin = User::factory()->role('admin')->create([
            'name' => 'Admin Cardio',
            'email' => 'adminx@cardiocare.test',
        ]);

        AuditLog::create(['user_id' => $admin->id, 'action' => 'Created new receptionist account from IP: 127.0.0.1']);

        $rec = User::factory()->role('receptionist')->create(['name' => 'Jane Baterbonia', 'email' => 'reception@cardiocare.test']);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'Created new receptionist account from IP: 127.0.0.1']);

        // Directory lists both
        $this->actingAs($admin)->get(route('admin.users.directory'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->has('users.data', 2)
                ->where('users.total', 2)
                ->where('users.data.1.name', 'Jane Baterbonia'));

        // Search by name fragment (lowercase vs CamelCase stored name)
        $this->actingAs($admin)->get(route('admin.users.directory', ['search' => 'bater']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Jane Baterbonia'));

        // Search by email fragment
        $this->actingAs($admin)->get(route('admin.users.directory', ['search' => 'reception@']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.total', 1));

        // Role filter
        $this->actingAs($admin)->get(route('admin.users.directory', ['role' => 'receptionist']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('users.total', 1));

        // Audit log full list
        $this->actingAs($admin)->get(route('admin.audit-log'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLog')
                ->where('logs.total', 2));

        // Audit log search works (case-insensitive)
        $this->actingAs($admin)->get(route('admin.audit-log', ['search' => 'RECEPTIONIST']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 2));

        $this->actingAs($admin)->get(route('admin.audit-log', ['search' => 'zzznone']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));

        // Audit log date filter works
        $this->actingAs($admin)->get(route('admin.audit-log', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 2));
    }
}
