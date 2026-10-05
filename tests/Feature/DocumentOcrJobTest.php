<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentOcr;
use App\Models\Appointment;
use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentOcrJobTest extends TestCase
{
    use RefreshDatabase;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->document = Document::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'uploaded_by' => User::factory()->create(['role' => 'receptionist'])->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'pending',
        ]);
    }

    public function test_an_unreachable_service_leaves_the_scan_retryable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not connect to 127.0.0.1:8010'));

        $job = new ProcessDocumentOcr($this->document->id);
        $job->attempts = 1;

        try {
            $job->handle();
            $this->fail('The job should rethrow so the queue can retry it.');
        } catch (ConnectionException) {
            // Expected.
        }

        $document = $this->document->fresh();

        $this->assertSame(
            'processing',
            $document->ocr_status,
            'A dead OCR service must not permanently fail the document.'
        );
        $this->assertStringContainsString('retrying', $document->ocr_error);
        $this->assertStringContainsString('attempt 1 of 3', $document->ocr_error);
    }

    public function test_a_successful_response_stores_layout_and_visuals(): void
    {
        Http::fake([
            '*/ocr' => Http::response([
                'text' => "Tab Amlodipine 5mg",
                'confidence' => 0.93,
                'lines' => [['text' => 'Tab Amlodipine 5mg', 'confidence' => 0.93]],
                'layout' => [
                    ['label' => 'heading', 'bbox' => [10, 10, 200, 40], 'order' => 0, 'column' => 0],
                ],
                'visuals' => [
                    ['kind' => 'seal', 'bbox' => [300, 400, 380, 480], 'confidence' => 0.81],
                ],
                'line_count' => 1,
                'engine' => 'PP-OCRv5_server_rec',
                'language' => 'en',
                'processing_time_ms' => 1234.5,
            ]),
        ]);

        (new ProcessDocumentOcr($this->document->id))->handle();

        $document = $this->document->fresh();

        $this->assertSame('completed', $document->ocr_status);
        $this->assertSame(0.93, $document->ocr_confidence);
        $this->assertSame('Tab Amlodipine 5mg', $document->ocr_text);

        $raw = $document->ocr_raw;

        $this->assertSame('heading', $raw['layout'][0]['label']);
        $this->assertSame('seal', $raw['visuals'][0]['kind']);
        $this->assertSame(1, $raw['line_count']);
        $this->assertSame('PP-OCRv5_server_rec', $raw['engine']);
    }

    public function test_a_response_without_layout_keys_still_completes(): void
    {
        Http::fake([
            '*/ocr' => Http::response([
                'text' => 'plain text',
                'confidence' => 0.5,
                'lines' => [],
                'line_count' => 0,
            ]),
        ]);

        (new ProcessDocumentOcr($this->document->id))->handle();

        $document = $this->document->fresh();

        $this->assertSame('completed', $document->ocr_status);
        $this->assertSame([], $document->ocr_raw['layout']);
        $this->assertSame([], $document->ocr_raw['visuals']);
    }

    public function test_a_server_error_response_fails_the_scan(): void
    {
        Http::fake(['*/ocr' => Http::response('boom', 500)]);

        $this->expectException(\RuntimeException::class);

        try {
            (new ProcessDocumentOcr($this->document->id))->handle();
        } finally {
            $this->assertSame(
                'processing',
                $this->document->fresh()->ocr_status,
                'A 500 is retried by the queue, not failed on the first attempt.'
            );
        }
    }

    public function test_the_failed_callback_marks_the_scan_as_failed(): void
    {
        (new ProcessDocumentOcr($this->document->id))->failed(
            new \RuntimeException('All attempts exhausted')
        );

        $document = $this->document->fresh();

        $this->assertSame('failed', $document->ocr_status);
        $this->assertStringContainsString('All attempts exhausted', $document->ocr_error);
    }
}