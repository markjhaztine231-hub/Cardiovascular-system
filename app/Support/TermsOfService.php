<?php

namespace App\Support;

use RuntimeException;

/**
 * Single source of truth for the published Terms of Service.
 *
 * The wording lives in resources/content/terms-of-service.json, extracted
 * verbatim from AI-Cardio_Terms_of_Service-1.pdf. Legal text is kept in a data
 * file rather than in a Vue template so the same copy is rendered by the public
 * terms page, quoted on the registration form, and versioned on the user
 * record at signup.
 */
class TermsOfService
{
    /** Path relative to the project root. */
    private const JSON_PATH = 'resources/content/terms-of-service.json';

    /** Memoised so a single request reads the file at most once. */
    private static ?array $cached = null;

    /**
     * Full terms payload: title, entity, effective date, jurisdiction and the
     * ordered content blocks (paragraph | section | subsection | bullets).
     *
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $path = base_path(self::JSON_PATH);

        if (! is_file($path)) {
            throw new RuntimeException('Terms of Service content file is missing: '.self::JSON_PATH);
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            throw new RuntimeException('Terms of Service content file is malformed: '.self::JSON_PATH);
        }

        return self::$cached = $decoded;
    }

    /**
     * Stable identifier recorded against each accepting user.
     *
     * Derived from the document's effective date rather than a counter, so the
     * value is stable across deploys and changes only when the legal text is
     * reissued. Timestamps are NOT used here: they would make every signup
     * look like a different version of the same document.
     */
    public static function version(): string
    {
        $effective = (string) (self::payload()['effective_date'] ?? '');
        $iso = strtotime($effective);

        return $iso !== false ? date('Y-m-d', $iso) : 'unknown';
    }

    public static function title(): string
    {
        return (string) (self::payload()['title'] ?? 'Terms of Service');
    }

    /**
     * Short label for the registration checkbox, e.g. "Terms of Service and
     * Patient Agreement".
     *
     * The document's cover renders the title in all caps, which is correct on
     * the published page but wrong inside a sentence, so the words are folded
     * to sentence case with the usual connecting words left lower-case.
     */
    public static function shortTitle(): string
    {
        $small = [
            'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor',
            'of', 'on', 'or', 'per', 'the', 'to', 'via', 'vs',
        ];

        $words = preg_split('/\s+/', mb_strtolower(trim(self::title()))) ?: [];
        $cased = [];

        foreach ($words as $index => $word) {
            $cased[] = $index > 0 && in_array($word, $small, true)
                ? $word
                : ucfirst($word);
        }

        return implode(' ', $cased);
    }

    /** Reset the memoised payload. Intended for tests. */
    public static function flush(): void
    {
        self::$cached = null;
    }
}
