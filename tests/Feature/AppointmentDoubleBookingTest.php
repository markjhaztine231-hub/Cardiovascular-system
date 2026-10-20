<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentDoubleBookingTest extends TestCase
{
    use RefreshDatabase;

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

        $this->appointment($doctorOne, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Walk In Two',
                'age' => 41,
                'gender' => 'Female',
                'phone' => '09171234567',
                'doctor_id' => $doctorTwo->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time' => '09:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'provider_id' => $doctorTwo->id,
            'scheduled_date' => $date,
            'status' => 'Pending',
        ]);
    }

    public function test_walk_in_cannot_take_a_slot_already_held_by_the_same_doctor(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $provider = Provider::factory()->create();
        $date = now()->addDays(2)->format('Y-m-d');

        $this->appointment($provider, $date, '09:00:00');

        $this->actingAs($receptionist)
            ->post(route('appointments.manual'), [
                'name' => 'Walk In Clash',
                'age' => 30,
                'gender' => 'Male',
                'phone' => '09170000000',
                'doctor_id' => $provider->id,
                'service_type' => 'Consultation',
                'date' => $date,
                'time' => '09:00',
            ])
            ->assertSessionHasErrors('time');

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
