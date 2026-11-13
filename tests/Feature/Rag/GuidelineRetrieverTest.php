<?php

namespace Tests\Feature\Rag;

use App\Services\Rag\GuidelineRetriever;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuidelineRetrieverTest extends TestCase
{
    public function test_it_returns_null_without_calling_anything_when_disabled(): void
    {
        config(['rag.enabled' => false]);
        Http::fake();

        $retriever = $this->app->make(GuidelineRetriever::class);

        $this->assertNull($retriever->retrieve('acute coronary syndrome', 3));
        Http::assertNothingSent();
    }

    public function test_it_returns_null_for_an_empty_query(): void
    {
        config(['rag.enabled' => true]);
        Http::fake();

        $retriever = $this->app->make(GuidelineRetriever::class);

        $this->assertSame([], $retriever->search('   '));
        Http::assertNothingSent();
    }
}
