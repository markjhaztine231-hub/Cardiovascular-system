<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns an OCR'd echo / TMT / Doppler report into a structured field set that
 * maps 1:1 onto the study forms (EchoData / TMTData / DopplerData).
 *
 * The PaddleOCR markdown is sent to the same provider chain the AI narrative
 * uses (OpenRouter primary -> DeepSeek direct -> HuggingFace) with JSON mode
 * and temperature 0 so results are reproducible. The output is only ever a
 * draft: the study forms are pre-filled and the doctor must review and confirm
 * before the consultation is saved.
 */
class StudyAutoFillService
{
    private const MAX_TEXT_LENGTH = 3000;

    private const MAX_VALUE_LENGTH = 200;

    /** Select fields that only accept one of these exact values. */
    private const ENUMS = [
        'av_ar_jet' => ['Faint', 'Dense'],
        'pv_pr_jet' => ['Faint', 'Dense'],
    ];

    /** Free-text fields tolerate multi-line clinical prose. */
    private const TEXT_FIELDS = [
        'findings',
        'conclusion',
        'resting_ecg',
        'response_to_exercise',
        'exercise_capacity',
        'arrhythmias',
        'termination_reason',
        'indication',
        'medicines',
        'probability_cad',
        'witness_address',
        'residence',
        'consent_to',
        'requesting_physician',
        'attending_physician',
        'procedure',
    ];

