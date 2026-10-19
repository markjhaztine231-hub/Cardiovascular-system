<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();

            $table->string('requesting_physician', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('draft')->nullable();
            $table->text('conclusion')->nullable();
            // [{id, url, name}] — references to the source scan documents
            // (or local blobs) attached to the draft.
            $table->json('attachments')->nullable();

            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_drafts');
    }
};
