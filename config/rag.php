<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retrieval-Augmented Generation (pgvector)
    |--------------------------------------------------------------------------
    |
    | This RAG index contains Philippine Heart Association guideline text only.
    | Patient records, SOAP notes, documents and diagnoses must never be sent
    | to the embedding provider.
    |
    | Retrieval is opt-in: when disabled, or when pgvector/the index is not
    | ready, AiController falls back to its local term-frequency search.
    |
    */

    'enabled' => (bool) env('RAG_ENABLED', false),

    'embedding' => [
        // OpenRouter embeddings endpoint: POST /api/v1/embeddings
        'model' => env('RAG_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
        'dimensions' => (int) env('RAG_EMBEDDING_DIMENSIONS', 1536),
        'batch_size' => (int) env('RAG_EMBEDDING_BATCH_SIZE', 32),
        'timeout' => (int) env('RAG_EMBEDDING_TIMEOUT', 60),
    ],

    'table' => 'rag_chunks',

    // Number of guideline sections injected into the prompt.
    'top_k' => (int) env('RAG_TOP_K', 3),

    // Cosine similarity a chunk must reach to be used. 0 = accept anything.
    'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.2),

    'guidelines' => [
        'source' => 'pha-cad-2014',
        'url' => env('RAG_GUIDELINE_URL', 'https://www.philheart.org/images/guidelines/cad2014.pdf'),
        'chunk_chars' => (int) env('RAG_CHUNK_CHARS', 1200),
        // Cached copy of the parsed LlamaCloud markdown sections.
        'cache_key' => 'philheart_cad_2014_chunks_v3',
        'cache_days' => 30,
    ],

];
