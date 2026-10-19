<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echocardiograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            // Chambers & function
            $table->decimal('lvef_percent', 4, 1)->nullable();        // Left ventricular ejection fraction
            $table->decimal('lvfs_percent', 4, 1)->nullable();        // LV fractional shortening
            $table->decimal('lv_mass_grams', 6, 1)->nullable();
            $table->decimal('tapse_mm', 4, 1)->nullable();            // Tricuspid annular plane systolic excursion
            $table->decimal('ivs_mm', 4, 1)->nullable();              // Interventricular septum
            $table->decimal('lvpw_mm', 4, 1)->nullable();              // LV posterior wall
            $table->decimal('lvidd_mm', 4, 1)->nullable();             // LV internal diastolic dimension
            $table->decimal('lvids_mm', 4, 1)->nullable();             // LV internal systolic dimension
            $table->decimal('aortic_root_mm', 4, 1)->nullable();
            $table->decimal('la_diameter_mm', 4, 1)->nullable();       // Left atrium
            $table->string('rv_size', 30)->nullable();

            $table->boolean('pericardial_effusion')->default(false);
            $table->text('valve_findings')->nullable();
            $table->text('wall_motion')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echocardiograms');
    }
};
