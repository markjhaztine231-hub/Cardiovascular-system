<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('npi', 20)->nullable()->unique(); // National Provider Identifier
            $table->string('license_number', 50)->nullable();
            $table->string('specialty', 100)->nullable();
            $table->string('credentials', 50)->nullable(); // MD | DO | NP | PA | RN

            $table->timestamps();

            $table->index(['specialty', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
