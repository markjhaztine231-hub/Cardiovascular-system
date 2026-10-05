<?php

namespace App\Services;

class ClinicalJsonService
{
    /**
     * Encode a clinical data array as compact JSON for DB storage.
     */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Smart decoder — JSON-first, XML fallback for legacy rows.
     *
     * @param  string  $value  Raw column value from DB.
     * @param  string  $type  'echo' | 'tmt' | 'doppler'
     */
    public static function decode(string $value, string $type): array
    {
        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return [];
        }

        // ── New JSON format ───────────────────────────────────────────
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        // ── Legacy XML fallback (rows saved before this migration) ────
        if (str_starts_with($trimmed, '<')) {
            return ClinicalXmlService::decode($value, $type);
        }

        return [];
    }
}
