<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_exposes_weekly_trend_and_role_mix(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'Completed',
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin')
                ->where('stats.appointments_today', 2)
                ->where('stats.completed_today', 1)
                ->has('stats.appointments_trend', 7)
                ->where('stats.appointments_trend.6.total', 2)
                ->where('stats.appointments_trend.6.completed', 1)
                ->where('stats.role_distribution.0.key', 'doctor')
                ->where('stats.role_distribution.0.value', 1)
                ->where('stats.role_distribution.1.value', 0)
                ->has('recent', 3)
            );
    }
}
