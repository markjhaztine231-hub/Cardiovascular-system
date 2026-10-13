<?php

namespace Tests\Feature;

use App\Models\AmbulatoryBloodPressureMonitoring;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\StressEchocardiogram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StressEchoAbpmConsultationTest extends TestCase
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
            'service_type' => 'Procedure',
            'status' => 'Pending',
        ]);

        return [$doctor, $provider, $patient, $appointment];
    }

    public function test_type_5_consultation_persists_a_stress_echo_and_maps_it_to_history(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Exertional chest pain',
                'vitals' => 'BP: 130/85 mmHg | HR: 78 bpm',
                'diagnosis' => 'Ischaemia workup',
                'consultation_type' => ['Type 5'],
                'stress_echo_data' => [
                    'requesting_physician' => 'Dr. Reyes',
                    'file_no' => '26-0001',
                    'indication' => 'Rule out inducible ischaemia',
                    'pre_bp' => '130/85',
                    'pre_hr' => '78',
                    'protocol' => 'BRUCE',
                    'stages' => [
                        ['stage' => 'STAGE 1', 'time' => '3', 'speed' => '1.7', 'grade' => '10', 'hr' => '110', 'bp' => '150/90', 'ecg' => 'No ST changes', 'symptoms' => 'None'],
                    ],
                    'peak_hr' => '150',
                    'target_hr' => '162',
                    'rest_ef' => '60',
                    'peak_ef' => '65',
                    'findings' => 'No inducible wall motion abnormality.',
                    'conclusion' => 'Negative for stress-induced ischaemia.',
                ],
            ]
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($encounter);
        $this->assertSame('stress_echo', $encounter->encounter_type);

        $echo = $encounter->stressEcho;
        $this->assertNotNull($echo);
        $this->assertSame('BRUCE', $echo->payload['protocol']);
        $this->assertSame('150', $echo->payload['peak_hr']);
        $this->assertCount(1, $echo->payload['stages']);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.consultation_type', 'Type 5 - Stress Echo')
                ->where('history.0.stress_echo_data.protocol', 'BRUCE')
                ->where('history.0.stress_echo_data.conclusion', 'Negative for stress-induced ischaemia.')
            );
    }

    public function test_type_6_consultation_persists_an_abpm_and_maps_it_to_history(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Elevated home BP readings',
                'vitals' => 'BP: 148/92 mmHg | HR: 80 bpm',
                'diagnosis' => 'Hypertension',
                'consultation_type' => ['Type 6'],
                'abpm_data' => [
                    'requesting_physician' => 'Dr. Reyes',
                    'file_no' => '26-0002',
                    'monitor_device' => 'Spacelabs',
                    'day_sbp' => '142',
                    'day_dbp' => '88',
                    'night_sbp' => '128',
                    'night_dbp' => '76',
                    'avg_sbp' => '138',
                    'avg_dbp' => '84',
                    'dipping' => 'Non-dipper',
                    'findings' => 'Stage 1 hypertension with a non-dipping pattern.',
                    'conclusion' => 'Continue current antihypertensive.',
                ],
            ]
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $this->assertSame('abpm', $encounter->encounter_type);

        $abpm = $encounter->abpm;
        $this->assertNotNull($abpm);
        $this->assertSame('Spacelabs', $abpm->payload['monitor_device']);
        $this->assertSame('Non-dipper', $abpm->payload['dipping']);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.consultation_type', 'Type 6 - Ambulatory BP Monitoring')
                ->where('history.0.abpm_data.monitor_device', 'Spacelabs')
                ->where('history.0.abpm_data.conclusion', 'Continue current antihypertensive.')
            );
    }

    public function test_new_types_are_cleared_when_not_selected(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Checkup',
                'vitals' => 'BP: 118/76 mmHg',
                'diagnosis' => 'Routine',
                'consultation_type' => ['Type 1'],
                'echo_data' => ['lv_ef' => '55'],
                'stress_echo_data' => ['protocol' => 'Ignored'],
                'abpm_data' => ['monitor_device' => 'Ignored'],
            ]
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $this->assertSame('echo', $encounter->encounter_type);
        $this->assertNull($encounter->stressEcho);
        $this->assertNull($encounter->abpm);
    }

    public function test_stress_echo_pdf_route_streams_a_pdf(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'stress_echo',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        StressEchocardiogram::create([
            'encounter_id' => $encounter->id,
            'payload' => ['protocol' => 'BRUCE', 'findings' => 'Normal.'],
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/stress_echo')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_abpm_pdf_route_streams_a_pdf(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'abpm',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        AmbulatoryBloodPressureMonitoring::create([
            'encounter_id' => $encounter->id,
            'payload' => ['monitor_device' => 'Spacelabs', 'conclusion' => 'Non-dipper.'],
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/abpm')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_new_pdf_routes_404_without_a_record(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/stress_echo')
            ->assertNotFound();

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/abpm')
            ->assertNotFound();
    }
}
