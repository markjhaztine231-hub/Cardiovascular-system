<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->restrictOnDelete();

            $table->string('encounter_type', 30)->default('consultation'); // consultation | echo | tmt | doppler | follow_up
            $table->string('status', 30)->default('in_progress'); // in_progress | completed | cancelled
            $table->text('chief_complaint')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            // Core clinical query: all encounters for a patient
            $table->index(['patient_id', 'started_at']);
            // Provider workflow: encounters per doctor ordered by time
            $table->index(['provider_id', 'status', 'started_at']);
            // Encounters derived from an appointment
            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
