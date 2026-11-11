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

class AppointmentDoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(Provider $provider, string $date, string $start = '06:00:00', string $end = '12:00:00'): DoctorSchedule
    {
        return DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [Carbon::parse($date)->dayOfWeekIso],
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function appointment(Provider $provider, string $date, string $time, string $status = 'Scheduled'): Appointment
    {
        return Appointment::create([
            'patient_id' => Patient::factory()->create()->id,
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'service_type' => 'Consultation',
            'status' => $status,
        ]);
    }

    public function test_two_doctors_can_see_patients_at_the_same_time_in_separate_rooms(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $doctorOne = Provider::factory()->create();
        $doctorTwo = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->schedule($doctorTwo, $date);
        $this->appointment($doctorOne, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Walk In Two',
                'date_of_birth' => '1985-03-15',
                'gender' => 'Female',
                'phone' => '09171234567',
                'doctor_id' => $doctorTwo->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time_slot' => 'morning',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'provider_id' => $doctorTwo->id,
            'scheduled_date' => $date,
            'scheduled_time' => '06:00:00',
            'status' => 'Pending',
        ]);

        $patient = Patient::where('date_of_birth', '1985-03-15')
            ->where('sex', 'Female')
            ->firstOrFail();

        // Phone numbers are encrypted at rest, so they cannot be asserted
        // through a raw database query.
        $this->assertSame('09171234567', $patient->contact_phone);
    }

    public function test_walk_in_only_requires_name_doctor_service_and_slot_date(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Minimal Walk In',
                'doctor_id' => $provider->id,
                'service_type' => 'Consultation',
                'date' => $date,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => null,
            'status' => 'Pending',
        ]);

        $this->assertDatabaseHas('patients', [
            'date_of_birth' => null,
            'sex' => null,
            'contact_phone' => null,
        ]);
    }

    public function test_a_window_booking_takes_the_next_free_time_for_the_same_doctor(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->schedule($provider, $date);
        $this->appointment($provider, $date, '06:00:00');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Walk In Next',
                'doctor_id' => $provider->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time_slot' => 'morning',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => '06:05:00',
            'status' => 'Pending',
        ]);
    }

    public function test_patient_window_booking_auto_assigns_a_scheduled_doctor(): void
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->schedule($provider, $date);

        $this->actingAs($patient->user)
            ->post(route('appointments.store'), [
                'date' => $date,
                'time_slot' => 'morning',
                'service' => 'Consultation',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => '06:00:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_returning_patient_window_booking_uses_the_selected_doctors_next_free_time(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->schedule($provider, $date);
        $this->appointment($provider, $date, '06:00:00');

        $this->actingAs($receptionist)
            ->post('/appointments/returning', [
                'patient_id' => $patient->id,
                'doctor_id' => $provider->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time_slot' => 'morning',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => $date,
            'scheduled_time' => '06:05:00',
            'status' => 'Pending',
        ]);
    }

    public function test_legacy_exact_time_cannot_double_book_the_same_doctor(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->appointment($provider, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Walk In Clash',
                'date_of_birth' => '1996-06-15',
                'gender' => 'Male',
                'phone' => '09170000000',
                'doctor_id' => $provider->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time' => '09:00',
            ])
            ->assertSessionHasErrors('time_slot');

        $this->assertSame(0, Appointment::where('provider_id', $provider->id)->whereTime('scheduled_time', '09:00:00')->where('status', 'Pending')->count());
    }

    public function test_transfer_is_rejected_when_the_new_doctor_already_has_that_slot(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $from = Provider::factory()->create();
        $to = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->appointment($to, $date, '09:00:00');
        $moving = $this->appointment($from, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->put(route('appointments.transfer', $moving), ['doctor_id' => $to->id])
            ->assertSessionHasErrors('doctor_id');

        $this->assertDatabaseHas('appointments', [
            'id' => $moving->id,
            'provider_id' => $from->id,
        ]);
    }

    public function test_cancelled_appointment_cannot_be_reactivated_into_a_taken_slot(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $cancelled = $this->appointment($provider, $date, '09:00:00', 'Cancelled');
        $this->appointment($provider, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $cancelled), ['status' => 'Scheduled'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('appointments', [
            'id' => $cancelled->id,
            'status' => 'Cancelled',
        ]);
    }

    public function test_cancelled_appointment_can_be_reactivated_when_the_slot_is_free(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $cancelled = $this->appointment($provider, $date, '09:00:00', 'Cancelled');

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $cancelled), ['status' => 'Scheduled'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'id' => $cancelled->id,
            'status' => 'Scheduled',
        ]);
    }
}
