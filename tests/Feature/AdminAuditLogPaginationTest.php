<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuditLogPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->role('admin')->create(['name' => 'Admin Cardio']);
    }

    public function test_the_audit_log_shows_at_most_twenty_entries_per_page(): void
    {
        AuditLog::create(['user_id' => $this->admin->id, 'action' => 'Bulk seed entry']);

        for ($i = 0; $i < 24; $i++) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => 'Seeded audit event '.$i,
            ]);
        }

        $this->actingAs($this->admin)->get(route('admin.audit-log'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLog')
                ->has('logs.data', 20)
                ->where('logs.total', 25)
                ->where('logs.per_page', 20)
                ->where('logs.current_page', 1)
                ->where('logs.last_page', 2)
            );
    }

    public function test_the_second_page_holds_the_remainder(): void
    {
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => 'Seeded audit event '.$i,
            ]);
        }

        $this->actingAs($this->admin)->get(route('admin.audit-log', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 5)
                ->where('logs.current_page', 2)
                ->where('logs.last_page', 2)
            );
    }

    public function test_search_also_matches_the_user_name(): void
    {
        $doctor = User::factory()->role('doctor')->create(['name' => 'Baterbonia Jane']);
        $other = User::factory()->role('receptionist')->create(['name' => 'Dela Cruz Juan']);

        AuditLog::create(['user_id' => $doctor->id, 'action' => 'Viewed patient chart']);
        AuditLog::create(['user_id' => $other->id, 'action' => 'Viewed patient chart']);

        // Case-insensitive fragment of the user's name.
        $this->actingAs($this->admin)->get(route('admin.audit-log', ['search' => 'bater']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.user', 'Baterbonia Jane')
            );

        // The action column still matches.
        $this->actingAs($this->admin)->get(route('admin.audit-log', ['search' => 'patient chart']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 2));

        // A term matching neither column returns nothing.
        $this->actingAs($this->admin)->get(route('admin.audit-log', ['search' => 'zzznone']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
    }

    public function test_search_filters_respect_the_twenty_per_page_cap(): void
    {
        $doctor = User::factory()->role('doctor')->create(['name' => 'Baterbonia Jane']);

        for ($i = 0; $i < 21; $i++) {
            AuditLog::create([
                'user_id' => $doctor->id,
                'action' => 'Prescription viewed '.$i,
            ]);
        }

        $this->actingAs($this->admin)->get(route('admin.audit-log', ['search' => 'bater']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 20)
                ->where('logs.total', 21)
                ->where('logs.last_page', 2)
            );
    }

    public function test_filters_are_carried_on_the_pagination_links(): void
    {
        for ($i = 0; $i < 21; $i++) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => 'Seeded audit event '.$i,
            ]);
        }

        $this->actingAs($this->admin)->get(route('admin.audit-log', ['search' => 'seeded']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'seeded')
                ->where('logs.total', 21)
            );
    }

    public function test_each_row_carries_a_stable_id_for_rendering(): void
    {
        AuditLog::create(['user_id' => $this->admin->id, 'action' => 'Viewed patient chart']);

        $this->actingAs($this->admin)->get(route('admin.audit-log'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.data.0.id', AuditLog::first()->id)
                ->where('logs.data.0.user', 'Admin Cardio')
            );
    }
}
