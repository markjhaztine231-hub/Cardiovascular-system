<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use SimpleXMLElement;

/**
 * Converts clinical form data between PHP arrays and structured XML.
 *
 * WHY XML?
 * ─ XML is the native format for SOAP web services, HL7/FHIR medical messaging,
 *   and Word OOXML document interchange.
 * ─ A well-formed XML tree is self-describing and can be validated against an XSD.
 * ─ Tools like LibreOffice, MS Word, and FHIR converters consume XML natively.
 *
 * STORAGE FORMAT
 * ─ echo_data   → <EchocardiographicReport>  (Type 1)
 * ─ tmt_data    → <TreadmillExerciseTest>     (Type 2)
 * ─ doppler_data→ <ColorFlowDopplerStudy>     (Type 3)
 *
 * Leaf element names mirror the PHP / TypeScript flat-key names (e.g. <mitral_ef>)
 * so round-tripping to the frontend is lossless.
 *
 * BACKWARD COMPATIBILITY
 * ─ decode() falls back to JSON parsing for any row that was saved before
 *   this migration, so existing data is never lost.
 */
class ClinicalXmlService
{
    // ══════════════════════════════════════════════════════════════════
    // Type 1 — Echocardiographic Report
    // ══════════════════════════════════════════════════════════════════

    public static function echoToXml(array $d): string
    {
        [$doc, $root] = self::createDoc('EchocardiographicReport');

        self::section($doc, $root, 'PatientInfo', $d, [
            'requesting_physician', 'contact_no', 'age', 'sex',
            'file_no', 'bsa', 'height', 'weight',
        ]);
        self::section($doc, $root, 'MitralValve', $d, [
            'mitral_ef', 'mitral_de', 'mitral_epss', 'mitral_area',
        ]);
        self::section($doc, $root, 'IVSeptum', $d, [
            'ivs_dia', 'ivs_sys', 'ivs_exc',
        ]);
        self::section($doc, $root, 'TricuspidValve', $d, [
            'tricuspid_valve',
        ]);
        self::section($doc, $root, 'AorticValve', $d, [
            'aortic_root', 'aortic_valve', 'aortic_excursion',
        ]);
        self::section($doc, $root, 'LVPosteriorWall', $d, [
            'lvpw_dia', 'lvpw_sys', 'lvpw_exc',
        ]);
        self::section($doc, $root, 'RightAtrium', $d, ['ra_rl']);
        self::section($doc, $root, 'PulmonicValve', $d, [
            'pulmonic_root', 'pulmonic_notching',
        ]);
        self::section($doc, $root, 'LeftAtrium', $d, [
            'la_ap', 'la_rl', 'la_lavi',
        ]);
        self::section($doc, $root, 'RightVentricle', $d, [
            'rv_ap', 'rv_rl',
        ]);
        self::section($doc, $root, 'LeftVentricle', $d, [
            'lv_dia', 'lv_sys', 'lv_lvedv', 'lv_lvesv',
            'lv_mass', 'lv_rwt', 'lv_ef', 'lv_fs',
            'hr', 'simpsons', 'tapse',
        ]);
        self::section($doc, $root, 'PericardialSpace', $d, [
            'peri_ant', 'peri_post',
        ]);
        self::section($doc, $root, 'OutflowTracts', $d, [
            'rvot', 'lvot',
        ]);
        self::section($doc, $root, 'Findings', $d, ['findings']);

        return $doc->saveXML();
    }

    public static function xmlToEcho(string $xml): array
    {
        return self::flattenLeaves($xml);
    }

    // ══════════════════════════════════════════════════════════════════
    // Type 2 — Treadmill Exercise Test
    // ══════════════════════════════════════════════════════════════════

    public static function tmtToXml(array $d): string
    {
        [$doc, $root] = self::createDoc('TreadmillExerciseTest');

        self::section($doc, $root, 'PatientInfo', $d, [
            'age', 'pmhr', 'height', 'bp', 'weight', 'cell_no', 'birthday',
            'medicines', 'requesting_physician', 'procedure',
            'attending_physician', 'residence',
        ]);
        self::section($doc, $root, 'Consent', $d, [
            'consent_to', 'consent_day', 'consent_month',
            'consent_place', 'witness_name', 'witness_address',
        ]);
        self::section($doc, $root, 'Protocol', $d, [
            'protocol', 'indication',
        ]);

        // Stages — each Stage carries data as attributes AND child elements
        $stagesEl = $root->appendChild($doc->createElement('Stages'));
        foreach ((array) ($d['stages'] ?? []) as $stage) {
            $stageEl = $doc->createElement('Stage');
            $stageEl->setAttribute('name', (string) ($stage['name'] ?? ''));
            $stageEl->setAttribute('speed', (string) ($stage['speed'] ?? ''));
            $stageEl->setAttribute('grade', (string) ($stage['grade'] ?? ''));
            $stageEl->setAttribute('mets', (string) ($stage['mets'] ?? ''));
            self::appendLeaves($doc, $stageEl, $stage, ['bp', 'hr', 'remarks']);
            $stagesEl->appendChild($stageEl);
        }

        self::section($doc, $root, 'PeakExercise', $d, [
            'peak_stage', 'peak_minutes', 'peak_sbp',
            'peak_hr', 'peak_mets', 'termination_reason',
        ]);
        self::section($doc, $root, 'STSegment', $d, [
            'st_shape', 'st_displacement', 'st_duration',
        ]);
        self::section($doc, $root, 'Interpretation', $d, [
            'resting_ecg', 'response_to_exercise', 'exercise_capacity',
            'arrhythmias', 'probability_cad', 'conclusion',
            'pre_ef', 'post_ef',
        ]);

        return $doc->saveXML();
    }

