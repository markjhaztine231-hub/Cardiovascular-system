<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\MedicationLog;
use App\Models\MonthlyReport;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Support\AgeBand;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for monthly performance statistics and AI narrative.
 *
 * Both the manual "Generate Report" button (AiController) and the
 * automated Artisan command (GenerateMonthlyReports) call this service
 * so numbers and narrative are always identical regardless of trigger.
 */
class MonthlyReportService
{
    /**
     * Builds stats, generates a factual AI narrative, persists both,
     * and returns ['stats' => [...], 'report_text' => '...'].
     *
     * @param  string  $month  "Y-m" format, e.g. "2026-04"
     * @return array{stats: array, report_text: string|null}
     */
    public static function generate(int $doctorId, string $month): array
    {
        $providerId = Provider::where('user_id', $doctorId)->value('id');

        // No Provider row means the account is not a practising doctor yet
        // (registration creates the pair in two separate statements). Return a
        // well-formed payload but persist nothing — monthly_reports.provider_id
        // is NOT NULL, so writing here would throw instead of degrading.
        if (! $providerId) {
            Log::warning("MonthlyReportService: user {$doctorId} has no provider profile; skipping report for {$month}.");

            return ['stats' => self::emptyStats(), 'report_text' => null];
        }

        return self::generateForProvider($providerId, $month);
    }

    /**
     * Generates and persists a report scoped to one provider, or to every
     * provider when $providerId is null.
     *
     * A null provider is the "practice-wide" scope: identical metrics summed
     * across all doctors, so the practice row is provably the sum of the
     * per-doctor rows rather than a separately-drifting calculation.
     *
     * @return array{stats: array, report_text: string|null}
     */
    public static function generateForProvider(?int $providerId, string $month): array
    {
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // ── Previous month lookup (same scope) ──────────────────────────────
        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $prevReport = MonthlyReport::query()
            ->when(
                $providerId !== null,
                fn ($q) => $q->where('provider_id', $providerId),
                fn ($q) => $q->whereNull('provider_id'),
            )
            ->where('month', $prevMonth)
            ->latest()
            ->first();
        $prevStats = $prevReport?->stats;

        $appointments = self::scopedAppointments($providerId, $monthStart, $monthEnd);

        if ($appointments->isEmpty()) {
            $stats = self::emptyStats();
            self::persist($providerId, $month, $stats, null);

            return ['stats' => $stats, 'report_text' => null];
        }

        $stats = self::buildStats($appointments, $providerId, $monthStart, $monthEnd, $prevStats, $prevMonth);

        // ── AI narrative — factual only, no predictions ────────────────────
        $narrative = self::generateNarrative($stats, $month, $prevStats, $prevMonth);

        self::persist($providerId, $month, $stats, $narrative);

        return ['stats' => $stats, 'report_text' => $narrative];
    }

    /**
     * Practice-wide narrative plus a per-doctor breakdown, in one pass.
     *
     * The breakdown is derived from the SAME buildStats() used for individual
     * reports, so a doctor's row here always reconciles with their own report.
     * No extra AI calls and no extra rows are written for the breakdown.
     *
     * @return array{stats: array, report_text: string|null, by_provider: array}
     */
    public static function generatePracticeReport(string $month): array
    {
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $prevReport = MonthlyReport::query()
            ->whereNull('provider_id')
            ->where('scope', MonthlyReport::SCOPE_PRACTICE)
            ->where('month', $prevMonth)
            ->latest()
            ->first();
        $prevStats = $prevReport?->stats;

        $appointments = self::scopedAppointments(null, $monthStart, $monthEnd);

        if ($appointments->isEmpty()) {
            $stats = self::emptyStats();
            $byProvider = [];
            $narrative = null;
        } else {
            $stats = self::buildStats($appointments, null, $monthStart, $monthEnd, $prevStats, $prevMonth);
            $byProvider = self::practiceBreakdown($month);
            // The narrative is built from practice totals only. Feeding the AI
            // a doctor-by-doctor table invites exactly the ranking and
            // performance-judgement output this report must not contain.
            $narrative = self::generateNarrative($stats, $month, $prevStats, $prevMonth);
        }

        self::persist(null, $month, $stats + ['by_provider' => $byProvider], $narrative);

        return ['stats' => $stats + ['by_provider' => $byProvider], 'report_text' => $narrative, 'by_provider' => $byProvider];
    }