    /**
     * Per study type: sections of fields whose keys match the Vue form model.
     * The hint text describes which measurement each key holds and its unit.
     *
     * @var array<string, list<array{section: string, fields: array<string, string>}>>
     */
    public const SCHEMAS = [
        'echo' => [
            ['section' => 'Demographics', 'fields' => [
                'requesting_physician' => 'Requesting physician name',
                'contact_no' => 'Contact number',
                'file_no' => 'File number',
                'bsa' => 'Body surface area (m2)',
                'age' => 'Age (years)',
                'sex' => 'Sex, M or F',
                'height' => 'Height (cm)',
                'weight' => 'Weight (kg)',
            ]],
            ['section' => 'Mitral Valve', 'fields' => [
                'mitral_ef' => 'EF velocity (mm2)',
                'mitral_de' => 'DE excursion (mm)',
                'mitral_epss' => 'EPSS (mm)',
                'mitral_area' => 'Area by planimetry (cm2)',
            ]],
            ['section' => 'Interventricular Septum', 'fields' => [
                'ivs_dia' => 'Diastolic thickness (mm)',
                'ivs_sys' => 'Systolic thickness (mm)',
                'ivs_exc' => 'Excursion (mm)',
            ]],
            ['section' => 'Tricuspid Valve', 'fields' => [
                'tricuspid_valve' => 'Tricuspid valve status (e.g. normal)',
            ]],
            ['section' => 'Aortic Valve', 'fields' => [
                'aortic_root' => 'Root diameter (mm)',
                'aortic_valve' => 'Valve opening (mm)',
                'aortic_excursion' => 'Excursion (mm)',
            ]],
            ['section' => 'LV Posterior Wall', 'fields' => [
                'lvpw_dia' => 'Diastolic thickness (mm)',
                'lvpw_sys' => 'Systolic thickness (mm)',
                'lvpw_exc' => 'Excursion (mm)',
            ]],
            ['section' => 'Right Atrium & Pulmonic Valve', 'fields' => [
                'ra_rl' => 'Right atrium RL diameter (mm)',
                'pulmonic_root' => 'Pulmonic root diameter (mm)',
                'pulmonic_notching' => 'Systolic notching (yes/no)',
            ]],
            ['section' => 'Left Atrium', 'fields' => [
                'la_ap' => 'AP diameter (mm)',
                'la_rl' => 'RL diameter (mm)',
                'la_lavi' => 'LAVI (mL/m2)',
            ]],
            ['section' => 'Right Ventricle', 'fields' => [
                'rv_ap' => 'AP diameter (mm)',
                'rv_rl' => 'RL diameter (mm)',
            ]],
            ['section' => 'Left Ventricle', 'fields' => [
                'lv_dia' => 'Diastolic diameter (mm)',
                'lv_sys' => 'Systolic diameter (mm)',
                'lv_lvedv' => 'LVEDV (mL)',
                'lv_lvesv' => 'LVESV (mL)',
                'lv_mass' => 'LV mass index (g/m2)',
                'lv_rwt' => 'Relative wall thickness (mm)',
                'lv_ef' => 'Ejection fraction (%)',
                'lv_fs' => 'Fractional shortening (%)',
                'hr' => 'Heart rate (bpm)',
                'simpsons' => "Simpson's method value (%)",
                'tapse' => 'TAPSE (cm)',
            ]],
            ['section' => 'Pericardial Space', 'fields' => [
                'peri_ant' => 'Anterior effusion (mm)',
                'peri_post' => 'Posterior effusion (mm)',
            ]],
            ['section' => 'RVOT / LVOT', 'fields' => [
                'rvot' => 'RVOT diameter (mm)',
                'lvot' => 'LVOT diameter (mm)',
            ]],
            ['section' => 'Findings', 'fields' => [
                'findings' => 'Echocardiographic findings, transcribed verbatim',
            ]],
        ],
        'tmt' => [
            ['section' => 'Demographics', 'fields' => [
                'age' => 'Age (years)',
                'pmhr' => 'Predicted maximum heart rate (PMHR)',
                'height' => 'Height (cm)',
                'weight' => 'Weight (kg)',
                'bp' => 'Blood pressure, e.g. 120/80',
                'cell_no' => 'Cell phone number',
                'birthday' => 'Birthday (date)',
                'medicines' => 'Current medications',
                'requesting_physician' => 'Requesting physician name',
                'attending_physician' => 'Attending physician name',
                'residence' => 'Residence / address',
            ]],
            ['section' => 'Consent', 'fields' => [
                'consent_to' => 'Consent wording',
                'consent_day' => 'Consent day',
                'consent_month' => 'Consent month',
                'consent_place' => 'Consent place',
                'witness_name' => 'Witness name',
                'witness_address' => 'Witness address',
                'procedure' => 'Procedure name',
            ]],
            ['section' => 'Protocol', 'fields' => [
                'protocol' => 'Exercise protocol (e.g. BRUCE)',
                'indication' => 'Indication for the test',
            ]],
            ['section' => 'Peak exercise', 'fields' => [
                'peak_stage' => 'Peak stage reached',
                'peak_minutes' => 'Peak exercise time (minutes)',
                'peak_sbp' => 'Peak systolic BP',
                'peak_hr' => 'Peak heart rate',
                'peak_mets' => 'Peak METs',
            ]],
            ['section' => 'ECG / response', 'fields' => [
                'termination_reason' => 'Reason for stopping the test',
                'st_shape' => 'ST segment shape',
                'st_displacement' => 'ST displacement (mm)',
                'st_duration' => 'ST duration',
                'resting_ecg' => 'Resting ECG findings, verbatim',
                'response_to_exercise' => 'BP/HR response to exercise',
                'exercise_capacity' => 'Exercise capacity assessment',
                'arrhythmias' => 'Arrhythmias observed',
                'probability_cad' => 'Probability of CAD',
            ]],
            ['section' => 'Conclusion', 'fields' => [
                'conclusion' => 'Conclusion, transcribed verbatim',
                'pre_ef' => 'Pre-exercise ejection fraction',
                'post_ef' => 'Post-exercise ejection fraction',
            ]],
        ],
        'doppler' => [
            ['section' => 'Mitral Valve', 'fields' => [
                'mv_peak_vel_e' => 'Peak E velocity (cm/s)',
                'mv_peak_vel_a' => 'Peak A velocity (cm/s)',
                'mv_peak_grad_e' => 'Peak E gradient (mmHg)',
                'mv_peak_grad_a' => 'Peak A gradient (mmHg)',
                'mv_ivrt' => 'IVRT (seconds)',
                'mv_regurg_frac' => 'Regurgitant fraction (%)',
                'mv_dectime' => 'Deceleration time (ms)',
                'mv_ea_ratio' => 'E/A ratio',
                'mv_area_pht' => 'Mitral area by PHT (cm2)',
                'mv_mean_pressure' => 'Mean pressure gradient (mmHg)',
            ]],
            ['section' => 'Tissue Doppler (cm/s)', 'fields' => [
                'td_septal_e' => "Septal e' (cm/s)",
                'td_septal_a' => "Septal a' (cm/s)",
                'td_septal_ee' => "Septal E/e' ratio",
                'td_lateral_e' => "Lateral e' (cm/s)",
                'td_lateral_a' => "Lateral a' (cm/s)",
                'td_lateral_ee' => "Lateral E/e' ratio",
            ]],
            ['section' => 'Tricuspid Valve', 'fields' => [
                'tv_e_wave' => 'E wave velocity (cm/s)',
                'tv_a_wave' => 'A wave velocity (cm/s)',
                'tv_regurg_frac' => 'Regurgitant fraction (%)',
                'tv_peak_tr' => 'Peak TR jet velocity (cm/s)',
                'tv_sys_pap' => 'Systolic PAP from TR (mmHg)',
            ]],
            ['section' => 'Aortic Valve (BEFORE/AFTER)', 'fields' => [
                'av_peak_vel_before' => 'Peak velocity before (cm/s)',
                'av_peak_vel_after' => 'Peak velocity after (cm/s)',
                'av_press_grad_before' => 'Pressure gradient before (mmHg)',
                'av_press_grad_after' => 'Pressure gradient after (mmHg)',
                'av_area' => 'Area by continuity (cm2)',
                'av_regurg_frac' => 'Regurgitant fraction (%)',
                'av_ar_pht' => 'AR PHT (msec)',
                'av_mean_grad' => 'AV mean gradient (mmHg)',
                'av_ar_jet' => 'AR jet density: Faint or Dense',
            ]],
            ['section' => 'Pulmonic Valve', 'fields' => [
                'pv_accel_time' => 'Acceleration time (msec)',
                'pv_press_grad' => 'Pressure gradient (mmHg)',
                'pv_regurg' => 'Regurgitation (%)',
                'pv_sys_pap' => 'Systolic PAP from acceleration time (mmHg)',
                'pv_pr_jet' => 'PR jet density: Faint or Dense',
                'pv_rvot_vti' => 'RVOT VTI (cm)',
            ]],
            ['section' => 'Shunt', 'fields' => [
                'qp_qs' => 'Qp/Qs ratio',
            ]],
            ['section' => 'Conclusion', 'fields' => [
                'conclusion' => 'Conclusion, transcribed verbatim',
            ]],
        ],
    ];

