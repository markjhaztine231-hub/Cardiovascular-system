<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soap_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            $table->text('subjective')->nullable();  // S: patient-reported symptoms
            $table->text('objective')->nullable();   // O: exam findings, vitals summary
            $table->text('assessment')->nullable();  // A: differential dx / diagnosis
            $table->text('plan')->nullable();        // P: treatment plan
            $table->text('ai_summary')->nullable();  // AI-generated abstract

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soap_notes');
    }
};
