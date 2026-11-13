<?php

use App\Services\Rag\RagSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100);
            $table->string('title', 255)->nullable();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->text('content');
            $table->string('content_hash', 64);
            $table->unsignedInteger('token_estimate')->nullable();
            $table->timestamps();

            $table->unique(['source', 'content_hash']);
            $table->index(['source', 'chunk_index']);
        });

        // Best effort: RDS ships pgvector 0.8.1, but a local PostgreSQL may
        // not have it installed. Without it the table still exists, the
        // retriever falls back to term-frequency search, and `rag:index`
        // can install the vector column later via RagSchema::ensure().
        RagSchema::ensure();
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_chunks');
    }
};