    public static function xmlToTmt(string $xml): array
    {
        // Collect all plain leaf nodes first (Stages container is skipped by traverseLeaves)
        $result = self::flattenLeaves($xml);

        // Parse Stages separately — they use XML attributes for speed / grade / mets
        try {
            $sxml = new SimpleXMLElement($xml);
            $stages = [];
            foreach ($sxml->Stages->Stage ?? [] as $stageEl) {
                $stages[] = [
                    'name' => (string) ($stageEl['name'] ?? ''),
                    'speed' => (string) ($stageEl['speed'] ?? ''),
                    'grade' => (string) ($stageEl['grade'] ?? ''),
                    'mets' => (string) ($stageEl['mets'] ?? ''),
                    'bp' => (string) ($stageEl->bp ?? ''),
                    'hr' => (string) ($stageEl->hr ?? ''),
                    'remarks' => (string) ($stageEl->remarks ?? ''),
                ];
            }
            $result['stages'] = $stages;
        } catch (\Throwable) {
            $result['stages'] = [];
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════
    // Type 3 — Color Flow & Doppler Study
    // ══════════════════════════════════════════════════════════════════

    public static function dopplerToXml(array $d): string
    {
        [$doc, $root] = self::createDoc('ColorFlowDopplerStudy');

        self::section($doc, $root, 'MitralValve', $d, [
            'mv_peak_vel_e', 'mv_peak_vel_a',
            'mv_peak_grad_e', 'mv_peak_grad_a',
            'mv_ivrt', 'mv_regurg_frac', 'mv_dectime',
            'mv_ea_ratio', 'mv_area_pht', 'mv_mean_pressure',
        ]);
        self::section($doc, $root, 'TissueDoppler', $d, [
            'td_septal_e', 'td_septal_a', 'td_septal_ee',
            'td_lateral_e', 'td_lateral_a', 'td_lateral_ee',
        ]);
        self::section($doc, $root, 'TricuspidValve', $d, [
            'tv_e_wave', 'tv_a_wave', 'tv_regurg_frac',
            'tv_peak_tr', 'tv_sys_pap',
        ]);
        self::section($doc, $root, 'AorticValve', $d, [
            'av_peak_vel_before', 'av_peak_vel_after',
            'av_press_grad_before', 'av_press_grad_after',
            'av_area', 'av_regurg_frac', 'av_ar_pht',
            'av_mean_grad', 'av_ar_jet',
        ]);
        self::section($doc, $root, 'PulmonicValve', $d, [
            'pv_accel_time', 'pv_press_grad', 'pv_regurg',
            'pv_sys_pap', 'pv_pr_jet', 'pv_rvot_vti',
        ]);
        self::section($doc, $root, 'Ratio', $d, ['qp_qs']);
        self::section($doc, $root, 'Conclusion', $d, ['conclusion']);

        return $doc->saveXML();
    }

    public static function xmlToDoppler(string $xml): array
    {
        return self::flattenLeaves($xml);
    }

    // ══════════════════════════════════════════════════════════════════
    // Unified decode — handles XML (new) and JSON (legacy)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Smart decoder used by the Appointment model's getters.
     * Returns a flat PHP array regardless of the storage format.
     *
     * @param  string  $value  Raw column value from the database.
     * @param  string  $type  'echo' | 'tmt' | 'doppler'
     */
    public static function decode(string $value, string $type): array
    {
        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return [];
        }

        // ── New XML format ────────────────────────────────────────────
        if (str_starts_with($trimmed, '<')) {
            return match ($type) {
                'tmt' => self::xmlToTmt($value),
                'echo' => self::xmlToEcho($value),
                'doppler' => self::xmlToDoppler($value),
                default => self::flattenLeaves($value),
            };
        }

        // ── Legacy JSON fallback ──────────────────────────────────────
        // Keeps any rows saved before this migration fully readable.
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    // ══════════════════════════════════════════════════════════════════
    // Private DOM / SimpleXML helpers
    // ══════════════════════════════════════════════════════════════════

    /** Creates a DOMDocument + root element. Returns [$doc, $rootEl]. */
    private static function createDoc(string $rootName): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->appendChild($doc->createElement($rootName));

        return [$doc, $root];
    }

    /** Appends a named section element that wraps a set of leaf fields. */
    private static function section(
        DOMDocument $doc,
        DOMElement $parent,
        string $name,
        array $data,
        array $fields,
    ): void {
        $section = $parent->appendChild($doc->createElement($name));
        self::appendLeaves($doc, $section, $data, $fields);
    }

    /** Appends one child text element per field name. */
    private static function appendLeaves(
        DOMDocument $doc,
        DOMElement $parent,
        array $data,
        array $fields,
    ): void {
        foreach ($fields as $field) {
            $el = $doc->createElement($field);
            $el->appendChild($doc->createTextNode((string) ($data[$field] ?? '')));
            $parent->appendChild($el);
        }
    }

    /**
     * Recursively traverses the XML tree and collects leaf-node text values
     * into a flat associative array keyed by element name.
     *
     * <Stage> nodes are intentionally skipped — xmlToTmt() handles them.
     */
    private static function flattenLeaves(string $xmlString): array
    {
        try {
            $sxml = new SimpleXMLElement($xmlString);
        } catch (\Throwable) {
            return [];
        }

        $result = [];
        self::traverseLeaves($sxml, $result);

        return $result;
    }

    private static function traverseLeaves(SimpleXMLElement $node, array &$out): void
    {
        foreach ($node->children() as $child) {
            $name = (string) $child->getName();

            if ($name === 'Stage') {
                // Handled explicitly in xmlToTmt()
                continue;
            }

            if ($child->count() === 0) {
                $out[$name] = (string) $child;
            } else {
                self::traverseLeaves($child, $out);
            }
        }
    }
}
