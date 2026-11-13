<?php

namespace Tests\Feature\Rag;

use App\Services\Rag\OpenRouterEmbedder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenRouterEmbedderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.api_key' => 'test-key',
            'rag.embedding.model' => 'openai/text-embedding-3-small',
            'rag.embedding.dimensions' => 3,
            'rag.embedding.timeout' => 5,
        ]);
    }

    public function test_it_embeds_a_batch_and_preserves_input_order(): void
    {
        Http::fake([
            OpenRouterEmbedder::ENDPOINT => Http::response([
                'data' => [
                    ['index' => 1, 'embedding' => [0.4, 0.5, 0.6]],
                    ['index' => 0, 'embedding' => [0.1, 0.2, 0.3]],
                ],
            ]),
        ]);

        $vectors = (new OpenRouterEmbedder)->embedMany(['first', 'second']);

        $this->assertEquals([0.1, 0.2, 0.3], $vectors[0]);
        $this->assertEquals([0.4, 0.5, 0.6], $vectors[1]);

        Http::assertSent(function (Request $request) {
            return $request->url() === OpenRouterEmbedder::ENDPOINT
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'openai/text-embedding-3-small'
                && $request['input'] === ['first', 'second'];
        });
    }

    public function test_it_rejects_missing_api_key_before_calling_the_api(): void
    {
        config(['services.openrouter.api_key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENROUTER_API_KEY');

        try {
            (new OpenRouterEmbedder)->embed('guideline text');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_rejects_http_errors(): void
    {
        Http::fake([
            OpenRouterEmbedder::ENDPOINT => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401');

        (new OpenRouterEmbedder)->embed('guideline text');
    }

    public function test_it_rejects_wrong_dimensions(): void
    {
        Http::fake([
            OpenRouterEmbedder::ENDPOINT => Http::response([
                'data' => [['index' => 0, 'embedding' => [0.1, 0.2]]],
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('dimensions');

        (new OpenRouterEmbedder)->embed('guideline text');
    }

    public function test_it_rejects_a_partial_batch(): void
    {
        Http::fake([
            OpenRouterEmbedder::ENDPOINT => Http::response([
                'data' => [['index' => 0, 'embedding' => [0.1, 0.2, 0.3]]],
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('1 embeddings for 2 inputs');

        (new OpenRouterEmbedder)->embedMany(['one', 'two']);
    }
}
