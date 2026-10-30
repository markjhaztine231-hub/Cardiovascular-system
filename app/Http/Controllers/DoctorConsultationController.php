<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\VitalSign;
use App\Services\PrescriptionTextParser;
use App\Support\ClinicalRecordMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorConsultationController extends Controller
{
    private const IN_PROGRESS = 'In Progress';

    public function show(Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        $providerId = Auth::user()?->provider?->id;
        $isReadingDoctor = $providerId !== null
            && $appointment->reader_provider_id === $providerId
            && $appointment->provider_id !== $providerId;

        $appointment->load([
            'patient:id,user_id,mrn,date_of_birth,sex,blood_type,allergies,insurance_provider',
            'patient.user:id,name,email',
            'provider:id,user_id,credentials,specialty',
            'provider.user:id,name',
            'readerProvider:id,user_id',
            'readerProvider.user:id,name',
            'documents' => fn ($query) => $query->with('uploader:id,name')->latest(),
            'encounter.echocardiogram',
            'encounter.treadmillTest',
            'encounter.dopplerStudy',
            'encounter.stressEcho',
            'encounter.abpm',
            'encounter.vitalSigns',
        ]);

        $patient = $appointment->patient;

        return response()->json([
            'appointment' => [
                'id' => $appointment->id,
                'status' => $appointment->status,
                'scheduled_date' => $appointment->scheduled_date?->toDateString(),
                'scheduled_time' => $appointment->scheduled_time,
                'consultation_started_at' => $appointment->consultation_started_at?->toIso8601String(),
                'service_type' => $appointment->service_type,
                'reason_for_visit' => $appointment->reason_for_visit,
                'requesting_doctor' => $appointment->provider?->user?->name,
                'reader_provider_id' => $appointment->reader_provider_id,
                'reader' => $appointment->readerProvider?->user?->name,
                'reading_date' => $appointment->reading_date?->toDateString(),
                'reading_status' => $appointment->reading_status,
                'reading_completed_at' => $appointment->reading_completed_at?->toIso8601String(),
                'is_reading_doctor' => $isReadingDoctor,
                // A reading doctor may only save while the reading is pending.
                'can_edit_reading' => $appointment->status === 'Completed'
                    && $appointment->reading_status !== 'completed'
                    && ($isReadingDoctor || $appointment->provider_id === $providerId),
            ],
            'patient' => $patient,
            'documents' => $appointment->documents->map(fn (Document $d) => $this->summarise($d)),
            'history' => $this->history($patient),
            'audit' => $this->auditHistory($appointment),
            // Study measurements the receptionist already recorded on a draft
            // encounter (or a completed record on a revisit) so the doctor's
            // forms can be pre-filled instead of re-keyed.
            'prefill' => $this->prefill($appointment->encounter),
        ]);
    }

    public function start(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        // A No-Show appointment is closed by the receptionist and must never be
        // pulled into a consultation — the queue hides it, and a hand-typed URL
        // must not resurrect it.
        if ($appointment->status === 'No-Show') {
            abort(422, 'This appointment was marked as No-Show and can no longer be edited.');
        }

        if ($appointment->status === 'Cancelled') {
            abort(422, 'This appointment was cancelled and can no longer be edited.');
        }

        // Opening a completed visit (typically from the reading queue) is a
        // review, not a new consultation. Do not push it back to In Progress.
        if ($appointment->status === 'Completed') {
            return $this->show($appointment->fresh());
        }

        if ($appointment->status !== self::IN_PROGRESS) {
            $appointment->update([
                'status' => self::IN_PROGRESS,
                'consultation_started_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'Consultation started for appointment #'.$appointment->id,
            ]);
        }

        return $this->show($appointment->fresh());
    }

    /**
     * Recent audit entries for this appointment, newest first. Reading-doctor
     * saves and reading assignments both land here so the panel shows who
     * touched the record and when.
     */
    private function auditHistory(Appointment $appointment)
    {
        return AuditLog::with('user:id,name')
            ->where('action', 'like', '%#'.$appointment->id.'%')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->name ?? 'System',
                'created_at' => $log->created_at?->toIso8601String(),
            ]);
    }

    /**
     * Study-form prefill for the consultation: whatever study rows exist on the
     * appointment's encounter, mapped back to the form key shape the front-end
     * binds to. `source` tells the doctor whether reception already measured
     * the study (draft) or whether it is a prior completed record.
     */
    private function prefill(?Encounter $encounter): ?array
    {
        if (! $encounter) {
            return null;
        }

        return [
            'status' => $encounter->status,
            'source' => $encounter->status === 'completed' ? 'doctor' : 'receptionist',
            'echo_data' => $encounter->echocardiogram
                ? ClinicalRecordMapper::forPayload($encounter->echocardiogram)
                : null,
            'tmt_data' => $encounter->treadmillTest
                ? ClinicalRecordMapper::forPayload(null, $encounter->treadmillTest)
                : null,
            'doppler_data' => $encounter->dopplerStudy
                ? ClinicalRecordMapper::forPayload(null, null, $encounter->dopplerStudy)
                : null,
            'stress_echo_data' => $encounter->stressEcho?->payload,
            'abpm_data' => $encounter->abpm?->payload,
            'vitals_data' => $this->vitalsToForm($encounter->vitalSigns->first()),
        ];
    }

    /**
     * Map a stored vitals row back to the vitals form key shape the doctor's
     * Objective (Vitals) block binds to.
     */
    private function vitalsToForm(?VitalSign $vitals): ?array
    {
        if (! $vitals) {
            return null;
        }

        return [
            'bp_sys' => $vitals->blood_pressure_systolic,
            'bp_dia' => $vitals->blood_pressure_diastolic,
            'hr' => $vitals->heart_rate_bpm,
            'rr' => $vitals->respiratory_rate,
            'temp' => $vitals->temperature_celsius,
            'spo2' => $vitals->oxygen_saturation_percent,
        ];
    }

    private function history(Patient $patient)
    {
        return Document::where('patient_id', $patient->id)
            ->with('uploader:id,name', 'appointment:id,scheduled_date,status')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'ocr_text' => $d->ocr_text,
                'ocr_status' => $d->ocr_status,
                'requires_review' => $d->requiresReview(),
                'uploaded_by' => $d->uploader?->name,
                'appointment' => $d->appointment ? [
                    'id' => $d->appointment->id,
                    'scheduled_date' => $d->appointment->scheduled_date?->toDateString(),
                    'status' => $d->appointment->status,
                ] : null,
                'created_at' => $d->created_at->toIso8601String(),
            ]);
    }

    private function summarise(Document $document): array
    {
        $isReadyPrescription = $document->type === 'prescription'
            && $document->ocr_status === 'completed';

        $parsed = $isReadyPrescription
            ? app(PrescriptionTextParser::class)->parse((string) $document->ocr_text, $document->lines())
            : ['medications' => [], 'warnings' => []];

        return [
            'id' => $document->id,
            'type' => $document->type,
            'url' => $document->url(),
            'original_filename' => $document->original_filename,
            'ocr_text' => $document->ocr_text,
            'ocr_status' => $document->ocr_status,
            'ocr_confidence' => $document->ocr_confidence,
            'ocr_error' => $document->ocr_error,
            'engine' => $document->engine(),
            'requires_review' => $document->requiresReview(),
            'uploaded_by' => $document->uploader?->name,
            'created_at' => $document->created_at->toIso8601String(),
            // OCR output is only a draft: it is shown beside the form for the
            // doctor to read and confirm, never written to prescriptions here.
            'medications' => $parsed['medications'],
            'warnings' => $parsed['warnings'],
            // Where the text sat on the page, plus the non-text marks, so the
            // doctor can sanity-check the extraction against the scan.
            'layout' => $document->layout(),
            'visuals' => $document->visuals(),
            'visual_summary' => $document->visualSummary(),
        ];
    }

    /**
     * Appointments hang off providers, not users, so ownership is resolved
     * through the authenticated user's linked provider record.
     */
    private function authorizeDoctor(Appointment $appointment): void
    {
        $providerId = Auth::user()?->provider?->id;

        if ($providerId === null || ! in_array($providerId, [$appointment->provider_id, $appointment->reader_provider_id], true)) {
            abort(403, 'This appointment belongs to another doctor.');
        }
    }
}
