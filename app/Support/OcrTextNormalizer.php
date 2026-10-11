<?php

namespace App\Support;

/**
 * Turns an OCR engine's raw output into readable plain text before it lands in
 * `documents.ocr_text`.
 *
 * The hosted model (PaddleOCR-VL) recognises documents into Markdown, and for
 * pages with tables or complex layouts that Markdown carries inline HTML
 * (`<div>`, `<tr>`, `<td>`, `<br>`...). Storing it verbatim leaks raw tags
 * into the Converted text panel and can confuse PrescriptionTextParser, which
 * parses line by line.
 *
 * The normalizer strips the markup but keeps the readable content and, most
 * importantly, the line breaks: PrescriptionTextParser splits on /\R/ and its
 * rules are anchored to line starts.
 */
class OcrTextNormalizer
{
    public static function toReadableText(string $markdown): string
    {
        $text = self::stripInlineHtml($markdown);

        // Markdown links and images reduce to their visible text.
        $text = (string) preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $text);
        $text = (string) preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $text);

        // Emphasis and inline-code markers show up literally otherwise. Single
        // * and _ are left alone: medical text can legitimately contain them.
        $text = str_replace(['**', '`'], '', $text);
        $text = (string) preg_replace('/(?<![\p{L}\p{N}])__(?![\p{L}\p{N}])/u', '', $text);

        // Markdown table separator rows ("|---|---|") carry no content.
        $text = (string) preg_replace('/^\s*\|[\s:\-|]+\|?\s*$/mu', '', $text);

        // Tidy whitespace while keeping line separation for the parser.
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/[ \t]*\R/u', "\n", $text);
        $text = (string) preg_replace('/\n{2,}/u', "\n", $text);

        $lines = array_map('trim', explode("\n", $text));

        return trim(implode("\n", $lines));
    }

    private static function stripInlineHtml(string $text): string
    {
        // Decode entities first so escaped markup ("&lt;div&gt;") is treated
        // like the real thing and stripped rather than shown literally.
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // <br> and horizontal rules end a line.
        $text = (string) preg_replace('/<br\s*\/?>|<hr[^>]*>/iu', "\n", $text);

        // Block-level and row-opening tags start a fresh line so cell content
        // never gets glued together ("<td>A</td><td>B</td>" -> "A B").
        $text = (string) preg_replace(
            '/<\/?(?:p|div|tr|li|ul|ol|table|thead|tbody|tfoot|caption|section|article|header|footer|h[1-6]|blockquote)(?:\s[^>]*)?>/iu',
            "\n",
            $text
        );

        // Table cells separate their contents with a space.
        $text = (string) preg_replace('/<\/?(?:td|th)(?:\s[^>]*)?>/iu', ' ', $text);

        // Any surviving tags (span, b, strong, i, u, font, img...) are dropped.
        return (string) preg_replace('/<[^>]+>/u', '', $text);
    }
}