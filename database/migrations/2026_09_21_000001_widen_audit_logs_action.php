<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit detail lines (e.g. '"Receptionist rescheduled appointment #2 ..."')
        // routinely exceed the original varchar(100), which made PostgreSQL throw
        // [22001] "value too long" on write → 500 on edit-reschedule. Widen it.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 100)->change();
        });
    }
};
