<?php

namespace Tests\Unit;

use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\TreadmillTest;
use App\Support\ClinicalRecordMapper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClinicalRecordMapperTest extends TestCase
{
    public function test_echo_maps_normalized_columns_to_legacy_keys(): void
    {
        $echo = new Echocardiogram;
        $echo->lvef_percent = 34.0;
        $echo->lvfs_percent = 43.0;
        $echo->tapse_mm = 22;
        $echo->valve_findings = 'Normal valve findings';
        $echo->pericardial_effusion = false;
        $echo->notes = 'mnemonic';

        $legacy = ClinicalRecordMapper::echo($echo);

        $this->assertSame(34.0, $legacy['lv_ef']);
        $this->assertSame(43.0, $legacy['lv_fs']);
        $this->assertSame(22, $legacy['tapse']);
        $this->assertSame('Absent', $legacy['peri_ant']);
        $this->assertSame('Normal valve findings', $legacy['findings']);
    }

    public function test_tmt_recovers_embedded_form_json_and_strips_blob_from_conclusion(): void
    {
        $tmt = new TreadmillTest;
        $tmt->protocol = 'BRUCE';
        $tmt->notes = 'Negative exercise stress test. | {"pmhr":"173","bp":"120/80","resting_ecg":"Normal"}';

        $legacy = ClinicalRecordMapper::tmt($tmt);

        $this->assertSame('173', $legacy['pmhr']);
        $this->assertSame('120/80', $legacy['bp']);
        $this->assertSame('Normal', $legacy['resting_ecg']);
        $this->assertSame('Negative exercise stress test.', $legacy['conclusion']);
        $this->assertStringNotContainsString('{', $legacy['conclusion']);
    }

    public function test_doppler_recovers_embedded_form_json_and_strips_blob_from_conclusion(): void
    {
        $dop = new DopplerStudy;
        $dop->ea_ratio = 1.15;
        $dop->notes = 'Normal study. | {"qp_qs":"1.0","av_mean_grad":"4"}';

        $legacy = ClinicalRecordMapper::doppler($dop);

        $this->assertSame(1.15, $legacy['mv_ea_ratio']);
        $this->assertSame('1.0', $legacy['qp_qs']);
        $this->assertSame('4', $legacy['av_mean_grad']);
        $this->assertSame('Normal study.', $legacy['conclusion']);
    }

    public function test_html_escaped_embedded_json_is_recovered(): void
    {
        $tmt = new TreadmillTest;
        $tmt->notes = 'Some conclusion. | {&quot;age&quot;:&quot;34&quot;,&quot;bp&quot;:&quot;69&quot;}';

        $legacy = ClinicalRecordMapper::tmt($tmt);

        $this->assertSame('34', $legacy['age']);
        $this->assertSame('69', $legacy['bp']);
        $this->assertSame('Some conclusion.', $legacy['conclusion']);
    }

    public function test_for_payload_never_leaks_the_raw_blob_as_notes(): void
    {
        $echo = new Echocardiogram;
        $echo->lvef_percent = 34.0;
        $echo->valve_findings = 'Normal';
        $echo->notes = '| {"lv_ef":"34"}';

        $payload = ClinicalRecordMapper::forPayload($echo, null, null);

        $this->assertArrayHasKey('lv_ef', $payload);
        $this->assertNull($payload['notes']);
        $this->assertArrayNotHasKey('conclusion', $payload);
    }

    public function test_patient_context_includes_age_and_sex(): void
    {
        $patient = new Patient;
        $patient->sex = 'Female';
        $patient->date_of_birth = Carbon::now()->subYears(45)->toDateString();

        $legacy = ClinicalRecordMapper::echo(new Echocardiogram, $patient);

        $this->assertSame('Female', $legacy['sex']);
        $this->assertSame(45, $legacy['age']);
    }

    public function test_prescriptions_map_to_legacy_rows(): void
    {
        $rx = new Prescription;
        $rx->medication_name = 'Metoprolol';
        $rx->dosage = '25 mg';
        $rx->frequency = 'daily';
        $rx->duration_days = 14;

        $rows = ClinicalRecordMapper::prescriptions(new Collection([$rx]));

        $this->assertCount(1, $rows);
        $this->assertSame('Metoprolol', $rows[0]['medicine']);
        $this->assertSame('14 days', $rows[0]['duration']);
    }
}
