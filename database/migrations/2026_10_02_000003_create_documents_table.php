<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            $table->string('type', 40)->default('prescription'); // prescription | lab_result | referral | other
            $table->string('original_filename', 255)->nullable();
            $table->string('image_path');

            $table->longText('ocr_text')->nullable();
            $table->json('ocr_raw')->nullable();
            $table->decimal('ocr_confidence', 4, 3)->nullable();

            // pending | processing | completed | failed
            $table->string('ocr_status', 20)->default('pending');
            $table->text('ocr_error')->nullable();

            $table->timestamps();

            // Doctor reads every document attached to an appointment at consult start
            $table->index(['appointment_id', 'created_at']);
            // Patient history across all appointments
            $table->index(['patient_id', 'created_at']);
            // Receptionist dashboard: anything still awaiting OCR
            $table->index(['ocr_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
