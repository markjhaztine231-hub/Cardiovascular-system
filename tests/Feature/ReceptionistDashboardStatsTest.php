<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReceptionistDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(string $date, string $serviceType, string $status): Appointment
    {
        $patient = Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => 'patient'])->id,
        ]);
        $provider = Provider::factory()->create([
            'user_id' => User::factory()->doctor()->create()->id,
        ]);

        return Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => '10:00',
            'service_type' => $serviceType,
            'status' => $status,
        ]);
    }

    public function test_dashboard_reports_today_completed_counts_by_service_type(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $today = today()->toDateString();

        $this->makeAppointment($today, 'Consultation', 'Completed');
        $this->makeAppointment($today, 'Procedure', 'Completed');
        $this->makeAppointment($today, 'Consultation', 'Scheduled');

        $this->actingAs($receptionist)
            ->get('/receptionist/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Receptionist')
                ->where('stats.appointmentsToday', 3)
                ->where('stats.completedToday', 2)
                ->where('stats.completedConsultationToday', 1)
                ->where('stats.completedProcedureToday', 1)
                ->where('stats.completedConsultationProcedureToday', 0)
            );
    }

    public function test_dashboard_exposes_per_day_activity_history(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();
        $tomorrow = today()->addDay()->toDateString();

        $this->makeAppointment($today, 'Consultation', 'Completed');
        $this->makeAppointment($today, 'Procedure', 'Scheduled');
        $this->makeAppointment($yesterday, 'Consultation and Procedure', 'Completed');
        $this->makeAppointment($tomorrow, 'Procedure', 'Scheduled');

        $this->actingAs($receptionist)
            ->get('/receptionist/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Receptionist')
                ->where("stats.dailyActivity.{$today}.total", 2)
                ->where("stats.dailyActivity.{$today}.completed", 1)
                ->where("stats.dailyActivity.{$today}.completed_consultation", 1)
                ->where("stats.dailyActivity.{$today}.completed_procedure", 0)
                ->where("stats.dailyActivity.{$yesterday}.total", 1)
                ->where("stats.dailyActivity.{$yesterday}.completed", 1)
                ->where("stats.dailyActivity.{$yesterday}.completed_consultation_procedure", 1)
                ->where("stats.dailyActivity.{$tomorrow}.total", 1)
                ->where("stats.dailyActivity.{$tomorrow}.completed", 0)
            );
    }
}
