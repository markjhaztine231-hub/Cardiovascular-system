<?php

namespace App\Services\Rag;

use App\Models\RagChunk;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Embeds the guideline chunks and stores them in `rag_chunks`.
 *
 * Re-running is cheap: chunks whose hash already has an embedding are
 * skipped unless --force is passed.
 */
class GuidelineIndexer
{
    public function __construct(
        private readonly GuidelineRepository $guidelines,
        private readonly OpenRouterEmbedder $embedder,
    ) {}

    /**
     * @return array{total: int, embedded: int, skipped: int, deleted: int}
     */
    public function index(bool $force = false): array
    {
        if (! RagSchema::available()) {
            throw new RuntimeException(
                'pgvector is not ready. Install the extension and rerun: php artisan rag:index'
            );
        }

        $source = (string) config('rag.guidelines.source');
        $chunks = $this->guidelines->chunks();

        if ($chunks === []) {
            throw new RuntimeException(
                'No guideline chunks were downloaded. Check LLAMA_CLOUD_API_KEY and the guideline URL.'
            );
        }

        $existing = DB::table(RagSchema::TABLE)
            ->where('source', $source)
            ->get(['content_hash', 'embedding'])
            ->keyBy('content_hash');

        $stats = ['total' => count($chunks), 'embedded' => 0, 'skipped' => 0, 'deleted' => 0];
        $batchSize = max(1, (int) config('rag.embedding.batch_size', 32));

        $pending = [];

        foreach ($chunks as $index => $chunk) {
            $current = $existing->get($chunk['hash']);

            if (! $force && $current !== null && $current->embedding !== null) {
                $stats['skipped']++;

                continue;
            }

            $pending[] = ['index' => $index, 'chunk' => $chunk];
        }

        foreach (array_chunk($pending, $batchSize) as $batch) {
            $vectors = $this->embedder->embedMany(array_map(
                fn (array $item) => $this->embeddingText($item['chunk']),
                $batch,
            ));

            DB::transaction(function () use ($batch, $vectors, $source, &$stats) {
                foreach ($batch as $offset => $item) {
                    $this->store($source, $item['index'], $item['chunk'], $vectors[$offset]);
                    $stats['embedded']++;
                }
            });
        }

        // Remove chunks that no longer exist in the current parse (for example
        // after the PDF is re-parsed with better section boundaries).
        $stats['deleted'] = DB::table(RagSchema::TABLE)
            ->where('source', $source)
            ->whereNotIn('content_hash', array_column($chunks, 'hash'))
            ->delete();

        return $stats;
    }

    /**
     * @param  array{title: string|null, content: string, hash: string}  $chunk
     * @param  array<int, float>  $vector
     */
    private function store(string $source, int $index, array $chunk, array $vector): void
    {
        $model = RagChunk::firstOrNew([
            'source' => $source,
            'content_hash' => $chunk['hash'],
        ]);

        $model->fill([
            'title' => $chunk['title'],
            'chunk_index' => $index,
            'content' => $chunk['content'],
            'token_estimate' => (int) ceil(mb_strlen($this->embeddingText($chunk)) / 4),
        ])->save();

        try {
            DB::update(
                'update '.RagSchema::TABLE.' set embedding = ?::vector where id = ?',
                [VectorLiteral::format($vector), $model->getKey()],
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Could not store the guideline embedding: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @param  array{title: string|null, content: string}  $chunk
     */
    private function embeddingText(array $chunk): string
    {
        return $chunk['title'] !== null
            ? $chunk['title']."\n\n".$chunk['content']
            : $chunk['content'];
    }
}
