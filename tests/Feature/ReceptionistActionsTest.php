<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(): Provider
    {
        return Provider::factory()->create();
    }

    private function makeAppointment(Provider $provider): Appointment
    {
        $patient = Patient::factory()->create();

        return Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'service_type' => 'Consultation',
            'status' => 'Scheduled',
        ]);
    }

    public function test_receptionist_can_mark_appointment_as_no_show()
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment($this->makeProvider());

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $appointment), ['status' => 'No-Show'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
        ]);
    }

    public function test_receptionist_can_transfer_patient_to_another_doctor()
    {
        $receptionist = User::factory()->receptionist()->create();
        $oldDoctor = $this->makeProvider();
        $newDoctor = $this->makeProvider();
        $appointment = $this->makeAppointment($oldDoctor);

        $this->actingAs($receptionist)
            ->put(route('appointments.transfer', $appointment), ['doctor_id' => $newDoctor->id])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'provider_id' => $newDoctor->id,
        ]);
    }

    public function test_receptionist_can_approve_reschedule_when_doctor_is_scheduled()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();

        $requested = now()->addDays(5);
        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$requested->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $appointment = $this->makeAppointment($provider);
        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $requested->format('Y-m-d'),
            'reschedule_requested_time' => '10:00:00',
        ]);

        $this->actingAs($receptionist)
            ->post(route('appointments.approve-reschedule', $appointment))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Scheduled',
            'scheduled_date' => $requested->format('Y-m-d'),
        ]);
    }

    public function test_reschedule_approval_is_rejected_when_doctor_is_not_scheduled()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();

        $requested = now()->addDays(5);
        // Doctor schedule covers only the morning window — request lands at 19:00.
        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$requested->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $appointment = $this->makeAppointment($provider);
        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $requested->format('Y-m-d'),
            'reschedule_requested_time' => '19:00:00',
        ]);

        $this->actingAs($receptionist)
            ->from(route('receptionist.dashboard'))
            ->post(route('appointments.approve-reschedule', $appointment))
            ->assertRedirect(route('receptionist.dashboard'))
            ->assertSessionHasErrors('time');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Reschedule Requested',
        ]);
    }

    public function test_receptionist_can_deny_reschedule_and_keep_original_schedule()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();
        $appointment = $this->makeAppointment($provider);
        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => now()->addDays(5)->format('Y-m-d'),
            'reschedule_requested_time' => '11:00:00',
        ]);

        $this->actingAs($receptionist)
            ->post(route('appointments.deny-reschedule', $appointment))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Scheduled',
            'reschedule_requested_date' => null,
            'reschedule_requested_time' => null,
        ]);
    }

    public function test_receptionist_can_plot_and_then_delete_a_recurring_schedule()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();

        $this->actingAs($receptionist)
            ->post(route('schedules.store'), [
                'doctor_id' => $provider->id,
                'day_of_week' => ['Monday', 'Wednesday'],
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])
            ->assertRedirect(route('receptionist.dashboard'))
            ->assertSessionHas('success');

        $schedule = DoctorSchedule::where('provider_id', $provider->id)->firstOrFail();

        $this->assertSame([1, 3], $schedule->day_of_week);

        $this->actingAs($receptionist)
            ->delete(route('schedules.delete', $schedule))
            ->assertRedirect();

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_receptionist_can_reschedule_appointment_from_the_queue()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();
        $appointment = $this->makeAppointment($provider);

        $target = Carbon::today()->addDays(3);

        $this->actingAs($receptionist)
            ->post(route('appointments.edit-reschedule', $appointment), [
                'date' => $target->format('Y-m-d'),
                'time' => '15:30',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'scheduled_date' => $target->format('Y-m-d'),
            'scheduled_time' => '15:30:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_queue_reschedule_to_the_same_slot_is_a_noop_not_a_crash()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();
        $appointment = $this->makeAppointment($provider);
        $target = Carbon::parse($appointment->scheduled_date);

        $this->actingAs($receptionist)
            ->post(route('appointments.edit-reschedule', $appointment), [
                'date' => $target->format('Y-m-d'),
                'time' => '09:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'scheduled_date' => $target->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_reschedule_with_non_military_time_is_rejected_without_500()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();
        $appointment = $this->makeAppointment($provider);

        $this->actingAs($receptionist)
            ->post(route('appointments.edit-reschedule', $appointment), [
                'date' => now()->addDays(3)->format('Y-m-d'),
                'time' => '10:00 AM',
            ])
            ->assertSessionHasErrors('time');
    }

    public function test_dashboard_schedules_provide_iso_requested_values_for_modal_prefill()
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = $this->makeProvider();
        $appointment = $this->makeAppointment($provider);
        $requested = now()->addDays(4);
        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $requested->format('Y-m-d'),
            'reschedule_requested_time' => '14:30:00',
        ]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('rescheduleRequests', 1)
            ->where('rescheduleRequests.0.requested_date_iso', $requested->format('Y-m-d'))
            ->where('rescheduleRequests.0.requested_time_iso', '14:30'));
    }
}
