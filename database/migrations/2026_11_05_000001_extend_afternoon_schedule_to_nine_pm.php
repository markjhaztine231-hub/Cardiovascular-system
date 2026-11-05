<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The afternoon shift now runs 1:00 PM - 9:00 PM. Schedules created by the
     * previous shift picker stored exactly 13:00-18:00; extend only those rows
     * so custom or legacy doctor ranges are left untouched.
     */
    public function up(): void
    {
        DB::table('doctor_schedules')
            ->where('start_time', '13:00:00')
            ->where('end_time', '18:00:00')
            ->update(['end_time' => '21:00:00']);
    }

    public function down(): void
    {
        // Deliberately left alone: shortening every 13:00-21:00 row would
        // silently change schedules saved with the new afternoon shift.
    }
};
