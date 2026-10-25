<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Support\ClinicalRecordMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DoctorController extends Controller
{
    // 🚀 PERFORMANCE FIX: Moved from route closure with optimized queries
    public function dashboard()
    {
        if (Auth::user()->role !== 'doctor') {
            abort(403);
        }

        $providerId = Auth::user()->provider?->id;
        if (! $providerId) {
            abort(403, 'No provider record linked to your account.');
        }

        // ✅ OPTIMIZED: Single efficient query with proper eager loading.
        // Patient display data lives on patients (allergies) + their linked user (name).
        $queue = Appointment::with([
            'patient:id,user_id,allergies',
            'patient.user:id,name',
            'patient.appointments' => function ($q) {
                $q->where('status', 'Completed')
                    ->latest('scheduled_date')
                    ->limit(5);
            },
            'patient.prescriptions' => function ($q) {
                $q->where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', today()))
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                    ->orderByDesc('id');
            },
        ])
            ->whereDate('scheduled_date', today())
            ->where('provider_id', $providerId)
            ->whereNotIn('status', ['Completed', 'No-Show'])
            ->orderBy('scheduled_time')
            ->select([
                'id', 'patient_id', 'provider_id', 'scheduled_date',
                'scheduled_time', 'service_type', 'status',
                'reader_provider_id', 'reading_status', 'reading_date',
            ])
            ->get()
            ->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'patient_id' => $appt->patient_id,
                    'patient_user_id' => $appt->patient?->user_id,
                    'time' => Carbon::parse($appt->scheduled_time)->format('h:i A'),
                    'name' => $appt->patient?->user?->anonymizedName() ?? 'Unknown',
                    'service' => $appt->service_type,
                    'allergies' => $appt->patient?->allergies,
                    'current_medications' => ($appt->patient?->prescriptions ?? collect())
                        ->map(fn ($rx) => [
                            'medicine' => $rx->medication_name,
                            'dosage' => $rx->dosage,
                            'frequency' => $rx->frequency,
                        ])
                        ->values()
                        ->all(),
                    'status' => $appt->status,
                    'history' => $appt->patient?->appointments,
                    'reader_provider_id' => $appt->reader_provider_id,
                    'reading_status' => $appt->reading_status,
                    'reading_date' => $appt->reading_date?->toDateString(),
                ];
            });

        $readingQueue = Appointment::with([
            'patient:id,user_id,allergies',
            'patient.user:id,name',
            'provider:id,user_id',
            'provider.user:id,name',
        ])
            ->where('reader_provider_id', $providerId)
            ->where('status', 'Completed')
            ->where('reading_status', 'pending')
            ->orderByRaw("COALESCE(reading_date, scheduled_date) ASC")
            ->select([
                'id', 'patient_id', 'provider_id', 'reader_provider_id', 'scheduled_date',
                'scheduled_time', 'service_type', 'status', 'reading_status', 'reading_date',
            ])
            ->get()
            ->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'patient_id' => $appt->patient_id,
                    'patient_user_id' => $appt->patient?->user_id,
                    'scheduled_date' => $appt->scheduled_date?->toDateString(),
                    'time' => $appt->scheduled_time ? Carbon::parse($appt->scheduled_time)->format('h:i A') : null,
                    'name' => $appt->patient?->user?->anonymizedName() ?? 'Unknown',
                    'service' => $appt->service_type,
                    'status' => $appt->status,
                    'reading_status' => $appt->reading_status,
                    'reading_date' => $appt->reading_date?->toDateString(),
                    'requested_by' => $appt->provider?->user?->name,
                ];
            });

        $doctors = Provider::with('user:id,name')
            ->whereHas('user')
            ->orderBy('id')
            ->get()
            ->map(fn (Provider $p) => [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Unknown',
            ]);

        return Inertia::render('Dashboard/Doctor', [
            'user' => Auth::user(),
            'queue' => $queue,
            'readingQueue' => $readingQueue,
            'doctors' => $doctors,
        ]);
    }

    // 🚀 PERFORMANCE FIX: Moved from route closure with optimized N+1 elimination
    public function emr()
    {
        if (Auth::user()->role !== 'doctor') {
            abort(403);
        }

        // ✅ OPTIMIZED: No N+1 query—use relationship in single query
        $patients = Patient::whereHas('user', fn ($q) => $q->where('role', 'patient'))
            ->with([
                'user:id,name,email',
                'appointments' => function ($q) {
                    $q->where('status', 'Completed')
                        ->select(['id', 'patient_id', 'scheduled_date', 'service_type'])
                        ->latest('scheduled_date')
                        ->limit(1);
                },
            ])
            ->get(['id', 'user_id'])
            ->map(function ($patient) {
                $latestAppt = $patient->appointments->first();

                return [
                    // The history/analytics routes bind `User $patient`, so the
                    // roster surfaces the user id (patients.user_id).
                    'id' => $patient->user_id,
                    'name' => $patient->user?->anonymizedName() ?? 'Unknown',
                    'consultation_type' => $latestAppt->service_type ?? 'No consultation',
                    'last_visit' => $latestAppt ? Carbon::parse($latestAppt->scheduled_date)->format('Y-m-d') : 'N/A',
                    'app_status' => 'Registered',
                ];
            })
            ->sortBy('name')
            ->take(1000)  // ✅ Cap at 1000 for frontend
            ->values();

        return Inertia::render('Dashboard/EMR', [
            'user' => Auth::user(),
            'patients' => $patients,
        ]);
    }

    /**
     * Parse vitals text like "BP: 138/88 mmHg | HR: 82 bpm | RR: 18 | Temp: 36.7°C | SpO2: 97%"
     * into structured key-value pairs.
     */
    private function parseVitals(?string $vitals): array
    {
        if (! $vitals) {
            return [];
        }

        $result = [];

        // BP: 138/88 mmHg
        if (preg_match('/BP:\s*(\d+)\/(\d+)/i', $vitals, $m)) {
            $result['bp_systolic'] = (int) $m[1];
            $result['bp_diastolic'] = (int) $m[2];
        }

        // HR: 82 bpm
        if (preg_match('/HR:\s*(\d+)/i', $vitals, $m)) {
            $result['hr'] = (int) $m[1];
        }

        // RR: 18
        if (preg_match('/RR:\s*(\d+)/i', $vitals, $m)) {
            $result['rr'] = (int) $m[1];
        }

        // Temp: 36.7
        if (preg_match('/Temp:\s*([\d.]+)/i', $vitals, $m)) {
            $result['temp'] = (float) $m[1];
        }

        // SpO2: 97%
        if (preg_match('/SpO2:\s*(\d+)/i', $vitals, $m)) {
            $result['spo2'] = (int) $m[1];
        }

        // Weight (from vitals or echo/tmt)
        if (preg_match('/Weight:\s*(\d+)/i', $vitals, $m)) {
            $result['weight'] = (int) $m[1];
        }

        return $result;
    }

    public function patientAnalytics(User $patient)
    {
        if (Auth::user()->role !== 'doctor') {
            abort(403);
        }
        if ($patient->role !== 'patient') {
            abort(404);
        }

        $patientRecord = $patient->patient;
        if (! $patientRecord) {
            return response()->json(['vitals' => [], 'echo' => [], 'tmt' => [], 'doppler' => []]);
        }

        // Structured clinical series: one point per completed encounter.
        $encounters = $patientRecord->encounters()
            ->with(['vitalSigns', 'echocardiogram', 'treadmillTest', 'dopplerStudy'])
            ->where('status', 'completed')
            ->orderBy('started_at', 'asc')
            ->get();

        $vitals = [];
        $echo = [];
        $tmt = [];
        $doppler = [];

        foreach ($encounters as $encounter) {
            $label = $encounter->started_at?->format('M d, Y') ?? 'N/A';

            $vs = $encounter->vitalSigns->first();
            if ($vs) {
                $point = ['date' => $label];
                foreach ([
                    'bp_systolic' => $vs->blood_pressure_systolic,
                    'bp_diastolic' => $vs->blood_pressure_diastolic,
                    'hr' => $vs->heart_rate_bpm,
                    'rr' => $vs->respiratory_rate,
                    'temp' => $vs->temperature_celsius,
                    'spo2' => $vs->oxygen_saturation_percent,
                    'weight' => $vs->weight_kg,
                ] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $point[$key] = (float) $value;
                    }
                }
                if (count($point) > 1) {
                    $vitals[] = $point;
                }
            }

            // Map normalized columns back to the legacy UI keys used by AnalyticsPanel.
            if ($encounter->echocardiogram) {
                $d = $encounter->echocardiogram;
                $point = ['date' => $label];
                foreach ([
                    'lv_ef' => $d->lvef_percent,
                    'lv_fs' => $d->lvfs_percent,
                    'lv_mass' => $d->lv_mass_grams,
                    'tapse' => $d->tapse_mm,
                ] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $point[$key] = (float) $value;
                    }
                }
                if ($vs?->weight_kg !== null) {
                    $point['weight'] = (float) $vs->weight_kg;
                }
                if ($vs?->heart_rate_bpm !== null) {
                    $point['hr'] = (float) $vs->heart_rate_bpm;
                }
                if (count($point) > 1) {
                    $echo[] = $point;
                }
            }

            if ($encounter->treadmillTest) {
                $t = $encounter->treadmillTest;
                $point = ['date' => $label];
                foreach ([
                    'peak_hr' => $t->peak_heart_rate_bpm,
                    'peak_mets' => $t->mets_achieved,
                    'peak_sbp' => $t->peak_bp_systolic,
                ] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $point[$key] = (float) $value;
                    }
                }
                if ($vs?->weight_kg !== null) {
                    $point['weight'] = (float) $vs->weight_kg;
                }
                if (count($point) > 1) {
                    $tmt[] = $point;
                }
            }

            if ($encounter->dopplerStudy) {
                $d = $encounter->dopplerStudy;
                $point = ['date' => $label];
                foreach ([
                    'mv_ea_ratio' => $d->ea_ratio,
                    'av_mean_grad' => $d->aortic_gradient,
                ] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $point[$key] = (float) $value;
                    }
                }
                if (count($point) > 1) {
                    $doppler[] = $point;
                }
            }
        }

        return response()->json([
            'vitals' => $vitals,
            'echo' => $echo,
            'tmt' => $tmt,
            'doppler' => $doppler,
        ]);
    }

    // 🚀 PERFORMANCE FIX: Already optimized in routes, kept for reference
    public function patientHistory(User $patient)
    {
        if (Auth::user()->role !== 'doctor') {
            abort(403);
        }
        if ($patient->role !== 'patient') {
            abort(404);
        }

        $patientRecord = $patient->patient;
        if (! $patientRecord) {
            abort(404);
        }

        // Eager-load everything needed to build the EMR record in one pass.
        $history = $patientRecord->encounters()
            ->with([
                'appointment:id,patient_id,provider_id,scheduled_date,scheduled_time,service_type',
                'appointment.provider.user:id,name',
                'provider.user:id,name',
                'soapNote',
                'diagnoses',
                'prescriptions',
                'vitalSigns',
                'echocardiogram',
                'treadmillTest',
                'dopplerStudy',
                'manualDraft',
                'stressEcho',
                'abpm',
            ])
            ->where('status', 'completed')
            ->orderByDesc('started_at')
            ->get()
            ->map(function ($encounter) use ($patientRecord) {
                $appt = $encounter->appointment;
                $provider = $encounter->provider ?? $appt?->provider;
                $soap = $encounter->soapNote;
                $vs = $encounter->vitalSigns->first();

                $date = $encounter->started_at?->format('M d, Y')
                    ?? ($appt?->scheduled_date?->format('M d, Y') ?? 'N/A');
                $time = $appt?->scheduled_time
                    ? Carbon::parse($appt->scheduled_time)->format('h:i A') : '';

                return [
                    'id' => $encounter->id,
                    // The PDF export route binds an Appointment, but the page
                    // historically sent the encounter id. The two id spaces
                    // do not line up (encounter 25 belonged to appointment 29),
                    // so every download 404'd. Ship the appointment id too.
                    'appointment_id' => $encounter->appointment_id,
                    'date' => $date,
                    'time' => $time,
                    'doctor' => $provider?->user?->name ?? 'Unknown',
                    'specialty' => $provider?->specialty,
                    'service' => $appt?->service_type ?? 'Consultation',
                    'symptoms' => $soap?->subjective,
                    'vitals' => $soap?->objective,
                    'prescriptions' => $encounter->prescriptions->map(fn ($rx) => [
                        'medicine' => $rx->medication_name,
                        'dosage' => $rx->dosage,
                        'frequency' => $rx->frequency,
                        'duration' => $rx->duration_days ? $rx->duration_days.' days' : null,
                        'instructions' => $rx->instructions,
                    ])->values(),
                    'ai_summary' => $soap?->ai_summary,
                    // Type numbering is canonical app-wide: echo=Type 1,
                    // tmt=Type 2, doppler=Type 3 (see AppointmentController
                    // and MonthlyReportService). These were previously swapped,
                    // which mislabelled every completed consultation.
                    'consultation_type' => match ($encounter->encounter_type) {
                        'echo' => 'Type 1 - Echocardiographic',
                        'tmt' => 'Type 2 - Treadmill (TMT)',
                        'doppler' => 'Type 3 - Color Doppler',
                        'manual_draft' => 'Type 4 - Manual Draft',
                        'stress_echo' => 'Type 5 - Stress Echo',
                        'abpm' => 'Type 6 - Ambulatory BP Monitoring',
                        default => 'Consultation',
                    },
                    // Study panels are gated on the relation existing rather
                    // than on encounter_type, because a Doppler encounter also
                    // carries the echo fields.
                    'echo_data' => ClinicalRecordMapper::forPayload(
                        $encounter->echocardiogram, null, null, $patientRecord
                    ),
                    'tmt_data' => ClinicalRecordMapper::forPayload(
                        null, $encounter->treadmillTest, null, $patientRecord
                    ),
                    'doppler_data' => ClinicalRecordMapper::forPayload(
                        null, null, $encounter->dopplerStudy, $patientRecord
                    ),
                    'manual_draft_data' => $encounter->manualDraft ? [
                        'requesting_physician' => $encounter->manualDraft->requesting_physician,
                        'title' => $encounter->manualDraft->title,
                        'draft' => $encounter->manualDraft->draft,
                        'conclusion' => $encounter->manualDraft->conclusion,
                        'images' => $encounter->manualDraft->attachments ?? [],
                    ] : null,
                    'stress_echo_data' => $encounter->stressEcho?->payload ?? null,
                    'abpm_data' => $encounter->abpm?->payload ?? null,
                    'vital_sign' => [
                        'bp' => $vs ? $vs->blood_pressure_systolic.'/'.$vs->blood_pressure_diastolic : null,
                        'hr' => $vs?->heart_rate_bpm,
                        'spo2' => $vs?->oxygen_saturation_percent,
                        'temp' => $vs?->temperature_celsius,
                    ],
                ];
            });

        return Inertia::render('Dashboard/PatientHistory', [
            'user' => Auth::user(),
            'patient' => [
                'id' => $patient->id,
                'patient_id' => $patient->patient_id,
                'name' => $patient->name,
                'email' => $patient->email,
                'allergies' => $patientRecord->allergies,
                'joined' => $patient->created_at?->format('M Y'),
            ],
            'history' => $history,
            'ocrHistory' => $this->ocrHistory($patientRecord),
        ]);
    }

    /**
     * The patient's uploaded scans with their OCR lifecycle, so the doctor can
     * review what has been recognised (and whether it still needs a look) from
     * the same EMR screen as the rest of the record.
     */
    private function ocrHistory(Patient $patientRecord): array
    {
        return Document::where('patient_id', $patientRecord->id)
            ->with('uploader:id,name', 'appointment:id,scheduled_date,status')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'original_filename' => $d->original_filename,
                'url' => $d->url(),
                'ocr_status' => $d->ocr_status,
                'ocr_confidence' => $d->ocr_confidence,
                'ocr_error' => $d->ocr_error,
                'engine' => $d->engine(),
                'requires_review' => $d->requiresReview(),
                'uploaded_by' => $d->uploader?->name,
                'appointment' => $d->appointment ? [
                    'id' => $d->appointment->id,
                    'scheduled_date' => $d->appointment->scheduled_date?->toDateString(),
                    'status' => $d->appointment->status,
                ] : null,
                'ocr_text' => $d->ocr_text,
                'created_at' => $d->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
