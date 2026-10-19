<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();

            $table->jsonb('day_of_week'); // ISO array [1..7] = Mon..Sun
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['provider_id', 'is_active']);
        });

        // Guard: day_of_week must always be a JSON array
        DB::statement(
            "ALTER TABLE doctor_schedules ADD CONSTRAINT doctor_schedules_day_of_week_array_check
             CHECK (jsonb_typeof(day_of_week) = 'array')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
