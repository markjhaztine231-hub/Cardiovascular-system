<?php

namespace App\Support;

/**
 * Age banding for clinical analytics.
 *
 * The bands are deliberately cardiovascular-clinic oriented: risk rises sharply
 * from 45, and the 18-29 bracket is where "unexpected" presentations such as
 * hypertension in a 20-year-old become visible instead of being averaged away.
 *
 * Kept as a single source of truth so the service, the API payload and the UI
 * can never disagree about which bucket an age belongs to.
 */
final class AgeBand
{
    public const UNDER_18 = '0-17';

    public const EIGHTEEN_TO_TWENTYNINE = '18-29';

    public const THIRTY_TO_FORTYFOUR = '30-44';

    public const FORTYFIVE_TO_FIFTYNINE = '45-59';

    public const SIXTY_TO_SEVENTYFOUR = '60-74';

    public const SEVENTYFIVE_PLUS = '75+';

    public const UNKNOWN = 'unknown';

    /**
     * Ordered oldest-last, excluding UNKNOWN. Labels are presentation-ready and
     * use an en dash so the UI never has to rebuild them.
     *
     * @return array<string, string>
     */
    public static function bands(): array
    {
        return [
            self::UNDER_18 => 'Under 18',
            self::EIGHTEEN_TO_TWENTYNINE => '18–29',
            self::THIRTY_TO_FORTYFOUR => '30–44',
            self::FORTYFIVE_TO_FIFTYNINE => '45–59',
            self::SIXTY_TO_SEVENTYFOUR => '60–74',
            self::SEVENTYFIVE_PLUS => '75+',
        ];
    }

    public static function isKnown(string $band): bool
    {
        return $band !== self::UNKNOWN;
    }

    /**
     * Bucket an age in years. Returns UNKNOWN when the patient has no usable
     * date of birth, so an incomplete chart is visible rather than silently
     * inflating the youngest band.
     */
    public static function band(?int $age): string
    {
        if ($age === null || $age < 0) {
            return self::UNKNOWN;
        }

        return match (true) {
            $age < 18 => self::UNDER_18,
            $age < 30 => self::EIGHTEEN_TO_TWENTYNINE,
            $age < 45 => self::THIRTY_TO_FORTYFOUR,
            $age < 60 => self::FORTYFIVE_TO_FIFTYNINE,
            $age < 75 => self::SIXTY_TO_SEVENTYFOUR,
            default => self::SEVENTYFIVE_PLUS,
        };
    }

    public static function label(string $band): string
    {
        // Keep this in sync with emptyHistogram() so the narrative breakdown
        // and the UI histogram never render the same band under two names.
        return $band === self::UNKNOWN
            ? 'Unknown age'
            : (self::bands()[$band] ?? 'Unknown age');
    }

    /**
     * A full histogram including the trailing UNKNOWN column, so every band is
     * present in the payload even at zero. Charts and narratives both rely on
     * this instead of inferring missing keys.
     *
     * @return array<string, array{band: string, label: string, count: int}>
     */
    public static function emptyHistogram(): array
    {
        $histogram = [];

        foreach (self::bands() as $key => $label) {
            $histogram[$key] = ['band' => $key, 'label' => $label, 'count' => 0];
        }

        $histogram[self::UNKNOWN] = [
            'band' => self::UNKNOWN,
            'label' => 'Unknown age',
            'count' => 0,
        ];

        return $histogram;
    }
}
