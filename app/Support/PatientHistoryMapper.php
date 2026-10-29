<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Patient;
use Carbon\Carbon;

/**
 * Builds the canonical EMR history payload shared by the doctor and
 * receptionist history screens, so both roles see every recorded
 * consultation field and study rather than two diverging shapes.
 */
class PatientHistoryMapper
{
    public static function history(Patient $patientRecord): array
    {
        return $patientRecord->encounters()
            ->with([
                'appointment:id,patient_id,provider_id,scheduled_date,scheduled_time,service_type',
                'appointment.provider.user:id,name',
                'provider.user:id,name',
                'soapNote',
                'diagnoses',
                'prescriptions',
                'clinicalRequests',
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

                $forms = [];

                foreach ([
                    'echo' => [$encounter->echocardiogram, 'Echo'],
                    'tmt' => [$encounter->treadmillTest, 'TMT'],
                    'doppler' => [$encounter->dopplerStudy, 'Doppler'],
                    'manual_draft' => [$encounter->manualDraft, 'Manual Draft'],
                    'stress_echo' => [$encounter->stressEcho, 'Stress Echo'],
                    'abpm' => [$encounter->abpm, 'ABPM'],
                ] as $type => [$study, $label]) {
                    if ($study) {
                        $forms[] = [
                            'type' => $type,
                            'label' => $label,
                            'url' => route('pdf.download', [
                                'appointment' => $encounter->appointment_id,
                                'type' => $type,
                            ]),
                        ];
                    }
                }

                if ($encounter->prescriptions->isNotEmpty()) {
                    $forms[] = [
                        'type' => 'prescription',
                        'label' => 'Prescription',
                        'url' => route('pdf.download', [
                            'appointment' => $encounter->appointment_id,
                            'type' => 'prescription',
                        ]),
                    ];
                }

                if ($encounter->clinicalRequests->isNotEmpty()) {
                    $forms[] = [
                        'type' => 'clinical_requests',
                        'label' => 'Clinical Requests',
                        'url' => route('pdf.download', [
                            'appointment' => $encounter->appointment_id,
                            'type' => 'clinical_requests',
                        ]),
                    ];
                }

                return [
                    'id' => $encounter->id,
                    // The PDF export route binds an Appointment, while this
                    // record is an Encounter. Keep both ids explicit.
                    'appointment_id' => $encounter->appointment_id,
                    'date' => $date,
                    'time' => $time,
                    'doctor' => $provider?->user?->name ?? 'Unknown',
                    'specialty' => $provider?->specialty,
                    'service' => $appt?->service_type ?? 'Consultation',
                    'symptoms' => $soap?->subjective,
                    'vitals' => $soap?->objective,
                    'diagnosis' => $encounter->diagnoses->firstWhere('is_primary', true)?->description
                        ?? $encounter->diagnoses->first()?->description,
                    'plan' => $soap?->plan,
                    'prescriptions' => $encounter->prescriptions->map(fn ($rx) => [
                        'medicine' => $rx->medication_name,
                        'dosage' => $rx->dosage,
                        'frequency' => $rx->frequency,
                        'duration' => $rx->duration_days ? $rx->duration_days.' days' : null,
                        'instructions' => $rx->instructions,
                        'is_freeform' => (bool) $rx->is_freeform,
                        'free_text' => $rx->free_text,
                    ])->values(),
                    'requests' => $encounter->clinicalRequests
                        ->map(fn ($request) => [
                            'id' => $request->id,
                            'type' => $request->type,
                            'type_label' => $request->type_label,
                            'body' => $request->body,
                            'priority' => $request->priority,
                            'status' => $request->status,
                        ])
                        ->values(),
                    'ai_summary' => $soap?->ai_summary,
                    // Type numbering is canonical app-wide: echo=Type 1,
                    // tmt=Type 2, doppler=Type 3 (see AppointmentController
                    // and MonthlyReportService).
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
                    'stress_echo_data' => $encounter->stressEcho?->payload,
                    'abpm_data' => $encounter->abpm?->payload,
                    'vital_sign' => [
                        'bp' => $vs ? $vs->blood_pressure_systolic.'/'.$vs->blood_pressure_diastolic : null,
                        'hr' => $vs?->heart_rate_bpm,
                        'spo2' => $vs?->oxygen_saturation_percent,
                        'temp' => $vs?->temperature_celsius,
                    ],
                    'forms' => $forms,
                ];
            })
            ->values()
            ->all();
    }

    public static function ocrHistory(Patient $patientRecord): array
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
