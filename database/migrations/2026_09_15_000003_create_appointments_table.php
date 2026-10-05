<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->restrictOnDelete();

            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('service_type', 50)->default('General Cardiology');
            $table->string('status', 30)->default('Scheduled'); // Scheduled | Confirmed | In Progress | Completed | Cancelled | Rescheduled | No Show
            $table->text('reason_for_visit')->nullable();
            $table->text('notes')->nullable();

            $table->string('google_event_id', 100)->nullable();
            $table->string('google_event_url')->nullable();

            $table->date('reschedule_requested_date')->nullable();
            $table->time('reschedule_requested_time')->nullable();

            $table->timestamps();

            // Scheduling lookups: dashboard filters by patient + date + status
            $table->index(['patient_id', 'scheduled_date', 'status']);
            // Provider dashboard: today's list by doctor
            $table->index(['provider_id', 'scheduled_date']);
            // Status + date for calendar views / no-show sweeps
            $table->index(['status', 'scheduled_date']);

            // Partial index: only pending reschedule requests (ANALYZE-able, tiny tree)
            $table->index(
                ['reschedule_requested_date'],
                'idx_appointments_reschedule_pending'
            )->where('reschedule_requested_date IS NOT NULL AND status != ?', ['Rescheduled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};