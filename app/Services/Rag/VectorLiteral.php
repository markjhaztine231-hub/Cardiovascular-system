<?php

namespace App\Services\Rag;

/**
 * Formats a PHP float array as a PostgreSQL vector literal: [0.1,0.2,...].
 *
 * pgvector's text input format is used because there is no PHP extension
 * requirement; the value is cast with `?::vector` in SQL.
 */
class VectorLiteral
{
    /**
     * @param  array<int, float|int>  $vector
     */
    public static function format(array $vector): string
    {
        $parts = array_map(
            static fn ($value) => rtrim(rtrim(sprintf('%.9F', (float) $value), '0'), '.'),
            $vector,
        );

        return '['.implode(',', $parts).']';
    }
}
