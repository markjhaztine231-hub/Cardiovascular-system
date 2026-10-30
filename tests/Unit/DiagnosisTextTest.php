<?php

namespace Tests\Unit;

use App\Support\DiagnosisText;
use PHPUnit\Framework\TestCase;

class DiagnosisTextTest extends TestCase
{
    public function test_true_placeholders_normalize_to_null(): void
    {
        foreach (['', '   ', 'N/A', 'na', 'n/a.', 'N.A.', 'N / A', 'none', 'NONE', 'no', 'nil', '--', '?', 'not applicable', 'no diagnosis'] as $placeholder) {
            $this->assertNull(
                DiagnosisText::normalize($placeholder),
                "'{$placeholder}' should be treated as a placeholder."
            );
        }
    }

    public function test_normalize_preserves_meaningful_clinical_notes(): void
    {
        $this->assertSame(
            'For clearance',
            DiagnosisText::normalize('  For   clearance  '),
        );

        $this->assertSame(
            'Essential (primary) hypertension',
            DiagnosisText::normalize(' Essential  (primary)   hypertension '),
        );
    }

    public function test_report_safe_diagnosis_rejects_clearance_and_doctor_requests(): void
    {
        foreach ([
            'For clearance',
            'Medical clearance',
            'Cleared for surgery',
            "Doctor's request",
            'For doctor request',
            'Requested by doctor',
            'Doctor requesting review',
            'Consultation only',
            'Fit for work',
            'normal',
            'Unremarkable',
        ] as $nonDiagnosis) {
            $this->assertNull(
                DiagnosisText::asDiagnosis($nonDiagnosis),
                "'{$nonDiagnosis}' must not be counted as a diagnosis."
            );
        }
    }

    public function test_real_diagnoses_are_not_over_filtered(): void
    {
        foreach ([
            'Essential (primary) hypertension',
            'Atrial fibrillation',
            'Non-ST elevation myocardial infarction',
            'Chronic heart failure',
            'Hypertensive heart disease',
        ] as $diagnosis) {
            $this->assertSame($diagnosis, DiagnosisText::asDiagnosis($diagnosis));
            $this->assertNotNull(DiagnosisText::key($diagnosis));
        }
    }

    public function test_key_collapses_case_and_whitespace_variants(): void
    {
        $this->assertSame(
            DiagnosisText::key('Essential (primary) hypertension'),
            DiagnosisText::key('essential  (primary)   hypertension'),
        );

        $this->assertSame(
            DiagnosisText::key('Atrial fibrillation'),
            DiagnosisText::key('  ATRIAL   FIBRILLATION  '),
        );
    }

    public function test_key_is_null_for_placeholders_and_visit_reasons(): void
    {
        $this->assertNull(DiagnosisText::key('N/A'));
        $this->assertNull(DiagnosisText::key('For clearance'));
        $this->assertNull(DiagnosisText::key(null));
    }
}
