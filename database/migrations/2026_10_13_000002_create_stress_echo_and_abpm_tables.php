<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stress_echocardiograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            // Free-form report values keyed by the field map shared by the
            // Stress Echo PDF blade and the doctor's fill-in form.
            $table->json('payload')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ambulatory_blood_pressure_monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            // Like stress_echocardiograms — ABPM has no fixed measurement grid,
            // just the report blanks from the PDF form.
            $table->json('payload')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambulatory_blood_pressure_monitorings');
        Schema::dropIfExists('stress_echocardiograms');
    }
};
