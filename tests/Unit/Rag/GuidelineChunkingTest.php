<?php

namespace Tests\Unit\Rag;

use App\Services\Rag\GuidelineRepository;
use App\Services\Rag\VectorLiteral;
use PHPUnit\Framework\TestCase;

class GuidelineChunkingTest extends TestCase
{
    public function test_it_keeps_section_headings_as_titles(): void
    {
        $markdown = $this->intro()
            ."\n## Diagnosis of Stable CAD\n"
            .str_repeat('Evaluate symptoms, risk factors and resting ECG. ', 3)
            ."\n## Long-term Management\n"
            .str_repeat('Start aspirin and a statin unless contraindicated. ', 3);

        $chunks = (new GuidelineRepository)->chunkMarkdown($markdown, 1200);

        $titles = array_values(array_filter(array_column($chunks, 'title')));

        $this->assertContains('Diagnosis of Stable CAD', $titles);
        $this->assertContains('Long-term Management', $titles);
        $this->assertStringNotContainsString('## Diagnosis', $chunks[1]['content']);
    }

    public function test_it_caps_chunk_length_and_marks_truncation(): void
    {
        $markdown = "## Section\n".str_repeat('word ', 500);

        $chunks = (new GuidelineRepository)->chunkMarkdown($markdown, 100);

        $this->assertCount(1, $chunks);
        $this->assertLessThanOrEqual(101, mb_strlen($chunks[0]['content']));
        $this->assertStringEndsWith('…', $chunks[0]['content']);
    }

    public function test_it_drops_short_sections_and_duplicates(): void
    {
        $body = str_repeat('Guideline recommendation text. ', 4);

        $markdown = "## Too short\nnope\n"
            ."\n## Duplicate\n".$body
            ."\n## Duplicate\n".$body;

        $chunks = (new GuidelineRepository)->chunkMarkdown($markdown, 1200);

        $this->assertCount(1, $chunks);
        $this->assertSame('Duplicate', $chunks[0]['title']);
    }

    public function test_hashes_are_stable_for_the_same_section(): void
    {
        $markdown = "## Section\n".str_repeat('Stable content. ', 10);

        $first = (new GuidelineRepository)->chunkMarkdown($markdown)[0]['hash'];
        $second = (new GuidelineRepository)->chunkMarkdown($markdown)[0]['hash'];

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
    }

    public function test_vector_literals_use_pgvector_text_format(): void
    {
        $this->assertSame('[0.5,1,-0.25]', VectorLiteral::format([0.5, 1.0, -0.25]));
        $this->assertSame('[0]', VectorLiteral::format([0.0]));
        $this->assertStringStartsWith('[0.123456789,', VectorLiteral::format([0.1234567891, 1]));
    }

    private function intro(): string
    {
        return str_repeat('Philippine Heart Association guideline introduction. ', 3);
    }
}
