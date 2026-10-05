<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();

            $table->string('icd_code', 20)->nullable(); // ICD-10 code (e.g. I10, I25.10)
            $table->text('description');
            $table->boolean('is_primary')->default(false);

            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamps();

            // Patient problem-list resolution via encounter join
            $table->index(['encounter_id', 'is_primary']);
            // Anonymous outcome analytics by code without touching PHI tables
            $table->index('icd_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};