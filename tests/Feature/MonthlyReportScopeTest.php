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
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Locks in the scope refactor: one stats builder serving both a single
 * doctor's report and a practice-wide one, with the practice row provably
 * equal to the sum of the per-doctor rows.
 */
class MonthlyReportScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouter.api_key' => 'test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Narrative body.']]],
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        config(['services.openrouter.api_key' => null]);

        parent::tearDown();
    }

    /**
     * @param  array<int, int>  $ages
     * @param  array<int, string>  $statuses
     */
    private function doctorWithPatients(array $ages, array $statuses = []): array
    {
        $user = User::factory()->create(['role' => 'doctor']);
        $provider = Provider::factory()->create(['user_id' => $user->id]);

        foreach ($ages as $i => $age) {
            $status = $statuses[$i] ?? 'Completed';

            $patient = Patient::factory()->create([
                'sex' => $i % 2 ? 'Female' : 'Male',
                'date_of_birth' => Carbon::now()->subYears($age)->startOfDay(),
            ]);

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
                'scheduled_date' => Carbon::parse('2026-09-10'),
                'scheduled_time' => '09:00',
                'service_type' => 'Consultation',
                'status' => $status,
            ]);

            if ($status !== 'Completed') {
                continue;
            }

            $encounter = Encounter::create([
                'provider_id' => $provider->id,
                'patient_id' => $patient->id,
                'encounter_type' => 'echo',
                'status' => 'completed',
                'started_at' => Carbon::parse('2026-09-10 09:00:00'),
            ]);

            Diagnosis::create([
                'encounter_id' => $encounter->id,
                'description' => 'Essential (primary) hypertension',
                'icd_code' => 'I10',
                'is_primary' => true,
            ]);
        }

        return [$user, $provider];
    }

    public function test_practice_row_is_the_sum_of_every_per_doctor_row(): void
    {
        [$userA] = $this->doctorWithPatients([20, 45, 67], ['Completed', 'Completed', 'No-Show']);
        [$userB] = $this->doctorWithPatients([28, 81]);

        MonthlyReportService::generate($userA->id, '2026-09');
        MonthlyReportService::generate($userB->id, '2026-09');
        MonthlyReportService::generateForProvider(null, '2026-09');

        $doctorRows = MonthlyReport::where('month', '2026-09')->where('scope', 'provider')->get();
        $practice = MonthlyReport::practice()->where('month', '2026-09')->firstOrFail();

        $this->assertCount(2, $doctorRows);

        foreach (['total', 'completed', 'no_shows', 'cancelled'] as $metric) {
            $sum = $doctorRows->sum(fn ($r) => $r->stats[$metric]);
            $this->assertSame($sum, $practice->stats[$metric], "practice {$metric} must equal the sum of doctors");
        }

        // Procedures and diagnoses aggregate too.
        $this->assertSame(
            $doctorRows->sum(fn ($r) => $r->stats['procedures']['total']),
            $practice->stats['procedures']['total'],
        );

        $practiceHtn = collect($practice->stats['diagnosis_by_age'])->firstWhere('code', 'I10');
        $this->assertSame($doctorRows->sum(
            fn ($r) => collect($r->stats['diagnosis_by_age'])->firstWhere('code', 'I10')['total']
        ), $practiceHtn['total']);
    }

    public function test_practice_report_spans_every_doctor_and_keeps_the_young_band(): void
    {
        $this->doctorWithPatients([20]);
        $this->doctorWithPatients([22]);

        $practice = MonthlyReportService::generateForProvider(null, '2026-09')['stats'];

        $this->assertSame(2, $practice['total']);

        $bands = collect($practice['age_distribution'])->keyBy('band');
        $this->assertSame(2, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);

        $htn = collect($practice['diagnosis_by_age'])->firstWhere('code', 'I10');
        $this->assertSame(2, $htn['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
    }

    public function test_a_doctor_report_excludes_other_doctors_activity(): void
    {
        [$userA] = $this->doctorWithPatients([20]);
        [$userB] = $this->doctorWithPatients([67, 81, 45]);

        $aStats = MonthlyReportService::generate($userA->id, '2026-09')['stats'];

        $this->assertSame(1, $aStats['total'], 'Dr A must only see their own appointment.');

        $bands = collect($aStats['age_distribution'])->keyBy('band');
        $this->assertSame(1, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);
        $this->assertSame(0, $bands[AgeBand::SIXTY_TO_SEVENTYFOUR]['count']);

        // And Dr B's patients never leak into A's hypertension rows.
        $htn = collect($aStats['diagnosis_by_age'])->firstWhere('code', 'I10');
        $this->assertSame(1, $htn['total']);
    }

    public function test_for_doctor_scope_never_returns_a_practice_row(): void
    {
        [$userA] = $this->doctorWithPatients([20]);

        MonthlyReportService::generate($userA->id, '2026-09');
        MonthlyReportService::generateForProvider(null, '2026-09');

        $rows = MonthlyReport::forDoctor($userA->id)->where('month', '2026-09')->get();

        $this->assertCount(1, $rows, 'forDoctor() must not pick up the practice-wide row.');
        $this->assertFalse($rows->first()->isPracticeWide());
        $this->assertSame(MonthlyReport::SCOPE_PROVIDER, $rows->first()->scope);
    }

    public function test_practice_row_has_a_null_provider_and_is_labelled(): void
    {
        $this->doctorWithPatients([20]);

        MonthlyReportService::generateForProvider(null, '2026-09');

        $practice = MonthlyReport::practice()->firstOrFail();

        $this->assertNull($practice->provider_id);
        $this->assertSame(MonthlyReport::SCOPE_PRACTICE, $practice->scope);
        $this->assertTrue($practice->isPracticeWide());
    }

    public function test_month_over_month_comparison_does_not_cross_scopes(): void
    {
        [$userA] = $this->doctorWithPatients([20]);
        [$userB] = $this->doctorWithPatients([67]);

        // Seed ONLY a practice-wide August report with a distinctive total.
        MonthlyReport::create([
            'provider_id' => null,
            'scope' => MonthlyReport::SCOPE_PRACTICE,
            'month' => '2026-08',
            'stats' => ['total' => 999, 'completed' => 999, 'no_shows' => 0, 'cancelled' => 0],
        ]);

        MonthlyReportService::generate($userA->id, '2026-09');

        $doctorSept = MonthlyReport::forDoctor($userA->id)->forMonth('2026-09')->latest()->first();
        $trends = $doctorSept->stats['diagnosis_trends'];

        // A per-doctor report must not treat the practice row as its own
        // previous month; that would fabricate a +998 swing.
        $this->assertSame([], $trends);
    }

    public function test_doctor_without_provider_row_persists_nothing_and_does_not_throw(): void
    {
        $orphan = User::factory()->create(['role' => 'doctor']);

        $result = MonthlyReportService::generate($orphan->id, '2026-09');

        $this->assertSame(0, $result['stats']['total']);
        $this->assertNull($result['report_text']);

        // No row at all: an unconfigured account must never be mistaken for a
        // practice-wide report.
        $this->assertSame(0, MonthlyReport::count());
        $this->assertSame(0, MonthlyReport::practice()->count());
    }

    /**
     * Http::fake() merges stubs, and the setUp default is registered first, so
     * a fresh factory is swapped in to fully replace the default narrative.
     */
    private function fakeNarrativeResponses(array $stubs): void
    {
        Http::swap(new Factory);

        Http::fake($stubs);
    }

    public function test_empty_ai_content_is_treated_as_a_failure_and_not_persisted(): void
    {
        // A reasoning model can return 200 with an empty string once its token
        // budget is spent. That must never be stored as a "blank" narrative.
        $this->fakeNarrativeResponses([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => "   \n  "]]],
            ]),
        ]);

        [$user] = $this->doctorWithPatients([20]);

        $result = MonthlyReportService::generate($user->id, '2026-09');

        $this->assertNull($result['report_text']);

        $row = MonthlyReport::forDoctor($user->id)->forMonth('2026-09')->latest()->firstOrFail();
        $this->assertNull($row->report_text);
        $this->assertGreaterThan(0, $row->stats['total'], 'stats must still be persisted');
    }

    public function test_transient_server_error_is_retried_until_success(): void
    {
        $this->fakeNarrativeResponses([
            'openrouter.ai/*' => Http::sequence()
                ->push('upstream exploded', 500)
                ->push(['choices' => [['message' => ['content' => 'Recovered narrative.']]]]),
        ]);

        [$user] = $this->doctorWithPatients([20]);

        $result = MonthlyReportService::generate($user->id, '2026-09');

        $this->assertSame('Recovered narrative.', $result['report_text']);
    }

    public function test_generation_surfaces_an_error_when_the_narrative_is_unavailable(): void
    {
        $this->fakeNarrativeResponses([
            'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '']]]]),
        ]);

        [$user] = $this->doctorWithPatients([20]);

        $response = $this->actingAs($user)
            ->post('/doctor/reports/monthly', ['month' => '2026-09']);

        $response->assertSessionHasErrors('month');
        $this->assertDatabaseHas('monthly_reports', ['month' => '2026-09']);
    }

    public function test_practice_scope_generates_one_combined_report_with_a_doctor_breakdown(): void
    {
        [$userA] = $this->doctorWithPatients([20]);
        $this->doctorWithPatients([67, 81]);

        MonthlyReportService::generate($userA->id, '2026-09', MonthlyReport::SCOPE_PRACTICE);

        $practice = MonthlyReport::practice()->forMonth('2026-09')->firstOrFail();

        $this->assertSame(3, $practice->stats['total']);
        $this->assertCount(2, $practice->stats['by_provider']);

        // The practice request must not also create a personal row for user A.
        $this->assertSame(0, MonthlyReport::forDoctor($userA->id)->count());
    }

    public function test_personal_and_practice_scopes_are_generated_and_read_separately(): void
    {
        [$userA] = $this->doctorWithPatients([20]);
        $this->doctorWithPatients([67, 81]);

        $this->actingAs($userA)
            ->post('/doctor/reports/monthly', ['month' => '2026-09', 'scope' => 'personal'])
            ->assertOk();

        $personal = MonthlyReport::forDoctor($userA->id)->forMonth('2026-09')->firstOrFail();
        $this->assertSame(1, $personal->stats['total']);

        $this->actingAs($userA)
            ->post('/doctor/reports/monthly', ['month' => '2026-09', 'scope' => 'practice'])
            ->assertOk();

        $practice = MonthlyReport::practice()->forMonth('2026-09')->latest()->firstOrFail();
        $this->assertSame(3, $practice->stats['total']);

        // Loading with ?scope=practice must surface the practice row, and the
        // default (no scope) must remain the doctor's personal report.
        $this->actingAs($userA)
            ->get('/doctor/reports/monthly?scope=practice')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/MonthlyReport')
                ->where('currentScope', MonthlyReport::SCOPE_PRACTICE)
                ->where('currentReportId', $practice->id));

        $this->actingAs($userA)
            ->get('/doctor/reports/monthly')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/MonthlyReport')
                ->where('currentScope', MonthlyReport::SCOPE_PROVIDER)
                ->where('currentReportId', $personal->id));
    }

    public function test_a_doctor_cannot_open_another_doctors_personal_report(): void
    {
        [$userA] = $this->doctorWithPatients([20]);
        [$userB] = $this->doctorWithPatients([67]);

        MonthlyReportService::generate($userB->id, '2026-09');
        $bReport = MonthlyReport::forDoctor($userB->id)->forMonth('2026-09')->firstOrFail();

        $this->actingAs($userA)
            ->get('/doctor/reports/monthly?report='.$bReport->id)
            ->assertNotFound();
    }
}