    /**
     * @return array{type: string, fields: array<string, string>, provider: string}
     */
    public function extract(string $type, string $ocrText): array
    {
        $schema = self::SCHEMAS[$type] ?? null;

        if ($schema === null) {
            throw new \InvalidArgumentException("Unsupported study type: {$type}");
        }

        [$jsonText, $provider] = $this->completeJson($type, $schema, $ocrText);

        return [
            'type' => $type,
            'fields' => $this->normalise(json_decode($jsonText, true)),
            'provider' => $provider,
        ];
    }

    /**
     * Run the extraction prompt through the provider chain. OpenRouter is the
     * primary, then DeepSeek direct, then HuggingFace inference. Every provider
     * is first tried with strict JSON mode; a single retry without it is made
     * for providers that reject response_format.
     *
     * @param  list<array{section: string, fields: array<string, string>}>  $schema
     * @return array{0: string, 1: string}
     */
    private function completeJson(string $type, array $schema, string $ocrText): array
    {
        $system = 'You are a clinical data extraction specialist for cardiology reports. '
            .'You are given OCR text produced from a scanned '.strtoupper($type).' report and '
            .'must extract every measurement it states into a flat JSON object. Follow these rules exactly: '
            .'1) Return ONLY valid JSON. No prose, no code fences, no extra keys. '
            .'2) Use exactly the keys listed in the schema and include every one. '
            .'3) Every value is a plain string with the unit omitted (the schema implies the unit). '
            .'4) Keep the raw number the report prints, e.g. "45", "1.7", "68/40". '
            .'5) Return an empty string "" when a measurement is absent, illegible or unclear. '
            .'6) For jet-density fields use exactly "Faint" or "Dense" when stated, otherwise "". '
            .'7) Transcribe free-text sections (findings, conclusion, ECG) verbatim. '
            .'8) Never invent, estimate or infer a measurement the report does not state.';

        $user = "Schema sections with expected units:\n"
            .json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ."\n\nOCR TEXT (markdown from the scanned document):\n```\n"
            .mb_substr($ocrText, 0, 24000)
            ."\n```";

        $basePayload = [
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0,
            'max_tokens' => 8000,
        ];

        $chain = $this->providers();

        foreach ($chain as $provider) {
            $labels = ['openrouter' => 'OpenRouter', 'deepseek' => 'DeepSeek', 'hf' => 'HuggingFace'];

            // First attempt: strict JSON mode.
            $text = $this->attempt($provider, array_merge($basePayload, ['response_format' => ['type' => 'json_object']]));
            if ($text !== null) {
                return [$text, $labels[$provider['key']]];
            }

            // Second attempt: JSON by instruction only (older providers).
            $text = $this->attempt($provider, $basePayload);
            if ($text !== null) {
                return [$text, $labels[$provider['key']]];
            }
        }

        throw new \RuntimeException('No AI provider responded successfully for study auto-fill. Check your AI API keys.');
    }

