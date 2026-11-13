<?php

namespace App\Services\Rag;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenRouter embeddings client.
 *
 * Only guideline text is ever sent here. Patient data must not pass through
 * this class (see the RAG scope decision in config/rag.php).
 */
class OpenRouterEmbedder
{
    public const ENDPOINT = 'https://openrouter.ai/api/v1/embeddings';

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0];
    }

    /**
     * Embeds a batch of strings, preserving input order.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $texts): array
    {
        $texts = array_values(array_map(static fn ($text) => (string) $text, $texts));

        if ($texts === []) {
            return [];
        }

        $apiKey = config('services.openrouter.api_key');
        if (blank($apiKey)) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        $payload = [
            'model' => config('rag.embedding.model'),
            'input' => $texts,
        ];

        $response = $this->send($payload, (string) $apiKey);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'OpenRouter embedding request failed with HTTP %d: %s',
                $response->status(),
                mb_substr($response->body(), 0, 500),
            ));
        }

        $vectors = $this->parse($response->json('data'));

        if (count($vectors) !== count($texts)) {
            throw new RuntimeException(sprintf(
                'OpenRouter returned %d embeddings for %d inputs.',
                count($vectors),
                count($texts),
            ));
        }

        return $vectors;
    }

    private function send(array $payload, string $apiKey): Response
    {
        $attempts = 0;
        $timeout = max(5, (int) config('rag.embedding.timeout', 60));

        while (true) {
            $attempts++;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => url('/'),
                'X-Title' => 'CardioCare EMR',
            ])
                ->timeout($timeout)
                ->post(self::ENDPOINT, $payload);

            // Retry transient upstream failures only; a bad model or key must
            // fail fast so the operator sees the real error.
            if ($response->successful() || ! $response->serverError() || $attempts >= 3) {
                return $response;
            }

            sleep($attempts);
        }
    }

    /**
     * @param  mixed  $data
     * @return array<int, array<int, float>>
     */
    private function parse($data): array
    {
        if (! is_array($data)) {
            throw new RuntimeException('OpenRouter embedding response has no data array.');
        }

        // The API returns an `index` per item; sort before mapping back onto
        // inputs so a reordered batch cannot attach the wrong vector.
        usort($data, static fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $dimensions = (int) config('rag.embedding.dimensions', RagSchema::DIMENSIONS);
        $vectors = [];

        foreach ($data as $item) {
            $embedding = $item['embedding'] ?? null;

            if (! is_array($embedding) || $embedding === []) {
                throw new RuntimeException('OpenRouter embedding response contains an empty vector.');
            }

            if (count($embedding) !== $dimensions) {
                throw new RuntimeException(sprintf(
                    'Embedding has %d dimensions but RAG_EMBEDDING_DIMENSIONS is %d.',
                    count($embedding),
                    $dimensions,
                ));
            }

            $vectors[] = array_map(static fn ($value) => (float) $value, $embedding);
        }

        return $vectors;
    }
}
