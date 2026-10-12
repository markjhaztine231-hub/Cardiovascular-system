<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudyAutoFillTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Provider $provider;

    private Patient $patient;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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
    }

    private function makeDocument(string $type = 'echo', string $ocrStatus = 'completed'): Document
    {
        return Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->doctorUser->id,
            'type' => $type,
            'original_filename' => "report.{$type}.png",
            'image_path' => "documents/report.{$type}.png",
            'ocr_status' => $ocrStatus,
            'ocr_text' => "Left Ventricle\nEjection Fraction 45%\nIVSd 11 mm\nFindings: Mild MR.",
        ]);
    }

    private function fakeOpenRouter(array $fields): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.model' => 'deepseek/deepseek-v4-flash-0731',
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($fields)]],
                ],
            ], 200),
        ]);
    }

    public function test_doctor_autofills_an_echo_scan_from_recognised_text(): void
    {
        $this->fakeOpenRouter([
            'lv_ef' => '45',
            'ivs_dia' => '11',
            'findings' => 'Mild MR.',
            'contact_no' => '09170000000',
        ]);

        $document = $this->makeDocument('echo');

        $response = $this->actingAs($this->doctorUser)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill");

        $response->assertOk()
            ->assertJsonPath('type', 'echo')
            ->assertJsonPath('provider', 'OpenRouter')
            ->assertJsonPath('fields.lv_ef', '45')
            ->assertJsonPath('fields.ivs_dia', '11')
            ->assertJsonPath('fields.findings', 'Mild MR.');
    }

    public function test_autofill_keeps_every_form_key_and_defaults_missing_values_to_empty_string(): void
    {
        $this->fakeOpenRouter(['lv_ef' => '45']);

        $document = $this->makeDocument('echo');

        $response = $this->actingAs($this->doctorUser)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill")
            ->assertOk();

        $fields = $response->json('fields');

        $this->assertArrayHasKey('lv_fs', $fields);
        $this->assertArrayHasKey('mitral_area', $fields);
        $this->assertArrayHasKey('findings', $fields);
        $this->assertSame('', $fields['lv_fs']);
        $this->assertSame('', $fields['findings']);
    }

    public function test_autofill_rejects_non_study_scan_types(): void
    {
        $this->fakeOpenRouter([]);

        $document = $this->makeDocument('prescription');

        $this->actingAs($this->doctorUser)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill")
            ->assertStatus(422);
    }

    public function test_autofill_requires_the_scan_text_to_be_ready(): void
    {
        $this->fakeOpenRouter([]);

        $document = $this->makeDocument('echo', 'pending');

        $this->actingAs($this->doctorUser)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill")
            ->assertStatus(422);
    }

    public function test_autofill_rejects_scans_from_another_doctor(): void
    {
        $this->fakeOpenRouter([]);

        $otherDoctor = User::factory()->doctor()->create();
        Provider::factory()->create(['user_id' => $otherDoctor->id]);

        $document = $this->makeDocument('echo');

        $this->actingAs($otherDoctor)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill")
            ->assertStatus(403);
    }

    public function test_autofill_reports_502_when_no_ai_provider_is_configured(): void
    {
        config([
            'services.openrouter.api_key' => null,
            'services.deepseek.api_key' => null,
            'services.hf.token' => null,
        ]);

        $document = $this->makeDocument('echo');

        $this->actingAs($this->doctorUser)
            ->postJson("/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill")
            ->assertStatus(502);
    }

    public function test_autofill_result_is_cached_and_can_be_refreshed(): void
    {
        $this->fakeOpenRouter(['lv_ef' => '45']);

        $document = $this->makeDocument('echo');
        $url = "/doctor/appointments/{$this->appointment->id}/consultation/scan/{$document->id}/autofill";

        $first = $this->actingAs($this->doctorUser)->postJson($url)->assertOk();
        $this->assertFalse($first->json('cached'));

        $second = $this->actingAs($this->doctorUser)->postJson($url)->assertOk();
        $this->assertTrue($second->json('cached'));

        $third = $this->actingAs($this->doctorUser)->postJson("$url?refresh=1")->assertOk();
        $this->assertFalse($third->json('cached'));
    }

    public function test_reception_can_upload_study_type_scans(): void
    {
        Queue::fake();

        $receptionist = User::factory()->create(['role' => 'receptionist']);

        foreach (['echo', 'tmt', 'doppler'] as $type) {
            $this->actingAs($receptionist)
                ->post("/appointments/{$this->appointment->id}/documents", [
                    'document' => \Illuminate\Http\UploadedFile::fake()->create("{$type}.png", 10, 'image/png'),
                    'type' => $type,
                ])
                ->assertCreated()
                ->assertJsonPath('document.type', $type);
        }
    }
}