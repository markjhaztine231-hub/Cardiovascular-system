<?php

namespace Tests\Unit;

use App\Support\OcrTextNormalizer;
use PHPUnit\Framework\TestCase;

class OcrTextNormalizerTest extends TestCase
{
    public function test_plain_text_passes_through_unchanged(): void
    {
        $this->assertSame(
            'Tab Amlodipine 5mg',
            OcrTextNormalizer::toReadableText('Tab Amlodipine 5mg')
        );
    }

    public function test_html_table_cells_become_readable_lines(): void
    {
        $input = '<table><tr><td>Losartan</td><td>50 mg</td></tr>'
            .'<tr><td>Metformin</td><td>500 mg</td></tr></table>';

        $this->assertSame(
            "Losartan 50 mg\nMetformin 500 mg",
            OcrTextNormalizer::toReadableText($input)
        );
    }

    public function test_div_and_br_markers_end_lines(): void
    {
        $this->assertSame(
            "Chief complaint\nchest pain",
            OcrTextNormalizer::toReadableText('<div>Chief complaint</div><br>chest pain')
        );
    }

    public function test_surviving_inline_tags_are_dropped_without_gluing_words(): void
    {
        $this->assertSame(
            'Bold label',
            OcrTextNormalizer::toReadableText('<b>Bold</b> label')
        );
    }

    public function test_html_entities_are_decoded(): void
    {
        $this->assertSame(
            'Tylenol & codeine',
            OcrTextNormalizer::toReadableText('Tylenol &amp; codeine')
        );
    }

    public function test_escaped_markup_is_stripped_like_real_markup(): void
    {
        $this->assertSame(
            "A\nB",
            OcrTextNormalizer::toReadableText('&lt;div&gt;A&lt;/div&gt;&lt;div&gt;B&lt;/div&gt;')
        );
    }

    public function test_markdown_links_and_images_reduce_to_their_text(): void
    {
        $this->assertSame(
            'logo',
            OcrTextNormalizer::toReadableText('![logo](https://x/logo.png)')
        );

        $this->assertSame(
            'see label',
            OcrTextNormalizer::toReadableText('[see label](https://x/plain)')
        );
    }

    public function test_markdown_emphasis_markers_are_removed(): void
    {
        $this->assertSame(
            'Take on an empty stomach',
            OcrTextNormalizer::toReadableText('Take on an **empty** `stomach`')
        );
    }

    public function test_markdown_table_separator_rows_are_removed(): void
    {
        $input = "Drug Dose\n|---|---|\nLosartan 50 mg";

        $this->assertSame(
            "Drug Dose\nLosartan 50 mg",
            OcrTextNormalizer::toReadableText($input)
        );
    }

    public function test_blank_lines_are_collapsed_to_single_newlines(): void
    {
        $this->assertSame(
            "a\nb",
            OcrTextNormalizer::toReadableText("a\n\n\n\nb")
        );
    }

    public function test_tags_only_input_becomes_empty_string(): void
    {
        $this->assertSame('', OcrTextNormalizer::toReadableText('<div></div>'));
        $this->assertSame('', OcrTextNormalizer::toReadableText(''));
    }
}
