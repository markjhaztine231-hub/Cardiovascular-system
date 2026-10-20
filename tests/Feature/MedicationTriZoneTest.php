<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MedicationTriZoneTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixture(): array
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        $prescription = Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '3x a day',
            'status' => 'active',
        ]);

        return [$appointment, $prescription, $patient->user];
    }

    private function postDose($user, Appointment $appointment, string $scheduledTime, string $medicine = 'Metoprolol'): TestResponse
    {
        return $this->actingAs($user)->postJson(
            '/medications/'.$appointment->id.'/log-dose',
            [
                'prescription_index' => 0,
                'medicine' => $medicine,
                'scheduled_time' => $scheduledTime,
            ]
        );
    }

    public function test_on_time_intake_inside_green_window_is_recoded_as_taken()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $response = $this->postDose($user, $appointment, '08:00');

        $response->assertOk()
            ->assertJsonFragment(['zone' => 'GREEN'])
            ->assertJsonFragment(['adherence_flag' => 'ON_TIME']);

        $this->assertDatabaseHas('medication_logs', [
            'prescription_id' => $prescription->id,
            'scheduled_time' => '08:00',
            'status' => 'TAKEN',
            'adherence_flag' => 'ON_TIME',
        ]);

        Carbon::setTestNow();
    }

    public function test_late_intake_inside_amber_window_is_recorded_as_late()
    {
        Carbon::setTestNow('2026-09-21 11:00:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $response = $this->postDose($user, $appointment, '08:00');

        $response->assertOk()
            ->assertJsonFragment(['zone' => 'AMBER'])
            ->assertJsonFragment(['adherence_flag' => 'LATE']);

        $this->assertDatabaseHas('medication_logs', [
            'prescription_id' => $prescription->id,
            'scheduled_time' => '08:00',
            'status' => 'TAKEN',
            'adherence_flag' => 'LATE',
        ]);

        Carbon::setTestNow();
    }

    public function test_expired_window_locks_the_dose_as_missed_and_audits_the_attempt()
    {
        Carbon::setTestNow('2026-09-21 13:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $response = $this->postDose($user, $appointment, '08:00');

        $response->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'SAFETY_LOCKOUT'])
            ->assertJsonFragment(['zone' => 'RED'])
            ->assertJsonFragment(['adherence_flag' => 'EXPIRED_WINDOW']);

        $this->assertDatabaseHas('medication_logs', [
            'prescription_id' => $prescription->id,
            'scheduled_time' => '08:00',
            'status' => 'MISSED',
            'adherence_flag' => 'EXPIRED_WINDOW',
        ]);

        $this->assertTrue(
            DB::table('audit_logs')->where('action', 'like', 'ATTEMPTED_OFF_SCHEDULE_INTAKE%')->exists()
        );

        Carbon::setTestNow();
    }

    public function test_safety_cooldown_blocks_a_second_intake_within_five_hours()
    {
        Carbon::setTestNow('2026-09-21 10:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $this->postDose($user, $appointment, '08:00')->assertOk();

        Carbon::setTestNow('2026-09-21 15:10:00');
        $response = $this->postDose($user, $appointment, '16:00');

        $response->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'COOLDOWN_ACTIVE'])
            ->assertJsonPath('next_available_at', '2026-09-21 15:30:00');

        $this->assertDatabaseMissing('medication_logs', [
            'prescription_id' => $prescription->id,
            'scheduled_time' => '16:00',
            'status' => 'TAKEN',
        ]);

        Carbon::setTestNow();
    }

    public function test_today_meds_report_locked_missed_slots_and_zone_state()
    {
        Carbon::setTestNow('2026-09-21 13:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $response = $this->actingAs($user)->getJson('/patient/medications/today');

        $response->assertOk();
        $med = $response->json()[0];
        $this->assertSame(3, $med['expected_doses']);

        $doses = collect($med['doses'])->keyBy('scheduled_time');

        $this->assertSame('MISSED', $doses['00:00']['status']);
        $this->assertFalse($doses['00:00']['can_take']);
        $this->assertSame('SAFETY_LOCKOUT', $doses['00:00']['blocked_by']);

        $this->assertSame('MISSED', $doses['08:00']['status']);
        $this->assertFalse($doses['08:00']['can_take']);
        $this->assertSame('SAFETY_LOCKOUT', $doses['08:00']['blocked_by']);

        $this->assertFalse($doses['16:00']['can_take']);
        $this->assertSame('WINDOW_NOT_OPEN', $doses['16:00']['blocked_by']);
        $this->assertSame('PENDING', $doses['16:00']['status']);

        Carbon::setTestNow();
    }

    public function test_today_meds_index_stays_aligned_when_a_prn_is_listed_first()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        // The PRN is created first, so it owns index 0 on the server even
        // though the tracker does not show it as a scheduled dose.
        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Nitroglycerin',
            'dosage' => '0.4 mg',
            'frequency' => 'As needed',
            'status' => 'active',
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '1x a day',
            'status' => 'active',
        ]);

        $response = $this->actingAs($patient->user)->getJson('/patient/medications/today');
        $response->assertOk();

        $med = collect($response->json())->firstWhere('medicine', 'Metoprolol');
        $this->assertNotNull($med);
        $this->assertSame(1, $med['prescription_index']);

        $log = $this->actingAs($patient->user)->postJson(
            '/medications/'.$appointment->id.'/log-dose',
            [
                'prescription_index' => $med['prescription_index'],
                'medicine' => 'Metoprolol',
                'scheduled_time' => '08:00',
            ]
        );

        $log->assertOk()->assertJsonFragment(['status' => 'TAKEN']);

        Carbon::setTestNow();
    }

    public function test_today_meds_index_is_scoped_to_each_appointment()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $older = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->subDays(7)->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $olderEncounter = Encounter::create([
            'appointment_id' => $older->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now()->subDays(7),
            'ended_at' => Carbon::now()->subDays(7),
        ]);

        Prescription::create([
            'encounter_id' => $olderEncounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Aspirin',
            'dosage' => '80 mg',
            'frequency' => '1x a day',
            'status' => 'active',
        ]);

        $newer = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $newerEncounter = Encounter::create([
            'appointment_id' => $newer->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $newerEncounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '1x a day',
            'status' => 'active',
        ]);

        $response = $this->actingAs($patient->user)->getJson('/patient/medications/today');
        $response->assertOk();

        $meds = collect($response->json())->keyBy('medicine');
        $this->assertSame(0, $meds['Metoprolol']['prescription_index']);
        $this->assertSame(0, $meds['Aspirin']['prescription_index']);

        // The older appointment's index must resolve against its own list.
        $log = $this->actingAs($patient->user)->postJson(
            '/medications/'.$older->id.'/log-dose',
            [
                'prescription_index' => $meds['Aspirin']['prescription_index'],
                'medicine' => 'Aspirin',
                'scheduled_time' => '08:00',
            ]
        );

        $log->assertOk()->assertJsonFragment(['status' => 'TAKEN']);

        Carbon::setTestNow();
    }

    public function test_today_meds_skips_expired_and_future_courses_without_breaking_indexes()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        // Expired course: owns canonical index 0 but must not be displayed.
        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Amoxicillin',
            'dosage' => '500 mg',
            'frequency' => '3x a day (8h)',
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->subDay(),
            'status' => 'active',
        ]);

        // Future course: canonical index 1.
        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Atorvastatin',
            'dosage' => '20 mg',
            'frequency' => '1x a day',
            'start_date' => Carbon::today()->addDay(),
            'end_date' => Carbon::today()->addDays(30),
            'status' => 'active',
        ]);

        // Current course: canonical index 2.
        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '3x a day (8h)',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(7),
            'status' => 'active',
        ]);

        $response = $this->actingAs($patient->user)->getJson('/patient/medications/today');
        $response->assertOk();

        $meds = collect($response->json());
        $this->assertCount(1, $meds);
        $this->assertSame('Metoprolol', $meds->first()['medicine']);
        $this->assertSame(2, $meds->first()['prescription_index']);

        $log = $this->actingAs($patient->user)->postJson(
            '/medications/'.$appointment->id.'/log-dose',
            [
                'prescription_index' => 2,
                'medicine' => 'Metoprolol',
                'scheduled_time' => '08:00',
            ]
        );

        $log->assertOk()->assertJsonFragment(['status' => 'TAKEN']);

        Carbon::setTestNow();
    }

    public function test_today_meds_lists_as_needed_medications_without_doses()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');

        [$appointment, , $user] = $this->makeFixture();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $appointment->patient_id,
            'provider_id' => $appointment->provider_id,
            'medication_name' => 'Nitroglycerin',
            'dosage' => '0.4 mg',
            'frequency' => 'As needed',
            'instructions' => 'For chest pain',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson('/patient/medications/today');
        $response->assertOk();

        $med = collect($response->json())->firstWhere('medicine', 'Nitroglycerin');

        $this->assertNotNull($med);
        $this->assertTrue($med['as_needed']);
        $this->assertSame(0, $med['expected_doses']);
        $this->assertSame([], $med['doses']);
        $this->assertSame('For chest pain', $med['instructions']);

        Carbon::setTestNow();
    }

    public function test_dose_cannot_be_recorded_for_a_closed_course()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $prescription->update([
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(
            '/medications/'.$appointment->id.'/log-dose',
            [
                'prescription_index' => 0,
                'medicine' => 'Metoprolol',
                'scheduled_time' => '08:00',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'COURSE_CLOSED']);

        Carbon::setTestNow();
    }

    public function test_adherence_uses_the_most_recent_ninety_days_for_long_courses()
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->subDays(120)->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '1x a day',
            'start_date' => Carbon::today()->subDays(120),
            'status' => 'active',
        ]);

        $response = $this->actingAs($patient->user)
            ->getJson('/medications/'.$appointment->id.'/adherence');
        $response->assertOk();

        $daily = collect($response->json('daily'));
        $this->assertCount(90, $daily);
        $this->assertSame(Carbon::today()->subDays(89)->toDateString(), $daily->first()['date']);
        $this->assertSame(Carbon::today()->toDateString(), $daily->last()['date']);

        Carbon::setTestNow();
    }

    public function test_toggle_rejects_dates_outside_the_prescription_course()
    {
        Carbon::setTestNow('2026-09-21 08:30:00');
        [$appointment, $prescription, $user] = $this->makeFixture();

        $prescription->update([
            'start_date' => Carbon::today()->subDays(3),
            'end_date' => Carbon::today()->subDay(),
        ]);

        $before = $this->actingAs($user)->postJson(
            '/medications/'.$appointment->id.'/toggle-dose',
            [
                'prescription_index' => 0,
                'date' => Carbon::today()->subDays(5)->toDateString(),
            ]
        );
        $before->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'COURSE_CLOSED']);

        $future = $this->actingAs($user)->postJson(
            '/medications/'.$appointment->id.'/toggle-dose',
            [
                'prescription_index' => 0,
                'date' => Carbon::today()->addDay()->toDateString(),
            ]
        );
        $future->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'FUTURE_DATE']);

        $inside = $this->actingAs($user)->postJson(
            '/medications/'.$appointment->id.'/toggle-dose',
            [
                'prescription_index' => 0,
                'date' => Carbon::today()->subDays(2)->toDateString(),
            ]
        );
        $inside->assertOk()->assertJsonFragment(['action' => 'added']);

        Carbon::setTestNow();
    }

    public function test_adherence_stops_counting_doses_after_the_course_ends()
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->subDays(10)->toDateString(),
            'scheduled_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '1x a day',
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->subDays(4),
            'status' => 'active',
        ]);

        $response = $this->actingAs($patient->user)
            ->getJson('/medications/'.$appointment->id.'/adherence');
        $response->assertOk();

        $daily = collect($response->json('daily'));
        $this->assertCount(11, $daily);

        $courseDays = $daily->filter(
            fn ($day) => collect($day['medications'])->contains('medicine', 'Metoprolol')
        );
        $this->assertCount(7, $courseDays);
        $this->assertSame(
            1,
            collect($daily->first()['medications'])->firstWhere('medicine', 'Metoprolol')['expected']
        );

        $afterCourse = $daily->filter(
            fn ($day) => $day['date'] > Carbon::today()->subDays(4)->toDateString()
        );
        $this->assertTrue(
            $afterCourse->every(fn ($day) => collect($day['medications'])->isEmpty())
        );

        Carbon::setTestNow();
    }
}
