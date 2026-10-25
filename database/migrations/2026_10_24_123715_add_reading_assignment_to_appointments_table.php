<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('reader_provider_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('providers')
                ->nullOnDelete();
            $table->date('reading_date')->nullable()->after('reader_provider_id');
            $table->string('reading_status', 20)->nullable()->after('reading_date');
            $table->timestamp('reading_completed_at')->nullable()->after('reading_status');

            $table->index(['reader_provider_id', 'reading_status', 'reading_date']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['reader_provider_id', 'reading_status', 'reading_date']);
            $table->dropConstrainedForeignId('reader_provider_id');
            $table->dropColumn(['reading_date', 'reading_status', 'reading_completed_at']);
        });
    }
};
