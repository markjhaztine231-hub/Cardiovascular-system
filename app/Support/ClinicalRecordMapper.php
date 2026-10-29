<?php

namespace App\Support;

use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\TreadmillTest;
use Illuminate\Support\Collection;

/**
 * Maps the normalised clinical study tables onto the "legacy" key shape that
 * both the PDF blade views and the View History Vue tables were written
 * against.
 *
 * The studies used to live in JSON blobs on the appointment (echo_data,
 * tmt_data, doppler_data) and were later split into echocardiograms,
 * treadmill_tests and doppler_studies. The presentation layer was never
 * updated, so it still asks for keys like `lv_ef`, `conclusion` and `rvot`.
 * Handing it raw columns (`lvef_percent`, `notes`) silently yields blank
 * panels, because the tables filter out any row whose two values are both
 * empty. Keeping the mapping in one place stops the screen and the PDF from
 * drifting apart again.
 */
class ClinicalRecordMapper
{
    /**
     * Echocardiogram -> legacy echo keys, plus patient age/sex which the echo
     * table and PDF header both display.
     *
     * @return array<string, mixed>
     */
    public static function echo(Echocardiogram $echo, ?Patient $patient = null): array
    {
        $legacy = self::dropEmpty([
            'lv_ef' => $echo->lvef_percent,
            'lv_fs' => $echo->lvfs_percent,
            'lv_mass' => $echo->lv_mass_grams,
            'tapse' => $echo->tapse_mm,
            'ivs_dia' => $echo->ivs_mm,
            'lvpw_dia' => $echo->lvpw_mm,
            'lv_dia' => $echo->lvidd_mm,
            'lv_sys' => $echo->lvids_mm,
            'aortic_root' => $echo->aortic_root_mm,
            'la_ap' => $echo->la_diameter_mm,
            'rv_ap' => $echo->rv_size,
            'tricuspid_valve' => $echo->wall_motion,
            // A boolean false is meaningful here, so only null is treated as
            // "not recorded" - otherwise an absent effusion and a negative
            // finding would be indistinguishable.
            'peri_ant' => $echo->pericardial_effusion === null
                ? null
                : ($echo->pericardial_effusion ? 'Present' : 'Absent'),
            'findings' => $echo->valve_findings,
            'notes' => self::humanNotes($echo->notes),
        ]);

        // The migrated consultation dumped the raw Echo form JSON into
        // `notes`. Recover those keys (height, bsa, hr, simpsons, rvot, ...)
        // beneath the normalized column values so the detail table is full.
        $form = self::embeddedForm($echo->notes, $echo->valve_findings);

        return array_merge($form, $legacy, self::patientContext($patient));
    }

    /**
     * Treadmill test -> legacy TMT keys.
     *
     * @return array<string, mixed>
     */
    public static function tmt(TreadmillTest $tmt, ?Patient $patient = null): array
    {
        $legacy = self::dropEmpty([
            'max_stage' => $tmt->max_stage,
            'peak_hr' => $tmt->peak_heart_rate_bpm,
            'peak_sbp' => $tmt->peak_bp_systolic,
            'peak_dbp' => $tmt->peak_bp_diastolic,
            'peak_mets' => $tmt->mets_achieved,
            'peak_minutes' => $tmt->exercise_duration_seconds !== null
                ? round($tmt->exercise_duration_seconds / 60, 1)
                : null,
            'st_segment_changes' => $tmt->st_segment_changes,
            'arrhythmias' => $tmt->arrhythmias,
            'response_to_exercise' => $tmt->symptoms_during_test,
            'termination_reason' => $tmt->reason_for_termination,
            'protocol' => $tmt->protocol,
            'conclusion' => self::humanNotes($tmt->notes),
        ]);

        // The TMT panel reads `pmhr` and `bp` rather than the peak_* keys.
        // Only override when the columns hold a value; otherwise let the
        // recovered form JSON supply them.
        if ($tmt->peak_heart_rate_bpm !== null) {
            $legacy['pmhr'] = $tmt->peak_heart_rate_bpm;
        }

        if ($tmt->peak_bp_systolic !== null || $tmt->peak_bp_diastolic !== null) {
            $legacy['bp'] = $tmt->peak_bp_systolic.'/'.$tmt->peak_bp_diastolic;
        }

        // Recover the raw TMT form JSON dumped into `notes` (age, pmhr,
        // resting_ecg, exercise_capacity, probability_cad, ...).
        $form = self::embeddedForm($tmt->notes);

        return array_merge($form, $legacy, self::patientContext($patient));
    }

