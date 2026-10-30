<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the consultation write path after assessment became optional:
 * clearance/request-only visits keep their note but never create a diagnosis
 * row, while real diagnoses continue to be persisted exactly as before.
 */
class ConsultationDiagnosisTextTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): array
    {
        $doctor = User::factory()->role('doctor')->create();
        $provider = Provider::factory()->create(['user_id' => $doctor->id]);
        $patient = Patient::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'service_type' => 'Consultation',
            'status' => 'Pending',
        ]);

        return [$doctor, $provider, $patient, $appointment];
    }

    public function test_clearance_visit_keeps_its_note_without_creating_a_diagnosis_row(): void
    {
        [$doctor, , $patient, $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'For clearance',
                'vitals' => 'BP: 120/80 mmHg | HR: 72 bpm',
                'diagnosis' => 'For clearance',
            ],
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame('For clearance', $encounter->soapNote->assessment);
        $this->assertCount(0, $encounter->diagnoses, 'A clearance visit must not create a diagnosis row.');

        // The history screen must still surface the note via the SOAP fallback.
        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.diagnosis', 'For clearance')
            );
    }

    public function test_placeholder_assessment_is_neither_stored_nor_counted(): void
    {
        [$doctor, , , $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Routine follow-up',
                'vitals' => 'BP: 118/76 mmHg | HR: 70 bpm',
                'diagnosis' => 'N/A',
            ],
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertNull($encounter->soapNote->assessment);
        $this->assertCount(0, $encounter->diagnoses);
    }

    public function test_assessment_is_optional_when_completing_a_consultation(): void
    {
        [$doctor, , , $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Doctor requested review',
                'vitals' => 'BP: 126/82 mmHg | HR: 74 bpm',
            ],
        )->assertSessionHasNoErrors()->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertNull($encounter->soapNote->assessment);
        $this->assertCount(0, $encounter->diagnoses);
    }

    public function test_real_diagnosis_still_creates_a_diagnosis_row(): void
    {
        [$doctor, , , $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Chest pain on exertion',
                'vitals' => 'BP: 140/90 mmHg | HR: 88 bpm',
                'diagnosis' => '  Essential   (primary) hypertension ',
            ],
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame(
            'Essential (primary) hypertension',
            $encounter->soapNote->assessment,
        );
        $this->assertCount(1, $encounter->diagnoses);
        $this->assertSame(
            'Essential (primary) hypertension',
            $encounter->diagnoses->first()->description,
        );
    }
}
