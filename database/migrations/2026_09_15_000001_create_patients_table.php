<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->string('mrn', 30)->nullable()->unique(); // Medical Record Number
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 10)->nullable(); // Male | Female | Other | Unknown

            $table->string('blood_type', 5)->nullable(); // A+ | A- | B+ | B- | AB+ | AB- | O+ | O-
            $table->string('contact_phone', 30)->nullable();
            $table->text('address')->nullable();

            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            $table->text('allergies')->nullable();

            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_id', 50)->nullable();

            $table->timestamps();

            $table->index(['date_of_birth', 'sex']);
            $table->index('blood_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
