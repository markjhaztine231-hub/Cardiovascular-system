<?php

namespace App\Models\Concerns;

/**
 * Provides a privacy-aware display name for patient records.
 *
 * When patient anonymization is enabled in config/privacy.php the full legal
 * name is replaced by initials or a stable pseudonym so the name is not
 * exposed in dashboards, registries, logs or reports.
 */
trait AnonymizableName
{
    /**
     * The identifier column (e.g. "id") used to build pseudonyms.
     */
    public function anonymizableIdentifier(): string
    {
        return (string) $this->getKey();
    }

    /**
     * True when the anonymization toggle is active.
     */
    public function patientAnonymizationEnabled(): bool
    {
        return (bool) config('privacy.anonymize_patient_names', false);
    }

    /**
     * A privacy-safe label: full name when disabled, anonymized when enabled.
     * Only patient records are ever anonymized — staff names stay intact.
     * Falls back to a pseudonym whenever the raw name is unavailable.
     */
    public function anonymizedName(): string
    {
        $name = trim((string) $this->name);

        if (empty($name)) {
            return $this->anonymousLabel();
        }

        $isProtectedRole = in_array($this->role ?? null, ['patient', 'walk-in'], true);

        if (! $isProtectedRole || ! $this->patientAnonymizationEnabled()) {
            return $name;
        }

        $mode = config('privacy.patient_name_mode', 'pseudonym');

        return match ($mode) {
            'initials' => $this->toInitials($name),
            default => $this->anonymousLabel(),
        };
    }

    /**
     * Stable, collision-safe pseudonym: "Patient #12".
     */
    public function anonymousLabel(): string
    {
        return 'Patient #'.$this->anonymizableIdentifier();
    }

    /**
     * "John Michael Doe" -> "J. M. D."
     */
    protected function toInitials(string $name): string
    {
        $initial = function (string $part): string {
            $letters = preg_replace('/[^A-Za-z]/', '', $part);

            return $letters === '' ? '' : strtoupper(mb_substr($letters, 0, 1));
        };

        $initials = collect(preg_split('/\s+/', $name))
            ->map($initial)
            ->filter()
            ->implode('. ');

        return $initials === '' ? $this->anonymousLabel() : $initials.'.';
    }
}
