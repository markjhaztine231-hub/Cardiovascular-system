<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Installs and inspects the pgvector pieces of the RAG index.
 *
 * The migration creates `rag_chunks` everywhere. The `embedding` column and
 * its HNSW index are only added when the PostgreSQL server has pgvector, so a
 * developer machine without the extension can still migrate and run tests.
 */
class RagSchema
{
    public const DIMENSIONS = 1536;

    public const TABLE = 'rag_chunks';

    public const INDEX = 'rag_chunks_embedding_hnsw';

    /**
     * Create the vector extension, column and index when the server supports
     * it. Safe to call repeatedly.
     */
    public static function ensure(): bool
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable(self::TABLE)) {
            return false;
        }

        try {
            // Check first: a failed CREATE EXTENSION aborts the surrounding
            // migration transaction on PostgreSQL.
            if (! self::extensionAvailable()) {
                Log::info('pgvector is not available on this PostgreSQL server; RAG index disabled.');

                return false;
            }

            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

            if (! self::extensionInstalled()) {
                return false;
            }

            if (! self::hasVectorColumn()) {
                DB::statement(sprintf(
                    'ALTER TABLE %s ADD COLUMN embedding vector(%d)',
                    self::TABLE,
                    self::DIMENSIONS,
                ));
            }

            if (! self::hasIndex()) {
                DB::statement(sprintf(
                    'CREATE INDEX %s ON %s USING hnsw (embedding vector_cosine_ops)',
                    self::INDEX,
                    self::TABLE,
                ));
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('pgvector schema setup failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Whether the vector column is usable right now. Never runs DDL.
     */
    public static function available(): bool
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable(self::TABLE)) {
            return false;
        }

        try {
            return self::extensionInstalled() && self::hasVectorColumn();
        } catch (Throwable) {
            return false;
        }
    }

    private static function extensionInstalled(): bool
    {
        return DB::selectOne("select 1 as ok from pg_extension where extname = 'vector'") !== null;
    }

    /**
     * Whether the server ships pgvector at all (installed or not).
     */
    private static function extensionAvailable(): bool
    {
        return DB::selectOne(
            "select 1 as ok from pg_available_extensions where name = 'vector'"
        ) !== null;
    }

    private static function hasVectorColumn(): bool
    {
        $row = DB::selectOne(
            "select atttypid::regtype::text as type
               from pg_attribute
              where attrelid = '".self::TABLE."'::regclass
                and attname = 'embedding'
                and not attisdropped"
        );

        return $row !== null && str_starts_with((string) $row->type, 'vector');
    }

    private static function hasIndex(): bool
    {
        return DB::selectOne(
            'select 1 as ok from pg_indexes where tablename = ? and indexname = ?',
            [self::TABLE, self::INDEX],
        ) !== null;
    }
}
