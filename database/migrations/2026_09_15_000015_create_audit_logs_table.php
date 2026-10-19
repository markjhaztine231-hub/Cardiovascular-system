<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();

            $table->string('action', 100);                       // viewed | created | updated | deleted | exported
            $table->string('resource_type', 100)->nullable();    // appointment | encounter | prescription | patient
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->text('description')->nullable();

            $table->jsonb('old_values')->nullable();             // before-image for PHI tamper forensics
            $table->jsonb('new_values')->nullable();             // after-image

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');

            // HIPAA: forward audit — all access to a patient's record
            $table->index(['patient_id', 'created_at']);
        });

        // Chronological compliance read: who did what on a given day.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['action', 'resource_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
