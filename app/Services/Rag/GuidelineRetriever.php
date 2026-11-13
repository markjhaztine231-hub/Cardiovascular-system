<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Semantic search over the guideline index.
 *
 * Returns null whenever RAG is disabled, unconfigured or unreachable, so the
 * caller can fall back to the existing term-frequency retrieval.
 */
class GuidelineRetriever
{
    public function __construct(
        private readonly OpenRouterEmbedder $embedder,
    ) {}

    /**
     * Formatted guideline sections for the AI prompt, or null to fall back.
     */
    public function retrieve(string $query, ?int $topN = null): ?string
    {
        if (! config('rag.enabled')) {
            return null;
        }

        $rows = $this->search($query, $topN);

        if ($rows === []) {
            return null;
        }

        return implode("\n\n---\n\n", array_map(
            static fn ($row) => ($row->title ? '## '.$row->title."\n" : '').$row->content,
            $rows,
        ));
    }

    /**
     * @return array<int, object{id: int, title: string|null, content: string, similarity: float|string}>
     */
    public function search(string $query, ?int $topN = null): array
    {
        $query = trim($query);

        if ($query === '' || ! RagSchema::available()) {
            return [];
        }

        try {
            $vector = VectorLiteral::format($this->embedder->embed($query));
        } catch (Throwable $e) {
            Log::warning('RAG query embedding failed; falling back to keyword search: '.$e->getMessage());

            return [];
        }

        $limit = min(20, max(1, $topN ?? (int) config('rag.top_k', 3)));
        $source = (string) config('rag.guidelines.source');

        try {
            $rows = DB::select(
                'select id, title, content, 1 - (embedding <=> ?::vector) as similarity
                   from '.RagSchema::TABLE.'
                  where source = ?
                    and embedding is not null
                  order by embedding <=> ?::vector
                  limit '.$limit,
                [$vector, $source, $vector],
            );
        } catch (Throwable $e) {
            Log::warning('RAG search failed; falling back to keyword search: '.$e->getMessage());

            return [];
        }

        $min = (float) config('rag.min_similarity', 0);

        return array_values(array_filter(
            $rows,
            static fn ($row) => (float) $row->similarity >= $min,
        ));
    }
}
