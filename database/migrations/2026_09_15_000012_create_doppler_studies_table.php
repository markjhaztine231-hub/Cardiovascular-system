<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doppler_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            $table->decimal('ea_ratio', 4, 2)->nullable();                     // E/A ratio
            $table->decimal('tissue_doppler_e', 4, 2)->nullable();             // e' velocity
            $table->decimal('tissue_doppler_a', 4, 2)->nullable();             // a' velocity

            $table->decimal('mitral_gradient', 4, 1)->nullable();
            $table->decimal('aortic_gradient', 4, 1)->nullable();
            $table->decimal('tricuspid_gradient', 4, 1)->nullable();
            $table->decimal('pulmonic_gradient', 4, 1)->nullable();

            $table->string('mitral_regurgitation', 30)->nullable();            // none | mild | moderate | severe
            $table->string('aortic_regurgitation', 30)->nullable();
            $table->string('tricuspid_regurgitation', 30)->nullable();

            $table->text('valve_findings')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doppler_studies');
    }
};