<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentOcr;
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

class ReprocessOcrCommandTest extends TestCase
{
    use RefreshDatabase;

    private function document(string $status = 'pending'): Document
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/rx.png', 'binary');

        $patient = Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => 'patient'])->id,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => Provider::factory()->create()->id,
            'scheduled_date' => today(),
            'scheduled_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        return Document::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'uploaded_by' => User::factory()->create(['role' => 'receptionist'])->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => $status,
        ]);
    }

    public function test_it_requeues_stuck_documents(): void
    {
        Queue::fake();
        $document = $this->document('pending');

        $this->artisan('ocr:reprocess')->assertSuccessful();

        Queue::assertPushed(ProcessDocumentOcr::class, fn ($job) => $job->documentId === $document->id);
        $this->assertSame('pending', $document->fresh()->ocr_status);
    }

    public function test_dry_run_lists_documents_without_changing_them(): void
    {
        Queue::fake();
        $document = $this->document('failed');

        $this->artisan('ocr:reprocess', ['--status' => 'failed', '--dry-run' => true])
            ->expectsOutputToContain('Would reprocess 1 document(s).')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertSame('failed', $document->fresh()->ocr_status);
    }

    public function test_sync_processes_documents_without_a_queue_worker(): void
    {
        Http::fake([
            '*/ocr' => Http::response([
                'text' => 'Tab Aspirin 80mg',
                'confidence' => 0.94,
            ]),
        ]);

        $document = $this->document('processing');

        $this->artisan('ocr:reprocess', ['--sync' => true])->assertSuccessful();

        $document = $document->fresh();

        $this->assertSame('completed', $document->ocr_status);
        $this->assertSame('Tab Aspirin 80mg', $document->ocr_text);
    }

    public function test_it_skips_documents_whose_image_is_missing(): void
    {
        Storage::fake('public');

        $patient = Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => 'patient'])->id,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => Provider::factory()->create()->id,
            'scheduled_date' => today(),
            'scheduled_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $document = Document::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'uploaded_by' => User::factory()->create(['role' => 'receptionist'])->id,
            'type' => 'prescription',
            'original_filename' => 'gone.png',
            'image_path' => 'documents/gone.png',
            'ocr_status' => 'pending',
        ]);

        Queue::fake();

        $this->artisan('ocr:reprocess', ['--sync' => true])->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertSame('pending', $document->fresh()->ocr_status);
    }
}
