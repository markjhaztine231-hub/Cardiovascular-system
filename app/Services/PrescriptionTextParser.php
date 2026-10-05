<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Turns OCR output from a scanned prescription into one array per medication,
 * shaped to match the `prescriptions` table columns.
 *
 * Engine-independent: it only ever sees text, never an image or an HTTP client.
 *
 * Parsing is line-scoped on purpose. Scanning the whole document for a token
 * cannot tell you *which* drug it belongs to, so "once daily" on drug #1 and
 * "twice daily" on drug #2 collapse into one row. Every field here is extracted
 * from the single line the drug was found on (plus its continuation lines), so
 * drugs never bleed into each other.
 */
class PrescriptionTextParser
{
    private const FREQUENCIES = [
        'once a day' => 'Once daily', 'once daily' => 'Once daily', 'one daily' => 'Once daily',
        'twice a day' => 'Twice daily', 'twice daily' => 'Twice daily', 'two times a day' => 'Twice daily',
        'three times a day' => 'Three times daily', 'three times daily' => 'Three times daily',
        'four times a day' => 'Four times daily', 'four times daily' => 'Four times daily',
        'at bedtime' => 'At bedtime', 'before bed' => 'At bedtime', 'empty stomach' => 'Empty stomach',
        'before meals' => 'Before meals', 'after meals' => 'After meals', 'with meals' => 'With meals',
        'when required' => 'As needed', 'as needed' => 'As needed',
        'od' => 'Once daily', 'qd' => 'Once daily', 'qds' => 'Four times daily',
        'bd' => 'Twice daily', 'bid' => 'Twice daily', 'tds' => 'Three times daily', 'tid' => 'Three times daily',
        'qid' => 'Four times daily', 'hs' => 'At bedtime', 'nocte' => 'At bedtime', 'prn' => 'As needed',
        'sos' => 'As needed', 'ac' => 'Before meals', 'pc' => 'After meals', 'morning' => 'In the morning',
        'evening' => 'In the evening', 'night' => 'At night',
    ];

    private const ROUTES = [
        'oral' => 'Oral', 'po' => 'Oral', 'iv' => 'Intravenous', 'im' => 'Intramuscular',
        'sc' => 'Subcutaneous', 'sq' => 'Subcutaneous', 'sl' => 'Sublingual',
        'sublingual' => 'Sublingual', 'pr' => 'Rectal', 'rectal' => 'Rectal',
        'td' => 'Transdermal', 'transdermal' => 'Transdermal', 'top' => 'Topical', 'topical' => 'Topical',
        'inh' => 'Inhaled', 'inhaled' => 'Inhaled', 'neb' => 'Nebulised', 'nebulised' => 'Nebulised',
        'nebulizer' => 'Nebulised', 'vaginally' => 'Vaginal', 'ophthalmic' => 'Ophthalmic',
    ];

    private const DRUG_FORMS = [
        'tab', 'tabs', 'tablet', 'tablets', 'cap', 'caps', 'capsule', 'capsules',
        'inj', 'injection', 'amp', 'ampoule', 'vial', 'syr', 'syringe',
        'syp', 'syrup', 'susp', 'suspension', 'drops', 'oint', 'ointment',
        'cream', 'gel', 'lotion', 'inh', 'inhaler', 'neb', 'nebuliser',
        'sachet', 'powder', 'patch', 'suppository', 'lozenge', 'pessary',
    ];

    private const DRUG_SUFFIXES = [
        'pril', 'sartan', 'statin', 'vastatin', 'dipine', 'azole', 'olol',
        'formin', 'prazole', 'gliflozin', 'setron', 'tidine', 'floxacin',
        'oxacin', 'cycline', 'caine', 'mab', 'zine', 'idine', 'pam',
    ];

