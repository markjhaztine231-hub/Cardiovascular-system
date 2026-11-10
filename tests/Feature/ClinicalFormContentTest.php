<?php

namespace Tests\Feature;

use App\Models\AmbulatoryBloodPressureMonitoring;
use App\Models\Appointment;
use App\Models\ClinicalRequest;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\ManualDraft;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\StressEchocardiogram;
use App\Models\TreadmillTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * A form only becomes a document once the user actually enters something.
 * Default-only forms, attachment-only drafts and blank prescriptions must not
 * appear in View History and must not be printable.
 */
class ClinicalFormContentTest extends TestCase
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
            'scheduled_time' => '09:00:00',
            'service_type' => 'Procedure',
            'status' => 'Completed',
        ]);

        return [$doctor, $provider, $patient, $appointment];
    }

    private function makeEncounter(array $args): Encounter
    {
        [$doctor, $provider, $patient, $appointment] = $args;

        return Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);
    }

    public function test_default_only_studies_do_not_appear_in_history_or_export(): void
    {
        $args = $this->makeAppointment();
        [$doctor, $provider, $patient, $appointment] = $args;
        $encounter = $this->makeEncounter($args);

        // Exactly what the save path stores when the user never types into the
        // forms: the tricuspid default, the TMT protocol presets and the
        // "Faint" Doppler jets, plus generated file/patient context.
        Echocardiogram::create([
            'encounter_id' => $encounter->id,
            'wall_motion' => 'Tricuspid valve: normal',
            'pericardial_effusion' => false,
            'notes' => ' | {"tricuspid_valve":"normal","peri_ant":"Absent","peri_post":"Absent","file_no":"2026-0001","age":"54","sex":"M"}',
        ]);

        TreadmillTest::create([
            'encounter_id' => $encounter->id,
            'protocol' => 'BRUCE, MODIFIED BRUCE, NIH',
            'notes' => ' | {"procedure":"Treadmill Exercise Test","protocol":"BRUCE, MODIFIED BRUCE, NIH","file_no":"2026-0001","age":"54","sex":"M","stages":[{"name":"Pre-test","speed":"0","grade":"0","mets":"0","bp":"","hr":"","remarks":""},{"name":"STAGE 1","speed":"1.7","grade":"10","mets":"4.6","bp":"","hr":"","remarks":""}]}',
        ]);

        DopplerStudy::create([
            'encounter_id' => $encounter->id,
            'notes' => ' | {"av_ar_jet":"Faint","pv_pr_jet":"Faint","file_no":"2026-0001","age":"54","sex":"M"}',
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/PatientHistory')
                ->has('history', 1)
                ->has('history.0.forms', 0)
                ->where('history.0.echo_data', null)
                ->where('history.0.tmt_data', null)
                ->where('history.0.doppler_data', null)
            );

        foreach (['echo', 'tmt', 'doppler'] as $type) {
            $this->actingAs($doctor)
                ->get('/doctor/pdf/'.$appointment->id.'/'.$type)
                ->assertNotFound();
        }
    }

    public function test_attachment_only_drafts_and_generated_context_do_not_count(): void
    {
        $args = $this->makeAppointment();
        [$doctor, $provider, $patient, $appointment] = $args;
        $encounter = $this->makeEncounter($args);

        ManualDraft::create([
            'encounter_id' => $encounter->id,
            'attachments' => [['url' => '/storage/scans/scan.png', 'name' => 'scan.png']],
        ]);

        StressEchocardiogram::create([
            'encounter_id' => $encounter->id,
            'payload' => ['file_no' => '2026-0001', 'age' => '54', 'sex' => 'M', 'stages' => []],
        ]);

        AmbulatoryBloodPressureMonitoring::create([
            'encounter_id' => $encounter->id,
            'payload' => ['file_no' => '2026-0002', 'age' => '54', 'sex' => 'M'],
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('history.0.forms', 0)
                ->where('history.0.manual_draft_data', null)
                ->where('history.0.stress_echo_data', null)
                ->where('history.0.abpm_data', null)
            );

        foreach (['manual_draft', 'stress_echo', 'abpm'] as $type) {
            $this->actingAs($doctor)
                ->get('/doctor/pdf/'.$appointment->id.'/'.$type)
                ->assertNotFound();
        }
    }

    public function test_blank_prescriptions_and_requests_are_not_listed_or_exported(): void
    {
        $args = $this->makeAppointment();
        [$doctor, $provider, $patient, $appointment] = $args;
        $encounter = $this->makeEncounter($args);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => '',
            'is_freeform' => false,
            'free_text' => null,
        ]);

        ClinicalRequest::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'type' => 'lab',
            'body' => '',
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('history.0.forms', 0)
                ->has('history.0.prescriptions', 0)
                ->has('history.0.requests', 0)
            );

        foreach (['prescription', 'clinical_requests'] as $type) {
            $this->actingAs($doctor)
                ->get('/doctor/pdf/'.$appointment->id.'/'.$type)
                ->assertNotFound();
        }
    }

    public function test_filled_forms_are_still_listed_and_exportable(): void
    {
        $args = $this->makeAppointment();
        [$doctor, $provider, $patient, $appointment] = $args;
        $encounter = $this->makeEncounter($args);

        Echocardiogram::create([
            'encounter_id' => $encounter->id,
            'lvef_percent' => 55,
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Amlodipine',
            'is_freeform' => false,
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('history.0.forms', 2)
                ->where('history.0.forms.0.type', 'echo')
                ->where('history.0.forms.1.type', 'prescription')
                ->where('history.0.echo_data.lv_ef', '55.0')
                ->has('history.0.prescriptions', 1)
            );

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/echo')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/prescription')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_completing_with_default_only_forms_creates_no_study_rows(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();
        $appointment->update(['status' => 'Pending']);

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Routine checkup',
                'diagnosis' => 'Routine',
                'consultation_type' => ['Type 1', 'Type 2', 'Type 3', 'Type 5', 'Type 6'],
                'echo_data' => [
                    'file_no' => '2026-0001',
                    'age' => '54',
                    'sex' => 'M',
                    'tricuspid_valve' => 'normal',
                    'peri_ant' => 'Absent',
                    'peri_post' => 'Absent',
                ],
                'tmt_data' => [
                    'file_no' => '2026-0001',
                    'age' => '54',
                    'sex' => 'M',
                    'procedure' => 'Treadmill Exercise Test',
                    'protocol' => 'BRUCE, MODIFIED BRUCE, NIH',
                    'stages' => [
                        ['name' => 'STAGE 1', 'speed' => '1.7', 'grade' => '10', 'mets' => '4.6', 'bp' => '', 'hr' => '', 'remarks' => ''],
                    ],
                ],
                'doppler_data' => [
                    'file_no' => '2026-0001',
                    'age' => '54',
                    'sex' => 'M',
                    'av_ar_jet' => 'Faint',
                    'pv_pr_jet' => 'Faint',
                ],
                'stress_echo_data' => [
                    'file_no' => '2026-0001',
                    'age' => '54',
                    'sex' => 'M',
                    'stages' => [],
                ],
                'abpm_data' => [
                    'file_no' => '2026-0001',
                    'age' => '54',
                    'sex' => 'M',
                ],
            ]
        )->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertNull($encounter->echocardiogram);
        $this->assertNull($encounter->treadmillTest);
        $this->assertNull($encounter->dopplerStudy);
        $this->assertNull($encounter->stressEcho);
        $this->assertNull($encounter->abpm);

        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('history.0.forms', 0)
                ->where('history.0.echo_data', null)
            );
    }
}
