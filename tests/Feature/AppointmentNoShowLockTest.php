<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentNoShowLockTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(Provider $provider, string $status = 'Scheduled'): Appointment
    {
        return Appointment::create([
            'patient_id' => Patient::factory()->create()->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'service_type' => 'Consultation',
            'status' => $status,
        ]);
    }

    public function test_status_cannot_be_changed_off_a_no_show()
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $appointment), ['status' => 'Completed'])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
        ]);
    }

    public function test_no_show_cannot_be_transferred_to_another_doctor()
    {
        $receptionist = User::factory()->receptionist()->create();
        $oldDoctor = Provider::factory()->create();
        $newDoctor = Provider::factory()->create();
        $appointment = $this->makeAppointment($oldDoctor, 'No-Show');

        $this->actingAs($receptionist)
            ->put(route('appointments.transfer', $appointment), ['doctor_id' => $newDoctor->id])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'provider_id' => $oldDoctor->id,
        ]);
    }

    public function test_no_show_cannot_be_rescheduled_from_the_queue()
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');
        $originalDate = $appointment->scheduled_date;

        $this->actingAs($receptionist)
            ->post(route('appointments.edit-reschedule', $appointment), [
                'date' => now()->addDays(3)->format('Y-m-d'),
                'time' => '15:30',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'scheduled_date' => $originalDate,
            'status' => 'No-Show',
        ]);
    }

    public function test_denying_a_reschedule_on_a_no_show_does_not_resurrect_it()
    {
        // Before the lock, denyReschedule unconditionally reset status to
        // "Scheduled" — denying a request on a No-Show silently un-marked it.
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');
        $appointment->update([
            'reschedule_requested_date' => now()->addDays(5)->format('Y-m-d'),
            'reschedule_requested_time' => '11:00:00',
        ]);

        $this->actingAs($receptionist)
            ->post(route('appointments.deny-reschedule', $appointment))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
            'reschedule_requested_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
    }

    public function test_approving_a_reschedule_on_a_no_show_is_blocked()
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');
        $appointment->update([
            'reschedule_requested_date' => now()->addDays(5)->format('Y-m-d'),
            'reschedule_requested_time' => '11:00:00',
        ]);

        $this->actingAs($receptionist)
            ->post(route('appointments.approve-reschedule', $appointment))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
        ]);
    }

    public function test_patient_cannot_request_a_reschedule_on_a_no_show()
    {
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');
        $patientUser = $appointment->patient->user;

        $this->actingAs($patientUser)
            ->post(route('appointments.request-reschedule', $appointment), [
                'date' => now()->addDays(4)->format('Y-m-d'),
                'time' => '14:00',
                'reason' => 'Conflict with a work meeting.',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
            'reschedule_requested_date' => null,
        ]);
    }

    public function test_patient_cannot_reschedule_a_no_show()
    {
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'No-Show');
        $originalDate = $appointment->scheduled_date;
        $patientUser = $appointment->patient->user;

        $this->actingAs($patientUser)
            ->put(route('appointments.reschedule', $appointment), [
                'scheduled_date' => now()->addDays(4)->format('Y-m-d'),
                'scheduled_time' => '14:00',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'scheduled_date' => $originalDate,
            'status' => 'No-Show',
        ]);
    }

    public function test_doctor_cannot_start_a_consultation_on_a_no_show()
    {
        $provider = Provider::factory()->create();
        $appointment = $this->makeAppointment($provider, 'No-Show');

        $this->actingAs($provider->user)
            ->post(route('consultation.start', $appointment))
            ->assertStatus(422);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
        ]);
    }

    public function test_no_show_is_absent_from_the_doctor_queue()
    {
        $provider = Provider::factory()->create();
        $noShow = $this->makeAppointment($provider, 'No-Show');
        $scheduled = $this->makeAppointment($provider, 'Scheduled');

        $response = $this->actingAs($provider->user)->get(route('doctor.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Doctor')
            ->has('queue', 1)
            ->where('queue.0.id', $scheduled->id));
    }

    public function test_control_scheduled_appointment_is_still_mutable()
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'Scheduled');

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $appointment), ['status' => 'No-Show'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'No-Show',
        ]);
    }
}
