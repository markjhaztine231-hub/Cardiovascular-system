<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): array
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();
        $receptionist = User::factory()->receptionist()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => now()->addDays(3)->format('Y-m-d'),
            'reschedule_requested_time' => '10:00:00',
        ]);

        return [$receptionist, $appointment];
    }

    public function test_receptionist_can_edit_a_reschedule_request_without_500()
    {
        [$receptionist, $appointment] = $this->makeAppointment();

        $response = $this->actingAs($receptionist)->post(
            route('appointments.edit-reschedule', $appointment),
            ['date' => now()->addDays(3)->format('Y-m-d'), 'time' => '10:00:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Scheduled',
        ]);

        // The audit detail line is longer than the original varchar(100) column.
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertGreaterThan(
            100,
            strlen(AuditLog::first()->action),
            'Audit action should store the full narrative (regression for varchar(100) 500).'
        );
    }

    public function test_schedule_list_is_read_live_instead_of_from_a_stale_cache()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();

        $schedule = DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [1, 3],
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs($receptionist)->get(route('receptionist.dashboard'));

        // Simulate a reseed: the row disappears, but nothing may keep it cached.
        $schedule->delete();

        $response = $this->actingAs($receptionist)->get(route('receptionist.dashboard'));
        $response->assertInertia(fn ($page) => $page->where('schedules', []));

        // Editing the now-deleted id must 404 — and the dashboard must no longer render it.
        $this->actingAs($receptionist)
            ->put(route('schedules.update', 99999))
            ->assertNotFound();
    }

    public function test_schedule_list_exposes_doctor_name_and_doctor_id_for_the_weekly_plot()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $provider->user->update(['name' => 'Dr. Cardio']);

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [1, 3],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('schedules', 1)
            ->where('schedules.0.doctor_id', $provider->id)
            ->where('schedules.0.doctor.name', 'Dr. Cardio')
            ->where('schedules.0.start_time', '09:00:00'));
    }

    public function test_editing_a_real_schedule_does_not_404_and_updates_it()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();

        $schedule = DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [1, 3],
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($receptionist)->put(
            route('schedules.update', $schedule),
            ['doctor_id' => $provider->id, 'day_of_week' => [2, 4], 'start_time' => '09:00', 'end_time' => '13:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $schedule->id,
            'start_time' => '09:00:00',
        ]);
        $this->assertEquals([2, 4], DoctorSchedule::find($schedule->id)->day_of_week);
    }
}
