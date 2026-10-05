<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a `scope` discriminator so one table can hold both per-doctor and
     * practice-wide reports.
     *
     * Backfill notes:
     *  - provider_id IS NULL is made nullable because a practice-wide row has
     *    no single provider. Rows that are NULL today are the pre-existing bug
     *    where generate() tried to persist a report for a doctor with no
     *    Provider row; those are backfilled as 'provider' so they stay
     *    retrievable rather than being mislabelled as practice reports.
     */
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->string('scope', 20)->default('provider')->after('provider_id');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->foreignId('provider_id')->nullable()->change();
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
        });

        // Existing rows all belong to a single doctor, never the practice.
        DB::table('monthly_reports')->update(['scope' => 'provider']);

        // NOTE: deliberately no UNIQUE(scope, provider_id, month).
        // Regenerating a month appends a new history row by design (see
        // AiController::generateMonthlyReport), so a uniqueness constraint
        // would reject legitimate re-runs. Reads use latest().
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->index(['scope', 'month']);
            $table->index(['scope', 'provider_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropIndex(['scope', 'month']);
            $table->dropIndex(['scope', 'provider_id', 'month']);

            // Restore NOT NULL: practice rows cannot survive the rollback.
            DB::table('monthly_reports')->whereNull('provider_id')->delete();

            $table->dropForeign(['provider_id']);
            $table->foreignId('provider_id')->nullable(false)->change();
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();

            $table->dropColumn('scope');
            $table->index(['provider_id', 'month']);
        });
    }
};
