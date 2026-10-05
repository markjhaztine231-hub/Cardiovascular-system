<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Patient;
use App\Services\PrescriptionTextParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorConsultationController extends Controller
{
    private const IN_PROGRESS = 'In Progress';

    public function show(Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        $appointment->load([
            'patient:id,user_id,mrn,date_of_birth,sex,blood_type,allergies,insurance_provider',
            'patient.user:id,name,email',
            'provider:id,user_id,credentials,specialty',
            'documents' => fn ($query) => $query->with('uploader:id,name')->latest(),
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
            ],
            'patient' => $patient,
            'documents' => $appointment->documents->map(fn (Document $d) => $this->summarise($d)),
            'history' => $this->history($patient),
        ]);
    }

    public function start(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

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

        if ($providerId === null || $appointment->provider_id !== $providerId) {
            abort(403, 'This appointment belongs to another doctor.');
        }
    }
}
