<?php

namespace App\Console\Commands;

use App\Services\Rag\GuidelineRetriever;
use App\Services\Rag\RagSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Artisan command: rag:search
 *
 * Manual smoke test for semantic retrieval. It ignores RAG_ENABLED so the
 * index can be verified before turning it on for the application.
 *
 * Usage:
 *   php artisan rag:search "acute coronary syndrome"
 *   php artisan rag:search "hypertension" --top=5
 */
class SearchGuidelines extends Command
{
    protected $signature = 'rag:search
                            {query* : Clinical text to search for.}
                            {--top=5 : Number of guideline sections to return.}';

    protected $description = 'Search the guideline RAG index (guideline text only).';

    public function handle(GuidelineRetriever $retriever): int
    {
        if (! RagSchema::available()) {
            $this->error('pgvector index is not ready. Run: php artisan rag:index');

            return self::FAILURE;
        }

        $query = implode(' ', (array) $this->argument('query'));
        $rows = $retriever->search($query, max(1, (int) $this->option('top')));

        if ($rows === []) {
            $this->warn('No guideline sections matched (or the query embedding failed).');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Similarity', 'Title', 'Preview'],
            array_map(static fn ($row) => [
                $row->id,
                number_format((float) $row->similarity, 4),
                $row->title ?? '—',
                Str::limit(preg_replace('/\s+/', ' ', (string) $row->content), 90),
            ], $rows),
        );

        if (! config('rag.enabled')) {
            $this->warn('RAG_ENABLED is false — the application is still using keyword search.');
        }

        return self::SUCCESS;
    }
}
