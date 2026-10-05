<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pharmacokinetic Tri-Zone adherence ("50% Halfway Rule"):
        //   status          = TAKEN | MISSED (null → dose slot still pending)
        //   adherence_flag  = ON_TIME | LATE | EXPIRED_WINDOW | null
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->string('status')->nullable()->after('taken_at');
            $table->string('adherence_flag')->nullable()->after('status');
        });

        // Historical rows that already recorded a taken_at are treated as on-time
        // intakes so monthly adherence figures stay stable after the changeover.
        DB::table('medication_logs')
            ->whereNotNull('taken_at')
            ->update(['status' => 'TAKEN', 'adherence_flag' => 'ON_TIME']);
    }

    public function down(): void
    {
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->dropColumn(['status', 'adherence_flag']);
        });
    }
};
