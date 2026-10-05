<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentOcr;
use App\Models\Appointment;
use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanOcrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private User $doctorUser;

    private Provider $provider;

    private Patient $patient;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->receptionist = User::factory()->create(['role' => 'receptionist']);

        $this->doctorUser = User::factory()->doctor()->create();
        $this->provider = Provider::factory()->create([
            'user_id' => $this->doctorUser->id,
        ]);

        $patientUser = User::factory()->create(['role' => 'patient']);
        $this->patient = Patient::factory()->create([
            'user_id' => $patientUser->id,
        ]);

        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'provider_id' => $this->provider->id,
            'scheduled_date' => today(),
            'scheduled_time' => '10:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_receptionist_uploads_a_scan_and_it_is_queued_for_ocr(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->receptionist)
            ->post("/appointments/{$this->appointment->id}/documents", [
                'document' => UploadedFile::fake()->create('rx.png', 10, 'image/png'),
                'type' => 'prescription',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('document.ocr_status', 'pending');

        $document = Document::firstOrFail();

        $this->assertSame('prescription', $document->type);
        $this->assertSame($this->patient->id, $document->patient_id);
        $this->assertSame($this->appointment->id, $document->appointment_id);
        $this->assertSame($this->receptionist->id, $document->uploaded_by);
        $this->assertSame('pending', $document->ocr_status);

        Storage::disk('public')->assertExists($document->image_path);

        Queue::assertPushed(ProcessDocumentOcr::class, fn ($job) => $job->documentId === $document->id);
    }

    public function test_scan_upload_rejects_non_image_files(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->post("/appointments/{$this->appointment->id}/documents", [
                'document' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
                'type' => 'prescription',
            ]);

        $response->assertSessionHasErrors('document');
        $this->assertSame(0, Document::count());
    }

    public function test_scan_upload_requires_a_known_document_type(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->post("/appointments/{$this->appointment->id}/documents", [
                'document' => UploadedFile::fake()->create('rx.png', 10, 'image/png'),
                'type' => 'not-a-real-type',
            ]);

        $response->assertSessionHasErrors('type');
        $this->assertSame(0, Document::count());
    }

    public function test_patients_and_doctors_cannot_upload_scans(): void
    {
        $patientUser = User::factory()->create(['role' => 'patient']);

        $this->actingAs($patientUser)
            ->post("/appointments/{$this->appointment->id}/documents", [
                'document' => UploadedFile::fake()->create('rx.png', 10, 'image/png'),
                'type' => 'prescription',
            ])
            ->assertForbidden();

        $this->actingAs($this->doctorUser)
            ->post("/appointments/{$this->appointment->id}/documents", [
                'document' => UploadedFile::fake()->create('rx.png', 10, 'image/png'),
                'type' => 'prescription',
            ])
            ->assertForbidden();

        $this->assertSame(0, Document::count());
    }

    public function test_consultation_returns_converted_text_and_recognised_medications(): void
    {
        $document = Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'completed',
            'ocr_confidence' => 0.94,
            'ocr_text' => "Tab Amlodipine 5mg - once daily\nTab Metformin 500mg - twice daily",
            'ocr_raw' => ['engine' => 'PP-OCRv5_server_rec', 'lines' => []],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/doctor/appointments/{$this->appointment->id}/consultation");

        $response->assertOk();

        $payload = $response->json('documents.0');

        $this->assertSame($document->id, $payload['id']);
        $this->assertSame('completed', $payload['ocr_status']);
        $this->assertFalse($payload['requires_review']);
        $this->assertStringContainsString('Amlodipine', $payload['ocr_text']);

        $names = array_column($payload['medications'], 'medication_name');

        $this->assertContains('Amlodipine', $names);
        $this->assertContains('Metformin', $names);
    }

    public function test_consultation_marks_a_pending_scan_as_requiring_review(): void
    {
        Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'processing',
        ]);

        $payload = $this->actingAs($this->doctorUser)
            ->get("/doctor/appointments/{$this->appointment->id}/consultation")
            ->json('documents.0');

        $this->assertSame('processing', $payload['ocr_status']);
        $this->assertTrue($payload['requires_review']);
        $this->assertSame([], $payload['medications']);
    }

    public function test_starting_a_consultation_moves_the_appointment_in_progress(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/appointments/{$this->appointment->id}/consultation/start")
            ->assertOk();

        $this->assertSame('In Progress', $this->appointment->fresh()->status);
        $this->assertNotNull($this->appointment->fresh()->consultation_started_at);
    }

    public function test_doctor_cannot_open_a_consultation_for_another_doctor(): void
    {
        $otherProvider = Provider::factory()->create();
        $otherAppointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'provider_id' => $otherProvider->id,
            'scheduled_date' => today(),
        ]);

        $this->actingAs($this->doctorUser)
            ->get("/doctor/appointments/{$otherAppointment->id}/consultation")
            ->assertForbidden();
    }

    public function test_doctor_cannot_read_a_scan_belonging_to_another_doctor(): void
    {
        $otherProvider = Provider::factory()->create();
        $otherAppointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'provider_id' => $otherProvider->id,
            'scheduled_date' => today(),
        ]);

        $document = Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $otherAppointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'completed',
        ]);

        $this->actingAs($this->doctorUser)
            ->get("/documents/{$document->id}")
            ->assertForbidden();

        $this->actingAs($this->receptionist)
            ->get("/documents/{$document->id}")
            ->assertOk();
    }

    public function test_reception_queue_exposes_scan_counts(): void
    {
        Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'processing',
        ]);

        $props = $this->actingAs($this->receptionist)
            ->get('/receptionist/dashboard')
            ->assertOk()
            ->viewData('page')['props'];

        $row = collect($props['masterQueue'])->firstWhere('id', $this->appointment->id);

        $this->assertNotNull($row, 'The appointment should appear in the reception queue.');
        $this->assertSame(1, $row['scan_count']);
        $this->assertTrue($row['scans_in_progress']);
        $this->assertSame('processing', $row['scans'][0]['ocr_status']);
        $this->assertSame('prescription', $row['scans'][0]['type']);
    }

    public function test_receptionist_can_retry_a_failed_scan_without_re_uploading_it(): void
    {
        Queue::fake();

        Storage::disk('public')->put('documents/rx.png', 'binary');

        $document = Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'failed',
            'ocr_error' => 'OCR service unreachable.',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->post("/documents/{$document->id}/retry-ocr");

        $response->assertOk();
        $response->assertJsonPath('document.ocr_status', 'pending');

        $document->refresh();

        $this->assertSame('pending', $document->ocr_status);
        $this->assertNull($document->ocr_error, 'The stale error should be cleared on retry.');

        Queue::assertPushed(ProcessDocumentOcr::class, fn ($job) => $job->documentId === $document->id);
    }

    public function test_retrying_a_scan_is_refused_when_the_stored_image_is_gone(): void
    {
        Queue::fake();

        $document = Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/deleted.png',
            'ocr_status' => 'failed',
        ]);

        $this->actingAs($this->receptionist)
            ->post("/documents/{$document->id}/retry-ocr")
            ->assertStatus(422);

        $this->assertSame('failed', $document->fresh()->ocr_status);
        Queue::assertNothingPushed();
    }

    public function test_a_doctor_cannot_retry_ocr(): void
    {
        Queue::fake();

        Storage::disk('public')->put('documents/rx.png', 'binary');

        $document = Document::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'uploaded_by' => $this->receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'failed',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/documents/{$document->id}/retry-ocr")
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
