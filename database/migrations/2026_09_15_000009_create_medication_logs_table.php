<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();

            $table->date('log_date');
            $table->time('scheduled_time')->nullable();
            $table->timestamp('taken_at')->nullable();

            $table->timestamps();

            // One adherence record per prescription dose (date + scheduled time)
            $table->unique(
                ['prescription_id', 'log_date', 'scheduled_time'],
                'medication_logs_unique_dose'
            );
            // Adherence sweep: who missed a dose on a given day
            $table->index(['log_date', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
