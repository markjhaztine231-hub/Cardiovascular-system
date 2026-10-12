<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentOcr;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReceptionistDocumentController extends Controller
{
    public function store(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,tiff,bmp,webp|max:10240',
            'type' => 'required|in:prescription,lab_result,referral,other,echo,tmt,doppler',
        ]);

        $file = $validated['document'];
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'uploaded_by' => Auth::id(),
            'type' => $validated['type'],
            'original_filename' => $file->getClientOriginalName(),
            'image_path' => $path,
            'ocr_status' => 'pending',
        ]);

        ProcessDocumentOcr::dispatch($document->id);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Document scanned for appointment #'.$appointment->id.': '.$file->getClientOriginalName(),
        ]);

        return response()->json([
            'message' => 'Document uploaded. OCR is processing.',
            'document' => $document->only(['id', 'type', 'ocr_status']),
        ], 201);
    }

    public function show(Document $document)
    {
        $this->authorizeView($document);

        return response()->json([
            'document' => $document,
            'url' => $document->url(),
            'lines' => $document->lines(),
            'requires_review' => $document->requiresReview(),
        ]);
    }

    /**
     * Re-run OCR for a scan that failed or stalled (service was down, worker was
     * not running, image was too blurry). Reception owns this, not the doctor:
     * the image already exists, so nothing has to be re-uploaded.
     */
    public function retryOcr(Document $document)
    {
        $role = Auth::user()?->role;

        abort_if(! in_array($role, ['receptionist', 'admin'], true), 403);

        abort_if(
            ! Storage::disk('public')->exists($document->image_path),
            422,
            'The stored image is missing; please upload the scan again.'
        );

        $document->update(['ocr_status' => 'pending', 'ocr_error' => null]);

        ProcessDocumentOcr::dispatch($document->id);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'OCR retried for document #'.$document->id,
        ]);

        return response()->json([
            'message' => 'OCR re-queued. This can take a minute once the service is up.',
            'document' => $document->only(['id', 'type', 'ocr_status']),
        ]);
    }

    /**
     * Reception and admin staff work clinic-wide, but a doctor may only read
     * scans belonging to their own appointments.
     */
    private function authorizeView(Document $document): void
    {
        $role = Auth::user()?->role;

        if (in_array($role, ['receptionist', 'admin'], true)) {
            return;
        }

        if ($role === 'doctor') {
            $providerId = Auth::user()?->provider?->id;

            $ownsAppointment = $providerId !== null
                && $document->appointment()->where('provider_id', $providerId)->exists();

            if ($ownsAppointment) {
                return;
            }
        }

        abort(403, 'This document belongs to another doctor.');
    }

    /**
     * Scans still waiting on the OCR queue, for the receptionist dashboard.
     */
    public function pending()
    {
        return response()->json(
            Document::whereIn('ocr_status', ['pending', 'processing'])
                ->with('appointment:id,scheduled_date,scheduled_time')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Document $d) => [
                    'id' => $d->id,
                    'type' => $d->type,
                    'ocr_status' => $d->ocr_status,
                    'original_filename' => $d->original_filename,
                    'appointment_id' => $d->appointment_id,
                    'created_at' => $d->created_at->toIso8601String(),
                ])
        );
    }
}
