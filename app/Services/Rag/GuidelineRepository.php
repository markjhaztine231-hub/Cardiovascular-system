<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Downloads, parses and chunks the PHA CAD 2014 guideline PDF.
 *
 * This is the only corpus the RAG index is allowed to embed. The parsed
 * sections are cached because a LlamaCloud parse takes minutes.
 */
class GuidelineRepository
{
    /**
     * @return array<int, array{title: string|null, content: string, hash: string}>
     */
    public function chunks(): array
    {
        $key = (string) config('rag.guidelines.cache_key');

        $cached = Cache::get($key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $markdown = $this->downloadAndParse();
        if ($markdown === null) {
            return [];
        }

        $chunks = $this->chunkMarkdown($markdown, (int) config('rag.guidelines.chunk_chars', 1200));

        // Never cache a failed/empty parse for 30 days.
        if ($chunks !== []) {
            Cache::put($key, $chunks, now()->addDays((int) config('rag.guidelines.cache_days', 30)));
        }

        return $chunks;
    }

    /**
     * Raw markdown from LlamaCloud, or null when parsing failed.
     */
    public function downloadAndParse(): ?string
    {
        $apiKey = config('services.llama.api_key');
        if (blank($apiKey)) {
            Log::warning('PHA guidelines unavailable: LLAMA_CLOUD_API_KEY is missing.');

            return null;
        }

        try {
            $pdf = Http::timeout(60)->get((string) config('rag.guidelines.url'));
            if (! $pdf->successful() || $pdf->body() === '') {
                Log::warning('PHA guidelines unavailable: PDF download failed.');

                return null;
            }

            $tempPath = storage_path('app/temp_cad2014.pdf');
            file_put_contents($tempPath, $pdf->body());

            try {
                $upload = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                    ->attach('file', file_get_contents($tempPath), 'cad2014.pdf')
                    ->post('https://api.cloud.llamaindex.ai/api/parsing/upload');
            } finally {
                @unlink($tempPath);
            }

            if (! $upload->successful()) {
                Log::warning('PHA guidelines unavailable: LlamaParse upload failed.');

                return null;
            }

            $jobId = $upload->json('id');

            for ($attempt = 0; $attempt < 30; $attempt++) {
                sleep(2);

                $status = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                    ->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}");

                if ($status->json('status') === 'SUCCESS') {
                    $markdown = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                        ->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}/result/markdown")
                        ->json('markdown');

                    return is_string($markdown) && trim($markdown) !== '' ? $markdown : null;
                }

                if ($status->json('status') === 'ERROR') {
                    Log::warning('PHA guidelines unavailable: LlamaParse returned ERROR.');

                    return null;
                }
            }

            Log::warning('PHA guidelines unavailable: LlamaParse timed out.');
        } catch (Throwable $e) {
            Log::error('PHA guideline parsing failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Splits markdown on level-2 headings. Each section keeps its heading as
     * the chunk title (used for display and de-duplication) and is capped at
     * $maxLen characters.
     *
     * @return array<int, array{title: string|null, content: string, hash: string}>
     */
    public function chunkMarkdown(string $markdown, int $maxLen = 1200): array
    {
        $sections = preg_split('/^##\s+/m', $markdown) ?: [];
        $chunks = [];
        $seen = [];

        foreach ($sections as $section) {
            $trimmed = trim($section);
            if (mb_strlen($trimmed) < 80) {
                continue;
            }

            [$title, $body] = $this->splitTitle($trimmed);

            $body = mb_strlen($body) > $maxLen
                ? mb_substr($body, 0, $maxLen).'…'
                : $body;

            $hash = hash('sha256', ($title ?? '')."\n".$body);

            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;

            $chunks[] = [
                'title' => $title,
                'content' => $body,
                'hash' => $hash,
            ];
        }

        return $chunks;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function splitTitle(string $section): array
    {
        $parts = preg_split('/\R/', $section, 2) ?: [];
        $first = trim($parts[0] ?? '');
        $rest = trim($parts[1] ?? '');

        // Reject headings that are actually a wrapped body paragraph.
        $looksLikeTitle = $first !== ''
            && mb_strlen($first) <= 200
            && ! str_contains($first, '. ')
            && $rest !== '';

        if (! $looksLikeTitle) {
            return [null, $section];
        }

        $first = preg_replace('/<[^>]*>/', '', $first) ?? $first;
        $first = preg_replace('/^[#*\s_]+|[#*\s_]+$/u', '', $first) ?? $first;
        $first = trim(preg_replace('/\s+/', ' ', $first) ?? $first);

        return [$first !== '' ? $first : null, $rest];
    }
}
