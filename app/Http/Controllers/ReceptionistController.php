<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use App\Support\PatientHistoryMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ReceptionistController extends Controller
{
    // 🔒 SECURITY FIX: Moved dashboard logic from routes/web.php to controller
    public function dashboard()
    {
        // Authorization check
        if (Auth::user()->role !== 'receptionist' && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access to receptionist dashboard.');
        }

        $stats = Cache::remember('receptionist_stats', 60, function () {
            $dailyActivity = $this->dailyActivity();
            $today = today()->toDateString();
            $todayActivity = $dailyActivity[$today] ?? [
                'total' => 0,
                'completed' => 0,
                'completed_consultation' => 0,
                'completed_procedure' => 0,
                'completed_consultation_procedure' => 0,
            ];

            return [
                'totalPatients' => Patient::count(),
                'appointmentsToday' => Appointment::whereDate('scheduled_date', today())->count(),
                'completedToday' => $todayActivity['completed'],
                'completedConsultationToday' => $todayActivity['completed_consultation'],
                'completedProcedureToday' => $todayActivity['completed_procedure'],
                'completedConsultationProcedureToday' => $todayActivity['completed_consultation_procedure'],
                // Per-calendar-day totals + completed-by-service, keyed by
                // Y-m-d, so the dashboard can render history in the calendar.
                'dailyActivity' => $dailyActivity,
            ];
        });

        $doctors = Cache::remember('doctor_list', 1800, function () {
            return Provider::with('user:id,name')
                ->get(['id', 'user_id', 'specialty'])
                ->map(fn ($provider) => [
                    'id' => $provider->id,
                    'name' => $provider->user?->name,
                    'specialty' => $provider->specialty,
                ])
                ->values();
        });

        // M3 FIX: select only queue-relevant columns — clinical study blobs live in
        // encounters/studies now, so the queue stays light.
        $masterQueue = Appointment::with([
            'patient:id,user_id,mrn,contact_phone',
            'patient.user:id,name',
            'provider:id,user_id',
            'provider.user:id,name',
            'readerProvider:id,user_id',
            'readerProvider.user:id,name',
            // Only counters are needed in the table; ocr_text blobs stay out of
            // the queue payload and are fetched per-scan when a modal opens.
            'documents:id,appointment_id,type,ocr_status',
        ])
            ->whereDate('scheduled_date', today())
            ->select([
                'id', 'patient_id', 'provider_id', 'reader_provider_id', 'scheduled_date',
                'scheduled_time', 'status', 'service_type', 'google_event_url',
                'reading_status', 'reading_date',
            ])
            ->orderBy('scheduled_time')->get()->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'time' => $appt->scheduled_time ? Carbon::parse($appt->scheduled_time)->format('h:i A') : 'N/A',
                    'patient' => [
                        'id' => $appt->patient?->user_id,
                        'patient_record_id' => $appt->patient?->id,
                        'system_id' => $appt->patient?->user_id
                            ? 'PT-'.str_pad((string) $appt->patient->user_id, 5, '0', STR_PAD_LEFT)
                            : null,
                        'mrn' => $appt->patient?->mrn,
                        'phone' => $appt->patient?->contact_phone,
                        'name' => $appt->patient?->user?->anonymizedName() ?? 'Unknown',
                    ],
                    'doctor' => [
                        'id' => $appt->provider?->id,
                        'name' => $appt->provider?->user?->name ?? 'Unknown',
                    ],
                    'reading_doctor' => [
                        'id' => $appt->readerProvider?->id,
                        'name' => $appt->readerProvider?->user?->name ?? null,
                    ],
                    'service_type' => $appt->service_type,
                    'status' => $appt->status,
                    'reading_status' => $appt->reading_status,
                    'reading_date' => $appt->reading_date?->toDateString(),
                    'reader_provider_id' => $appt->reader_provider_id,
                    'google_event_url' => $appt->google_event_url,
                    'scans' => $appt->documents->map(fn ($doc) => [
                        'id' => $doc->id,
                        'type' => $doc->type,
                        'ocr_status' => $doc->ocr_status,
                    ]),
                    'scan_count' => $appt->documents->count(),
                    'scans_in_progress' => $appt->documents
                        ->contains(fn ($doc) => in_array($doc->ocr_status, ['pending', 'processing'], true)),
                ];
            });

        // Always read live: a cached schedule can outlive its deleted DB row and
        // render stale rows the receptionist then fails to edit (404 on PUT).
        // Expose the roster as `doctor` (id/name) so the weekly-plot list renders
        // doctor names and the edit form can pre-select the provider.
        $schedules = DoctorSchedule::with('provider.user:id,name')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'provider_id' => $s->provider_id,
                'doctor_id' => $s->provider_id,
                'doctor' => [
                    'id' => $s->provider?->id,
                    'name' => $s->provider?->user?->name ?? 'Unknown',
                ],
                'day_of_week' => $s->day_of_week,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'is_active' => (bool) $s->is_active,
            ])
            ->values();

        $auditLogs = AuditLog::with('user:id,name')->latest()->take(5)->get()->map(function ($log) {
            return [
                'user' => $log->user ? $log->user->name : 'System',
                'action' => $log->action,
                'time' => $log->created_at->diffForHumans(),
            ];
        });

        $futureAppointments = Appointment::with([
            'patient:id,user_id',
            'patient.user:id,name',
            'provider:id,user_id',
            'provider.user:id,name',
        ])
            // Load every appointment (past and future) so browsing to any day
            // in the calendar — including yesterday — still lists its visits.
            ->select([
                'id', 'patient_id', 'provider_id', 'scheduled_date',
                'scheduled_time', 'status', 'service_type',
            ])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get()
            ->groupBy(fn ($a) => Carbon::parse($a->scheduled_date)->format('F Y'))
            ->map(fn ($group, $month) => [
                'month' => $month,
                'count' => $group->count(),
                'appointments' => $group->map(fn ($a) => [
                    'id' => $a->id,
                    'date' => Carbon::parse($a->scheduled_date)->format('M d, Y'),
                    'date_iso' => Carbon::parse($a->scheduled_date)->format('Y-m-d'),
                    'time' => $a->scheduled_time ? Carbon::parse($a->scheduled_time)->format('h:i A') : 'N/A',
                    'patient' => [
                        'id' => $a->patient?->user_id,
                        'name' => $a->patient?->user?->anonymizedName() ?? 'Unknown',
                    ],
                    'doctor' => [
                        'id' => $a->provider?->id,
                        'name' => $a->provider?->user?->name ?? 'Unknown',
                    ],
                    'status' => $a->status,
                    'service_type' => $a->service_type,
                ]),
            ])
            ->values();

        $rescheduleRequests = Appointment::with([
            'patient:id,user_id',
            'patient.user:id,name',
            'provider:id,user_id',
            'provider.user:id,name',
        ])
            ->where('status', 'Reschedule Requested')
            ->orderBy('created_at')
            ->get()->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'patient' => [
                        'id' => $appt->patient?->user_id,
                        'name' => $appt->patient?->user?->anonymizedName() ?? 'Unknown',
                    ],
                    'doctor' => [
                        'id' => $appt->provider?->id,
                        'name' => $appt->provider?->user?->name ?? 'Unknown',
                    ],
                    'current_date' => Carbon::parse($appt->scheduled_date)->format('M d, Y'),
                    'current_time' => $appt->scheduled_time ? Carbon::parse($appt->scheduled_time)->format('h:i A') : 'N/A',
                    'requested_date' => $appt->reschedule_requested_date
                        ? Carbon::parse($appt->reschedule_requested_date)->format('M d, Y')
                        : 'N/A',
                    'requested_time' => $appt->reschedule_requested_time
                        ? Carbon::parse($appt->reschedule_requested_time)->format('h:i A')
                        : 'N/A',
                    // Machine-readable values so the edit modal can pre-fill
                    // <input type="date"/time> (display strings would render empty).
                    'requested_date_iso' => $appt->reschedule_requested_date
                        ? Carbon::parse($appt->reschedule_requested_date)->format('Y-m-d')
                        : null,
                    'requested_time_iso' => $appt->reschedule_requested_time
                        ? Carbon::parse($appt->reschedule_requested_time)->format('H:i')
                        : null,
                ];
            });

        // All patient records with search support
        $patients = Patient::with('user:id,name,email')
            ->withCount(['appointments as completed_visits_count' => function ($query) {
                $query->where('status', 'Completed');
            }])
            ->with(['appointments' => function ($query) {
                $query->where('status', 'Completed')
                    ->with('provider.user:id,name')
                    ->latest('scheduled_date')
                    ->latest('scheduled_time')
                    ->limit(1);
            }])
            ->get(['id', 'user_id', 'mrn', 'date_of_birth', 'sex', 'contact_phone'])
            ->map(function ($p) {
                $lastVisit = $p->appointments->first();

                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'system_id' => $p->user_id ? 'PT-'.str_pad((string) $p->user_id, 5, '0', STR_PAD_LEFT) : null,
                    'name' => $p->user?->name ?? 'Unknown',
                    'email' => $p->user?->email ?? '',
                    'mrn' => $p->mrn,
                    'phone' => $p->contact_phone,
                    'date_of_birth' => $p->date_of_birth?->format('Y-m-d'),
                    'sex' => $p->sex,
                    'total_visits' => $p->completed_visits_count,
                    'last_visit' => $lastVisit?->scheduled_date?->format('Y-m-d'),
                    'last_doctor' => $lastVisit?->provider?->user?->name,
                    'last_service' => $lastVisit?->service_type,
                ];
            })
            ->sortBy('name')
            ->values();

        return Inertia::render('Dashboard/Receptionist', [
            'user' => Auth::user(),
            'stats' => $stats,
            'doctors' => $doctors,
            'masterQueue' => $masterQueue,
            'futureAppointments' => $futureAppointments,
            'schedules' => $schedules,
            'auditLogs' => $auditLogs,
            'rescheduleRequests' => $rescheduleRequests,
            'patients' => $patients,
        ]);
    }

    /**
     * Read-only clinical history for one patient, loaded on demand so the
     * receptionist roster stays small and fast to render.
     */
    public function patientHistory(Patient $patient)
    {
        if (! in_array(Auth::user()->role, ['receptionist', 'admin'])) {
            abort(403, 'Unauthorized access to patient records.');
        }

        $patient->loadMissing('user:id,name');

        $records = array_map(function (array $record) {
            unset($record['ai_summary']);

            return $record;
        }, PatientHistoryMapper::history($patient));

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->user?->name ?? 'Unknown',
                'system_id' => $patient->user_id
                    ? 'PT-'.str_pad((string) $patient->user_id, 5, '0', STR_PAD_LEFT)
                    : null,
                'mrn' => $patient->mrn,
                'date_of_birth' => $patient->date_of_birth?->format('M d, Y'),
                'sex' => $patient->sex,
                'phone' => $patient->contact_phone,
            ],
            'records' => $records,
        ]);
    }

    /**
     * Aggregate appointment counts per calendar day, broken down by service
     * type for completed visits. Keyed by Y-m-d so the receptionist calendar
     * can render each day's totals without an extra round-trip.
     *
     * @return array<string, array<string, int>>
     */
    private function dailyActivity(): array
    {
        return Appointment::query()
            ->selectRaw('DATE(scheduled_date) as activity_date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'Completed' AND service_type = 'Consultation' THEN 1 ELSE 0 END) as completed_consultation")
            ->selectRaw("SUM(CASE WHEN status = 'Completed' AND service_type = 'Procedure' THEN 1 ELSE 0 END) as completed_procedure")
            ->selectRaw("SUM(CASE WHEN status = 'Completed' AND service_type = 'Consultation and Procedure' THEN 1 ELSE 0 END) as completed_consultation_procedure")
            ->groupByRaw('DATE(scheduled_date)')
            ->orderByRaw('DATE(scheduled_date)')
            ->get()
            ->mapWithKeys(fn ($row) => [
                Carbon::parse($row->activity_date)->format('Y-m-d') => [
                    'total' => (int) $row->total,
                    'completed' => (int) $row->completed,
                    'completed_consultation' => (int) $row->completed_consultation,
                    'completed_procedure' => (int) $row->completed_procedure,
                    'completed_consultation_procedure' => (int) $row->completed_consultation_procedure,
                ],
            ])
            ->all();
    }
}
