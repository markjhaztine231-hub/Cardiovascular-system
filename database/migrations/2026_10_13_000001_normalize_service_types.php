<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapse every legacy service-type label onto the three canonical types —
     * Consultation, Procedure, Consultation and Procedure — and give the column
     * a default that matches the booking forms.
     */
    public function up(): void
    {
        DB::table('appointments')
            ->where('service_type', 'Lab Exam')
            ->update(['service_type' => 'Procedure']);

        DB::table('appointments')
            ->where('service_type', 'Consultation with Lab Exam')
            ->update(['service_type' => 'Consultation and Procedure']);

        DB::table('appointments')
            ->whereIn('service_type', [
                'ECG & Heart Checkup',
                'Echocardiogram',
                'Echo',
                'TMT',
                'Doppler',
                'General Cardiology',
            ])
            ->update(['service_type' => 'Procedure']);

        DB::table('appointments')
            ->where('service_type', 'Follow-up Consultation')
            ->update(['service_type' => 'Consultation']);

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('service_type', 50)->default('Consultation')->change();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('service_type', 50)->default('General Cardiology')->change();
        });
    }
};