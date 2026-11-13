<?php

namespace App\Models;

use App\Services\Rag\RagSchema;
use Illuminate\Database\Eloquent\Model;

class RagChunk extends Model
{
    protected $fillable = [
        'source',
        'title',
        'chunk_index',
        'content',
        'content_hash',
        'token_estimate',
    ];

    /**
     * The embedding is a pgvector column; keep it out of JSON responses and
     * never expose the raw 1536-float string.
     */
    protected $hidden = [
        'embedding',
    ];

    public function hasEmbedding(): bool
    {
        return $this->getAttribute('embedding') !== null;
    }

    public static function vectorReady(): bool
    {
        return RagSchema::available();
    }
}
