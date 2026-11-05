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

    public function test_availability_returns_the_morning_window_for_a_morning_schedule()
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
        $response->assertJsonCount(1, 'slots');
        $response->assertJsonFragment([
            'value' => 'morning',
            'label' => 'Morning (6:00 AM - 12:00 PM)',
        ]);
    }

    public function test_availability_returns_the_afternoon_window_for_an_afternoon_schedule()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $target->format('Y-m-d')])
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'slots');
        $response->assertJsonFragment([
            'value' => 'afternoon',
            'label' => 'Afternoon (1:00 PM - 9:00 PM)',
        ]);
    }

    public function test_availability_returns_both_windows_for_a_full_day_schedule()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $target->format('Y-m-d')])
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'slots');

        $values = collect($response->json('slots'))->pluck('value')->all();
        $this->assertSame(['morning', 'afternoon'], $values);
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
        $this->assertContains('morning', $values);
        $this->assertNotContains('afternoon', $values);
    }

    public function test_a_window_is_hidden_when_every_exact_time_is_booked()
    {
        $target = Carbon::parse(now()->addDays(2)->format('Y-m-d'));
        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$target->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '08:15:00',
        ]);

        $patientRecord = Patient::factory()->create();
        $patient = $patientRecord->user;

        foreach (['08:00:00', '08:05:00', '08:10:00'] as $time) {
            Appointment::create([
                'patient_id' => $patientRecord->id,
                'provider_id' => $provider->id,
                'scheduled_date' => $target->format('Y-m-d'),
                'scheduled_time' => $time,
                'status' => 'Scheduled',
            ]);
        }

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => $target->format('Y-m-d')])
        );

        $response->assertOk()->assertJsonCount(0, 'slots');
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
        $this->assertContains('morning', $values);
        $this->assertNotContains('afternoon', $values);

        Carbon::setTestNow();
    }

    public function test_a_window_whose_times_have_all_passed_is_hidden()
    {
        Carbon::setTestNow('2026-09-21 10:00:00');

        $provider = Provider::factory()->create();

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [Carbon::now()->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ]);

        $patient = Patient::factory()->create()->user;

        $response = $this->actingAs($patient)->getJson(
            route('appointments.availability', ['date' => Carbon::now()->format('Y-m-d')])
        );

        $response->assertOk()->assertJsonCount(0, 'slots');

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
