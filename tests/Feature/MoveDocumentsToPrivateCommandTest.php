<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MoveDocumentsToPrivateCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    private function document(string $path): Document
    {
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
            'original_filename' => basename($path),
            'image_path' => $path,
            'ocr_status' => 'completed',
        ]);
    }

    public function test_it_moves_referenced_and_orphaned_scans_to_private_storage(): void
    {
        Storage::disk('public')->put('documents/rx.png', 'prescription');
        Storage::disk('public')->put('documents/orphan.png', 'orphan');

        $document = $this->document('documents/rx.png');

        $this->artisan('documents:move-to-private')->assertSuccessful();

        Storage::disk('public')->assertMissing('documents/rx.png');
        Storage::disk('public')->assertMissing('documents/orphan.png');
        Storage::disk('local')->assertExists('documents/rx.png');
        Storage::disk('local')->assertExists('documents/orphan.png');

        $this->assertSame('documents/rx.png', $document->fresh()->image_path);
        $this->assertSame('prescription', Storage::disk('local')->get('documents/rx.png'));
    }

    public function test_a_dry_run_moves_nothing(): void
    {
        Storage::disk('public')->put('documents/rx.png', 'prescription');

        $document = $this->document('documents/rx.png');

        $this->artisan('documents:move-to-private', ['--dry-run' => true])
            ->expectsOutputToContain('Would move 1 scan(s)')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('documents/rx.png');
        Storage::disk('local')->assertMissing('documents/rx.png');
        $this->assertSame('documents/rx.png', $document->fresh()->image_path);
    }

    public function test_it_reports_a_missing_scan_without_failing_the_command(): void
    {
        $this->document('documents/gone.png');

        $this->artisan('documents:move-to-private')->assertSuccessful();

        Storage::disk('local')->assertMissing('documents/gone.png');
    }
}
