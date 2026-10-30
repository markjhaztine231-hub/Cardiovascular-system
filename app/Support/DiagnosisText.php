<?php

namespace App\Support;

/**
 * Normalizes free-text assessment strings and decides whether they describe a
 * real diagnosis.
 *
 * The consultation form lets a doctor record any reason for a visit, including
 * visits that are only for clearance or for a doctor-requested review. Those
 * reasons belong in the clinical note, but they must never be counted as
 * diagnoses in monthly statistics or the AI narrative.
 *
 * Two deliberately different levels:
 *
 *  - normalize(): the write-path contract. It only removes unambiguous filler
 *    ("N/A", "none", "-"), so notes such as "For clearance" survive in
 *    soap_notes.assessment.
 *  - asDiagnosis() / key(): the read-path contract. They additionally reject
 *    visit reasons (clearance, doctor request, follow-up) and meaningless
 *    result words ("normal", "unremarkable").
 */
final class DiagnosisText
{
    /** Values that carry no clinical content at all. */
    private const PLACEHOLDERS = [
        'n/a',
        'na',
        'n.a',
        'none',
        'no',
        'nil',
        'null',
        'not applicable',
        'not available',
        'no diagnosis',
        'no diagnoses',
        'no dx',
        'nothing',
        'unknown',
        'not known',
        'not recorded',
        'no findings',
    ];

    /** Whole-value phrases that describe a visit reason or a non-result. */
    private const NON_DIAGNOSTIC_PHRASES = [
        'normal',
        'normal findings',
        'normal study',
        'unremarkable',
        'negative',
        'negative findings',
        'within normal limits',
        'wnl',
    ];

    /** Substrings that mark a visit reason rather than a diagnosis. */
    private const NON_DIAGNOSTIC_PATTERNS = [
        '/\bclearance\b/u',
        '/^cleared\b/u',
        '/\b(?:doctor|physician)(?:\'s|’s)?\s+request(?:ing|ed)?\b/u',
        '/\brequest(?:ed)?\s+by\s+(?:the\s+)?(?:doctor|physician)\b/u',
        '/\bfor\s+(?:the\s+)?(?:doctor|physician)(?:\'s|’s)?\s+request\b/u',
        '/\bper\s+(?:the\s+)?(?:doctor|physician)(?:\'s|’s)?\s+request\b/u',
        '/\bconsult(?:ation)?\s+only\b/u',
        '/^fit(?:\s+(?:for|to)\b.*)?$/u',
    ];

    /**
     * Storage-safe cleanup. Returns the trimmed, whitespace-collapsed note, or
     * null when the value is empty or true filler only.
     */
    public static function normalize(?string $value): ?string
    {
        $clean = self::clean($value);

        if ($clean === null) {
            return null;
        }

        return self::isPlaceholder($clean) ? null : $clean;
    }

    /**
     * Report-safe diagnosis text. Returns null for placeholders and for visit
     * reasons that must not be counted as diagnoses.
     */
    public static function asDiagnosis(?string $value): ?string
    {
        $clean = self::normalize($value);

        if ($clean === null) {
            return null;
        }

        return self::isNonDiagnostic($clean) ? null : $clean;
    }

    /**
     * Stable, case/whitespace-insensitive grouping key for a real diagnosis.
     * Mirrors the normalization already used by diagnosis_by_age so a stored
     * report and a freshly generated one bucket the same text together.
     */
    public static function key(?string $value): ?string
    {
        $diagnosis = self::asDiagnosis($value);

        if ($diagnosis === null) {
            return null;
        }

        $key = self::comparison($diagnosis);

        return $key === '' ? null : $key;
    }

    private static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Normalize non-breaking and other Unicode spaces before collapsing.
        $value = preg_replace('/[\x{00A0}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function isPlaceholder(string $value): bool
    {
        $comparison = self::comparison($value);

        // Punctuation used as filler ("-", "--", "?") reduces to nothing.
        if ($comparison === '') {
            return true;
        }

        // Covers n/a, N / A, n.a and na without enumerating every spacing.
        if (preg_match('/^n\s*[\/\.]?\s*a\.?$/u', $comparison) === 1) {
            return true;
        }

        return in_array($comparison, self::PLACEHOLDERS, true);
    }

    private static function isNonDiagnostic(string $value): bool
    {
        $comparison = self::comparison($value);

        if ($comparison === '' || in_array($comparison, self::NON_DIAGNOSTIC_PHRASES, true)) {
            return true;
        }

        foreach (self::NON_DIAGNOSTIC_PATTERNS as $pattern) {
            if (preg_match($pattern, $comparison) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function comparison(string $value): string
    {
        $value = self::clean($value) ?? '';
        $value = mb_strtolower($value, 'UTF-8');

        // Trim filler punctuation on both ends. Keep internal punctuation so
        // genuinely different diagnoses are not merged.
        $value = preg_replace('/^[\s\.\,\;\:\-\_\?\(\)\[\]\{\}]+/u', '', $value) ?? $value;
        $value = preg_replace('/[\s\.\,\;\:\-\_\?\(\)\[\]\{\}]+$/u', '', $value) ?? $value;

        return trim($value);
    }
}
