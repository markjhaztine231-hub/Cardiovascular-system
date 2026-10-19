<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treadmill_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            $table->string('protocol', 50)->default('Bruce'); // Bruce | Modified Bruce | Ramp | Balke
            $table->decimal('max_stage', 3, 1)->nullable();
            $table->unsignedSmallInteger('peak_heart_rate_bpm')->nullable();
            $table->unsignedSmallInteger('peak_bp_systolic')->nullable();
            $table->unsignedSmallInteger('peak_bp_diastolic')->nullable();
            $table->decimal('mets_achieved', 4, 1)->nullable();
            $table->unsignedSmallInteger('exercise_duration_seconds')->nullable();
            $table->unsignedSmallInteger('recovery_time_minutes')->nullable();

            $table->text('st_segment_changes')->nullable();
            $table->text('arrhythmias')->nullable();
            $table->text('symptoms_during_test')->nullable();
            $table->string('reason_for_termination', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treadmill_tests');
    }
};
