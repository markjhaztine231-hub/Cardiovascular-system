<?php

namespace App\Console\Commands;

use App\Models\RagChunk;
use App\Services\Rag\GuidelineIndexer;
use App\Services\Rag\GuidelineRepository;
use App\Services\Rag\RagSchema;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan command: rag:index
 *
 * Embeds the PHA guideline sections into the pgvector index. Guideline text
 * only — no patient records are read or sent to OpenRouter.
 *
 * Usage:
 *   php artisan rag:index --dry-run    # show what would be embedded
 *   php artisan rag:index              # embed missing/changed chunks
 *   php artisan rag:index --force      # re-embed everything
 */
class IndexGuidelines extends Command
{
    protected $signature = 'rag:index
                            {--force : Re-embed chunks that already have a vector.}
                            {--dry-run : Show chunk counts without calling OpenRouter.}';

    protected $description = 'Embed the PHA guideline sections into the pgvector RAG index.';

    public function handle(GuidelineRepository $guidelines, GuidelineIndexer $indexer): int
    {
        if (! RagSchema::ensure()) {
            $this->error('pgvector is not available on this database.');
            $this->line('On RDS PostgreSQL the vector extension must exist:');
            $this->line('  CREATE EXTENSION IF NOT EXISTS vector;');
            $this->line('Ensure the DB user is a member of rds_superuser, then rerun.');

            return self::FAILURE;
        }

        $this->components->info('Downloading and chunking the PHA guidelines...');

        try {
            $chunks = $guidelines->chunks();
        } catch (Throwable $e) {
            $this->error('Could not load guideline chunks: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($chunks === []) {
            $this->error('No guideline chunks were produced. Check LLAMA_CLOUD_API_KEY and the guideline URL.');

            return self::FAILURE;
        }

        $embedded = RagChunk::query()
            ->where('source', config('rag.guidelines.source'))
            ->whereNotNull('embedding')
            ->count();

        $this->line(sprintf('Chunks parsed: %d', count($chunks)));
        $this->line(sprintf('Already embedded: %d', $embedded));
        $this->line(sprintf('Model: %s (%d dimensions)', config('rag.embedding.model'), RagSchema::DIMENSIONS));

        if ($this->option('dry-run')) {
            $this->info('Dry run: no embeddings were requested.');

            return self::SUCCESS;
        }

        try {
            $stats = $indexer->index((bool) $this->option('force'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Total', 'Embedded', 'Skipped', 'Removed'],
            [[$stats['total'], $stats['embedded'], $stats['skipped'], $stats['deleted']]],
        );
        $this->info('RAG index is up to date.');

        if (! config('rag.enabled')) {
            $this->warn('RAG_ENABLED is false — retrieval is still using keyword search.');
        }

        return self::SUCCESS;
    }
}
