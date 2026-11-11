<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\User;
use App\Support\ClinicalRecordMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalDataEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_free_text_is_encrypted_at_rest(): void
    {
        $patient = Patient::factory()->create([
            'address' => '12 Mabini Street, Davao City',
            'allergies' => 'Penicillin',
            'contact_phone' => '+63 917 555 0100',
        ]);

        $raw = DB::table('patients')->where('id', $patient->id)->first();

        $this->assertNotSame('12 Mabini Street, Davao City', $raw->address);
        $this->assertNotSame('Penicillin', $raw->allergies);
        $this->assertSame('12 Mabini Street, Davao City', Crypt::decryptString($raw->address));
        $this->assertSame('Penicillin', Crypt::decryptString($raw->allergies));

        $fresh = $patient->fresh();

        $this->assertSame('12 Mabini Street, Davao City', $fresh->address);
        $this->assertSame('Penicillin', $fresh->allergies);
        $this->assertSame('+63 917 555 0100', $fresh->contact_phone);
    }

    public function test_short_identifier_columns_hold_ciphertext_beyond_the_old_varchar_limit(): void
    {
        $phone = str_repeat('9', 30);

        $patient = Patient::factory()->create(['contact_phone' => $phone]);

        $stored = DB::table('patients')->where('id', $patient->id)->value('contact_phone');

        $this->assertIsString($stored);
        $this->assertGreaterThan(30, strlen($stored));
        $this->assertSame($phone, $patient->fresh()->contact_phone);
    }

    public function test_empty_strings_are_encrypted_so_the_cast_can_read_them_back(): void
    {
        $patient = Patient::factory()->create(['address' => '']);

        $stored = DB::table('patients')->where('id', $patient->id)->value('address');

        $this->assertNotSame('', $stored);
        $this->assertSame('', Crypt::decryptString($stored));
        $this->assertSame('', $patient->fresh()->address);
    }

    public function test_soap_notes_are_encrypted_at_rest(): void
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
        ]);

        $note = SoapNote::create([
            'encounter_id' => $encounter->id,
            'subjective' => 'Chest pain on exertion',
            'objective' => 'BP 120/80 mmHg',
            'assessment' => 'Stable angina',
            'plan' => 'Start aspirin',
            'ai_summary' => 'Stable patient.',
        ]);

        $raw = DB::table('soap_notes')->where('id', $note->id)->first();

        $this->assertNotSame('Chest pain on exertion', $raw->subjective);
        $this->assertSame('Chest pain on exertion', Crypt::decryptString($raw->subjective));
        $this->assertSame('Chest pain on exertion', $note->fresh()->subjective);
    }

    public function test_document_ocr_payload_is_encrypted_and_round_trips_as_an_array(): void
    {
        $patient = Patient::factory()->create();
        $uploader = User::factory()->create();

        $document = Document::create([
            'patient_id' => $patient->id,
            'uploaded_by' => $uploader->id,
            'type' => 'prescription',
            'image_path' => 'scans/one.png',
            'ocr_text' => 'Tab Amlodipine 5mg',
            'ocr_raw' => [
                'engine' => 'PP-OCRv5',
                'lines' => [['text' => 'Tab Amlodipine 5mg']],
            ],
            'ocr_status' => 'completed',
        ]);

        $raw = DB::table('documents')->where('id', $document->id)->first();

        $this->assertNotSame('Tab Amlodipine 5mg', $raw->ocr_text);
        $this->assertStringNotContainsString('Amlodipine', (string) $raw->ocr_raw);

        $fresh = $document->fresh();

        $this->assertSame('Tab Amlodipine 5mg', $fresh->ocr_text);
        $this->assertSame([
            'engine' => 'PP-OCRv5',
            'lines' => [['text' => 'Tab Amlodipine 5mg']],
        ], $fresh->ocr_raw);
        $this->assertSame('Tab Amlodipine 5mg', $fresh->lines()[0]['text']);
    }

    public function test_study_payload_exposes_plaintext_not_ciphertext(): void
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
        ]);

        $echo = Echocardiogram::create([
            'encounter_id' => $encounter->id,
            'lvef_percent' => 55,
            'valve_findings' => 'Normal valves',
            'wall_motion' => 'Tricuspid valve: normal',
            'notes' => 'Normal study.',
        ]);

        $payload = ClinicalRecordMapper::forPayload($echo->fresh(), null, null, $patient);

        $this->assertSame('Normal valves', $payload['valve_findings']);
        $this->assertSame('Tricuspid valve: normal', $payload['wall_motion']);
        $this->assertSame('Normal study.', $payload['notes']);
    }

    public function test_rollback_decrypts_values_and_restores_the_original_column_types(): void
    {
        $patient = Patient::factory()->create([
            'address' => 'Legacy street address',
            'contact_phone' => '+63 917 555 0100',
        ]);

        /** @var object{down: callable} $migration */
        $migration = require database_path(
            'migrations/2026_11_11_000001_encrypt_clinical_text_columns.php'
        );

        $migration->down();

        $raw = DB::table('patients')->where('id', $patient->id)->first();

        $this->assertSame('Legacy street address', $raw->address);
        $this->assertSame('+63 917 555 0100', $raw->contact_phone);
    }

    public function test_backfill_encrypts_plaintext_rows_that_predate_the_cast(): void
    {
        $patient = Patient::factory()->create();

        // Bypass the casts, exactly like a row written before this feature.
        DB::table('patients')->where('id', $patient->id)->update([
            'address' => 'Legacy street address',
            'allergies' => 'Sulfa drugs',
        ]);

        /** @var object{up: callable} $migration */
        $migration = require database_path(
            'migrations/2026_11_11_000001_encrypt_clinical_text_columns.php'
        );

        $migration->up();
        $migration->up(); // Must be idempotent: never double-encrypt.

        $raw = DB::table('patients')->where('id', $patient->id)->first();

        $this->assertNotSame('Legacy street address', $raw->address);
        $this->assertSame('Legacy street address', Crypt::decryptString($raw->address));

        $fresh = $patient->fresh();

        $this->assertSame('Legacy street address', $fresh->address);
        $this->assertSame('Sulfa drugs', $fresh->allergies);
    }
}
