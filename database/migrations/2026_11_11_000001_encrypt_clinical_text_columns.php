<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts the free-text patient data that the application reads and writes
 * through Eloquent encrypted casts.
 *
 * Columns that are filtered, sorted, joined on, or searched in SQL (MRN, dates,
 * statuses, foreign keys, patient/provider names) are deliberately excluded:
 * Laravel ciphertext is non-deterministic, so an encrypted column cannot carry
 * a useful index or a LIKE/where clause.
 *
 * The migration backfills existing plaintext rows in place. It is safe to run
 * more than once: rows that already decrypt with the current APP_KEY are left
 * untouched.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'patients' => [
            'address',
            'allergies',
            'contact_phone',
            'emergency_contact_name',
            'emergency_contact_phone',
            'insurance_id',
        ],
        'soap_notes' => [
            'subjective',
            'objective',
            'assessment',
            'plan',
            'ai_summary',
        ],
        'manual_drafts' => [
            'draft',
            'conclusion',
        ],
        'documents' => [
            'ocr_text',
            'ocr_raw',
        ],
        'prescriptions' => [
            'instructions',
            'free_text',
        ],
        'clinical_requests' => [
            'body',
        ],
        'encounters' => [
            'chief_complaint',
        ],
        'appointments' => [
            'reason_for_visit',
            'notes',
        ],
        'echocardiograms' => [
            'notes',
            'valve_findings',
            'wall_motion',
        ],
        'treadmill_tests' => [
            'notes',
            'st_segment_changes',
            'arrhythmias',
            'symptoms_during_test',
        ],
        'doppler_studies' => [
            'notes',
            'valve_findings',
        ],
    ];

    public function up(): void
    {
        // Ciphertext is several times longer than the original identifiers, so
        // these short varchar columns must become text before the first write.
        Schema::table('patients', function (Blueprint $table) {
            $table->text('contact_phone')->nullable()->change();
            $table->text('emergency_contact_name')->nullable()->change();
            $table->text('emergency_contact_phone')->nullable()->change();
            $table->text('insurance_id')->nullable()->change();
        });

        $this->changeOcrRawColumn('text');

        foreach (self::COLUMNS as $table => $columns) {
            $this->encryptExistingRows($table, $columns);
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->decryptExistingRows($table, $columns);
        }

        $this->changeOcrRawColumn('json');

        Schema::table('patients', function (Blueprint $table) {
            $table->string('contact_phone', 30)->nullable()->change();
            $table->string('emergency_contact_name', 100)->nullable()->change();
            $table->string('emergency_contact_phone', 30)->nullable()->change();
            $table->string('insurance_id', 50)->nullable()->change();
        });
    }

    /**
     * `documents.ocr_raw` was created as a PostgreSQL `json` column. Ciphertext
     * is not valid JSON, so the column has to become text before it can hold an
     * encrypted payload (and turn back into json on rollback).
     */
    private function changeOcrRawColumn(string $type): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE "documents" ALTER COLUMN "ocr_raw" TYPE %s USING "ocr_raw"::%s',
                $type,
                $type
            ));

            return;
        }

        Schema::table('documents', function (Blueprint $table) use ($type) {
            $type === 'json'
                ? $table->json('ocr_raw')->nullable()->change()
                : $table->text('ocr_raw')->nullable()->change();
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function encryptExistingRows(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->chunkById(200, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null || $this->isEncrypted($value)) {
                            continue;
                        }

                        $changes[$column] = Crypt::encryptString((string) $value);
                    }

                    if ($changes !== []) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            }, 'id');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function decryptExistingRows(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->chunkById(200, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null) {
                            continue;
                        }

                        $plain = $this->decrypt($value);

                        if ($plain !== null) {
                            $changes[$column] = $plain;
                        }
                    }

                    if ($changes !== []) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            }, 'id');
    }

    private function isEncrypted(mixed $value): bool
    {
        return is_string($value) && $value !== '' && $this->decrypt($value) !== null;
    }

    private function decrypt(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }
};