    private const STRENGTH_RE = '/\b\d+(?:\.\d+)?\s*(?:mg|mcg|ug|gm|g|ml|iu|units?|%)(?:\s*\/\s*\d+(?:\.\d+)?\s*(?:mg|mcg|gm|g|ml))?/i';

    private const COUNT_RE = '/\b\d+\s*(?:tab|tabs|tablet|cap|caps|capsule|drops?|sachets?)\b/i';

    private const DURATION_RE = '/\b(?:for|over)?\s*(\d{1,3})\s*[- ]?\s*(day|week|month|year)s?\b/i';

    /**
     * A duration introduced by review wording ("review in 4 weeks") is a review
     * interval for the visit, not a course length for the drug above it.
     */
    private const FOLLOWUP_DURATION_RE = '/\b(?:review|re-?check|come\s+back|return|follow[\s-]?up)\s+(?:in|after|on|within)\s*\d{1,3}\s*[- ]?\s*(?:day|week|month|year)s?\b/i';

    private const DURATION_MULTIPLIERS = [
        'day' => 1, 'week' => 7, 'month' => 30, 'year' => 365,
    ];

    private const HEADER_RE = '/\b(clinic|hospital|polyclinic|pharmacy|dispensary|medical\s+(?:centre|center)|health\s+(?:centre|center)|nursing\s+home|registered\s+medical|medical\s+regi)\b/i';

    private const SIGNATORY_RE = '/^\s*(?:dr\.?|doctor)\s*[A-Z]/i';

    private const DEMOGRAPHIC_RE = '/^\s*(?:patient\s+)?(?:name|age|sex|gender|dob|d\.o\.b|date\s+of\s+birth|mrn|uhid|ip\s*(?:no|number)|address|phone|mobile|tel|contact|weight|height|waist|b\.?p\.?|blood\s+pressure|pulse|temperature)\b\s*[:\-]?/i';

    private const FOOTER_RE = '/^\s*(?:follow[\s-]?up|next\s+(?:visit|review)|come\s+back|review\s+(?:on|in|after|within)|re-?check|return\s+(?:on|in|after|within)|advice?\b|signature|stamp|reg\.?\s*no|consultant|ph\.|tel)\b/i';

    private const BULLET_RE = '/^\s*(?:\d+[.)]|[*\-•▪‣–])\s*/u';

    private const MAX_MEDICATIONS = 25;

    private const MIN_CONFIDENCE = 0.5;

    /**
     * @param  array<int, array{text?: string, confidence?: float|null}>|null  $lines  structured lines straight from `documents.ocr_raw.lines`
     * @return array{medications: array<int, array<string, string|int|null>>, warnings: array<int, string>}
     */
    public function parse(string $text, ?array $lines = null): array
    {
        $candidates = ($lines !== null && $lines !== [])
            ? $this->fromStructuredLines($lines)
            : $this->fromText($text);
        $warnings = $candidates['warnings'];
        $rows = $candidates['lines'];

        $medications = [];

        foreach ($rows as $row) {
            $text = $row['text'];
            $lower = mb_strtolower($text);

            if ($this->isNoise($text, $lower)) {
                continue;
            }

            $isDrugLine = $this->looksLikeDrug($lower);

            if ($isDrugLine) {
                if (count($medications) >= self::MAX_MEDICATIONS) {
                    break;
                }

                $medications[] = $this->buildRow($text);

                continue;
            }

            if ($medications === []) {
                continue;
            }

            $last = array_key_last($medications);

            $this->mergeContinuation($medications[$last], $text, $lower);
        }

        if ($medications === []) {
            $warnings[] = 'No medication lines were recognised in this document.';
        }

        return [
            'medications' => array_values(array_filter($medications, fn (array $m) => $m['medication_name'] !== null)),
            'warnings' => $warnings,
        ];
    }

    /**
     * Flattened convenience shape for the common single-drug case.
     *
     * @return array<string, string|int|null>|null
     */
    public function parseFirst(string $text, ?array $lines = null): ?array
    {
        return $this->parse($text, $lines)['medications'][0] ?? null;
    }

