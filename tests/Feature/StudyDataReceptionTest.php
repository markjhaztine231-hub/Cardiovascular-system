<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\TreadmillTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudyDataReceptionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Provider $provider;

    private Patient $patient;

    private Appointment $appointment;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->doctor()->create();
        $this->provider = Provider::factory()->create([
            'user_id' => $this->doctorUser->id,
        ]);

        $this->patient = Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => 'patient'])->id,
        ]);

        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'provider_id' => $this->provider->id,
            'scheduled_date' => today(),
            'scheduled_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $this->receptionist = User::factory()->create(['role' => 'receptionist']);
    }

    private function studyPayload(): array
    {
        return [
            'echo_data' => [
                'lv_ef' => '55',
                'findings' => 'Normal study',
            ],
            'tmt_data' => [
                'protocol' => 'Bruce',
                'peak_heart_rate_bpm' => '148',
            ],
            'doppler_data' => [
                'ea_ratio' => '1.1',
            ],
        ];
    }

    public function test_receptionist_saves_study_data_as_a_draft_encounter(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->put("/appointments/{$this->appointment->id}/study-data", $this->studyPayload());

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $encounter = Encounter::where('appointment_id', $this->appointment->id)->first();

        $this->assertNotNull($encounter);
        $this->assertSame('in_progress', $encounter->status);
        $this->assertSame('echo', $encounter->encounter_type);

        $this->assertDatabaseHas('echocardiograms', [
            'encounter_id' => $encounter->id,
            'lvef_percent' => 55.0,
            'valve_findings' => 'Normal study',
        ]);

        $this->assertDatabaseHas('treadmill_tests', [
            'encounter_id' => $encounter->id,
            'protocol' => 'Bruce',
            'peak_heart_rate_bpm' => 148,
        ]);

        $this->assertDatabaseHas('doppler_studies', [
            'encounter_id' => $encounter->id,
            'ea_ratio' => 1.1,
        ]);
    }

    public function test_blank_study_form_does_not_create_an_empty_record(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->putJson("/appointments/{$this->appointment->id}/study-data", [
                'echo_data' => [],
                'tmt_data' => [],
                'doppler_data' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('echo_data');

        $this->assertDatabaseCount('encounters', 0);
        $this->assertDatabaseCount('echocardiograms', 0);
        $this->assertDatabaseCount('treadmill_tests', 0);
        $this->assertDatabaseCount('doppler_studies', 0);
    }

    public function test_receptionist_cannot_edit_a_closed_appointment(): void
    {
        $this->appointment->update(['status' => 'Completed']);

        $this->actingAs($this->receptionist)
            ->putJson("/appointments/{$this->appointment->id}/study-data", $this->studyPayload())
            ->assertStatus(422);
    }

    public function test_receptionist_can_fetch_saved_study_data(): void
    {
        $this->actingAs($this->receptionist)
            ->put("/appointments/{$this->appointment->id}/study-data", $this->studyPayload());

        $response = $this->actingAs($this->receptionist)
            ->getJson("/appointments/{$this->appointment->id}/study-data");

        $response->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('echo_data.findings', 'Normal study')
            ->assertJsonPath('tmt_data.protocol', 'Bruce')
            ->assertJsonPath('doppler_data.mv_ea_ratio', '1.10');
    }

    public function test_fetching_study_data_for_an_untouched_appointment_reports_not_saved(): void
    {
        $this->actingAs($this->receptionist)
            ->getJson("/appointments/{$this->appointment->id}/study-data")
            ->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('echo_data', null);
    }

    public function test_doctor_sees_receptionist_study_data_prefilled_in_consultation(): void
    {
        $this->actingAs($this->receptionist)
            ->put("/appointments/{$this->appointment->id}/study-data", $this->studyPayload());

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/doctor/appointments/{$this->appointment->id}/consultation");

        $response->assertOk();
        $response->assertJsonPath('prefill.source', 'receptionist')
            ->assertJsonPath('prefill.status', 'in_progress')
            ->assertJsonPath('prefill.echo_data.findings', 'Normal study')
            ->assertJsonPath('prefill.tmt_data.protocol', 'Bruce')
            ->assertJsonPath('prefill.tmt_data.peak_hr', 148);

        $doppler = $response->json('prefill.doppler_data');

        $this->assertArrayHasKey('mv_ea_ratio', $doppler);
        $this->assertNotEmpty($doppler['mv_ea_ratio']);
    }

    public function test_doctor_sees_null_prefill_when_receptionist_has_not_saved_anything(): void
    {
        $this->actingAs($this->doctorUser)
            ->getJson("/doctor/appointments/{$this->appointment->id}/consultation")
            ->assertOk()
            ->assertJsonPath('prefill', null);
    }

    public function test_doctor_patient_history_includes_ocr_documents(): void
    {
        Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'echo',
            'original_filename' => 'report.echo.png',
            'image_path' => 'documents/report.echo.png',
            'ocr_status' => 'completed',
            'ocr_confidence' => 0.9,
            'ocr_error' => null,
            'ocr_raw' => ['engine' => 'PP-OCRv5'],
            'ocr_text' => "Ejection Fraction\n55%",
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/doctor/patients/{$this->patient->user_id}/history");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/PatientHistory')
            ->has('ocrHistory', 1)
            ->where('ocrHistory.0.type', 'echo')
            ->where('ocrHistory.0.original_filename', 'report.echo.png')
            ->where('ocrHistory.0.requires_review', false)
            ->where('ocrHistory.0.uploaded_by', $this->receptionist->name)
            ->where('ocrHistory.0.engine', 'PP-OCRv5')
        );
    }

    public function test_patient_history_has_empty_ocr_history_when_no_scans_exist(): void
    {
        $this->actingAs($this->doctorUser)
            ->get("/doctor/patients/{$this->patient->user_id}/history")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/PatientHistory')
                ->where('ocrHistory', [])
            );
    }
}