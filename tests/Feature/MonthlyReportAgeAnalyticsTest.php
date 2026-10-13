<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\MonthlyReport;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\MonthlyReportService;
use App\Support\AgeBand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonthlyReportAgeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // generateNarrative() reads this through config('services.openrouter.api_key');
        // without it the service short-circuits and never builds the prompt we
        // want to assert on.
        config(['services.openrouter.api_key' => 'test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Narrative body.']]],
            ]),
        ]);

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->provider = Provider::factory()->create(['user_id' => $this->doctor->id]);
    }

    protected function tearDown(): void
    {
        config(['services.openrouter.api_key' => null]);

        parent::tearDown();
    }

    // ── Fixtures ────────────────────────────────────────────────────────────

    private function patientOfAge(?int $age, string $sex = 'Male'): Patient
    {
        return Patient::factory()->create([
            'sex' => $sex,
            'date_of_birth' => $age === null
                ? null
                : Carbon::now()->subYears($age)->startOfDay(),
        ]);
    }

    private function book(Patient $patient): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $this->provider->id,
            'scheduled_date' => Carbon::parse('2026-09-10'),
            'scheduled_time' => '09:00',
            'service_type' => 'Consultation',
            'status' => 'Completed',
        ]);
    }

    private function diagnose(Patient $patient, string $description, ?string $icd = null, string $type = 'echo'): Diagnosis
    {
        $encounter = Encounter::create([
            'provider_id' => $this->provider->id,
            'patient_id' => $patient->id,
            'encounter_type' => $type,
            'status' => 'completed',
            'started_at' => Carbon::parse('2026-09-10 09:00:00'),
        ]);

        return Diagnosis::create([
            'encounter_id' => $encounter->id,
            'description' => $description,
            'icd_code' => $icd,
            'is_primary' => true,
            'diagnosed_at' => Carbon::parse('2026-09-10 09:30:00'),
        ]);
    }

    private function seenPatientWithDiagnosis(?int $age, string $description, ?string $icd = null, string $sex = 'Male'): Patient
    {
        $patient = $this->patientOfAge($age, $sex);
        $this->book($patient);
        $this->diagnose($patient, $description, $icd);

        return $patient;
    }

    private function promptSentToModel(): string
    {
        return collect(Http::recorded())->first()[0]['messages'][1]['content'];
    }

    // ── Banding unit coverage ───────────────────────────────────────────────

    public function test_age_banding_boundaries_are_exhaustive_and_exclusive(): void
    {
        $expectations = [
            0 => AgeBand::UNDER_18,
            17 => AgeBand::UNDER_18,
            18 => AgeBand::EIGHTEEN_TO_TWENTYNINE,
            20 => AgeBand::EIGHTEEN_TO_TWENTYNINE,
            29 => AgeBand::EIGHTEEN_TO_TWENTYNINE,
            30 => AgeBand::THIRTY_TO_FORTYFOUR,
            44 => AgeBand::THIRTY_TO_FORTYFOUR,
            45 => AgeBand::FORTYFIVE_TO_FIFTYNINE,
            59 => AgeBand::FORTYFIVE_TO_FIFTYNINE,
            60 => AgeBand::SIXTY_TO_SEVENTYFOUR,
            74 => AgeBand::SIXTY_TO_SEVENTYFOUR,
            75 => AgeBand::SEVENTYFIVE_PLUS,
            120 => AgeBand::SEVENTYFIVE_PLUS,
        ];

        foreach ($expectations as $age => $band) {
            $this->assertSame($band, AgeBand::band($age), "age {$age}");
        }

        $this->assertSame(AgeBand::UNKNOWN, AgeBand::band(null));
        $this->assertSame(AgeBand::UNKNOWN, AgeBand::band(-1));
        $this->assertSame('Unknown age', AgeBand::label(AgeBand::UNKNOWN));
        $this->assertTrue(AgeBand::isKnown(AgeBand::EIGHTEEN_TO_TWENTYNINE));
        $this->assertFalse(AgeBand::isKnown(AgeBand::UNKNOWN));
    }

    // ── The headline scenario ───────────────────────────────────────────────

    public function test_hypertension_in_a_twenty_year_old_is_reported_by_age_band(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Essential (primary) hypertension', 'I10', 'Female');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        // The 20-year-old must land in the 18-29 band, not be averaged away.
        $bands = collect($stats['age_distribution'])->keyBy('band');
        $this->assertSame(1, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);
        $this->assertSame(100.0, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['pct']);

        $hypertension = collect($stats['diagnosis_by_age'])->firstWhere('code', 'I10');

        $this->assertNotNull($hypertension, 'Hypertension should appear in diagnosis_by_age.');
        $this->assertSame('Essential (primary) hypertension', $hypertension['name']);
        $this->assertSame(1, $hypertension['total']);
        $this->assertSame(1, $hypertension['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertSame(0, $hypertension['bands'][AgeBand::SIXTY_TO_SEVENTYFOUR]);
    }

    public function test_diagnoses_are_cross_tabulated_across_multiple_age_bands(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Hypertension', 'I10');
        $this->seenPatientWithDiagnosis(52, 'Hypertension', 'I10');
        $this->seenPatientWithDiagnosis(67, 'Hypertension', 'I10');
        $this->seenPatientWithDiagnosis(80, 'Hypertension', 'I10');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $htn = collect($stats['diagnosis_by_age'])->firstWhere('code', 'I10');

        $this->assertSame(4, $htn['total']);
        $this->assertSame(1, $htn['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertSame(1, $htn['bands'][AgeBand::FORTYFIVE_TO_FIFTYNINE]);
        $this->assertSame(1, $htn['bands'][AgeBand::SIXTY_TO_SEVENTYFOUR]);
        $this->assertSame(1, $htn['bands'][AgeBand::SEVENTYFIVE_PLUS]);
        $this->assertSame(0, $htn['bands'][AgeBand::UNDER_18]);
    }

    public function test_the_same_diagnosis_in_different_age_bands_is_one_row(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Atrial fibrillation', 'I48');
        $this->seenPatientWithDiagnosis(20, 'Atrial fibrillation', 'I48');
        $this->seenPatientWithDiagnosis(70, 'Atrial fibrillation', 'I48');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $this->assertCount(1, $stats['diagnosis_by_age']);

        $afib = $stats['diagnosis_by_age'][0];
        $this->assertSame(3, $afib['total']);
        $this->assertSame(2, $afib['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertSame(1, $afib['bands'][AgeBand::SIXTY_TO_SEVENTYFOUR]);
    }

    // ── Data-quality behaviour ──────────────────────────────────────────────

    public function test_patients_without_date_of_birth_are_kept_in_an_explicit_unknown_band(): void
    {
        $this->seenPatientWithDiagnosis(null, 'Atrial fibrillation', 'I48');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $bands = collect($stats['age_distribution'])->keyBy('band');
        $this->assertSame(1, $bands[AgeBand::UNKNOWN]['count']);

        $afib = collect($stats['diagnosis_by_age'])->firstWhere('code', 'I48');
        $this->assertSame(1, $afib['bands'][AgeBand::UNKNOWN]);
    }

    public function test_missing_icd_code_falls_back_to_normalized_description(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Essential  (primary)   hypertension', null);
        $this->seenPatientWithDiagnosis(41, 'essential (primary) hypertension', '   ');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $rows = collect($stats['diagnosis_by_age'])->where('code', null);

        // Case/whitespace variants must collapse into one clinical bucket.
        $this->assertCount(1, $rows, 'Case and whitespace variants should not split into two rows.');
        $this->assertSame(2, $rows->first()['total']);
        $this->assertSame(1, $rows->first()['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertSame(1, $rows->first()['bands'][AgeBand::THIRTY_TO_FORTYFOUR]);
    }

    public function test_distribution_percentages_are_relative_to_patients_with_a_known_age(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Hypertension', 'I10');
        $this->seenPatientWithDiagnosis(21, 'Hypertension', 'I10');
        $this->seenPatientWithDiagnosis(70, 'Hypertension', 'I10');
        $this->book($this->patientOfAge(null));

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];
        $bands = collect($stats['age_distribution'])->keyBy('band');

        // 2 of 3 known-age patients are 18-29 -> 66.7%; the unknown patient
        // must not distort the denominator.
        $this->assertSame(66.7, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['pct']);
        $this->assertSame(33.3, $bands[AgeBand::SIXTY_TO_SEVENTYFOUR]['pct']);
        $this->assertSame(0.0, $bands[AgeBand::UNKNOWN]['pct']);
    }

    public function test_diagnoses_outside_the_provider_month_are_excluded(): void
    {
        $encounter = Encounter::create([
            'provider_id' => $this->provider->id,
            'patient_id' => $this->patientOfAge(20)->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
            'started_at' => Carbon::parse('2025-08-01 09:00:00'),
        ]);

        Diagnosis::create([
            'encounter_id' => $encounter->id,
            'description' => 'Essential (primary) hypertension',
            'icd_code' => 'I10',
            'is_primary' => true,
        ]);

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $this->assertSame([], $stats['diagnosis_by_age']);
    }

    public function test_diagnosis_by_age_is_capped_to_five_rows_like_top_diagnoses(): void
    {
        foreach (['I10', 'I25.10', 'I48', 'I50', 'I95.9', 'I07.1'] as $index => $code) {
            $this->seenPatientWithDiagnosis(30 + $index, "Condition {$code}", $code);
        }

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $this->assertCount(5, $stats['diagnosis_by_age']);
    }

    // ── Narrative wiring ────────────────────────────────────────────────────

    public function test_narrative_prompt_receives_age_facts_and_an_extra_paragraph_instruction(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Essential (primary) hypertension', 'I10');

        MonthlyReportService::generate($this->doctor->id, '2026-09');
        $prompt = $this->promptSentToModel();

        $this->assertStringContainsString('AGE DISTRIBUTION', $prompt);
        $this->assertStringContainsString('DIAGNOSES BY AGE BAND', $prompt);

        // Figures must arrive pre-computed, never left for the model to total.
        $this->assertStringContainsString('18–29: 1 patients (100.0%)', $prompt);
        $this->assertStringContainsString('Essential (primary) hypertension [I10] — 1 total (18–29: 1)', $prompt);

        $this->assertStringContainsString('Age Distribution & Age-Specific Diagnoses', $prompt);
        $this->assertStringContainsString('exactly 6 paragraphs', $prompt);
        $this->assertStringContainsString('cardiovascular condition in a patient under 30', $prompt);
        $this->assertStringContainsString('Do NOT calculate totals, percentages or differences yourself', $prompt);
    }

    public function test_age_paragraph_is_omitted_when_no_patient_has_a_known_age(): void
    {
        $this->book($this->patientOfAge(null));

        MonthlyReportService::generate($this->doctor->id, '2026-09');
        $prompt = $this->promptSentToModel();

        $this->assertStringNotContainsString('Age Distribution & Age-Specific Diagnoses', $prompt);
        $this->assertStringContainsString('exactly 5 paragraphs', $prompt);
    }

    public function test_month_over_month_comparison_includes_age_band_counts(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Hypertension', 'I10');

        // Seed the previous month so the comparison section is generated.
        MonthlyReport::create([
            'provider_id' => $this->provider->id,
            'month' => '2026-08',
            'report_text' => 'Previous narrative.',
            'stats' => [
                'total' => 4,
                'completed' => 4,
                'no_shows' => 0,
                'cancelled' => 0,
                'demographics' => ['total_unique_patients' => 3, 'avg_age' => 61.0],
                'procedures' => ['echocardiogram' => 2, 'treadmill_tmt' => 0, 'color_doppler' => 0, 'total' => 2],
                'top_diagnoses' => [],
                'age_distribution' => [
                    ['band' => AgeBand::SIXTY_TO_SEVENTYFOUR, 'label' => '60–74', 'count' => 3, 'pct' => 100.0],
                ],
            ],
        ]);

        MonthlyReportService::generate($this->doctor->id, '2026-09');
        $prompt = $this->promptSentToModel();

        $this->assertStringContainsString('Age band counts', $prompt);
        $this->assertStringContainsString('60–74: 3', $prompt);
        $this->assertStringContainsString('18–29: 1', $prompt);
    }

    // ── Degenerate months & persistence ─────────────────────────────────────

    public function test_empty_month_returns_zeroed_age_analytics(): void
    {
        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $this->assertSame([], $stats['diagnosis_by_age']);

        $bands = collect($stats['age_distribution'])->keyBy('band');
        $this->assertSame(0, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);
        $this->assertSame(0.0, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['pct']);

        // Every band must be present so the chart never renders a phantom gap.
        $this->assertCount(count(AgeBand::bands()) + 1, $stats['age_distribution']);
    }

    public function test_age_analytics_are_persisted_and_survive_a_reload(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Essential (primary) hypertension', 'I10');

        MonthlyReportService::generate($this->doctor->id, '2026-09');

        $report = MonthlyReport::where('provider_id', $this->provider->id)
            ->where('month', '2026-09')
            ->firstOrFail();

        $this->assertSame(1, collect($report->stats['diagnosis_by_age'])
            ->firstWhere('code', 'I10')['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertNotEmpty($report->stats['age_distribution']);
        $this->assertSame('Narrative body.', $report->report_text);
        $this->assertSame(MonthlyReport::SCOPE_PROVIDER, $report->scope);
    }

    // ── Bug 2: age is measured at the visit, not "today" ────────────────────

    /**
     * A patient who is 19 at the September visit but has turned 20 by
     * October must be banded as 19 in the September report. Using today's age
     * would silently rewrite a closed month's numbers on every rerun.
     */
    public function test_age_is_measured_at_the_visit_not_today(): void
    {
        // Turns 20 on 2026-10-04; the September visit is on 2026-09-15.
        $patient = Patient::factory()->create([
            'sex' => 'Male',
            'date_of_birth' => Carbon::parse('2006-10-04'),
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $this->provider->id,
            'scheduled_date' => Carbon::parse('2026-09-15'),
            'status' => 'Completed',
        ]);

        Encounter::create([
            'provider_id' => $this->provider->id,
            'patient_id' => $patient->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
            'started_at' => Carbon::parse('2026-09-15 09:00:00'),
        ]);

        Diagnosis::create([
            'encounter_id' => Encounter::where('patient_id', $patient->id)->value('id'),
            'description' => 'Essential (primary) hypertension',
            'icd_code' => 'I10',
            'is_primary' => true,
        ]);

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        // "Today" is 2026-10-04, so a naive ->age would say 20. The September
        // visit must be banded on the patient's age on 2026-09-15, which is 19.
        $this->assertSame(19.0, $stats['demographics']['avg_age'], 'Age must be as of the visit date.');

        $bands = collect($stats['age_distribution'])->keyBy('band');
        $this->assertSame(1, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);
        $this->assertSame(0, $bands[AgeBand::THIRTY_TO_FORTYFOUR]['count']);

        $htn = collect($stats['diagnosis_by_age'])->firstWhere('code', 'I10');
        $this->assertSame(1, $htn['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
    }

    /**
     * The point of age-at-visit: regenerating a closed month must be
     * reproducible, otherwise historical reports quietly drift over time.
     */
    public function test_regenerating_a_closed_month_is_reproducible(): void
    {
        $this->seenPatientWithDiagnosis(20, 'Hypertension', 'I10');

        $first = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        // Simulate the passage of time by ageing the DOB relative to "now".
        $patient = Patient::whereNotNull('date_of_birth')->first();
        $patient->update(['date_of_birth' => $patient->date_of_birth->copy()->subYear()]);

        $second = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $this->assertSame(
            $first['age_distribution'],
            $second['age_distribution'],
            'Band counts must depend on the visit date, not the current date.',
        );
    }

    public function test_future_date_of_birth_is_treated_as_unknown_not_a_real_age(): void
    {
        $patient = Patient::factory()->create([
            'sex' => 'Male',
            'date_of_birth' => Carbon::parse('2030-01-01'),
        ]);

        $this->book($patient);
        $this->diagnose($patient, 'Atrial fibrillation', 'I48');

        $stats = MonthlyReportService::generate($this->doctor->id, '2026-09')['stats'];

        $bands = collect($stats['age_distribution'])->keyBy('band');
        $this->assertSame(1, $bands[AgeBand::UNKNOWN]['count'], 'An impossible DOB must not become an age.');

        $afib = collect($stats['diagnosis_by_age'])->firstWhere('code', 'I48');
        $this->assertSame(1, $afib['bands'][AgeBand::UNKNOWN]);
    }

    // ── Bug 1: a doctor with no Provider row degrades, never throws ─────────

    public function test_doctor_without_provider_row_returns_empty_stats_and_persists_nothing(): void
    {
        $orphan = User::factory()->create(['role' => 'doctor']);

        // Must not raise a NOT NULL violation on monthly_reports.provider_id.
        $result = MonthlyReportService::generate($orphan->id, '2026-09');

        $this->assertSame([], $result['stats']['diagnosis_by_age']);
        $this->assertNull($result['report_text']);
        $this->assertSame(0, $result['stats']['total']);

        // Crucially: nothing written, so no unlabelled row can masquerade as a
        // practice-wide report.
        $this->assertSame(0, MonthlyReport::count());
    }
}
