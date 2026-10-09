<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
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

/**
 * The practice-wide report and its admin-only per-doctor breakdown.
 */
class PracticeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouter.api_key' => 'test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Practice narrative.']]],
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param  array<int, int>  $ages
     * @param  array<int, string>  $statuses
     */
    private function doctor(string $name, array $ages, array $statuses = []): User
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'doctor']);
        $provider = Provider::factory()->create([
            'user_id' => $user->id,
            'specialty' => 'Cardiology',
        ]);

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
                'service_type' => 'General Cardiology',
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

        return $user;
    }

    public function test_practice_report_persists_one_practice_scoped_row(): void
    {
        $this->doctor('Dr Alpha', [20, 45]);
        $this->doctor('Dr Beta', [67]);

        $result = MonthlyReportService::generatePracticeReport('2026-09');

        $this->assertSame('Practice narrative.', $result['report_text']);

        $row = MonthlyReport::practice()->where('month', '2026-09')->firstOrFail();

        $this->assertNull($row->provider_id);
        $this->assertSame(MonthlyReport::SCOPE_PRACTICE, $row->scope);
        $this->assertSame(3, $row->stats['total']);
        $this->assertCount(2, $row->stats['by_provider']);

        // The breakdown must not create extra rows for the doctors.
        $this->assertSame(1, MonthlyReport::count());
    }

    public function test_breakdown_rows_carry_the_doctor_identity_and_volumes(): void
    {
        $this->doctor('Dr Alpha', [20, 45, 67], ['Completed', 'Completed', 'No-Show']);
        $this->doctor('Dr Beta', [28]);

        $breakdown = MonthlyReportService::practiceBreakdown('2026-09');

        $this->assertCount(2, $breakdown);

        // Busiest first.
        $this->assertSame('Dr Alpha', $breakdown[0]['doctor_name']);
        $this->assertSame(3, $breakdown[0]['total']);
        $this->assertSame(2, $breakdown[0]['completed']);
        $this->assertSame(1, $breakdown[0]['no_shows']);
        $this->assertSame(3, $breakdown[0]['unique_patients']);
        $this->assertSame('Cardiology', $breakdown[0]['specialty']);
        $this->assertNotNull($breakdown[0]['provider_id']);

        $this->assertSame(1, $breakdown[1]['total']);
        $this->assertSame(0, $breakdown[1]['no_shows']);
    }

    public function test_breakdown_reconciles_with_each_doctors_own_report(): void
    {
        $alpha = $this->doctor('Dr Alpha', [20, 45, 67]);
        $this->doctor('Dr Beta', [28, 81]);

        MonthlyReportService::generate($alpha->id, '2026-09');
        $breakdown = MonthlyReportService::practiceBreakdown('2026-09');

        $own = MonthlyReport::forDoctor($alpha->id)->forMonth('2026-09')->latest()->firstOrFail();
        $alphaRow = collect($breakdown)->firstWhere('doctor_name', 'Dr Alpha');

        $this->assertSame($own->stats['total'], $alphaRow['total']);
        $this->assertSame($own->stats['completed'], $alphaRow['completed']);
        $this->assertSame($own->stats['no_shows'], $alphaRow['no_shows']);
        $this->assertSame(
            $own->stats['demographics']['total_unique_patients'],
            $alphaRow['unique_patients'],
        );
    }

    public function test_breakdown_ages_use_one_reference_date_per_patient(): void
    {
        // A patient whose birthday falls mid-month must appear in exactly one
        // band, and the same band on both charts.
        $user = User::factory()->create(['name' => 'Dr Alpha', 'role' => 'doctor']);
        $provider = Provider::factory()->create(['user_id' => $user->id]);

        $patient = Patient::factory()->create([
            'sex' => 'Male',
            'date_of_birth' => Carbon::parse('2006-09-20'),
        ]);

        // Two appointments either side of the birthday.
        foreach (['2026-09-10', '2026-09-25'] as $day) {
            Appointment::create([
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
                'scheduled_date' => Carbon::parse($day),
                'scheduled_time' => '09:00',
                'status' => 'Completed',
            ]);

            $encounter = Encounter::create([
                'provider_id' => $provider->id,
                'patient_id' => $patient->id,
                'encounter_type' => 'echo',
                'status' => 'completed',
                'started_at' => Carbon::parse($day.' 09:00:00'),
            ]);

            Diagnosis::create([
                'encounter_id' => $encounter->id,
                'description' => 'Essential (primary) hypertension',
                'icd_code' => 'I10',
                'is_primary' => true,
            ]);
        }

        $result = MonthlyReportService::generatePracticeReport('2026-09');

        $bands = collect($result['stats']['age_distribution'])->keyBy('band');

        // One patient, counted once (first visit 09-10 → age 19).
        $this->assertSame(1, $bands[AgeBand::EIGHTEEN_TO_TWENTYNINE]['count']);
        $this->assertSame(0, $bands[AgeBand::THIRTY_TO_FORTYFOUR]['count']);

        // Both diagnoses land in that same band, never split across two.
        $htn = collect($result['stats']['diagnosis_by_age'])->firstWhere('code', 'I10');
        $this->assertSame(2, $htn['total']);
        $this->assertSame(2, $htn['bands'][AgeBand::EIGHTEEN_TO_TWENTYNINE]);
        $this->assertSame(0, $htn['bands'][AgeBand::THIRTY_TO_FORTYFOUR]);
    }

    public function test_practice_report_with_no_activity_persists_an_empty_row(): void
    {
        $result = MonthlyReportService::generatePracticeReport('2026-09');

        $this->assertSame(0, $result['stats']['total']);
        $this->assertSame([], $result['by_provider']);
        $this->assertNull($result['report_text']);

        $row = MonthlyReport::practice()->where('month', '2026-09')->firstOrFail();
        $this->assertSame(0, $row->stats['total']);
    }

    public function test_practice_report_is_invisible_to_doctors(): void
    {
        $doctor = $this->doctor('Dr Alpha', [20]);
        MonthlyReportService::generatePracticeReport('2026-09');

        $this->actingAs($doctor)
            ->get('/admin/reports/practice?month=2026-09')
            ->assertForbidden();
    }

    public function test_practice_report_is_invisible_to_receptionists(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        MonthlyReportService::generatePracticeReport('2026-09');

        $this->actingAs($receptionist)
            ->get('/admin/reports/practice?month=2026-09')
            ->assertForbidden();
    }

    public function test_practice_report_requires_authentication(): void
    {
        $this->get('/admin/reports/practice?month=2026-09')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_practice_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->doctor('Dr Alpha', [20, 45]);
        $this->doctor('Dr Beta', [67]);

        MonthlyReportService::generatePracticeReport('2026-09');

        $this->actingAs($admin)
            ->get('/admin/reports/practice?month=2026-09')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/PracticeReport')
                ->where('month', '2026-09')
                ->where('reportText', 'Practice narrative.')
                ->has('byProvider', 2)
                ->where('byProvider.0.doctor_name', 'Dr Alpha')
                ->where('stats.total', 3)
            );
    }

    public function test_viewing_the_report_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        MonthlyReportService::generatePracticeReport('2026-09');

        $this->actingAs($admin)->get('/admin/reports/practice?month=2026-09')->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'Viewed practice-wide monthly report for 2026-09 from IP: 127.0.0.1',
        ]);
    }

    public function test_viewing_a_month_with_no_report_redirects_with_an_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/reports/practice')
            ->get('/admin/reports/practice?month=2020-01')
            ->assertRedirect('/admin/reports/practice')
            ->assertSessionHas('error');
    }

    public function test_viewing_never_creates_a_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->doctor('Dr Alpha', [20]);

        $this->actingAs($admin)->get('/admin/reports/practice?month=2026-09')->assertRedirect();

        $this->assertSame(0, MonthlyReport::count(), 'Viewing must not mutate data.');
    }

    public function test_admin_can_generate_the_practice_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->doctor('Dr Alpha', [20, 45]);

        $this->actingAs($admin)
            ->from('/admin/reports/practice')
            ->post('/admin/reports/practice', ['month' => '2026-09'])
            ->assertRedirect('/admin/reports/practice')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('monthly_reports', [
            'scope' => MonthlyReport::SCOPE_PRACTICE,
            'month' => '2026-09',
            'provider_id' => null,
        ]);

        $this->assertTrue(
            AuditLog::where('user_id', $admin->id)
                ->where('action', 'like', '%Generated practice-wide%')
                ->exists(),
        );
    }

    public function test_doctor_cannot_generate_a_practice_report(): void
    {
        $doctor = $this->doctor('Dr Alpha', [20]);

        $this->actingAs($doctor)
            ->post('/admin/reports/practice', ['month' => '2026-09'])
            ->assertForbidden();

        $this->assertSame(0, MonthlyReport::count());
    }

    public function test_generation_validates_the_month_format(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/reports/practice')
            ->post('/admin/reports/practice', ['month' => 'not-a-month'])
            ->assertRedirect('/admin/reports/practice')
            ->assertSessionHasErrors('month');

        $this->assertSame(0, MonthlyReport::count());
    }

    public function test_regenerating_appends_history_rather_than_overwriting(): void
    {
        $this->doctor('Dr Alpha', [20]);

        MonthlyReportService::generatePracticeReport('2026-09');
        MonthlyReportService::generatePracticeReport('2026-09');

        $this->assertSame(2, MonthlyReport::practice()->where('month', '2026-09')->count());

        // Reads take the newest.
        $latest = MonthlyReport::practice()->where('month', '2026-09')->latest()->first();
        $this->assertNotNull($latest);
    }

    public function test_practice_month_over_month_does_not_mix_with_doctor_reports(): void
    {
        $alpha = $this->doctor('Dr Alpha', [20]);

        // A doctor report for the previous month must not become the practice
        // baseline, which would fabricate a huge jump.
        MonthlyReport::create([
            'provider_id' => Provider::whereHas('user', fn ($q) => $q->where('id', $alpha->id))->value('id'),
            'scope' => MonthlyReport::SCOPE_PROVIDER,
            'month' => '2026-08',
            'stats' => ['total' => 777, 'completed' => 777, 'no_shows' => 0, 'cancelled' => 0],
        ]);

        $this->doctor('Dr Beta', [45]);

        $result = MonthlyReportService::generatePracticeReport('2026-09');

        // No previous practice row exists, so no trends are invented.
        $this->assertSame([], $result['stats']['diagnosis_trends']);
    }
}