    /**
     * Doppler study -> legacy Doppler keys.
     *
     * @return array<string, mixed>
     */
    public static function doppler(DopplerStudy $doppler, ?Patient $patient = null): array
    {
        $legacy = self::dropEmpty([
            'mv_ea_ratio' => $doppler->ea_ratio,
            'td_septal_e' => $doppler->tissue_doppler_e,
            'td_septal_a' => $doppler->tissue_doppler_a,
            'mv_mean_pressure' => $doppler->mitral_gradient,
            'av_mean_grad' => $doppler->aortic_gradient,
            'tv_peak_tr' => $doppler->tricuspid_gradient,
            'pv_press_grad' => $doppler->pulmonic_gradient,
            'findings' => $doppler->valve_findings,
            'conclusion' => self::humanNotes($doppler->notes),
        ]);

        // Recover the raw Doppler form JSON dumped into `notes`.
        $form = self::embeddedForm($doppler->notes, $doppler->valve_findings);

        return array_merge($form, $legacy, self::patientContext($patient));
    }

    /**
     * Prescriptions -> legacy prescription rows for the PDF view.
     *
     * @param  Collection<int, Prescription>|null  $prescriptions
     * @return array<int, array<string, mixed>>
     */
    public static function prescriptions(?Collection $prescriptions): array
    {
        if (! $prescriptions) {
            return [];
        }

        return $prescriptions
            ->map(fn ($rx) => [
                'medicine' => $rx->medication_name,
                'dosage' => $rx->dosage,
                'frequency' => $rx->frequency,
                'duration' => $rx->duration_days ? $rx->duration_days.' days' : '',
                'is_freeform' => (bool) $rx->is_freeform,
                'free_text' => $rx->free_text,
            ])
            ->values()
            ->all();
    }

    /**
     * Presentation payload for one study: the raw columns merged with the
     * legacy aliases on top.
     *
     * Raw keys are preserved because other views read them directly, while the
     * aliases satisfy the legacy table builders. Legacy keys win on collision
     * because they are the curated display values.
     *
     * @return array<string, mixed>|null
     */
    public static function forPayload(
        ?Echocardiogram $echo = null,
        ?TreadmillTest $tmt = null,
        ?DopplerStudy $doppler = null,
        ?Patient $patient = null
    ): ?array {
        $study = $echo ?? $tmt ?? $doppler;

        if (! $study) {
            return null;
        }

        $legacy = match (true) {
            $echo !== null => self::echo($echo, $patient),
            $tmt !== null => self::tmt($tmt, $patient),
            default => self::doppler($doppler, $patient),
        };

        $payload = array_merge($study->getAttributes(), $legacy);

        // Never leak the raw "text | {json}" blob as the study's notes; the
        // embedded form data has already been recovered into the legacy keys.
        $payload['notes'] = self::humanNotes($study->notes ?? null);

        return $payload;
    }

    /**
     * Depending on which study blob embedded the legacy form JSON, return
     * whichever one decodes successfully.
     *
     * @return array<string, mixed>
     */
    private static function embeddedForm(?string ...$texts): array
    {
        foreach ($texts as $text) {
            $decoded = self::embeddedFormJson($text);

            if ($decoded !== null) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Pull the trailing JSON document out of a "free text | {...}" notes
     * column. The save path JSON-encodes the legacy form and concatenates it
     * after a pipe, sometimes with HTML-escaped quotes, so both the raw and
     * HTML-decoded text are tried.
     *
     * @return array<string, mixed>|null
     */
    private static function embeddedFormJson(?string $text): ?array
    {
        if (! is_string($text) || $text === '') {
            return null;
        }

        foreach ([$text, html_entity_decode($text, ENT_QUOTES)] as $candidate) {
            $start = strpos($candidate, '{');

            if ($start === false) {
                continue;
            }

            $decoded = json_decode(trim(substr($candidate, $start)), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * The human-readable prefix of a "text | {json}" column, with the
     * embedded JSON blob (and its pipe separator) removed.
     */
    private static function humanNotes(?string $text): ?string
    {
        if (! is_string($text) || $text === '') {
            return null;
        }

        $human = preg_match('/^(.*?)\s*\|\s*\{/', $text, $m)
            ? trim($m[1])
            : (preg_match('/^(.*?)\s*\{/', $text, $m) ? trim($m[1]) : trim($text));

        return $human === '' ? null : $human;
    }

    /**
     * Age and sex, shown alongside every study type.
     *
     * @return array<string, mixed>
     */
    private static function patientContext(?Patient $patient): array
    {
        if (! $patient) {
            return [];
        }

        $context = ['sex' => $patient->sex];

        if ($patient->date_of_birth) {
            $context['age'] = $patient->date_of_birth->age;
        }

        return self::dropEmpty($context);
    }

    /**
     * Drop nulls and empty strings, but keep 0 and false, which are real
     * clinical values (a 0 mm pericardial effusion is not the same as unknown).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function dropEmpty(array $values): array
    {
        return array_filter(
            $values,
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
