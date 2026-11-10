<?php

namespace Tests\Unit;

use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\ManualDraft;
use App\Models\Prescription;
use App\Models\StressEchocardiogram;
use App\Models\TreadmillTest;
use App\Support\ClinicalFormData;
use Tests\TestCase;

/**
 * The history listing, PDF export and save endpoints all share this detector,
 * so these rules are the single definition of "this form was actually filled
 * in". Defaults that ship with the forms must never count as user input.
 */
class ClinicalFormDataTest extends TestCase
{
    public function test_echo_defaults_do_not_count_as_content(): void
    {
        $blank = [
            'file_no' => '2026-0001',
            'age' => '54',
            'sex' => 'M',
            'tricuspid_valve' => 'normal',
            'wall_motion' => 'Tricuspid valve: normal',
            'peri_ant' => 'Absent',
            'peri_post' => 'Absent',
            'pericardial_effusion' => false,
            'findings' => '',
        ];

        $this->assertFalse(ClinicalFormData::hasContent('echo', $blank));
        $this->assertTrue(ClinicalFormData::hasContent('echo', $blank + ['lv_ef' => '55']));
        $this->assertTrue(ClinicalFormData::hasContent('echo', array_merge($blank, ['pericardial_effusion' => true])));
    }

    public function test_tmt_stage_presets_and_protocol_defaults_do_not_count(): void
    {
        $blank = [
            'file_no' => '2026-0001',
            'age' => '54',
            'sex' => 'M',
            'procedure' => 'Treadmill Exercise Test',
            'protocol' => 'BRUCE, MODIFIED BRUCE, NIH',
            'stages' => [
                ['name' => 'Pre-test', 'speed' => '0', 'grade' => '0', 'mets' => '0', 'bp' => '', 'hr' => '', 'remarks' => ''],
                ['name' => 'STAGE 1', 'speed' => '1.7', 'grade' => '10', 'mets' => '4.6', 'bp' => '', 'hr' => '', 'remarks' => ''],
            ],
        ];

        $this->assertFalse(ClinicalFormData::hasContent('tmt', $blank));
        $this->assertTrue(ClinicalFormData::hasContent('tmt', $blank + ['peak_hr' => '150']));
        $this->assertTrue(ClinicalFormData::hasContent('tmt', [
            'stages' => [['bp' => '150/90', 'hr' => '', 'remarks' => '']],
        ]));
    }

    public function test_doppler_jet_defaults_do_not_count(): void
    {
        $this->assertFalse(ClinicalFormData::hasContent('doppler', [
            'file_no' => '2026-0001',
            'age' => '54',
            'sex' => 'F',
            'av_ar_jet' => 'Faint',
            'pv_pr_jet' => 'Faint',
        ]));

        $this->assertTrue(ClinicalFormData::hasContent('doppler', [
            'av_ar_jet' => 'Faint',
            'pv_pr_jet' => 'Faint',
            'ea_ratio' => '1.1',
        ]));
    }

    public function test_stress_echo_and_abpm_only_ignore_generated_context(): void
    {
        $generated = ['file_no' => '2026-0001', 'age' => '54', 'sex' => 'M'];

        $this->assertFalse(ClinicalFormData::hasContent('stress_echo', $generated + ['stages' => []]));
        $this->assertTrue(ClinicalFormData::hasContent('stress_echo', $generated + ['peak_hr' => '150']));

        $this->assertFalse(ClinicalFormData::hasContent('abpm', $generated));
        $this->assertTrue(ClinicalFormData::hasContent('abpm', $generated + ['monitor_device' => 'Spacelabs']));
    }

    public function test_manual_draft_requires_narrative_text_not_attachments(): void
    {
        $this->assertFalse(ClinicalFormData::hasContent('manual_draft', [
            'images' => [['url' => '/storage/scans/scan.png', 'name' => 'scan.png']],
        ]));

        $this->assertTrue(ClinicalFormData::hasContent('manual_draft', [
            'title' => 'External echo review',
        ]));
    }

    public function test_blank_prescriptions_and_requests_have_no_content(): void
    {
        $this->assertFalse(ClinicalFormData::prescriptionHasContent(
            new Prescription(['medication_name' => '', 'is_freeform' => false, 'free_text' => null])
        ));
        $this->assertTrue(ClinicalFormData::prescriptionHasContent(
            new Prescription(['medication_name' => 'Amlodipine', 'is_freeform' => false])
        ));
        $this->assertTrue(ClinicalFormData::prescriptionHasContent(
            new Prescription(['medication_name' => '', 'is_freeform' => true, 'free_text' => 'Take as needed'])
        ));

        $this->assertFalse(ClinicalFormData::requestHasContent(['body' => '   ']));
        $this->assertTrue(ClinicalFormData::requestHasContent(['body' => 'CBC and CXR']));
    }

    public function test_stored_default_only_studies_are_reported_empty(): void
    {
        $echo = new Echocardiogram;
        $echo->wall_motion = 'Tricuspid valve: normal';
        $echo->pericardial_effusion = false;
        $echo->notes = ' | {"tricuspid_valve":"normal","peri_ant":"Absent"}';
        $this->assertFalse(ClinicalFormData::modelHasContent('echo', $echo));

        $echo->lvef_percent = 55;
        $this->assertTrue(ClinicalFormData::modelHasContent('echo', $echo));

        $tmt = new TreadmillTest;
        $tmt->protocol = 'BRUCE, MODIFIED BRUCE, NIH';
        $tmt->notes = ' | {"procedure":"Treadmill Exercise Test","stages":[{"name":"STAGE 1","bp":"","hr":"","remarks":""}]}';
        $this->assertFalse(ClinicalFormData::modelHasContent('tmt', $tmt));

        $tmt->peak_heart_rate_bpm = 148;
        $this->assertTrue(ClinicalFormData::modelHasContent('tmt', $tmt));

        $doppler = new DopplerStudy;
        $doppler->notes = ' | {"av_ar_jet":"Faint","pv_pr_jet":"Faint"}';
        $this->assertFalse(ClinicalFormData::modelHasContent('doppler', $doppler));

        $doppler->ea_ratio = 1.1;
        $this->assertTrue(ClinicalFormData::modelHasContent('doppler', $doppler));

        $stress = new StressEchocardiogram;
        $stress->payload = ['file_no' => '2026-0001', 'age' => '54', 'sex' => 'M', 'stages' => []];
        $this->assertFalse(ClinicalFormData::modelHasContent('stress_echo', $stress));

        $draft = new ManualDraft;
        $draft->attachments = [['url' => '/storage/scans/scan.png', 'name' => 'scan.png']];
        $this->assertFalse(ClinicalFormData::modelHasContent('manual_draft', $draft));

        $draft->draft = 'Reviewed the external report.';
        $this->assertTrue(ClinicalFormData::modelHasContent('manual_draft', $draft));
    }
}