    /**
     * @return array{lines: array<int, array{text: string, confidence: float|null}>, warnings: array<int, string>}
     */
    private function fromText(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $text) ?: []),
            fn (string $line) => $line !== ''
        ));

        return ['lines' => array_map(fn (string $line) => ['text' => $line, 'confidence' => null], $lines), 'warnings' => []];
    }

    /**
     * @param  array<int, array{text?: string, confidence?: float|null}>  $lines
     * @return array{lines: array<int, array{text: string, confidence: float|null}>, warnings: array<int, string>}
     */
    private function fromStructuredLines(array $lines): array
    {
        $kept = [];
        $warnings = [];
        $dropped = 0;

        foreach ($lines as $line) {
            $value = is_array($line) ? trim((string) ($line['text'] ?? '')) : trim((string) $line);

            if ($value === '') {
                continue;
            }

            $confidence = is_array($line) && isset($line['confidence']) ? (float) $line['confidence'] : null;

            if ($confidence !== null && $confidence < self::MIN_CONFIDENCE) {
                $dropped++;

                continue;
            }

            $kept[] = ['text' => $value, 'confidence' => $confidence];
        }

        if ($dropped > 0) {
            $warnings[] = sprintf('%d low-confidence line(s) below %.0f%% were skipped.', $dropped, self::MIN_CONFIDENCE * 100);
        }

        return ['lines' => $kept, 'warnings' => $warnings];
    }

    private function isNoise(string $text, string $lower): bool
    {
        return preg_match(self::HEADER_RE, $lower) === 1
            || preg_match(self::SIGNATORY_RE, $text) === 1
            || preg_match(self::DEMOGRAPHIC_RE, $text) === 1
            || preg_match(self::FOOTER_RE, $lower) === 1;
    }

    private function looksLikeDrug(string $lower): bool
    {
        $stripped = (string) preg_replace(self::BULLET_RE, '', $lower);

        foreach (self::DRUG_FORMS as $form) {
            if (preg_match('/^'.preg_quote($form, '/').'\b/', $stripped) === 1) {
                return true;
            }
        }

        foreach (self::DRUG_SUFFIXES as $suffix) {
            if (preg_match('/\b'.preg_quote($suffix, '/').'\b/', $stripped) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string|int|null>
     */
    private function buildRow(string $line): array
    {
        $lower = mb_strtolower($line);

        $strength = $this->firstGroup(self::STRENGTH_RE, $line) ?? $this->firstGroup(self::COUNT_RE, $line);
        $frequency = $this->lookup($lower, self::FREQUENCIES);
        $route = $this->lookup($lower, self::ROUTES);
        $duration = $this->durationDays($lower);

        $name = $this->drugName($line, $strength, $frequency, $route, $duration['phrase'] ?? null);

        return [
            'medication_name' => $name !== '' ? Str::limit($name, 150, '') : null,
            'dosage' => $strength !== null ? Str::limit($strength, 100, '') : null,
            'frequency' => $frequency,
            'route' => $route,
            'duration_days' => $duration['days'],
            'instructions' => null,
        ];
    }

    /**
     * Fold a non-drug line (frequency/duration tails, or "- once daily for 10 days")
     * into the drug above it.
     *
     * @param  array<string, string|int|null>  $row
     */
    private function mergeContinuation(array &$row, string $text, string $lower): void
    {
        $strength = $this->firstGroup(self::STRENGTH_RE, $text) ?? $this->firstGroup(self::COUNT_RE, $text);
        $frequency = $this->lookup($lower, self::FREQUENCIES);
        $route = $this->lookup($lower, self::ROUTES);
        $duration = $this->durationDays($lower);

        if ($strength !== null && $row['dosage'] === null) {
            $row['dosage'] = Str::limit($strength, 100, '');
        }

        if ($frequency !== null && $row['frequency'] === null) {
            $row['frequency'] = $frequency;
        }

        if ($route !== null && $row['route'] === null) {
            $row['route'] = $route;
        }

        if ($duration['days'] !== null && $row['duration_days'] === null) {
            $row['duration_days'] = $duration['days'];
        }

        $leftover = $this->stripKnown($text, $strength, $frequency, $route, $duration['phrase'] ?? null);
        $leftover = trim(preg_replace('/^[\s\-–•*,;|:]+|[\s\-–•*,;|:]+$/u', '', $leftover) ?? '');

        if ($leftover !== '' && $row['medication_name'] !== null) {
            $existing = trim((string) ($row['instructions'] ?? ''));
            $merged = $existing !== '' ? $existing.' '.$leftover : $leftover;
            $row['instructions'] = Str::limit($merged, 1000, '');
        }
    }

    private function drugName(string $line, ?string $strength, ?string $frequency, ?string $route, ?string $durationPhrase): string
    {
        $remaining = (string) preg_replace(self::BULLET_RE, '', $line);

        // The alternation must stay grouped: ungrouped it binds at the top
        // level and would delete "tab"/"cap"/... from the middle of the line.
        $remaining = (string) preg_replace(
            '/^\s*(?:'.implode('|', array_map(fn (string $f) => preg_quote($f, '/'), self::DRUG_FORMS)).')\b\.?\s*/i',
            '',
            $remaining
        );

        $remaining = $this->stripKnown($remaining, $strength, $frequency, $route, $durationPhrase);

        // "Amlodipine 5mg - 1 tab once daily": the tablet count is dosing
        // detail, not part of the drug name.
        $remaining = (string) preg_replace(self::COUNT_RE, ' ', $remaining);

        $remaining = (string) preg_replace('/\s*[-–•|]\s*(?=[a-z]*\b(?:daily|night|bed|meals|required|week|month|day)\b).*$/i', '', $remaining);
        $remaining = (string) preg_replace('/\s+/', ' ', $remaining);
        $remaining = trim((string) preg_replace('/[\s\-–•|,;:\/]+$|^[\s\-–•|,;:\/]+/', '', $remaining));

        return $remaining;
    }

    private function stripKnown(string $line, ?string $strength, ?string $frequency, ?string $route, ?string $durationPhrase): string
    {
        $needles = array_filter(array_merge([$strength, $durationPhrase], [$frequency, $route]));

        foreach (self::FREQUENCIES as $needle => $label) {
            $needles[] = $needle;
        }

        foreach (self::ROUTES as $needle => $label) {
            $needles[] = $needle;
        }

        $needles = array_unique(array_filter($needles, fn ($n) => is_string($n) && trim($n) !== ''));

        usort($needles, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($needles as $needle) {
            $line = (string) preg_replace('/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/iu', ' ', $line);
        }

        return (string) preg_replace('/\s+/', ' ', $line);
    }

    private function firstGroup(string $pattern, string $subject): ?string
    {
        if (preg_match($pattern, $subject, $m) !== 1) {
            return null;
        }

        $value = trim((string) ($m[0] ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, string>  $map
     */
    private function lookup(string $lower, array $map): ?string
    {
        foreach ($map as $needle => $label) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u', $lower) === 1) {
                return $label;
            }
        }

        return null;
    }

    /**
     * @return array{days: int|null, phrase: string|null}
     */
    private function durationDays(string $lower): array
    {
        $scanned = (string) preg_replace(self::FOLLOWUP_DURATION_RE, ' ', $lower);

        if (preg_match(self::DURATION_RE, $scanned, $m) !== 1) {
            return ['days' => null, 'phrase' => null];
        }

        $count = (int) $m[1];
        $unit = strtolower($m[2]);

        return [
            'days' => $count * (self::DURATION_MULTIPLIERS[$unit] ?? 1),
            'phrase' => $m[0],
        ];
    }
}