    /**
     * @return list<array{key: string, url: string, model: string, headers: array<string, string>}>
     */
    private function providers(): array
    {
        return [
            [
                'key' => 'openrouter',
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'model' => config('services.openrouter.model'),
                'headers' => [
                    'Authorization' => 'Bearer '.config('services.openrouter.api_key'),
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => url('/'),
                    'X-Title' => 'CardioCare EMR',
                ],
            ],
            [
                'key' => 'deepseek',
                'url' => 'https://api.deepseek.com/chat/completions',
                'model' => config('services.deepseek.model'),
                'headers' => [
                    'Authorization' => 'Bearer '.config('services.deepseek.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ],
            [
                'key' => 'hf',
                'url' => 'https://api-inference.huggingface.co/models/'.config('services.hf.model').'/v1/chat/completions',
                'model' => config('services.hf.model'),
                'headers' => [
                    'Authorization' => 'Bearer '.config('services.hf.token'),
                    'Content-Type' => 'application/json',
                ],
            ],
        ];
    }

    /**
     * @param  array{key: string, url: string, model: string, headers: array<string, string>}  $provider
     * @param  array<string, mixed>  $payload
     */
    private function attempt(array $provider, array $payload): ?string
    {
        $enabled = match ($provider['key']) {
            'openrouter' => config('services.openrouter.api_key'),
            'deepseek' => config('services.deepseek.api_key'),
            'hf' => config('services.hf.token'),
            default => null,
        };

        if (empty($enabled)) {
            return null;
        }

        $payload['model'] = $provider['model'];

        try {
            $response = Http::retry(2, 1000)
                ->withHeaders($provider['headers'])
                ->timeout(120)
                ->post($provider['url'], $payload);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');

                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            }

            Log::warning("StudyAutoFill {$provider['key']} error: ".$response->status().' '.$response->body());
        } catch (\Throwable $e) {
            Log::warning("StudyAutoFill {$provider['key']} exception: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Keep only the schema keys the form renders, defaulting missing values to
     * "" and stripping anything that is not a sane short string.
     *
     * @param  array<string, mixed>|null  $data
     * @return array<string, string>
     */
    private function normalise(?array $data): array
    {
        $allowed = [];

        foreach (self::SCHEMAS as $sections) {
            foreach ($sections as $section) {
                foreach (array_keys($section['fields']) as $key) {
                    $allowed[$key] = true;
                }
            }
        }

        $out = [];
        $source = is_array($data) ? $data : [];

        foreach (array_keys($allowed) as $key) {
            $value = $source[$key] ?? '';

            if (is_array($value) || is_object($value)) {
                $value = '';
            }

            $out[$key] = $this->sanitise((string) $value, $key);
        }

        return $out;
    }

    private function sanitise(string $value, string $key): string
    {
        $value = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $value));

        if (isset(self::ENUMS[$key])) {
            $value = in_array($value, self::ENUMS[$key], true) ? $value : '';
        }

        $limit = in_array($key, self::TEXT_FIELDS, true)
            ? self::MAX_TEXT_LENGTH
            : self::MAX_VALUE_LENGTH;

        return mb_substr($value, 0, $limit);
    }
}