<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_slots_are_generated_from_doctor_schedules_on_30_minute_grid()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $target->format('Y-m-d')])
        );

        $response->assertOk();
        $response->assertJsonCount(3, 'slots');
        $response->assertJsonFragment(['value' => '08:00:00', 'label' => '8:00 AM']);
        $response->assertJsonFragment(['value' => '08:30:00', 'label' => '8:30 AM']);
        $response->assertJsonFragment(['value' => '09:00:00', 'label' => '9:00 AM']);
    }

    public function test_booked_slots_are_excluded_from_availability_realtime()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $patientRecord = Patient::factory()->create();
        $patient = $patientRecord->user;

        Appointment::create([
            'patient_id' => $patientRecord->id,
            'provider_id' => $provider->id,
            'scheduled_date' => $target->format('Y-m-d'),
            'scheduled_time' => '08:30:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $target->format('Y-m-d')])
        );

        $response->assertOk();
        $values = collect($response->json('slots'))->pluck('value')->all();
        $this->assertContains('08:00:00', $values);
        $this->assertNotContains('08:30:00', $values);
        $this->assertContains('09:00:00', $values);
        $this->assertContains('09:30:00', $values);
    }

    public function test_past_slots_are_filtered_out_for_today()
    {
        Carbon::setTestNow('2026-09-21 10:00:00');

        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [Carbon::now()->dayOfWeekIso],
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => Carbon::now()->format('Y-m-d')])
        );

        $response->assertOk();
        $values = collect($response->json('slots'))->pluck('value')->all();
        $this->assertNotContains('10:00:00', $values, 'Exactly 30 minutes away must be excluded');
        $this->assertContains('10:30:00', $values);
        $this->assertContains('11:00:00', $values);

        Carbon::setTestNow();
    }

    public function test_unavailable_weekday_returns_empty_slots()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        // The schedule only covers $target's weekday; the day after is a
        // different weekday, so the doctor is unavailable there.
        $unavailable = $target->copy()->addDay();
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $unavailable->format('Y-m-d')])
        );

        $response->assertOk()->assertJsonCount(0, 'slots');
    }
}