    /**
     * Per-doctor figures for a month. Read-only: builds nothing persistent and
     * calls no AI, so it is safe to hit on every page view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function practiceBreakdown(string $month): array
    {
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $providerIds = Appointment::query()
            ->whereBetween('scheduled_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->distinct()
            ->pluck('provider_id')
            ->filter();

        if ($providerIds->isEmpty()) {
            return [];
        }

        $providers = Provider::with('user:id,name')
            ->whereIn('id', $providerIds)
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($providerIds as $providerId) {
            $appointments = self::scopedAppointments($providerId, $monthStart, $monthEnd);

            if ($appointments->isEmpty()) {
                continue;
            }

            $s = self::buildStats($appointments, $providerId, $monthStart, $monthEnd);
            $provider = $providers->get($providerId);
            $topDiagnosis = $s['top_diagnoses'][0] ?? null;

            $rows[] = [
                'provider_id' => $providerId,
                'doctor_name' => $provider?->user?->name ?? 'Unknown doctor',
                'specialty' => $provider?->specialty,
                'total' => $s['total'],
                'completed' => $s['completed'],
                'no_shows' => $s['no_shows'],
                'cancelled' => $s['cancelled'],
                'unique_patients' => $s['demographics']['total_unique_patients'],
                'avg_age' => $s['demographics']['avg_age'],
                'procedures' => $s['procedures']['total'],
                'top_diagnosis' => $topDiagnosis['name'] ?? null,
                'age_distribution' => $s['age_distribution'],
            ];
        }

        // Busiest first; ties broken by name so the order is stable across
        // renders rather than depending on insertion order.
        usort($rows, fn ($a, $b) => [$b['total'], $a['doctor_name']] <=> [$a['total'], $b['doctor_name']]);

        return $rows;
    }

    /** Appointments for one provider, or every provider when null. */
    private static function scopedAppointments(?int $providerId, Carbon $monthStart, Carbon $monthEnd)
    {
        return Appointment::query()
            ->when(
                $providerId !== null,
                fn ($q) => $q->where('provider_id', $providerId),
            )
            ->whereBetween('scheduled_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
    }

    /** Writes one report row. Practice-wide rows carry a null provider_id. */
    private static function persist(?int $providerId, string $month, array $stats, ?string $narrative): void
    {
        MonthlyReport::create([
            'provider_id' => $providerId,
            'scope' => $providerId === null ? 'practice' : 'provider',
            'month' => $month,
            'stats' => $stats,
            'report_text' => $narrative,
        ]);
    }

    /**
     * Pure statistics builder — no persistence, no narrative, no scope
     * assumption beyond the $providerId filter. Passing null yields
     * practice-wide totals; passing an id yields that doctor's figures.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return array The stats array, ready for persistence or narrative input.
     */
    private static function buildStats(
        $appointments,
        ?int $providerId,
        Carbon $monthStart,
        Carbon $monthEnd,
        ?array $prevStats = null,
        ?string $prevMonth = null,
    ): array {
        $completed = $appointments->where('status', 'Completed');
        $noShows = $appointments->where('status', 'No-Show');
        $cancelled = $appointments->where('status', 'Cancelled');
        $total = $appointments->count();

        // ── Demographics ───────────────────────────────────────────────────
        $patientIds = $appointments->pluck('patient_id')->unique()->filter();
        $patients = Patient::whereIn('id', $patientIds)->get(['id', 'sex', 'date_of_birth']);
        $totalPts = $patients->count();
        $maleCount = $patients->filter(fn ($p) => $p->sex && strcasecmp($p->sex, 'Male') === 0)->count();
        $femaleCount = $patients->filter(fn ($p) => $p->sex && strcasecmp($p->sex, 'Female') === 0)->count();
        $otherCount = $totalPts - $maleCount - $femaleCount;

        // Age is measured as of the visit, not "today", so a backfilled month
        // reproduces the same numbers and a patient is banded the way a
        // clinician would describe them at the time of the consultation.
        //
        // Every patient gets ONE reference date (their first appointment of the
        // month) and that single date drives both the age histogram and the
        // diagnosis-by-age chart. Deriving them from different dates would let
        // the same patient appear in one band on one chart and a different band
        // on the other within the same report.
        $asOfByPatient = [];
        foreach ($appointments->sortBy('scheduled_date') as $appt) {
            $pid = $appt->patient_id;
            if ($pid && ! isset($asOfByPatient[$pid])) {
                $asOfByPatient[$pid] = Carbon::parse($appt->scheduled_date);
            }
        }

        $ages = $patients->mapWithKeys(fn ($p) => [
            $p->id => self::ageAt($p->date_of_birth, $asOfByPatient[$p->id] ?? $monthEnd),
        ]);
        $avgAge = collect($ages)->filter(fn ($a) => $a !== null)->avg();

        // ── Procedure counts (from structured study tables) ────────────────
        $encounters = Encounter::query()
            ->when(
                $providerId !== null,
                fn ($q) => $q->where('provider_id', $providerId),
            )
            ->whereBetween('started_at', [$monthStart, $monthEnd])
            ->get(['id', 'encounter_type', 'patient_id', 'started_at']);

        $type1 = $encounters->where('encounter_type', 'echo')->count();
        $type2 = $encounters->where('encounter_type', 'tmt')->count();
        $type3 = $encounters->where('encounter_type', 'doppler')->count();

        // ── Top diagnoses (ICD-10 descriptions, normalized schema) ─────────
        $encounterIds = $encounters->pluck('id')->filter();
        $topDiagnoses = [];
        if ($encounterIds->isNotEmpty()) {
            $topDiagnoses = Diagnosis::whereIn('encounter_id', $encounterIds)
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->pluck('description')
                ->countBy()
                ->sortDesc()->take(5)
                ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
                ->values()->toArray();
        }

        // ── Age distribution + diagnosis by age band ────────────────────────
        $ageDistribution = self::ageDistribution($ages);

        $diagnosisByAge = $encounterIds->isEmpty()
            ? []
            : self::diagnosisByAge($encounterIds, $encounters, $asOfByPatient);

        // ── Top services ───────────────────────────────────────────────────
        $topServices = $appointments
            ->whereNotNull('service_type')
            ->countBy('service_type')
            ->sortDesc()->take(5)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values()->toArray();

        // ── No-Show by day-of-week ─────────────────────────────────────────
        $noShowByDay = [];
        foreach ($noShows as $ns) {
            $day = Carbon::parse($ns->scheduled_date)->format('l');
            $noShowByDay[$day] = ($noShowByDay[$day] ?? 0) + 1;
        }
        ksort($noShowByDay);

        // ── No-Show by time-of-day ─────────────────────────────────────────
        $noShowByTime = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
        foreach ($noShows as $ns) {
            if ($ns->scheduled_time) {
                $hour = Carbon::parse($ns->scheduled_time)->hour;
                if ($hour < 12) {
                    $noShowByTime['morning']++;
                } elseif ($hour < 17) {
                    $noShowByTime['afternoon']++;
                } else {
                    $noShowByTime['evening']++;
                }
            }
        }
        $noShowByTime = array_filter($noShowByTime);
        $cancelCount = $cancelled->count();

        // ── Medication adherence (prescription-keyed logs) ─────────────────
        $medicationAdherence = [];
        $completedIds = $completed->pluck('id');
        if ($completedIds->isNotEmpty()) {
            $daysInMonth = $monthStart->daysInMonth;

            $completedEncounters = Encounter::whereIn('appointment_id', $completedIds)
                ->get(['id', 'appointment_id']);
            $rxIds = $completedEncounters->pluck('id')->filter();

            $prescriptions = $rxIds->isNotEmpty()
                ? Prescription::whereIn('encounter_id', $rxIds)
                    ->get(['id', 'encounter_id', 'medication_name', 'frequency', 'start_date'])
                : collect();

            $medLogRows = $prescriptions->isNotEmpty()
                ? MedicationLog::whereIn('prescription_id', $prescriptions->pluck('id'))
                    ->whereBetween('log_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->get(['prescription_id', 'log_date', 'status', 'adherence_flag'])
                : collect();

            $totalTaken = 0;
            $totalOnTime = 0;
            $totalLate = 0;
            $totalMissed = 0;
            $totalExpected = 0;
            $byMedicine = [];

            foreach ($prescriptions as $rx) {
                $medicine = $rx->medication_name;
                if (empty($medicine)) {
                    continue;
                }

                $dailyDoses = self::dailyDoseCount($rx->frequency ?? '1x a day');
                if ($dailyDoses === 0) {
                    continue;
                }

                $startDate = Carbon::parse($rx->start_date ?? $monthStart);
                $treatDays = max(0, min($startDate->copy()->diffInDays($monthEnd) + 1, $daysInMonth));
                $expected = (int) round($treatDays * $dailyDoses);
                $totalExpected += $expected;

                $byMed = $medLogRows->where('prescription_id', $rx->id);
                $taken = $byMed->where('status', 'TAKEN');
                $onTime = $taken->where('adherence_flag', 'ON_TIME')->count();
                $late = $taken->where('adherence_flag', 'LATE')->count();
                $missed = $byMed->where('status', 'MISSED')->count();

                $totalTaken += $taken->count();
                $totalOnTime += $onTime;
                $totalLate += $late;
                $totalMissed += $missed;

                if (! isset($byMedicine[$medicine])) {
                    $byMedicine[$medicine] = ['expected' => 0, 'taken' => 0, 'on_time' => 0, 'late' => 0, 'missed' => 0];
                }
                $byMedicine[$medicine]['expected'] += $expected;
                $byMedicine[$medicine]['taken'] += $taken->count();
                $byMedicine[$medicine]['on_time'] += $onTime;
                $byMedicine[$medicine]['late'] += $late;
                $byMedicine[$medicine]['missed'] += $missed;
            }

            $medications = [];
            foreach ($byMedicine as $name => $data) {
                $rate = $data['expected'] > 0 ? round(($data['taken'] / $data['expected']) * 100, 1) : 0;
                $medications[] = [
                    'medicine' => $name,
                    'expected_doses' => $data['expected'],
                    'taken_doses' => $data['taken'],
                    'taken_on_time' => $data['on_time'],
                    'taken_late_safe_window' => $data['late'],
                    'missed_via_safety_lockout' => $data['missed'],
                    'adherence_rate' => $rate,
                ];
            }
            usort($medications, fn ($a, $b) => $a['adherence_rate'] <=> $b['adherence_rate']);

            $medicationAdherence = [
                'overall_adherence_index' => $totalExpected > 0
                    ? round(($totalTaken / $totalExpected) * 100, 1) : 0,
                'overall_adherence_rate' => $totalExpected > 0
                    ? round(($totalTaken / $totalExpected) * 100, 1) : 0,
                'total_expected_doses' => $totalExpected,
                'total_taken_doses' => $totalTaken,
                'taken_on_time' => $totalOnTime,
                'taken_late_safe_window' => $totalLate,
                'missed_via_safety_lockout' => $totalMissed,
                'medications' => $medications,
                'declining_medications' => array_values(
                    array_filter($medications, fn ($m) => $m['adherence_rate'] < 80)
                ),
            ];
        }

        // ── Diagnosis trends (current vs previous month) ────────────────────
        $diagnosisTrends = [];
        if ($prevMonth && $prevStats && ! empty($prevStats['top_diagnoses'])) {
            $prevDx = collect($prevStats['top_diagnoses']);
            $allDx = collect($topDiagnoses)
                ->pluck('name')
                ->merge($prevDx->pluck('name'))
                ->unique();

            foreach ($allDx as $dxName) {
                $curCnt = collect($topDiagnoses)->firstWhere('name', $dxName)['count'] ?? 0;
                $prvCnt = $prevDx->firstWhere('name', $dxName)['count'] ?? 0;
                $change = $curCnt - $prvCnt;
                $pct = $prvCnt > 0 ? round(($change / $prvCnt) * 100, 1) : ($curCnt > 0 ? 100 : 0);
                $diagnosisTrends[] = [
                    'name' => $dxName,
                    'current_count' => $curCnt,
                    'previous_count' => $prvCnt,
                    'change' => $change,
                    'change_pct' => $pct,
                ];
            }
            usort($diagnosisTrends, fn ($a, $b) => abs($b['change_pct']) <=> abs($a['change_pct']));
        }

        $stats = [
            'total' => $total,
            'completed' => $completed->count(),
            'no_shows' => $noShows->count(),
            'cancelled' => $cancelled->count(),
            'pending' => $appointments->whereIn('status', ['Pending', 'In Progress'])->count(),
            'demographics' => [
                'total_unique_patients' => $totalPts,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'other_count' => $otherCount,
                'avg_age' => $avgAge ? round($avgAge, 1) : 'N/A',
            ],
            'top_diagnoses' => $topDiagnoses,
            'age_distribution' => $ageDistribution,
            'diagnosis_by_age' => $diagnosisByAge,
            'top_services' => $topServices,
            'procedures' => [
                'echocardiogram' => $type1,
                'treadmill_tmt' => $type2,
                'color_doppler' => $type3,
                'total' => $type1 + $type2 + $type3,
            ],
            'no_show_by_day' => $noShowByDay,
            'no_show_by_time' => $noShowByTime,
            'medication_adherence' => $medicationAdherence,
            'diagnosis_trends' => $diagnosisTrends,
        ];

        return $stats;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Age in full years as of a given date, or null without a usable DOB.
     *
     * Accepts DateTimeInterface rather than Carbon specifically: Eloquent's
     * `date` cast returns CarbonImmutable while query builders hand back
     * Carbon, so both must be tolerated.
     *
     * Carbon 3's diffInYears() is signed and rounds toward zero, so we take the
     * absolute value and floor it. This matches Carbon's own ->age property
     * when $asOf is today, and handles 29-Feb birthdays correctly (a 29-Feb
     * DOB is still 25 on 28-Feb of a non-leap year).
     */
    private static function ageAt(?DateTimeInterface $dateOfBirth, DateTimeInterface $asOf): ?int
    {
        if (! $dateOfBirth) {
            return null;
        }

        // A DOB after the visit date is a data-entry error, not a real age.
        // Report it as unknown rather than inventing a positive number.
        if ($dateOfBirth > $asOf) {
            return null;
        }

        // Carbon 3's diffInYears() is signed and rounds toward zero, so floor
        // the absolute value. This matches Carbon's own ->age property when
        // $asOf is today, and keeps a 29-Feb DOB at 25 on 28-Feb of a
        // non-leap year.
        return (int) floor(abs(Carbon::instance($asOf)->diffInYears(Carbon::instance($dateOfBirth))));
    }

    /**
     * Buckets patients into clinical age bands.
     *
     * An average age hides the cases a cardiologist actually wants to see — for
     * example hypertension in a 20-year-old gets cancelled out by a 70-year-old
     * cohort. Patients with no date of birth land in an explicit "unknown" band
     * rather than being silently dropped or folded into the youngest bucket.
     *
     * @param  Collection<int, int|null>  $agesByPatient  patient_id => age at the visit
     * @return array<int, array{band: string, label: string, count: int, pct: float}>
     */
    private static function ageDistribution($agesByPatient): array
    {
        $histogram = AgeBand::emptyHistogram();

        foreach ($agesByPatient as $age) {
            $band = AgeBand::band($age);
            $histogram[$band]['count']++;
        }

        $withDob = collect($histogram)
            ->reject(fn ($row) => $row['band'] === AgeBand::UNKNOWN)
            ->sum('count');

        return collect($histogram)->map(function (array $row) use ($withDob) {
            // Percentages are taken over patients with a usable DOB so a clinic
            // with incomplete records still produces a meaningful 100% total.
            $row['pct'] = $withDob > 0 && $row['band'] !== AgeBand::UNKNOWN
                ? round(($row['count'] / $withDob) * 100, 1)
                : 0.0;

            return $row;
        })->values()->toArray();
    }

    /**
     * Cross-tabulates the month's diagnoses against patient age bands.
     *
     * This is what surfaces an unexpected demographic presentation — e.g.
     * "I10 hypertension: 1 case aged 18–29" — which a flat top-diagnoses list
     * cannot express. Grouping prefers icd_code (stable, analyst-friendly) and
     * falls back to the normalized description because icd_code is frequently
     * left blank in the normalized schema.
     *
     * @param  Collection  $encounterIds
     * @param  Collection<int, Encounter>  $encounters
     * @return array<int, array{name: string, code: string|null, total: int, bands: array<string, int>}>
     */
    /**
     * @param  Collection<int, Encounter>  $encounters
     * @param  array<int, Carbon>  $asOfByPatient  One reference date per patient
     *                                             so bands match ageDistribution().
     */
    private static function diagnosisByAge($encounterIds, $encounters, array $asOfByPatient = []): array
    {
        // encounter_id => patient_id, so diagnoses resolve to a patient's age.
        $patientByEncounter = $encounters->pluck('patient_id', 'id');

        $patientIds = $patientByEncounter->filter()->unique();
        if ($patientIds->isEmpty()) {
            return [];
        }

        $dobByPatient = Patient::whereIn('id', $patientIds)
            ->pluck('date_of_birth', 'id');

        if ($dobByPatient->isEmpty()) {
            return [];
        }

        $rows = Diagnosis::whereIn('encounter_id', $encounterIds->all())
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->get(['encounter_id', 'description', 'icd_code']);

        if ($rows->isEmpty()) {
            return [];
        }

        $grouped = [];

        foreach ($rows as $row) {
            $patientId = $patientByEncounter[$row->encounter_id] ?? null;
            if (! $patientId) {
                continue;
            }

            // Band by the patient's single reference date for the month, matching
            // ageDistribution(). Re-running an old month must reproduce the
            // original bands, and a patient must never straddle two bands.
            $dob = $dobByPatient[$patientId] ?? null;
            $asOf = $asOfByPatient[$patientId] ?? null;
            $band = AgeBand::band($dob && $asOf ? self::ageAt($dob, $asOf) : null);
            $code = $row->icd_code !== null && trim((string) $row->icd_code) !== ''
                ? trim((string) $row->icd_code)
                : null;

            // Collapse whitespace/case so "Hypertension, essential" and
            // "hypertension  essential" are one clinical bucket, not two.
            $normalized = strtolower(preg_replace('/\s+/', ' ', trim($row->description)));
            $key = $code !== null ? 'code:'.$code : 'desc:'.$normalized;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => trim($row->description),
                    'code' => $code,
                    'total' => 0,
                    'bands' => array_fill_keys(array_keys(AgeBand::emptyHistogram()), 0),
                ];
            }

            $grouped[$key]['total']++;
            $grouped[$key]['bands'][$band]++;
        }

        // Keep the same width as top_diagnoses so the narrative stays bounded.
        return collect($grouped)
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * Generates a factual AI narrative for the month.
     *
     * If $prevStats is provided (i.e. a previous month's report exists in the DB),
     * all deltas are pre-computed here in PHP so the AI never has to do arithmetic.
     * A 6th paragraph on month-over-month comparison is then requested.
     *
     * @param  array  $stats  Current month's stats.
     * @param  string  $month  Current month "Y-m".
     * @param  array|null  $prevStats  Previous month's stats, or null if none.
     * @param  string|null  $prevMonth  Previous month "Y-m", or null if none.
     */
    private static function generateNarrative(
        array $stats,
        string $month,
        ?array $prevStats = null,
        ?string $prevMonth = null,
    ): ?string {
        $monthLabel = Carbon::createFromFormat('Y-m', $month)->format('F Y');

        // ── Format current month lists ─────────────────────────────────────
        $dxList = collect($stats['top_diagnoses'])
            ->map(fn ($d) => "{$d['name']} ({$d['count']} ".($d['count'] === 1 ? 'case' : 'cases').')')
            ->join(', ') ?: 'none recorded';

        $svcList = collect($stats['top_services'])
            ->map(fn ($s) => "{$s['name']} ({$s['count']}x)")
            ->join(', ') ?: 'none recorded';

        $dem = $stats['demographics'];
        $proc = $stats['procedures'];

        // ── Age distribution + diagnoses by age band ───────────────────────
        // Counts are pre-computed here; the model is only allowed to narrate
        // them, never to total or divide them itself.
        $ageBandList = '';
        $knownAgeTotal = 0;
        if (! empty($stats['age_distribution'])) {
            $knownAgeTotal = collect($stats['age_distribution'])
                ->reject(fn ($b) => $b['band'] === AgeBand::UNKNOWN)
                ->sum('count');

            $ageBandList = collect($stats['age_distribution'])
                ->filter(fn ($b) => $b['count'] > 0)
                ->map(fn ($b) => "{$b['label']}: {$b['count']} patients (".number_format((float) $b['pct'], 1).'%)')
                ->join(', ') ?: 'no patients with a recorded date of birth';
        }

        $dxByAgeList = '';
        if (! empty($stats['diagnosis_by_age'])) {
            $dxByAgeList = collect($stats['diagnosis_by_age'])
                ->map(function (array $d) {
                    $breakdown = collect($d['bands'])
                        ->filter(fn ($c) => $c > 0)
                        ->map(fn ($c, $band) => AgeBand::label($band).': '.$c)
                        ->join(', ');
                    $code = $d['code'] ? " [{$d['code']}]" : '';

                    return "{$d['name']}{$code} — {$d['total']} total ({$breakdown})";
                })
                ->join('; ');
        }

        $hasAgeProfile = $knownAgeTotal > 0;

        // ── Format new sections ────────────────────────────────────────────
        // No-show by day
        $nsDayList = '';
        if (! empty($stats['no_show_by_day'])) {
            $nsDayList = collect($stats['no_show_by_day'])
                ->map(fn ($cnt, $day) => "{$day}: {$cnt}")
                ->join(', ');
        }

        // No-show by time
        $nsTimeList = '';
        if (! empty($stats['no_show_by_time'])) {
            $nsTimeList = collect($stats['no_show_by_time'])
                ->map(fn ($cnt, $period) => "{$period}: {$cnt}")
                ->join(', ');
        }

        // Medication adherence
        $medAdh = $stats['medication_adherence'] ?? [];
        $medSummary = '';
        $decliningMeds = '';
        if (! empty($medAdh)) {
            $onTime = $medAdh['taken_on_time'] ?? 0;
            $late = $medAdh['taken_late_safe_window'] ?? 0;
            $missed = $medAdh['missed_via_safety_lockout'] ?? 0;
            $medSummary = "Total doses taken: {$medAdh['total_taken_doses']} of {$medAdh['total_expected_doses']} expected doses"
                ." ({$onTime} taken on time, {$late} taken late within the safety window, {$missed} missed via safety lockout)";
            if (! empty($medAdh['medications'])) {
                $medList = collect($medAdh['medications'])
                    ->map(fn ($m) => "{$m['medicine']}: {$m['taken_doses']} of {$m['expected_doses']} doses taken "
                        ."({$m['taken_on_time']} on time, {$m['taken_late_safe_window']} late, {$m['missed_via_safety_lockout']} missed)")
                    ->join('; ');
                $medSummary .= ". Per-medication: {$medList}";
            }
            if (! empty($medAdh['declining_medications'])) {
                $decliningMeds = collect($medAdh['declining_medications'])
                    ->map(fn ($m) => "{$m['medicine']} ({$m['taken_doses']} of {$m['expected_doses']} doses)")
                    ->join(', ');
            }
        }

        // ── Pre-compute deltas if previous month data exists ───────────────
        $comparisonSection = '';
        $comparisonParagraphInstruction = '';
        $prevMonthLabel = '';
        $maxTokens = 1000;

        if ($prevStats && $prevMonth) {
            $prevMonthLabel = Carbon::createFromFormat('Y-m', $prevMonth)->format('F Y');

            $signed = fn (int|float $n): string => ($n > 0 ? '+' : '').$n;

            $dTotal = $stats['total'] - ($prevStats['total'] ?? 0);
            $dCompleted = $stats['completed'] - ($prevStats['completed'] ?? 0);
            $dNoShows = $stats['no_shows'] - ($prevStats['no_shows'] ?? 0);
            $dCancelled = $stats['cancelled'] - ($prevStats['cancelled'] ?? 0);
            $dCompRate = $stats['completed'] - ($prevStats['completed'] ?? 0);
            $dNoShowRate = $stats['no_shows'] - ($prevStats['no_shows'] ?? 0);

            $dPatients = $dem['total_unique_patients'] - ($prevStats['demographics']['total_unique_patients'] ?? 0);
            $prevAvgAge = $prevStats['demographics']['avg_age'] ?? 'N/A';
            $ageChange = ($dem['avg_age'] !== 'N/A' && $prevAvgAge !== 'N/A')
                ? $signed(round((float) $dem['avg_age'] - (float) $prevAvgAge, 1)).' years'
                : 'not comparable (missing data)';

            $dEcho = $proc['echocardiogram'] - ($prevStats['procedures']['echocardiogram'] ?? 0);
            $dTmt = $proc['treadmill_tmt'] - ($prevStats['procedures']['treadmill_tmt'] ?? 0);
            $dDoppler = $proc['color_doppler'] - ($prevStats['procedures']['color_doppler'] ?? 0);
            $dTotalProc = $proc['total'] - ($prevStats['procedures']['total'] ?? 0);

            $prevDxList = collect($prevStats['top_diagnoses'] ?? [])
                ->map(fn ($d) => "{$d['name']} ({$d['count']} ".($d['count'] === 1 ? 'case' : 'cases').')')
                ->join(', ') ?: 'none recorded';

            // Diagnosis trends from pre-computed data
            $dxTrendText = '';
            if (! empty($stats['diagnosis_trends'])) {
                $dxTrendText = collect($stats['diagnosis_trends'])
                    ->map(fn ($d) => "{$d['name']}: {$d['previous_count']} -> {$d['current_count']} (change: {$signed($d['change'])})")
                    ->join('; ');
            }

            $ageBandsOf = fn (?array $s): string => collect($s['age_distribution'] ?? [])
                ->filter(fn ($b) => ($b['count'] ?? 0) > 0)
                ->map(fn ($b) => "{$b['label']}: {$b['count']}")
                ->join(', ') ?: 'no age bands recorded';
            $prevAgeBands = $ageBandsOf($prevStats);
            $curAgeBands = $ageBandsOf($stats);

            $comparisonSection = <<<CMP

--- PREVIOUS MONTH ({$prevMonthLabel}) — PRE-CALCULATED CHANGES ---
All arithmetic has already been done. Use these exact signed values. Do NOT recalculate.

Operational:
  Total appointments : {$prevStats['total']} → {$stats['total']}  (change: {$signed($dTotal)})
  Completed          : {$prevStats['completed']} → {$stats['completed']}  (change: {$signed($dCompleted)})
  No-shows           : {$prevStats['no_shows']} → {$stats['no_shows']}  (change: {$signed($dNoShows)})
  Cancelled          : {$prevStats['cancelled']} → {$stats['cancelled']}  (change: {$signed($dCancelled)})

Demographics:
  Unique patients    : {$prevStats['demographics']['total_unique_patients']} → {$dem['total_unique_patients']}  (change: {$signed($dPatients)})
  Average patient age: {$prevAvgAge} yrs → {$dem['avg_age']} yrs  (change: {$ageChange})
  Age band counts    : [{$prevAgeBands}] → [{$curAgeBands}]

Procedures:
  Echocardiograms    : {$prevStats['procedures']['echocardiogram']} → {$proc['echocardiogram']}  (change: {$signed($dEcho)})
  TMT tests          : {$prevStats['procedures']['treadmill_tmt']} → {$proc['treadmill_tmt']}  (change: {$signed($dTmt)})
  Color Doppler      : {$prevStats['procedures']['color_doppler']} → {$proc['color_doppler']}  (change: {$signed($dDoppler)})
  Total procedures   : {$prevStats['procedures']['total']} → {$proc['total']}  (change: {$signed($dTotalProc)})

Previous month top diagnoses: {$prevDxList}
Diagnosis trends (current vs prev): {$dxTrendText}
CMP;

            $comparisonParagraphInstruction = 'Paragraph 8 — Month-over-Month Comparison: '
                ."Write one paragraph comparing {$monthLabel} to {$prevMonthLabel}. "
                .'Use ONLY the pre-calculated change values above. Do NOT recalculate anything.';
        }

        // ── Build the full prompt ──────────────────────────────────────────
        $hasAdherence = ! empty($medAdh) && ($medAdh['total_expected_doses'] ?? 0) > 0;
        $hasNsPattern = ! empty($stats['no_show_by_day']);
        $hasPrev = ! empty($prevStats);
        $paragraphs = 5;
        $paragraphs += $hasAgeProfile ? 1 : 0;   // age distribution & age-specific diagnoses
        $paragraphs += $hasNsPattern ? 1 : 0;   // no-show patterns
        $paragraphs += $hasAdherence ? 1 : 0;   // medication adherence
        $paragraphs += $hasPrev ? 1 : 0;   // month-over-month comparison

        $p = 1;
        $p1 = 'Paragraph '.($p++).' — Operational Metrics';
        $p2 = 'Paragraph '.($p++).' — Patient Demographics';
        $p3 = $hasAgeProfile ? 'Paragraph '.($p++).' — Age Distribution & Age-Specific Diagnoses' : '';
        $p4 = 'Paragraph '.($p++).' — Clinical Procedures';
        $p5 = 'Paragraph '.($p++).' — Clinical Diagnoses & Trends';
        $p6 = 'Paragraph '.($p++).' — Services & Conclusion';
        $p7 = $hasNsPattern ? 'Paragraph '.($p++).' — No-Show Pattern Analysis' : '';
        $p8 = $hasAdherence ? 'Paragraph '.($p++).' — Medication Adherence & Interventions' : '';
        $p9 = $hasPrev ? 'Paragraph '.($p++).' — Month-over-Month Comparison' : '';
        $p9_instruction = $hasPrev && $prevMonthLabel
            ? "{$p9}: Write one paragraph comparing {$monthLabel} to {$prevMonthLabel}. Use ONLY the pre-calculated change values above. Do NOT recalculate anything. Highlight the most significant increases or decreases in operational metrics, demographics, procedures, and diagnoses."
            : '';
        $maxTokens = $hasAdherence ? 1800 : ($hasNsPattern ? 1500 : ($hasPrev ? 1200 : 1000));
        $maxTokens += $hasAgeProfile ? 300 : 0;

        $prompt = <<<PROMPT
You are a clinical administrator writing a formal Monthly Clinical Report for Cardiovascular Care Center for {$monthLabel}.

Using ONLY the verified database figures provided below, write exactly {$paragraphs} paragraphs in formal administrative prose.
You MAY identify trends, patterns, and clinically relevant observations present in the data. Do NOT invent numbers, make predictions, or add any information not present in the provided data.
Do NOT use markdown, bullet points, or asterisks. Separate paragraphs with a single blank line. Write in plain past-tense prose.

--- CURRENT MONTH VERIFIED DATA ({$monthLabel}) ---

OPERATIONAL METRICS
- Total appointments scheduled : {$stats['total']}
- Completed                    : {$stats['completed']}
- No-shows                     : {$stats['no_shows']}
- Cancelled                    : {$stats['cancelled']}
- Pending / In Progress        : {$stats['pending']}

PATIENT DEMOGRAPHICS
- Unique patients seen  : {$dem['total_unique_patients']}
- Male patients         : {$dem['male_count']}
- Female patients       : {$dem['female_count']}
- Average patient age   : {$dem['avg_age']} years

AGE DISTRIBUTION (patients with a recorded date of birth)
{$ageBandList}

DIAGNOSES BY AGE BAND (each diagnosis with its age breakdown)
{$dxByAgeList}

CLINICAL PROCEDURES
- Echocardiograms (Type 1)       : {$proc['echocardiogram']}
- Treadmill Tests / TMT (Type 2) : {$proc['treadmill_tmt']}
- Color Doppler Studies (Type 3) : {$proc['color_doppler']}
- Total procedures performed     : {$proc['total']}

TOP DIAGNOSES        : {$dxList}
TOP SERVICES RENDERED: {$svcList}
{$comparisonSection}
--- NO-SHOW PATTERNS ---
{$nsDayList}
{$nsTimeList}

--- MEDICATION ADHERENCE ---
{$medSummary}
{$decliningMeds}
--- PARAGRAPHS TO WRITE ---

{$p1}: Narrate the appointment flow, completed count, no-show count, and cancellations using the exact current-month figures.
{$p2}: Narrate the patient profile including unique patient count, sex distribution, and average age using the exact current-month figures.
{$p3}: Describe the age distribution of patients seen and then report which diagnoses presented in which age bands. Give special attention to any diagnosis appearing in a much younger age band than expected, such as hypertension or another cardiovascular condition in a patient under 30, and state the exact count for that band. If no diagnosis falls outside the older bands, say so plainly. Do NOT calculate totals, percentages or differences yourself; report only the figures given above.
{$p4}: Narrate the breakdown of echocardiograms, treadmill tests, and color doppler studies performed using the exact current-month figures.
{$p5}: Narrate the most frequent diagnoses observed this month. If diagnosis trend data is available, highlight which diagnoses increased or decreased compared to the previous month, and note the change in number of cases.
{$p6}: Summarize the top services rendered and provide a closing overview of the month's clinical activity.
{$p7}: Analyse no-show patterns. Identify which days of the week or times of day have the highest no-show concentration. Suggest which patient segments or time slots may benefit from proactive reminder targeting.
{$p8}: Report on medication adherence using actual dose counts. Identify specific medications with low adherence that may require clinical intervention or patient education. Note the total doses taken versus expected and any notable trends.
{$p9_instruction}
PROMPT;

        $systemPrompt = 'You are a formal clinical administrator writing monthly reports for a cardiovascular care center. You write factual reports strictly from provided data. You never invent numbers, never make predictions, and never perform arithmetic — all change values are pre-calculated and supplied to you.';

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];

        // --- 1. OPENROUTER (DeepSeek via OpenRouter, primary) ---
        $orKey = config('services.openrouter.api_key');
        if ($orKey) {
            $text = self::chatAttempt(
                'https://openrouter.ai/api/v1/chat/completions',
                $orKey,
                [
                    'model' => config('services.openrouter.model'),
                    'messages' => $messages,
                    'temperature' => 0.1,
                    'max_tokens' => $maxTokens,
                ],
                90,
                [
                    'HTTP-Referer' => url('/'),
                    'X-Title' => 'CardioCare EMR',
                ],
            );

            if ($text !== null) {
                return $text;
            }
        }

        // --- 2. DEEPSEEK DIRECT ---
        $deepseekKey = config('services.deepseek.api_key');
        if ($deepseekKey) {
            $text = self::chatAttempt(
                'https://api.deepseek.com/chat/completions',
                $deepseekKey,
                array_merge([
                    'model' => config('services.deepseek.model'),
                    'temperature' => 0.1,
                    'max_tokens' => $maxTokens,
                ], ['messages' => $messages]),
                100,
            );

            if ($text !== null) {
                return $text;
            }
        }

        // --- 3. HUGGING FACE INFERENCE ---
        $hfToken = config('services.hf.token');
        if ($hfToken) {
            $text = self::chatAttempt(
                'https://api-inference.huggingface.co/models/'.config('services.hf.model').'/v1/chat/completions',
                $hfToken,
                [
                    'model' => config('services.hf.model'),
                    'messages' => $messages,
                    'temperature' => 0.1,
                    'max_tokens' => $maxTokens,
                ],
                120,
            );

            if ($text !== null) {
                return $text;
            }
        }

        if (! $orKey && ! $deepseekKey && ! $hfToken) {
            Log::warning('MonthlyReportService: no AI key configured. Narrative skipped.');
        } else {
            Log::error('MonthlyReportService: all AI providers failed. Narrative skipped.');
        }

        return null;
    }

    /**
     * Single OpenAI-compatible chat attempt, returning the trimmed text or null.
     */
    private static function chatAttempt(
        string $url,
        string $apiKey,
        array $payload,
        int $timeout,
        array $extraHeaders = [],
    ): ?string {
        try {
            $response = Http::withHeaders(array_merge([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ], $extraHeaders))
                ->timeout($timeout)
                ->post($url, $payload);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content') ?? '');
            }

            Log::error('MonthlyReportService narrative failed: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('MonthlyReportService narrative exception: '.$e->getMessage());
        }

        return null;
    }

    /** Returns a zeroed-out stats skeleton for months with no appointments. */
    private static function emptyStats(): array
    {
        return [
            'total' => 0,
            'completed' => 0,
            'no_shows' => 0,
            'cancelled' => 0,
            'pending' => 0,
            'demographics' => [
                'total_unique_patients' => 0,
                'male_count' => 0,
                'female_count' => 0,
                'other_count' => 0,
                'avg_age' => 'N/A',
            ],
            'top_diagnoses' => [],
            'age_distribution' => array_map(
                fn (array $row) => $row + ['pct' => 0.0],
                array_values(AgeBand::emptyHistogram()),
            ),
            'diagnosis_by_age' => [],
            'top_services' => [],
            'procedures' => [
                'echocardiogram' => 0,
                'treadmill_tmt' => 0,
                'color_doppler' => 0,
                'total' => 0,
            ],
            'no_show_by_day' => [],
            'no_show_by_time' => [],
            'medication_adherence' => [],
            'diagnosis_trends' => [],
        ];
    }

    /**
     * Calculates the expected number of daily doses from a frequency string.
     * Duplicates the logic in MedicationController for use in report context.
     */
    protected static function dailyDoseCount(string $frequency): int
    {
        $freq = strtolower($frequency);

        return match (true) {
            str_contains($freq, 'as needed') || str_contains($freq, 'prn') => 0,
            str_starts_with($freq, '1x') || str_contains($freq, 'once') => 1,
            str_starts_with($freq, '2x') || str_contains($freq, 'twice') => 2,
            str_starts_with($freq, '3x') || str_contains($freq, 'thrice') => 3,
            str_starts_with($freq, '4x') => 4,
            str_contains($freq, '4 hours') => 4,
            str_contains($freq, '6 hours') => 3,
            str_contains($freq, '8 hours') => 2,
            default => 1,
        };
    }
}
