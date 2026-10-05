<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records the patient-signed acceptance captured at public registration.
     *
     * Both columns are nullable: accounts created by the Clinic Administration
     * panel, by seeders, or before this release were not asked to consent, and
     * Section 1.2 of the Agreement treats the checkbox as the moment assent
     * becomes binding, so retro-filling a value would misstate the record.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('two_factor_confirmed_at');
            $table->string('terms_version', 50)->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'terms_version']);
        });
    }
};
