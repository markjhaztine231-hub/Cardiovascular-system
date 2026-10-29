<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Free-text prescriptions keep `medication_name` as a short label so
            // legacy consumers still render a row, but they carry no structured
            // frequency. Adherence/reporting must skip them instead of guessing.
            $table->boolean('is_freeform')->default(false)->after('instructions');
            $table->text('free_text')->nullable()->after('is_freeform');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['is_freeform', 'free_text']);
        });
    }
};
