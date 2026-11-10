<?php

namespace App\Support;

use App\Models\AmbulatoryBloodPressureMonitoring;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\ManualDraft;
use App\Models\StressEchocardiogram;
use App\Models\TreadmillTest;

/**
 * Decides whether a clinical form actually holds something a user entered.
 *
 * The Echo/TMT/Doppler forms ship with default values (a normal tricuspid
 * valve, a TMT protocol and stage presets, "Faint" Doppler jets) and some
 * fields are generated for the user (file numbers, patient age/sex added by
 * {@see ClinicalRecordMapper}). Those defaults must not make a blank form look
 * filled in: history listings, PDF exports and the save endpoints all use this
 * class so a form only appears once at least one field has real input.
 */
final class ClinicalFormData
{
    /**
     * Fields that are identifiers, injected context or database metadata rather
     * than something the user typed into the form.
     */
    private const IGNORED_KEYS = [
        'id',
        'encounter_id',
        'appointment_id',
        'patient_id',
        'provider_id',
        'created_by',
        'performed_at',
        'created_at',
        'updated_at',
        'deleted_at',
        'file_no',
        'age',
        'sex',
    ];

    /**
     * Values the form starts with. Matching input is treated as "not entered".
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const DEFAULTS = [
        'echo' => [
            'tricuspid_valve' => ['normal', 'tricuspid valve: normal'],
            'wall_motion' => ['tricuspid valve: normal'],
            'peri_ant' => ['absent'],
            'peri_post' => ['absent'],
        ],
        'tmt' => [
            'procedure' => ['treadmill exercise test'],
            'protocol' => ['bruce, modified bruce, nih'],
        ],
        'doppler' => [
            'av_ar_jet' => ['faint'],
            'pv_pr_jet' => ['faint'],
        ],
    ];

    /** Manual drafts only carry narrative text; attachments are not input. */
    private const MANUAL_DRAFT_TEXT_KEYS = [
        'requesting_physician',
        'title',
        'draft',
        'conclusion',
    ];

    /**
     * True when a posted form payload contains at least one meaningful value.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function hasContent(?string $type, ?array $data): bool
    {
        if ($data === null || $data === []) {
            return false;
        }

        if ($type === 'manual_draft') {
            return self::manualDraftHasText($data);
        }

        return self::arrayHasContent($type, $data);
    }

    /**
     * True when a stored study relation contains real user-entered content.
     * Default-only rows (e.g. an Echo saved with just the normal tricuspid
     * valve) are reported as empty.
     */
    public static function modelHasContent(?string $type, mixed $study): bool
    {
        if (! $study) {
            return false;
        }

        return match ($type) {
            'echo' => $study instanceof Echocardiogram
                && self::hasContent('echo', ClinicalRecordMapper::forPayload($study)),
            'tmt' => $study instanceof TreadmillTest
                && self::hasContent('tmt', ClinicalRecordMapper::forPayload(null, $study)),
            'doppler' => $study instanceof DopplerStudy
                && self::hasContent('doppler', ClinicalRecordMapper::forPayload(null, null, $study)),
            'manual_draft' => $study instanceof ManualDraft
                && self::manualDraftHasText($study->only(self::MANUAL_DRAFT_TEXT_KEYS)),
            'stress_echo' => $study instanceof StressEchocardiogram
                && self::hasContent('stress_echo', $study->payload ?? []),
            'abpm' => $study instanceof AmbulatoryBloodPressureMonitoring
                && self::hasContent('abpm', $study->payload ?? []),
            default => false,
        };
    }

    /**
     * A prescription row is real once it has a medicine name or free text.
     */
    public static function prescriptionHasContent(mixed $prescription): bool
    {
        if (! $prescription) {
            return false;
        }

        $isFreeform = (bool) self::read($prescription, 'is_freeform', false);
        $freeText = self::read($prescription, 'free_text', '');
        $medicine = self::read($prescription, 'medication_name')
            ?? self::read($prescription, 'medicine', '');

        $primary = $isFreeform ? $freeText : $medicine;
        $fallback = $isFreeform ? $medicine : $freeText;

        return self::hasText($primary) || self::hasText($fallback);
    }

    /**
     * A clinical request is real once its body has text.
     */
    public static function requestHasContent(mixed $request): bool
    {
        return $request !== null
            && self::hasText(self::read($request, 'body', ''));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function arrayHasContent(?string $type, array $data): bool
    {
        foreach ($data as $key => $value) {
            $key = (string) $key;

            if (in_array($key, self::IGNORED_KEYS, true)) {
                continue;
            }

            // TMT stage presets repeat on every row; only the measured columns
            // (BP / HR / remarks) tell us the technician actually used a stage.
            if ($type === 'tmt' && $key === 'stages' && is_array($value)) {
                if (self::tmtStagesHaveContent($value)) {
                    return true;
                }

                continue;
            }

            if (self::isDefaultValue($type, $key, $value)) {
                continue;
            }

            if (self::valueHasContent($type, $value)) {
                return true;
            }
        }

        return false;
    }

    private static function valueHasContent(?string $type, mixed $value): bool
    {
        if (is_array($value)) {
            return self::arrayHasContent($type, $value);
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    private static function isDefaultValue(?string $type, string $key, mixed $value): bool
    {
        // A negative effusion is stored as false/0 and is the default state.
        if ($type === 'echo' && $key === 'pericardial_effusion') {
            return ! $value;
        }

        if (! is_string($value)) {
            return false;
        }

        $normalized = mb_strtolower(trim($value));

        foreach (self::DEFAULTS[$type ?? ''][$key] ?? [] as $default) {
            if ($normalized === $default) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $stages
     */
    private static function tmtStagesHaveContent(array $stages): bool
    {
        foreach ($stages as $stage) {
            if (! is_array($stage)) {
                continue;
            }

            foreach (['bp', 'hr', 'remarks'] as $key) {
                if (self::hasText($stage[$key] ?? null)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function manualDraftHasText(array $data): bool
    {
        foreach (self::MANUAL_DRAFT_TEXT_KEYS as $key) {
            if (self::hasText($data[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private static function hasText(mixed $value): bool
    {
        return is_string($value)
            ? trim($value) !== ''
            : $value !== null && $value !== false;
    }

    private static function read(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        if (is_object($source)) {
            return $source->{$key} ?? $default;
        }

        return $default;
    }
}
