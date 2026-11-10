<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\MonthlyReport;
use App\Models\Patient;
use App\Support\ClinicalFormData;
use App\Support\ClinicalRecordMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfController extends Controller
{
    /**
     * Stream a PDF for the given appointment's clinical data.
     * PDF blade views still expect the legacy blob keys, so the structured
     * studies are mapped back to that shape at export time.
     *
     * @param  string  $type  echo | tmt | doppler | manual_draft | stress_echo | abpm | prescription | clinical_requests
     */
    public function download(Appointment $appointment, string $type)
    {
        // Clinicians and front-desk staff may download, as may the patient
        // themselves (restricted to their own record below).
        $user = Auth::user();
        if (! in_array($user->role, ['doctor', 'patient', 'receptionist', 'admin'])) {
            abort(403);
        }

        // Patients may only see their own records (patients.id, not the user id)
        if ($user->role === 'patient' && $appointment->patient_id !== $user->patient?->id) {
            abort(403);
        }

        $appointment->loadMissing([
            'provider.user:id,name',
            'readerProvider.user:id,name',
            'patient.user:id,name,email',
            'patient:id,user_id,sex,date_of_birth,allergies',
        ]);
        $patient = $appointment->patient;
        $patientName = $patient?->user?->name ?? 'Patient';
        $attendingName = $appointment->provider?->user?->name ?? 'Cardiologist';
        // Study reports are signed by the cardiologist who read the study. When
        // no reader is assigned, the attending doctor signs instead.
        $readerName = $appointment->readerProvider?->user?->name;
        $signingDoctorName = $readerName ?: $attendingName;
        $date = Carbon::parse($appointment->scheduled_date)->format('M d, Y');
        $slug = str_replace(' ', '_', $patientName);

        // Clinical data now lives on the appointment's encounter (structured tables).
        $encounter = Encounter::where('appointment_id', $appointment->id)
            ->first();

        // Fail loudly instead of streaming a blank document. Returning [] here
        // produced a valid but empty PDF, which looks like a corrupt download.
        if (! $encounter) {
            abort(404, 'No completed consultation is linked to this appointment.');
        }

        // Guard each document type against a consultation that never had that
        // study recorded, and against a form that only holds its default
        // values. Printing an untouched Echo/TMT/Doppler form is never useful.
        $prescriptions = $encounter->prescriptions
            ->filter(fn ($rx) => ClinicalFormData::prescriptionHasContent($rx))
            ->values();

        $requests = $encounter->clinicalRequests
            ->filter(fn ($request) => ClinicalFormData::requestHasContent($request))
            ->values();

        $hasContent = match ($type) {
            'echo' => ClinicalFormData::modelHasContent('echo', $encounter->echocardiogram),
            'tmt' => ClinicalFormData::modelHasContent('tmt', $encounter->treadmillTest),
            'doppler' => ClinicalFormData::modelHasContent('doppler', $encounter->dopplerStudy),
            'manual_draft' => ClinicalFormData::modelHasContent('manual_draft', $encounter->manualDraft),
            'stress_echo' => ClinicalFormData::modelHasContent('stress_echo', $encounter->stressEcho),
            'abpm' => ClinicalFormData::modelHasContent('abpm', $encounter->abpm),
            'prescription' => $prescriptions->isNotEmpty(),
            'clinical_requests' => $requests->isNotEmpty(),
            default => false,
        };

        if (! $hasContent) {
            abort(404, "This consultation has no {$type} content to export.");
        }

        return match ($type) {
            'echo' => Pdf::loadView('pdfs.echo', [
                'data' => ClinicalRecordMapper::echo($encounter->echocardiogram, $patient),
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("Echo_{$slug}_{$date}.pdf"),

            'tmt' => Pdf::loadView('pdfs.tmt', [
                'data' => ClinicalRecordMapper::tmt($encounter->treadmillTest, $patient),
                'patient' => $patientName,
                'date' => $date,
                'sex' => $patient?->sex ?? '',
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("TMT_{$slug}_{$date}.pdf"),

            'doppler' => Pdf::loadView('pdfs.doppler', [
                'data' => ClinicalRecordMapper::doppler($encounter->dopplerStudy, $patient),
                'echoData' => $encounter->echocardiogram
                    ? ClinicalRecordMapper::echo($encounter->echocardiogram, $patient)
                    : [],
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("Doppler_{$slug}_{$date}.pdf"),

            'prescription' => Pdf::loadView('pdfs.prescription', [
                'prescriptions' => ClinicalRecordMapper::prescriptions($prescriptions),
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $attendingName,
            ])
                ->setPaper('a5')
                ->download("Prescription_{$slug}_{$date}.pdf"),

            'clinical_requests' => Pdf::loadView('pdfs.clinical-requests', [
                'requests' => $requests
                    ->map(fn ($request) => [
                        'type' => $request->type,
                        'type_label' => $request->type_label,
                        'body' => $request->body,
                        'priority' => $request->priority,
                    ])
                    ->values()
                    ->all(),
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $attendingName,
            ])
                ->setPaper('a5')
                ->download("Clinical_Requests_{$slug}_{$date}.pdf"),

            'manual_draft' => Pdf::loadView('pdfs.manual-draft', [
                'draft' => $encounter->manualDraft,
                'attachments' => $this->resolveDraftAttachments($encounter->manualDraft->attachments),
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("Manual_Draft_{$slug}_{$date}.pdf"),

            'stress_echo' => Pdf::loadView('pdfs.stress-echo', [
                'data' => $encounter->stressEcho->payload ?? [],
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("Stress_Echo_{$slug}_{$date}.pdf"),

            'abpm' => Pdf::loadView('pdfs.abpm', [
                'data' => $encounter->abpm->payload ?? [],
                'patient' => $patientName,
                'date' => $date,
                'doctor' => $signingDoctorName,
            ])
                ->setPaper('letter')
                ->download("ABPM_{$slug}_{$date}.pdf"),

            default => abort(404, 'Unknown PDF type'),
        };
    }

    /**
     * Turn a stored Manual Draft attachment list into dompdf-embeddable local
     * file paths. Attachments referenced by document id (dragged scans) are
     * re-resolved from the public disk; locally-dropped blob URLs are skipped
     * because they have no server-side file.
     */
    private function resolveDraftAttachments(?array $attachments): array
    {
        $resolved = [];

        foreach ($attachments ?? [] as $image) {
            if (! empty($image['id'])) {
                $document = Document::find($image['id']);
                $absolutePath = $document?->absolutePath();

                if ($absolutePath !== null) {
                    $resolved[] = [
                        'path' => $absolutePath,
                        'name' => $image['name'] ?? basename($absolutePath),
                    ];

                    continue;
                }
            }

            // Older drafts may reference a file by its public URL. Those files
            // stay readable until the privatisation command has moved them.
            if (! empty($image['url']) && str_starts_with($image['url'], '/storage/')) {
                $storagePath = Str::after($image['url'], '/storage/');

                if (! Storage::disk('public')->exists($storagePath)) {
                    continue;
                }

                $resolved[] = [
                    'path' => Storage::disk('public')->path($storagePath),
                    'name' => $image['name'] ?? basename($storagePath),
                ];
            }
        }

        return $resolved;
    }

    /**
     * Download the monthly clinical report as a formatted PDF.
     * Includes all stats sections + the full AI narrative on a second page.
     */
    public function downloadMonthlyReport(Request $request)
    {
        if (Auth::user()->role !== 'doctor') {
            abort(403);
        }

        $providerId = Auth::user()->provider?->id;

        // A doctor may download their own personal reports and any practice-wide
        // report, but not another doctor's personal report.
        $query = MonthlyReport::query()
            ->where(function ($query) use ($providerId) {
                $query->where(function ($query) use ($providerId) {
                    $query->where('scope', MonthlyReport::SCOPE_PROVIDER)
                        ->where('provider_id', $providerId ?? 0);
                })->orWhere(function ($query) {
                    $query->where('scope', MonthlyReport::SCOPE_PRACTICE)
                        ->whereNull('provider_id');
                });
            });

        if ($request->filled('report')) {
            $query->where('id', $request->report);
        } else {
            $scope = $request->get('scope') === MonthlyReport::SCOPE_PRACTICE
                ? MonthlyReport::SCOPE_PRACTICE
                : MonthlyReport::SCOPE_PROVIDER;

            $query->where('scope', $scope)
                ->forMonth($request->get('month', date('Y-m')));

            if ($scope === MonthlyReport::SCOPE_PROVIDER) {
                $query->where('provider_id', $providerId ?? 0);
            } else {
                $query->whereNull('provider_id');
            }
        }

        $report = $query->latest()->first();

        if (! $report) {
            abort(404, 'No report found for this month. Please generate it first.');
        }

        $formattedMonth = Carbon::parse($report->month.'-01')->format('F Y');
        $slug = str_replace(' ', '_', $formattedMonth);
        $prefix = $report->isPracticeWide()
            ? 'Practice_Monthly_Report'
            : 'Personal_Monthly_Report';

        return Pdf::loadView('pdfs.monthly-report', [
            'stats' => $report->stats,
            'reportText' => $report->report_text,
            'formattedMonth' => $formattedMonth,
            'reportTitle' => MonthlyReport::labelForScope($report->scope),
            'doctor' => Auth::user()->name,
            'generatedAt' => now()->format('F d, Y'),
        ])
            ->setPaper('letter', 'portrait')
            ->download("{$prefix}_{$slug}.pdf");
    }

    // -------------------------------------------------------------------------
    // Legacy-shape mapping now lives in App\Support\ClinicalRecordMapper so the
    // PDF and the View History tables cannot drift apart.
    // -------------------------------------------------------------------------
}
