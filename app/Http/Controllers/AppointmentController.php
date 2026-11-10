<?php

namespace App\Http\Controllers;

use App\Events\AppointmentRescheduled;
use App\Events\AppointmentStatusChanged;
use App\Events\ConsultationCompleted;
use App\Events\QueueUpdated;
use App\Events\RescheduleDenied;
use App\Events\RescheduleRequested;
use App\Models\AmbulatoryBloodPressureMonitoring;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\DoctorSchedule;
use App\Models\Document;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\ManualDraft;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\StressEchocardiogram;
use App\Models\TreadmillTest;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\GoogleCalendarService;
use App\Support\AppointmentSlot;
use App\Support\ClinicalFormData;
use App\Support\ClinicalRecordMapper;
use App\Support\DiagnosisText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct(protected GoogleCalendarService $calendar) {}

    /**
     * A No-Show is terminal. Once the receptionist records that the patient did
     * not arrive, the appointment can no longer be edited, transferred, or
     * rescheduled by anyone — it can only be superseded by a new booking.
     */
    private function assertNotNoShow(Appointment $appointment): void
    {
        if ($appointment->status === 'No-Show') {
            throw ValidationException::withMessages([
                'error' => 'This appointment was marked as No-Show and can no longer be edited.',
            ]);
        }
    }

    /**
     * A completed visit is a finished clinical record: it can still receive a
     * reading-doctor update, but it cannot be cancelled, marked No-Show,
     * rescheduled, or transferred, because those actions would reopen a visit
     * that already produced EMR data.
     */
    private function assertNotCompleted(Appointment $appointment, string $message = ''): void
    {
        if ($appointment->status === 'Completed') {
            throw ValidationException::withMessages([
                'error' => $message !== ''
                    ? $message
                    : 'This appointment is already completed and can no longer be changed.',
            ]);
        }
    }

    // 1. Patient books a new appointment (max 2 per day enforced)
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
            'service' => 'required|string|in:Consultation,Procedure,Consultation and Procedure',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        // Legacy exact-time bookings keep their past-time guard. Slot bookings
        // are filtered while the first free time in the window is chosen.
        if ($exactTime !== null && $request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$exactTime);
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time slot has already passed. Please select a future time.',
                ]);
            }
        }

        // Resolve the Patient record behind the authenticated user
        $patient = Auth::user()->patient;
        if (! $patient) {
            throw ValidationException::withMessages([
                'date' => 'No patient record is linked to your account.',
            ]);
        }

        // Enforce max 3 active appointments per patient per day
        $dailyCount = Appointment::where('patient_id', $patient->id)
            ->whereDate('scheduled_date', $request->date)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->count();

        if ($dailyCount >= 3) {
            throw ValidationException::withMessages([
                'date' => 'You already have 3 appointments booked for this day. Maximum is 3 per day.',
            ]);
        }

        // Lock every provider working the requested window, then re-run
        // availability inside the lock. Without this, two simultaneous
        // bookings can both pass the check and claim the same doctor.
        [$providerId, $appointment] = DB::transaction(function () use ($request, $patient, $slot, $exactTime) {
            if ($slot !== null) {
                $this->lockProvidersForDate($request->date);
                $time = $this->firstAvailableTime($request->date, $slot);

                if ($time === null) {
                    throw ValidationException::withMessages([
                        'time' => 'No doctor is available in that time slot. Please choose another slot.',
                    ]);
                }

                $providerId = self::autoAssignDoctor($request->date, $time);
            } else {
                $time = $exactTime;
                $this->lockProvidersForSlot($request->date, $time);
                $providerId = self::autoAssignDoctor($request->date, $time);
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'provider_id' => $providerId,
                'scheduled_date' => $request->date,
                'scheduled_time' => $time,
                'duration_minutes' => $this->getDurationMinutes($patient->id),
                'service_type' => $request->service,
                'status' => 'Scheduled',
            ]);

            return [$providerId, $appointment];
        });

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Booked new appointment for '.$request->date.' (Assigned to Doctor ID: '.$providerId.')',
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($providerId);

        return back()->with('success', 'Appointment successfully booked! A doctor has been assigned.');
    }

    /**
     * Live slot-availability endpoint. Returns the two half-day windows that
     * still have at least one free exact time for a scheduled doctor. The
     * patient only ever sees Morning / Afternoon; the server assigns the
     * exact time when the booking is submitted.
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = $validated['date'];
        $dayInt = Carbon::parse($date)->dayOfWeekIso; // 1 = Monday .. 7 = Sunday

        $schedules = DoctorSchedule::whereJsonContains('day_of_week', $dayInt)->get();

        if ($schedules->isEmpty()) {
            return response()->json(['date' => $date, 'slots' => []]);
        }

        $slots = collect(AppointmentSlot::all())
            ->map(function (array $window, string $value) use ($schedules, $date) {
                $candidateTimes = collect();

                foreach ($schedules as $schedule) {
                    $candidateTimes = $candidateTimes->merge(
                        AppointmentSlot::times($value, $schedule->start_time, $schedule->end_time)
                    );
                }

                $hasAvailability = $candidateTimes
                    ->unique()
                    ->contains(function (string $time) use ($date) {
                        if (AppointmentSlot::isPast($date, $time)) {
                            return false;
                        }

                        return self::providersAvailableAt($date, $time)->isNotEmpty();
                    });

                return $hasAvailability
                    ? ['value' => $value, 'label' => $window['label']]
                    : null;
            })
            ->filter()
            ->values();

        return response()->json(['date' => $date, 'slots' => $slots]);
    }

    // 1b. Patient cancels their own upcoming appointment
    public function cancelAppointment(Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        // Cannot cancel a terminal or in-progress appointment
        if (in_array($appointment->status, ['Completed', 'No-Show', 'In Progress', 'Cancelled'])) {
            return back()->withErrors([
                'error' => 'This appointment cannot be cancelled — it is already '.$appointment->status.'.',
            ]);
        }

        $appointment->update(['status' => 'Cancelled']);
        $this->calendar->updateStatus($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Patient cancelled appointment #'.$appointment->id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        event(new AppointmentStatusChanged($appointment->fresh()));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Your appointment has been cancelled.');
    }

    // 2. Doctor/Receptionist updates the status
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $this->assertNotNoShow($appointment);

        $validated = $request->validate([
            'status' => 'required|string|in:Scheduled,Pending,Approved,Cancelled,No-Show,Completed,In Progress,Reschedule Requested',
            'reader_provider_id' => 'nullable|exists:providers,id',
            'reading_date' => 'nullable|required_with:reader_provider_id|date',
        ]);

        // Completed is terminal for cancellation / no-show / re-opening. The
        // reading doctor's own save path is separate and still allowed.
        if ($appointment->status === 'Completed' && $validated['status'] !== 'Completed') {
            throw ValidationException::withMessages([
                'status' => 'This appointment is already completed. Its status can no longer be changed to '.$validated['status'].'.',
            ]);
        }

        if (! empty($validated['reader_provider_id']) && ! $appointment->supportsReadingAssignment()) {
            throw ValidationException::withMessages([
                'reader_provider_id' => 'Only procedure appointments can be assigned to a reading doctor.',
            ]);
        }

        // Reviving a cancelled appointment must not land on a slot that has
        // since been booked by someone else.
        if ($appointment->status === 'Cancelled' && $validated['status'] !== 'Cancelled') {
            $date = $appointment->scheduled_date->format('Y-m-d');
            $time = $appointment->scheduled_time;

            DB::transaction(function () use ($appointment, $validated, $date, $time) {
                $this->lockProvidersForSlot($date, $time, $appointment->provider_id);

                if ($this->slotTaken($appointment->provider_id, $date, $time, $appointment->id)) {
                    throw ValidationException::withMessages([
                        'status' => 'This appointment cannot be reactivated — another appointment now occupies that slot.',
                    ]);
                }

                $appointment->update(['status' => $validated['status']]);
            });
        } else {
            $appointment->update(['status' => $validated['status']]);
        }

        if ($validated['status'] === 'Completed') {
            $this->applyReadingAssignment($appointment, $validated);
        }

        // Reflect new status on the Google Calendar event
        $this->calendar->updateStatus($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated appointment #'.$appointment->id.' status to '.$validated['status'],
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        event(new AppointmentStatusChanged($appointment->fresh()));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Status updated successfully!');
    }

    /**
     * Apply an optional reading assignment supplied while completing a visit.
     * A missing reader leaves the appointment unchanged; selecting a reader
     * always resets the reading back to pending.
     */
    private function applyReadingAssignment(Appointment $appointment, array $data): void
    {
        if (! array_key_exists('reader_provider_id', $data)) {
            return;
        }

        if (empty($data['reader_provider_id'])) {
            $appointment->update([
                'reader_provider_id' => null,
                'reading_date' => null,
                'reading_status' => null,
                'reading_completed_at' => null,
            ]);

            return;
        }

        $appointment->update([
            'reader_provider_id' => $data['reader_provider_id'],
            'reading_date' => $data['reading_date'] ?? null,
            'reading_status' => 'pending',
            'reading_completed_at' => null,
        ]);
    }

    /**
     * Assign, change, or complete the reading of a procedure result. The
     * receptionist may complete the reading on behalf of doctors who work
     * manually; a doctor may operate only on appointments they are involved in.
     */
    public function assignReading(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if (! in_array($user->role, ['receptionist', 'admin'], true)) {
            $providerId = $user->provider?->id;
            if ($providerId === null || ! in_array($providerId, [$appointment->provider_id, $appointment->reader_provider_id], true)) {
                abort(403, 'This appointment is not assigned to you.');
            }
        }

        // Reception may pick a reader while the consultation is still running.
        // Only a visit that never happened (cancelled / no-show) is ineligible.
        if (in_array($appointment->status, ['Cancelled', 'No-Show'], true)) {
            throw ValidationException::withMessages([
                'reading_date' => 'A cancelled or no-show appointment cannot be assigned a reading doctor.',
            ]);
        }

        if ($appointment->reading_status === 'completed') {
            throw ValidationException::withMessages([
                'reading_date' => 'This reading is already completed and can no longer be changed.',
            ]);
        }

        $validated = $request->validate([
            'reader_provider_id' => 'nullable|exists:providers,id',
            // The reading date is optional: an advance assignment made while
            // the consultation is still in progress falls back to the visit date.
            'reading_date' => 'nullable|date',
            'reading_status' => 'nullable|string|in:pending,completed',
        ]);

        if (! empty($validated['reader_provider_id']) && ! $appointment->supportsReadingAssignment()) {
            throw ValidationException::withMessages([
                'reader_provider_id' => 'Only procedure appointments can be assigned to a reading doctor.',
            ]);
        }

        $readerId = array_key_exists('reader_provider_id', $validated)
            ? $validated['reader_provider_id']
            : $appointment->reader_provider_id;

        // Keep an existing date, and default an advance assignment to the visit
        // date so the receptionist can assign a reader without guessing a date.
        $readingDate = $validated['reading_date']
            ?? $appointment->reading_date?->toDateString()
            ?? ($readerId ? $appointment->scheduled_date?->toDateString() : null);

        if (($validated['reading_status'] ?? null) === 'completed') {
            if (! $readerId) {
                throw ValidationException::withMessages([
                    'reader_provider_id' => 'Select a reading doctor before completing the reading.',
                ]);
            }

            $appointment->update([
                'reader_provider_id' => $readerId,
                'reading_date' => $readingDate ?? now()->toDateString(),
                'reading_status' => 'completed',
                'reading_completed_at' => now(),
            ]);

            $action = 'Completed reading for Appointment #'.$appointment->id.' (Reader ID: '.$readerId.')';
        } else {
            $appointment->update([
                'reader_provider_id' => $readerId,
                'reading_date' => $readerId ? $readingDate : null,
                'reading_status' => $readerId ? 'pending' : null,
                'reading_completed_at' => null,
            ]);

            $action = $readerId
                ? 'Assigned reading doctor ID '.$readerId.' for Appointment #'.$appointment->id
                : 'Cleared reading assignment for Appointment #'.$appointment->id;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'patient_id' => $appointment->patient_id,
            'action' => $action,
        ]);

        Cache::forget('receptionist_stats');
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Reading assignment updated.');
    }

    // 3. Show Manual Booking Page for Receptionist
    public function create()
    {
        // ✅ OPTIMIZED: clinics expose the Provider roster — the Vue form keeps
        // sending its selection back as `doctor_id` (now a provider ID).
        $doctors = Provider::with('user:id,name')
            ->get(['id', 'user_id', 'specialty'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Unknown',
                'specialty' => $p->specialty,
            ])
            ->values();

        return inertia('Appointments/Create', [
            'doctors' => $doctors,
        ]);
    }

    // 4. Handle Manual Walk-in Submission
    public function storeManual(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'doctor_id' => 'required|exists:providers,id',
            'service_type' => 'required|string|in:Consultation,Procedure,Consultation and Procedure',
            'date' => 'required|date',
            'time_slot' => 'nullable|in:morning,afternoon',
            'date_of_birth' => 'nullable|date|after:1900-01-01|before_or_equal:today',
            'gender' => 'nullable|string|max:20',
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]+$/', 'max:50'],
        ];

        $request->validate($rules);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        // Can't schedule a walk-in into an exact time that already started today.
        if ($exactTime !== null && $request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$exactTime);
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time_slot' => 'That time has already passed. Please select a future time.',
                ]);
            }
        }

        $appointment = DB::transaction(function () use ($request, $slot, $exactTime) {
            $doctorId = (int) $request->doctor_id;

            $this->lockProvidersForSlot($request->date, $exactTime ?? '00:00:00', $doctorId);

            if ($slot !== null) {
                $time = $this->firstAvailableTime($request->date, $slot, $doctorId);

                if ($time === null) {
                    throw ValidationException::withMessages([
                        'time_slot' => 'This doctor has no free time left in that slot.',
                    ]);
                }
            } else {
                $time = $exactTime;

                if ($time !== null && $this->slotTaken($doctorId, $request->date, $time)) {
                    throw ValidationException::withMessages([
                        'time_slot' => 'This doctor already has an appointment at this time.',
                    ]);
                }
            }

            // Use a cryptographically random suffix to prevent email collisions
            $generatedEmail = strtolower(str_replace(' ', '', $request->name))
                .'_'.Str::random(10)
                .'@walkin.clinic.com';

            $patient = User::create([
                'name' => $request->name,
                'email' => $generatedEmail,
                'password' => Hash::make(Str::random(16)), // random password — walk-ins log in via receptionist
                'role' => 'patient',
                'status' => 'Walk-in Register',
            ]);

            // Walk-in demographics belong to the normalized patients table.
            $patientRecord = Patient::create([
                'user_id' => $patient->id,
                'date_of_birth' => $request->date_of_birth,
                'sex' => $request->gender,
                'contact_phone' => $request->phone,
            ]);

            $notes = implode(' | ', array_filter([
                $request->filled('date_of_birth') ? 'DOB: '.$request->date_of_birth : null,
                $request->filled('gender') ? 'Gender: '.$request->gender : null,
                $request->filled('phone') ? 'Phone: '.$request->phone : null,
            ])) ?: null;

            return Appointment::create([
                'patient_id' => $patientRecord->id,
                'provider_id' => $doctorId,
                'scheduled_date' => $request->date,
                'scheduled_time' => $time,
                'duration_minutes' => $this->getDurationMinutes($patientRecord->id),
                'service_type' => $request->service_type,
                'status' => 'Pending',
                'notes' => $notes,
            ]);
        });

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Registered walk-in patient: '.$request->name,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($request->doctor_id);

        return redirect()->route('receptionist.dashboard')->with('success', 'Walk-in added to queue!');
    }

    // 5. Show Returning Patient Booking Page
    public function createReturning()
    {
        // ✅ OPTIMIZED: patients now live in the normalized patients table
        $patients = Patient::with('user:id,name,email')
            ->get(['id', 'user_id'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->anonymizedName() ?? 'Unknown',
                'email' => $p->user?->email ?? '',
            ])
            ->sortBy('name')
            ->take(500)  // ✅ Cap at 500 for frontend search
            ->values();

        $doctors = Provider::with('user:id,name')
            ->get(['id', 'user_id', 'specialty'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Unknown',
                'specialty' => $p->specialty,
            ])
            ->values();

        return inertia('Appointments/Returning', [
            'patients' => $patients,
            'doctors' => $doctors,
        ]);
    }

    // 6. Handle Returning Patient Booking
    public function storeReturning(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:providers,id',
            'service_type' => 'required|string|in:Consultation,Procedure,Consultation and Procedure',
            'date' => 'required|date',
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        // Can't book a returning patient into an exact time that already
        // started today. Window bookings are filtered during assignment.
        if ($exactTime !== null && $request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$exactTime);
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time has already passed. Please select a future time.',
                ]);
            }
        }

        $appointment = DB::transaction(function () use ($request, $slot, $exactTime) {
            $doctorId = (int) $request->doctor_id;

            $this->lockProvidersForSlot($request->date, $exactTime ?? '00:00:00', $doctorId);

            if ($slot !== null) {
                $time = $this->firstAvailableTime($request->date, $slot, $doctorId);

                if ($time === null) {
                    throw ValidationException::withMessages([
                        'time' => 'This doctor has no free time left in that slot.',
                    ]);
                }
            } else {
                $time = $exactTime;

                if ($this->slotTaken($doctorId, $request->date, $time)) {
                    throw ValidationException::withMessages([
                        'time' => 'This doctor already has an appointment at this time.',
                    ]);
                }
            }

            return Appointment::create([
                'patient_id' => $request->patient_id,
                'provider_id' => $doctorId,
                'scheduled_date' => $request->date,
                'scheduled_time' => $time,
                'duration_minutes' => $this->getDurationMinutes($request->patient_id),
                'service_type' => $request->service_type,
                'status' => 'Pending',
            ]);
        });

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Booked follow-up appointment for Patient ID: '.$request->patient_id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($request->doctor_id);

        return redirect()->route('receptionist.dashboard')->with('success', 'Returning patient successfully added to queue!');
    }

    // 7. Save Consultation (SOAP) Record — normalized into clinical sub-tables
    public function completeConsultation(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $providerId = $user->provider?->id;
        $isDoctorOwner = $providerId !== null && $appointment->provider_id === $providerId;
        $isReadingDoctor = $providerId !== null
            && ! $isDoctorOwner
            && $appointment->reader_provider_id === $providerId;
        $isStaff = in_array($user->role, ['receptionist', 'admin'], true);

        // Receptionists use "Mark Complete" to close a visit that the doctor
        // has already finished (or that needs no EMR entry). Only a full
        // clinical payload still needs explicit doctor authorization; an empty
        // payload is a status-only completion and must never touch the EMR.
        $hasClinicalPayload = $this->hasConsultationPayload($request);
        $isReceptionistAuthorized = false;

        if (! $isDoctorOwner && ! $isReadingDoctor && $isStaff) {
            if (! $hasClinicalPayload) {
                $isReceptionistAuthorized = true;
            } else {
                $authorized = $request->boolean('doctor_authorized') || $request->input('authorized_by_doctor') || $request->input('doctor_authorization');
                $isReceptionistAuthorized = $authorized
                    || ($appointment->notes && str_contains(strtolower($appointment->notes), 'receptionist_completion_authorized'));
            }
        }

        if (! $isDoctorOwner && ! $isReadingDoctor && ! $isReceptionistAuthorized) {
            abort(403, 'Only the assigned doctor may complete this consultation.');
        }

        $this->assertNotNoShow($appointment);

        if ($appointment->status === 'Cancelled') {
            throw ValidationException::withMessages([
                'error' => 'A cancelled appointment cannot be completed.',
            ]);
        }

        // A reading is final once its doctor has submitted it. The attending
        // doctor can still correct the visit, but the reading doctor cannot
        // reopen a completed reading.
        if ($isReadingDoctor && $appointment->reading_status === 'completed') {
            throw ValidationException::withMessages([
                'error' => 'This reading is already completed and can no longer be changed.',
            ]);
        }

        // Status-only completion for reception/admin: close the appointment
        // without creating a blank encounter or overwriting recorded SOAP data.
        if ($isStaff && ! $isDoctorOwner && ! $isReadingDoctor && ! $hasClinicalPayload) {
            return $this->completeAppointmentStatusOnly($appointment);
        }

        // Comprehensive validation with required fields and constraints
        $validated = $request->validate([
            'reader_provider_id' => 'nullable|exists:providers,id',
            'reading_date' => 'nullable|required_with:reader_provider_id|date',
            'symptoms' => 'nullable|string|max:5000',
            'vitals' => 'nullable|string|max:5000',
            'diagnosis' => 'nullable|string|max:500',
            'plan' => 'nullable|string|max:5000',
            'consultation_type' => 'nullable|array',
            'consultation_type.*' => 'string|in:Type 1,Type 2,Type 3,Type 4,Type 5,Type 6',
            'prescriptions' => 'nullable|array',
            'prescriptions.*.medicine' => 'nullable|string|max:255',
            'prescriptions.*.dosage' => 'nullable|string|max:100',
            'prescriptions.*.frequency' => 'nullable|string|max:100',
            'prescriptions.*.duration' => 'nullable|string|max:50',
            'prescriptions.*.is_freeform' => 'nullable|boolean',
            'prescriptions.*.free_text' => 'nullable|string|max:5000',
            'requests' => 'nullable|array',
            'requests.*.type' => 'nullable|string|in:lab,imaging,referral,other',
            'requests.*.body' => 'nullable|string|max:5000',
            'requests.*.priority' => 'nullable|string|in:routine,urgent,stat',
            'ai_summary' => 'nullable|string|max:5000',
            'echo_data' => 'nullable|array',
            'tmt_data' => 'nullable|array',
            'doppler_data' => 'nullable|array',
            'manual_draft_data' => 'nullable|array',
            'manual_draft_data.requesting_physician' => 'nullable|string|max:255',
            'manual_draft_data.title' => 'nullable|string|max:255',
            'manual_draft_data.draft' => 'nullable|string|max:5000',
            'manual_draft_data.conclusion' => 'nullable|string|max:5000',
            'manual_draft_data.images' => 'nullable|array',
            'manual_draft_data.images.*.id' => 'nullable',
            'manual_draft_data.images.*.url' => 'nullable|string|max:2048',
            'manual_draft_data.images.*.name' => 'nullable|string|max:255',
            'stress_echo_data' => 'nullable|array',
            'stress_echo_data.requesting_physician' => 'nullable|string|max:255',
            'stress_echo_data.file_no' => 'nullable|string|max:255',
            'stress_echo_data.age' => 'nullable|string|max:10',
            'stress_echo_data.sex' => 'nullable|string|max:10',
            'stress_echo_data.indication' => 'nullable|string|max:2000',
            'stress_echo_data.medications' => 'nullable|string|max:2000',
            'stress_echo_data.pre_bp' => 'nullable|string|max:50',
            'stress_echo_data.pre_hr' => 'nullable|string|max:50',
            'stress_echo_data.pre_ecg' => 'nullable|string|max:2000',
            'stress_echo_data.pre_symptoms' => 'nullable|string|max:2000',
            'stress_echo_data.protocol' => 'nullable|string|max:500',
            'stress_echo_data.stages' => 'nullable|array',
            'stress_echo_data.stages.*.stage' => 'nullable|string|max:100',
            'stress_echo_data.stages.*.time' => 'nullable|string|max:50',
            'stress_echo_data.stages.*.speed' => 'nullable|string|max:50',
            'stress_echo_data.stages.*.grade' => 'nullable|string|max:50',
            'stress_echo_data.stages.*.hr' => 'nullable|string|max:50',
            'stress_echo_data.stages.*.bp' => 'nullable|string|max:50',
            'stress_echo_data.stages.*.ecg' => 'nullable|string|max:1000',
            'stress_echo_data.stages.*.symptoms' => 'nullable|string|max:1000',
            'stress_echo_data.peak_hr' => 'nullable|string|max:50',
            'stress_echo_data.target_hr' => 'nullable|string|max:50',
            'stress_echo_data.peak_bp' => 'nullable|string|max:50',
            'stress_echo_data.mets' => 'nullable|string|max:50',
            'stress_echo_data.reached_target' => 'nullable|string|max:50',
            'stress_echo_data.rest_ef' => 'nullable|string|max:50',
            'stress_echo_data.rest_wma' => 'nullable|string|max:1000',
            'stress_echo_data.peak_ef' => 'nullable|string|max:50',
            'stress_echo_data.peak_wma' => 'nullable|string|max:1000',
            'stress_echo_data.ischaemia' => 'nullable|string|max:2000',
            'stress_echo_data.findings' => 'nullable|string|max:5000',
            'stress_echo_data.conclusion' => 'nullable|string|max:5000',
            'abpm_data' => 'nullable|array',
            'abpm_data.requesting_physician' => 'nullable|string|max:255',
            'abpm_data.file_no' => 'nullable|string|max:255',
            'abpm_data.age' => 'nullable|string|max:10',
            'abpm_data.sex' => 'nullable|string|max:10',
            'abpm_data.indication' => 'nullable|string|max:2000',
            'abpm_data.monitor_device' => 'nullable|string|max:255',
            'abpm_data.monitor_model' => 'nullable|string|max:255',
            'abpm_data.cuff_size' => 'nullable|string|max:100',
            'abpm_data.bp_medications' => 'nullable|string|max:2000',
            'abpm_data.start_date' => 'nullable|string|max:100',
            'abpm_data.start_time' => 'nullable|string|max:100',
            'abpm_data.end_date' => 'nullable|string|max:100',
            'abpm_data.end_time' => 'nullable|string|max:100',
            'abpm_data.total_duration' => 'nullable|string|max:100',
            'abpm_data.wake_from' => 'nullable|string|max:50',
            'abpm_data.wake_to' => 'nullable|string|max:50',
            'abpm_data.sleep_from' => 'nullable|string|max:50',
            'abpm_data.sleep_to' => 'nullable|string|max:50',
            'abpm_data.day_sbp' => 'nullable|string|max:50',
            'abpm_data.day_dbp' => 'nullable|string|max:50',
            'abpm_data.day_hr' => 'nullable|string|max:50',
            'abpm_data.night_sbp' => 'nullable|string|max:50',
            'abpm_data.night_dbp' => 'nullable|string|max:50',
            'abpm_data.night_hr' => 'nullable|string|max:50',
            'abpm_data.avg_sbp' => 'nullable|string|max:50',
            'abpm_data.avg_dbp' => 'nullable|string|max:50',
            'abpm_data.avg_hr' => 'nullable|string|max:50',
            'abpm_data.systolic_load' => 'nullable|string|max:50',
            'abpm_data.diastolic_load' => 'nullable|string|max:50',
            'abpm_data.baseline_bp' => 'nullable|string|max:50',
            'abpm_data.dipping' => 'nullable|string|max:500',
            'abpm_data.findings' => 'nullable|string|max:5000',
            'abpm_data.interpretation' => 'nullable|string|max:5000',
            'abpm_data.recommendations' => 'nullable|string|max:5000',
            'abpm_data.conclusion' => 'nullable|string|max:5000',
        ]);

        // Sanitize text fields to prevent XSS. The assessment is optional:
        // a visit can legitimately be only for clearance or a doctor-requested
        // review. Placeholders are nulled, but meaningful non-diagnostic notes
        // (e.g. "For clearance") are kept in soap_notes.assessment.
        $validated['symptoms'] = $this->sanitizeText($validated['symptoms'] ?? '');
        $validated['vitals'] = $this->sanitizeText($validated['vitals'] ?? '');
        $validated['diagnosis'] = DiagnosisText::normalize(
            $this->sanitizeText($validated['diagnosis'] ?? '')
        ) ?? '';
        $validated['plan'] = $this->sanitizeText($validated['plan'] ?? '');

        // Sanitize prescription data and filter out empty rows. A free-text row
        // has no structured dosage/frequency/duration: it keeps only a short
        // label in `medicine` (for legacy columns) and the full text in
        // `free_text`. `frequency` is nulled so adherence code skips it rather
        // than defaulting an unknown string to 1x/day.
        if (! empty($validated['prescriptions'])) {
            foreach ($validated['prescriptions'] as $i => $prescription) {
                $isFreeform = filter_var($prescription['is_freeform'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $validated['prescriptions'][$i]['is_freeform'] = $isFreeform;

                if ($isFreeform) {
                    $freeText = $this->sanitizeText($prescription['free_text'] ?? '');
                    $label = $this->sanitizeText($prescription['medicine'] ?? '');

                    if ($freeText === '') {
                        // Nothing to keep; the filter below drops this row.
                        $validated['prescriptions'][$i]['medicine'] = '';
                    } else {
                        if ($label === '') {
                            $firstLine = trim(strtok($freeText, "\n") ?: '');
                            $label = $firstLine !== '' ? $firstLine : 'Free-text prescription';
                        }

                        $validated['prescriptions'][$i]['medicine'] = mb_substr($label, 0, 255);
                    }

                    $validated['prescriptions'][$i]['free_text'] = $freeText;
                    $validated['prescriptions'][$i]['dosage'] = '';
                    $validated['prescriptions'][$i]['frequency'] = null;
                    $validated['prescriptions'][$i]['duration'] = '';
                } elseif (! empty($prescription['medicine'])) {
                    $validated['prescriptions'][$i]['free_text'] = null;
                    $validated['prescriptions'][$i]['medicine'] = $this->sanitizeText($prescription['medicine']);
                    $validated['prescriptions'][$i]['dosage'] = $this->sanitizeText($prescription['dosage'] ?? '');
                    $validated['prescriptions'][$i]['frequency'] = $this->sanitizeText($prescription['frequency'] ?? '');
                    $validated['prescriptions'][$i]['duration'] = $this->sanitizeText($prescription['duration'] ?? '');
                }
            }
            $validated['prescriptions'] = array_values(array_filter(
                $validated['prescriptions'],
                fn ($p) => ! empty($p['is_freeform'])
                    ? ! empty($p['free_text'])
                    : ! empty($p['medicine'])
            ));
        }

        // Sanitize free-text requests and drop blank rows.
        if (! empty($validated['requests'])) {
            foreach ($validated['requests'] as $i => $requestRow) {
                $validated['requests'][$i]['body'] = $this->sanitizeText($requestRow['body'] ?? '');
            }
            $validated['requests'] = array_values(array_filter(
                $validated['requests'],
                fn ($req) => ! empty($req['body'])
            ));
        }

        // A reading doctor reviews an already-completed procedure. The
        // consultation form only pre-fills the study/vitals blocks, so blank
        // SOAP fields must fall back to the attending doctor's record rather
        // than overwriting it with empty strings.
        $existingEncounter = null;
        $existingSoap = null;
        if ($isReadingDoctor) {
            $existingEncounter = $appointment->encounter()->with([
                'soapNote',
                'echocardiogram',
                'treadmillTest',
                'dopplerStudy',
                'stressEcho',
                'abpm',
            ])->first();
            $existingSoap = $existingEncounter?->soapNote;

            if ($validated['symptoms'] === '') {
                $validated['symptoms'] = $existingEncounter?->chief_complaint ?? $existingSoap?->subjective ?? '';
            }

            if ($validated['vitals'] === '') {
                $validated['vitals'] = $existingSoap?->objective ?? '';
            }

            if ($validated['diagnosis'] === '') {
                $validated['diagnosis'] = $existingSoap?->assessment ?? '';
            }

            if ($validated['plan'] === '') {
                $validated['plan'] = $existingSoap?->plan ?? '';
            }

            if (empty($validated['ai_summary'])) {
                $validated['ai_summary'] = $existingSoap?->ai_summary;
            }
        }

        $selectedTypes = $validated['consultation_type'] ?? [];

        if (! in_array('Type 1', $selectedTypes)) {
            $validated['echo_data'] = null;
        }

        if (! in_array('Type 2', $selectedTypes)) {
            $validated['tmt_data'] = null;
        }

        if (! in_array('Type 3', $selectedTypes)) {
            $validated['doppler_data'] = null;
        }

        if (! in_array('Type 4', $selectedTypes)) {
            $validated['manual_draft_data'] = null;
        }

        if (! in_array('Type 5', $selectedTypes)) {
            $validated['stress_echo_data'] = null;
        }

        if (! in_array('Type 6', $selectedTypes)) {
            $validated['abpm_data'] = null;
        }

        // A selected type whose form still only holds its defaults counts as
        // "not filled in": it must not create a study row, and a reading doctor
        // must not overwrite the attending doctor's measurements with it.
        foreach ([
            'echo' => 'echo_data',
            'tmt' => 'tmt_data',
            'doppler' => 'doppler_data',
            'manual_draft' => 'manual_draft_data',
            'stress_echo' => 'stress_echo_data',
            'abpm' => 'abpm_data',
        ] as $type => $field) {
            if (! ClinicalFormData::hasContent($type, $validated[$field] ?? null)) {
                $validated[$field] = null;
            }
        }

        $encounter = Encounter::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'encounter_type' => $isReadingDoctor && empty($selectedTypes) && $existingEncounter
                    ? $existingEncounter->encounter_type
                    : $this->resolveEncounterType($selectedTypes),
                'status' => 'completed',
                'chief_complaint' => $validated['symptoms'],
                'started_at' => Carbon::parse($appointment->scheduled_date->format('Y-m-d').' '.($appointment->scheduled_time ?? '08:00:00')),
                'ended_at' => now(),
            ]
        );

        SoapNote::updateOrCreate(
            ['encounter_id' => $encounter->id],
            [
                'subjective' => $validated['symptoms'],
                'objective' => $validated['vitals'],
                'assessment' => $validated['diagnosis'] !== '' ? $validated['diagnosis'] : null,
                'plan' => $validated['plan'] ?? '',
                'ai_summary' => $validated['ai_summary'] ?? null,
            ]
        );

        // Structured vitals — derive fields from the "BP: .. | HR: .." string
        $vitals = $this->parseVitalsText($validated['vitals']);
        if (! empty($vitals)) {
            VitalSign::updateOrCreate(
                ['encounter_id' => $encounter->id],
                array_merge($vitals, ['recorded_at' => now()])
            );
        }

        // Diagnosis rows (legacy single-string diagnosis → one ICD row).
        // Only real diagnoses get a row: clearance/request-only visits keep
        // their text in soap_notes.assessment and must not pollute report
        // diagnosis statistics. A reading doctor only rewrites them when the
        // assessment actually changed.
        $diagnosisChanged = ! $isReadingDoctor
            || $validated['diagnosis'] !== ($existingSoap?->assessment ?? '');

        if ($diagnosisChanged) {
            $encounter->diagnoses()->delete();
            $diagnosis = DiagnosisText::asDiagnosis($validated['diagnosis']);
            if ($diagnosis !== null) {
                $encounter->diagnoses()->create([
                    'icd_code' => null,
                    'description' => $diagnosis,
                    'is_primary' => true,
                    'diagnosed_at' => now(),
                ]);
            }
        }

        // Prescriptions (replaces the legacy JSON array; old adherence logs
        // cascade). A reading doctor may append a correction/order, but the
        // attending doctor's existing prescriptions are never deleted by them.
        $existingPrescriptionKeys = [];
        if ($isReadingDoctor) {
            $existingPrescriptionKeys = $encounter->prescriptions()
                ->get(['medication_name', 'free_text', 'is_freeform'])
                ->map(fn ($p) => mb_strtolower(trim((string) ($p->is_freeform ? $p->free_text : $p->medication_name))))
                ->filter()
                ->all();
        } else {
            $encounter->prescriptions()->delete();
        }

        foreach ($validated['prescriptions'] ?? [] as $rx) {
            $isFreeform = ! empty($rx['is_freeform']);
            $prescriptionKey = mb_strtolower(trim((string) ($isFreeform ? ($rx['free_text'] ?? '') : ($rx['medicine'] ?? ''))));

            if ($isReadingDoctor && $prescriptionKey !== '' && in_array($prescriptionKey, $existingPrescriptionKeys, true)) {
                continue;
            }

            $startDate = Carbon::parse($appointment->scheduled_date);
            $days = null;
            if (! $isFreeform && ! empty($rx['duration'])) {
                if (preg_match('/^\s*(\d{1,3})\s*(day|days|week|weeks|month|months)?/i', $rx['duration'], $m)) {
                    $mult = match (strtolower($m[2] ?? 'day')) {
                        'week', 'weeks' => 7,
                        'month', 'months' => 30,
                        default => 1,
                    };
                    $days = max(1, (int) $m[1] * $mult);
                }
            }
            Prescription::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'medication_name' => $rx['medicine'],
                'dosage' => $isFreeform ? null : ($rx['dosage'] ?? null),
                'frequency' => $isFreeform ? null : ($rx['frequency'] ?? null),
                'duration_days' => $isFreeform ? null : $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => ! $isFreeform && $days ? $startDate->copy()->addDays($days)->toDateString() : null,
                'is_freeform' => $isFreeform,
                'free_text' => $isFreeform ? ($rx['free_text'] ?? null) : null,
                'status' => 'active',
            ]);

            if ($prescriptionKey !== '') {
                $existingPrescriptionKeys[] = $prescriptionKey;
            }
        }

        // Free-text clinical requests (lab / imaging / referral / other). The
        // whole set is replaced on a normal save so a re-submitted consultation
        // cannot accumulate duplicate orders. A reading doctor only appends
        // genuinely new requests.
        $existingRequestKeys = [];
        if ($isReadingDoctor) {
            $existingRequestKeys = $encounter->clinicalRequests()
                ->get(['type', 'body'])
                ->map(fn ($r) => mb_strtolower(trim(($r->type ?? 'other').'|'.($r->body ?? ''))))
                ->filter()
                ->all();
        } else {
            $encounter->clinicalRequests()->delete();
        }

        foreach ($validated['requests'] ?? [] as $requestRow) {
            $requestKey = mb_strtolower(trim(($requestRow['type'] ?? 'other').'|'.($requestRow['body'] ?? '')));

            if ($isReadingDoctor && in_array($requestKey, $existingRequestKeys, true)) {
                continue;
            }

            $encounter->clinicalRequests()->create([
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'type' => $requestRow['type'] ?? 'other',
                'body' => $requestRow['body'] ?? '',
                'priority' => $requestRow['priority'] ?? 'routine',
                'status' => 'requested',
                'created_by' => Auth::id(),
            ]);

            $existingRequestKeys[] = $requestKey;
        }

        // A reading doctor's form only edits the study blocks it loaded. Keep
        // any study they did not submit instead of deleting the result they
        // were asked to read.
        if ($isReadingDoctor && $existingEncounter) {
            if (empty($validated['echo_data']) && $existingEncounter->echocardiogram) {
                $validated['echo_data'] = ClinicalRecordMapper::forPayload($existingEncounter->echocardiogram);
            }

            if (empty($validated['tmt_data']) && $existingEncounter->treadmillTest) {
                $validated['tmt_data'] = ClinicalRecordMapper::forPayload(null, $existingEncounter->treadmillTest);
            }

            if (empty($validated['doppler_data']) && $existingEncounter->dopplerStudy) {
                $validated['doppler_data'] = ClinicalRecordMapper::forPayload(null, null, $existingEncounter->dopplerStudy);
            }

            if (empty($validated['stress_echo_data']) && $existingEncounter->stressEcho) {
                $validated['stress_echo_data'] = $existingEncounter->stressEcho->payload;
            }

            if (empty($validated['abpm_data']) && $existingEncounter->abpm) {
                $validated['abpm_data'] = $existingEncounter->abpm->payload;
            }
        }

        // Structured cardiac studies (1:1 with the encounter). The content
        // check also covers payloads copied back from an existing study, so a
        // default-only row is never recreated.
        $encounter->echocardiogram()?->delete();
        if (ClinicalFormData::hasContent('echo', $validated['echo_data'] ?? null)) {
            Echocardiogram::create(array_merge(
                $this->mapEchoData($validated['echo_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->treadmillTest()?->delete();
        if (ClinicalFormData::hasContent('tmt', $validated['tmt_data'] ?? null)) {
            TreadmillTest::create(array_merge(
                $this->mapTmtData($validated['tmt_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->dopplerStudy()?->delete();
        if (ClinicalFormData::hasContent('doppler', $validated['doppler_data'] ?? null)) {
            DopplerStudy::create(array_merge(
                $this->mapDopplerData($validated['doppler_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->manualDraft()?->delete();
        if (ClinicalFormData::hasContent('manual_draft', $validated['manual_draft_data'] ?? null)) {
            ManualDraft::create(array_merge(
                $this->mapManualDraftData($validated['manual_draft_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->stressEcho()?->delete();
        if (ClinicalFormData::hasContent('stress_echo', $validated['stress_echo_data'] ?? null)) {
            StressEchocardiogram::create([
                'encounter_id' => $encounter->id,
                'payload' => $validated['stress_echo_data'],
                'performed_at' => now(),
            ]);
        }

        $encounter->abpm()?->delete();
        if (ClinicalFormData::hasContent('abpm', $validated['abpm_data'] ?? null)) {
            AmbulatoryBloodPressureMonitoring::create([
                'encounter_id' => $encounter->id,
                'payload' => $validated['abpm_data'],
                'performed_at' => now(),
            ]);
        }

        if (! empty($validated['reader_provider_id']) && ! $appointment->supportsReadingAssignment()) {
            throw ValidationException::withMessages([
                'reader_provider_id' => 'Only procedure appointments can be assigned to a reading doctor.',
            ]);
        }

        $appointment->update(['status' => 'Completed']);
        $this->applyReadingAssignment($appointment, $validated);

        // Saving from the reading doctor's review is the act of completing the
        // reading; it removes the appointment from their queue and locks the
        // study against further reading edits.
        if ($isReadingDoctor) {
            $appointment->update([
                'reader_provider_id' => $appointment->reader_provider_id ?? $providerId,
                'reading_status' => 'completed',
                'reading_completed_at' => now(),
            ]);
        }

        // Mark event as completed in Google Calendar
        $this->calendar->updateStatus($appointment);

        $action = $isReadingDoctor
            ? 'Reading doctor '.$user->name.' updated the reading for Appointment #'.$appointment->id.' and completed the reading'
            : 'Completed consultation and saved EMR for Appointment #'.$appointment->id;

        AuditLog::create([
            'user_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'action' => $action,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));

        // Broadcast for both the attending save and a reading-doctor save so an
        // open patient dashboard refreshes when the reading lands — the visit
        // was visible from the attending save and is updated in place here.
        event(new ConsultationCompleted($appointment));

        return back()->with(
            'success',
            $isReadingDoctor
                ? 'Reading saved successfully. Your changes are recorded in the appointment history.'
                : 'Consultation record saved successfully! Patient history updated.'
        );
    }

    /**
     * Close an appointment from the reception desk without touching the EMR.
     * Used by "Mark Complete" when no clinical payload is submitted.
     */
    private function completeAppointmentStatusOnly(Appointment $appointment)
    {
        if ($appointment->status !== 'Completed') {
            $appointment->update(['status' => 'Completed']);

            $this->calendar->updateStatus($appointment);

            AuditLog::create([
                'user_id' => Auth::id(),
                'patient_id' => $appointment->patient_id,
                'action' => 'Marked Appointment #'.$appointment->id.' as completed (status only)',
            ]);

            Cache::forget('receptionist_stats');
            Cache::forget('admin_dashboard_stats');
            $appointment->refresh();
            event(new AppointmentStatusChanged($appointment));
            $this->broadcastQueueUpdated($appointment->provider_id);
        }

        return back()->with('success', 'Appointment marked as completed.');
    }

    /**
     * A receptionist completion is status-only unless they submitted an actual
     * clinical field. This keeps "Mark Complete" from creating a blank EMR.
     */
    private function hasConsultationPayload(Request $request): bool
    {
        foreach ([
            'symptoms', 'vitals', 'diagnosis', 'plan', 'ai_summary',
        ] as $field) {
            if (trim((string) $request->input($field, '')) !== '') {
                return true;
            }
        }

        $prescriptions = $request->input('prescriptions');

        if (is_array($prescriptions) && collect($prescriptions)
            ->contains(fn ($rx) => ClinicalFormData::prescriptionHasContent($rx))) {
            return true;
        }

        $requests = $request->input('requests');

        if (is_array($requests) && collect($requests)
            ->contains(fn ($requestRow) => ClinicalFormData::requestHasContent($requestRow))) {
            return true;
        }

        $selectedTypes = $request->input('consultation_type');

        if (is_array($selectedTypes) && $selectedTypes !== []) {
            return true;
        }

        foreach ([
            'echo' => 'echo_data',
            'tmt' => 'tmt_data',
            'doppler' => 'doppler_data',
            'manual_draft' => 'manual_draft_data',
            'stress_echo' => 'stress_echo_data',
            'abpm' => 'abpm_data',
        ] as $type => $field) {
            if (ClinicalFormData::hasContent($type, $request->input($field))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the study data currently recorded for an appointment — the
     * receptionist's draft or the doctor's completed record. The doctor's
     * consultation form pre-fills from this so the reception does not have to
     * re-key anything the bench already measured.
     */
    public function showStudyData(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->role === 'doctor') {
            $providerId = $user->provider?->id;
            abort_unless(
                $providerId !== null && in_array($providerId, [$appointment->provider_id, $appointment->reader_provider_id], true),
                403,
                'This appointment is not assigned to you.'
            );
        }

        $encounter = $appointment->encounter()->with([
            'echocardiogram', 'treadmillTest', 'dopplerStudy', 'stressEcho', 'abpm', 'vitalSigns',
        ])->first();

        $appointment->load(['documents.uploader:id,name', 'readerProvider.user:id,name']);

        $readingPending = $appointment->status === 'Completed' && $appointment->reading_status === 'pending';

        $payload = [
            'saved' => $encounter !== null,
            'status' => $encounter?->status,
            'appointment_status' => $appointment->status,
            'reading_status' => $appointment->reading_status,
            'reading_date' => $appointment->reading_date?->toDateString(),
            'reading_completed_at' => $appointment->reading_completed_at?->toIso8601String(),
            'reader' => $appointment->readerProvider?->user?->name,
            'is_locked' => in_array($appointment->status, ['Cancelled', 'No-Show'], true)
                || ($appointment->status === 'Completed' && ! $readingPending),
            'echo_data' => $encounter?->echocardiogram
                ? ClinicalRecordMapper::forPayload($encounter->echocardiogram)
                : null,
            'tmt_data' => $encounter?->treadmillTest
                ? ClinicalRecordMapper::forPayload(null, $encounter->treadmillTest)
                : null,
            'doppler_data' => $encounter?->dopplerStudy
                ? ClinicalRecordMapper::forPayload(null, null, $encounter->dopplerStudy)
                : null,
            'stress_echo_data' => $encounter?->stressEcho?->payload,
            'abpm_data' => $encounter?->abpm?->payload,
            'vitals_data' => $this->mapVitalsToForm($encounter?->vitalSigns->first()),
        ];

        if (! $encounter) {
            $payload['status'] = null;
        }

        return response()->json(array_merge($payload, [
            'documents' => $appointment->documents->map(fn (Document $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'original_filename' => $d->original_filename,
                'url' => $d->url(),
                'ocr_status' => $d->ocr_status,
                'ocr_confidence' => $d->ocr_confidence,
                'ocr_error' => $d->ocr_error,
                'requires_review' => $d->requiresReview(),
                'uploaded_by' => $d->uploader?->name,
                'ocr_text' => $d->ocr_text,
                'created_at' => $d->created_at?->toIso8601String(),
            ])->values()->all(),
        ]));
    }

    /**
     * Save the receptionist's Echo / TMT / Doppler measurements as a draft
     * encounter on the appointment. The doctor then sees these values
     * pre-filled in their consultation form and confirms them on completion.
     * Once an appointment is closed its records are final and cannot be edited.
     */
    public function storeStudyData(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->role === 'doctor') {
            $providerId = $user->provider?->id;
            abort_unless(
                $providerId !== null && in_array($providerId, [$appointment->provider_id, $appointment->reader_provider_id], true),
                403,
                'This appointment is not assigned to you.'
            );
        }

        $readingPending = $appointment->status === 'Completed' && $appointment->reading_status === 'pending';

        abort_if(
            in_array($appointment->status, ['Cancelled', 'No-Show'], true)
                || ($appointment->status === 'Completed' && ! $readingPending),
            422,
            'This appointment is already closed and its records are final.'
        );

        $validated = $request->validate([
            'echo_data' => 'nullable|array',
            'tmt_data' => 'nullable|array',
            'doppler_data' => 'nullable|array',
            'stress_echo_data' => 'nullable|array',
            'abpm_data' => 'nullable|array',
            'vitals_data' => 'nullable|array',
            'vitals_data.bp_sys' => 'nullable|numeric|min:40|max:300',
            'vitals_data.bp_dia' => 'nullable|numeric|min:20|max:200',
            'vitals_data.hr' => 'nullable|numeric|min:20|max:300',
            'vitals_data.rr' => 'nullable|numeric|min:4|max:80',
            'vitals_data.temp' => 'nullable|numeric|min:30|max:45',
            'vitals_data.spo2' => 'nullable|numeric|min:40|max:100',
        ]);

        // A study form that was left entirely blank (or only holds its default
        // values) is treated as "not part of this visit" — the same rule the
        // history listing and PDF export use.
        $types = [];
        foreach (['echo', 'tmt', 'doppler', 'stress_echo', 'abpm'] as $type) {
            if (ClinicalFormData::hasContent($type, $validated[$type.'_data'] ?? null)) {
                $types[] = $type;
            }
        }

        $hasVitals = $this->hasVitalData($validated['vitals_data'] ?? []);

        if (empty($types) && ! $hasVitals) {
            throw ValidationException::withMessages([
                'echo_data' => 'Fill in at least one study or vitals field before saving.',
            ]);
        }

        $encounterType = 'echo';
        foreach (['echo', 'tmt', 'doppler', 'stress_echo', 'abpm'] as $candidate) {
            if (in_array($candidate, $types, true)) {
                $encounterType = $candidate;
                break;
            }
        }

        // Vitals-only entry: the doctor classifies the visit when completing it.
        if (empty($types)) {
            $encounterType = 'consultation';
        }

        $encounter = Encounter::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'encounter_type' => $encounterType,
                // A draft the doctor owns the moment they complete the visit.
                'status' => 'in_progress',
                'started_at' => Carbon::parse($appointment->scheduled_date->format('Y-m-d').' '.($appointment->scheduled_time ?? '08:00:00')),
            ]
        );

        // The form is the source of truth: re-key each provided study, drop any
        // study the receptionist cleared this time.
        $encounter->echocardiogram()?->delete();
        if (in_array('echo', $types, true)) {
            Echocardiogram::create(array_merge(
                $this->mapEchoData($validated['echo_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->treadmillTest()?->delete();
        if (in_array('tmt', $types, true)) {
            TreadmillTest::create(array_merge(
                $this->mapTmtData($validated['tmt_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->dopplerStudy()?->delete();
        if (in_array('doppler', $types, true)) {
            DopplerStudy::create(array_merge(
                $this->mapDopplerData($validated['doppler_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->stressEcho()?->delete();
        if (in_array('stress_echo', $types, true)) {
            StressEchocardiogram::create([
                'encounter_id' => $encounter->id,
                'payload' => $validated['stress_echo_data'],
                'performed_at' => now(),
            ]);
        }

        $encounter->abpm()?->delete();
        if (in_array('abpm', $types, true)) {
            AmbulatoryBloodPressureMonitoring::create([
                'encounter_id' => $encounter->id,
                'payload' => $validated['abpm_data'],
                'performed_at' => now(),
            ]);
        }

        // Vitals (objective) taken at the bench. Keyed by encounter so the
        // doctor's completeConsultation updates this same row instead of
        // creating a second set.
        if ($hasVitals) {
            VitalSign::updateOrCreate(
                ['encounter_id' => $encounter->id],
                [
                    'blood_pressure_systolic' => $validated['vitals_data']['bp_sys'] ?? null,
                    'blood_pressure_diastolic' => $validated['vitals_data']['bp_dia'] ?? null,
                    'heart_rate_bpm' => $validated['vitals_data']['hr'] ?? null,
                    'respiratory_rate' => $validated['vitals_data']['rr'] ?? null,
                    'temperature_celsius' => $validated['vitals_data']['temp'] ?? null,
                    'oxygen_saturation_percent' => $validated['vitals_data']['spo2'] ?? null,
                    'recorded_at' => now(),
                ]
            );
        } else {
            $encounter->vitalSigns()->delete();
        }

        Cache::forget('receptionist_stats');

        $loggedTypes = $types;
        if ($hasVitals) {
            $loggedTypes[] = 'vitals';
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'action' => 'Pre-filled study data for Appointment #'.$appointment->id.' ('.implode(', ', $loggedTypes).')',
        ]);

        return back()->with('success', 'Study data saved — the doctor will see it pre-filled in the consultation.');
    }

    /**
     * Map a stored vitals row back to the vitals form key shape shared by the
     * receptionist study form and the doctor's Objective (Vitals) block.
     */
    private function mapVitalsToForm(?VitalSign $vitals): ?array
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

    /**
     * True when the posted vitals array holds at least one measurement.
     */
    private function hasVitalData(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if (count(array_filter($value, fn ($v) => $v !== '' && $v !== null)) > 0) {
                    return true;
                }
            } elseif ($value !== '' && $value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * The half-day window the caller picked. Both the dedicated `time_slot`
     * field and a `time` value of `morning` / `afternoon` are accepted so the
     * older forms keep working during the transition.
     */
    private function requestedSlot(Request $request): ?string
    {
        foreach (['time_slot', 'time'] as $field) {
            $value = $request->input($field);

            if (AppointmentSlot::isValid($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * A legacy exact time (HH:MM or HH:MM:SS) submitted instead of a window.
     */
    private function requestedExactTime(Request $request): ?string
    {
        $value = $request->input('time');

        if (! is_string($value) || AppointmentSlot::isValid($value)) {
            return null;
        }

        return $this->normalizeTimeInput($value);
    }

    /**
     * Normalize "H:MM" / "HH:MM" / "HH:MM:SS" to "HH:MM:SS"; null when the
     * input is not a valid 24-hour time.
     */
    private function normalizeTimeInput(?string $time): ?string
    {
        if ($time === null) {
            return null;
        }

        $time = trim($time);

        if (! preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time.':00' : $time;
    }

    /**
     * First exact time inside a window that can still take a booking.
     *
     * When $providerId is given (returning patient, follow-up, reschedule)
     * only that doctor's own schedule and bookings are considered. Otherwise
     * any scheduled doctor may take the appointment and auto-assignment later
     * picks the actual provider.
     */
    private function firstAvailableTime(string $date, string $slot, ?int $providerId = null): ?string
    {
        $dayInt = Carbon::parse($date)->dayOfWeekIso;

        $schedules = DoctorSchedule::whereJsonContains('day_of_week', $dayInt)
            ->when($providerId !== null, fn ($q) => $q->where('provider_id', $providerId))
            ->get();

        $candidates = $schedules
            ->flatMap(fn (DoctorSchedule $schedule) => AppointmentSlot::times(
                $slot,
                $schedule->start_time,
                $schedule->end_time,
            ))
            ->unique()
            ->sort()
            ->values();

        foreach ($candidates as $time) {
            if (AppointmentSlot::isPast($date, $time)) {
                continue;
            }

            if ($providerId !== null) {
                if ($this->providerAvailableAt($providerId, $date, $time)) {
                    return $time;
                }
            } elseif (self::providersAvailableAt($date, $time)->isNotEmpty()) {
                return $time;
            }
        }

        return null;
    }

    /**
     * Whether a specific doctor works the given date/time and has no live
     * appointment there yet.
     */
    private function providerAvailableAt(int $providerId, string $date, string $time): bool
    {
        return Provider::whereKey($providerId)
            ->whereHas('schedules', function ($q) use ($date, $time) {
                $q->whereJsonContains('day_of_week', Carbon::parse($date)->dayOfWeekIso)
                    ->whereTime('start_time', '<=', $time)
                    ->whereTime('end_time', '>', $time);
            })
            ->whereDoesntHave('appointments', function ($q) use ($date, $time) {
                $q->whereDate('scheduled_date', $date)
                    ->whereTime('scheduled_time', $time)
                    ->whereNotIn('status', ['Cancelled', 'No-Show']);
            })
            ->exists();
    }

    /**
     * Lock every provider who works the requested date, in id order, so two
     * simultaneous window bookings cannot both claim the same exact time.
     */
    private function lockProvidersForDate(string $date): void
    {
        $dayInt = Carbon::parse($date)->dayOfWeekIso;

        $providerIds = DoctorSchedule::whereJsonContains('day_of_week', $dayInt)
            ->orderBy('provider_id')
            ->pluck('provider_id')
            ->unique()
            ->values();

        if ($providerIds->isEmpty()) {
            return;
        }

        Provider::whereIn('id', $providerIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Serialize bookings for a date/time slot by taking row locks on the
     * providers who work it. Two reception desks booking the same doctor at
     * the same moment otherwise both pass the conflict check and insert a
     * double booking. Locks are taken in provider-id order so concurrent
     * multi-provider bookings cannot deadlock each other. Bookings for other
     * doctors or other times lock different rows and stay parallel.
     */
    private function lockProvidersForSlot(string $date, string $time, ?int $providerId = null): void
    {
        if ($providerId !== null) {
            Provider::whereKey($providerId)->lockForUpdate()->first();

            return;
        }

        $dayInt = Carbon::parse($date)->dayOfWeekIso;

        $providerIds = DoctorSchedule::whereJsonContains('day_of_week', $dayInt)
            ->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>', $time)
            ->orderBy('provider_id')
            ->pluck('provider_id')
            ->unique()
            ->values();

        if ($providerIds->isEmpty()) {
            return;
        }

        Provider::whereIn('id', $providerIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * True when the provider already has a live appointment in the slot.
     * Must be called while holding the provider lock from
     * lockProvidersForSlot() for the check and the write to be safe.
     */
    private function slotTaken(
        int $providerId,
        string $date,
        string $time,
        ?int $ignoreAppointmentId = null,
    ): bool {
        return Appointment::where('provider_id', $providerId)
            ->whereDate('scheduled_date', $date)
            ->whereTime('scheduled_time', $time)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();
    }

    /**
     * Broadcast the current queue to a doctor's WebSocket channel.
     * The channel is keyed by the provider's user id (matches the doctor's
     * authenticated user), while the query is provider-scoped.
     */
    private function broadcastQueueUpdated(int $providerId): void
    {
        $queue = Appointment::with('patient:id,user_id', 'patient.user:id,name')
            ->where('provider_id', $providerId)
            ->whereDate('scheduled_date', today())
            ->orderBy('scheduled_time')
            ->get();

        $userId = Provider::whereKey($providerId)->value('user_id');

        event(new QueueUpdated($userId, $queue));
    }

    /**
     * Determine appointment duration based on visit history.
     * First visit → 90 min (1h 30m), follow-up → 30 min.
     */
    private function getDurationMinutes(int $patientId): int
    {
        $hasPreviousVisits = Appointment::where('patient_id', $patientId)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();

        return $hasPreviousVisits ? 30 : 90;
    }

    /**
     * Sanitize user input to prevent XSS attacks
     * Remove HTML-like characters and cap length
     */
    private function sanitizeText(string $text, int $maxLength = 5000): string
    {
        return trim(
            htmlspecialchars(
                substr($text, 0, $maxLength),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /**
     * Derive the encounter_type from the selected consultation types.
     */
    private function resolveEncounterType(array $types): string
    {
        return match (true) {
            in_array('Type 1', $types) => 'echo',
            in_array('Type 2', $types) => 'tmt',
            in_array('Type 3', $types) => 'doppler',
            in_array('Type 4', $types) => 'manual_draft',
            in_array('Type 5', $types) => 'stress_echo',
            in_array('Type 6', $types) => 'abpm',
            default => 'consultation',
        };
    }

    /**
     * Parse a legacy vitals string like "BP: 138/88 mmHg | HR: 82 bpm | ..."
     * into the structured vital_signs columns.
     */
    private function parseVitalsText(?string $text): array
    {
        if (! $text) {
            return [];
        }

        $row = [];

        if (preg_match('/BP:\s*(\d+)\/(\d+)/i', $text, $m)) {
            $row['blood_pressure_systolic'] = (int) $m[1];
            $row['blood_pressure_diastolic'] = (int) $m[2];
        }
        if (preg_match('/HR:\s*(\d+)/i', $text, $m)) {
            $row['heart_rate_bpm'] = (int) $m[1];
        }
        if (preg_match('/RR:\s*(\d+)/i', $text, $m)) {
            $row['respiratory_rate'] = (int) $m[1];
        }
        if (preg_match('/Temp:\s*([\d.]+)/i', $text, $m)) {
            $row['temperature_celsius'] = (float) $m[1];
        }
        if (preg_match('/SpO2:\s*(\d+)/i', $text, $m)) {
            $row['oxygen_saturation_percent'] = (int) $m[1];
        }
        if (preg_match('/Weight:\s*(\d+)/i', $text, $m)) {
            $row['weight_kg'] = (int) $m[1];
        }

        return $row;
    }

    /**
     * First non-empty numeric value among the given keys on the posted form.
     */
    private function num(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                $raw = trim((string) $data[$key]);
                // values may be formatted like "173 bpm" or "180 mmHg" - take the
                // first numeric token so they still fill the normalized column.
                if ($raw !== '' && preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $raw), $m)) {
                    return (float) $m[0];
                }
            }
        }

        return null;
    }

    /**
     * Serialize any leftover (unmapped) non-empty scalar form fields into a
     * JSON suffix so no clinical detail is lost during the schema migration.
     */
    private function leftoverNotes(array $data, array $usedKeys): string
    {
        $extra = collect($data)
            ->except($usedKeys)
            ->filter(fn ($v) => (is_array($v) ? count($v) > 0 : true) && $v !== '' && $v !== null)
            ->toArray();

        return $extra ? ' | '.json_encode($extra) : '';
    }

    /**
     * Normalize the legacy echo_data form blob into echocardiograms columns.
     */
    private function mapEchoData(array $d): array
    {
        $used = [
            'lvef_percent', 'lvfs_percent', 'lv_mass_grams', 'tapse_mm',
            'ivs_mm', 'lvpw_mm', 'lvidd_mm', 'lvids_mm', 'aortic_root_mm',
            'la_diameter_mm', 'rv_size', 'pericardial_effusion',
            'valve_findings', 'wall_motion', 'notes', 'findings',
        ];

        $mappedKeyMap = [
            'lvef_percent' => ['lv_ef'],
            'lvfs_percent' => ['lv_fs'],
            'lv_mass_grams' => ['lv_mass'],
            'tapse_mm' => ['tapse'],
            'ivs_mm' => ['ivs_dia'],
            'lvpw_mm' => ['lvpw_dia'],
            'lvidd_mm' => ['lv_dia'],
            'lvids_mm' => ['lv_sys'],
            'aortic_root_mm' => ['aortic_root'],
            'la_diameter_mm' => ['la_ap', 'la_rl'],
            'rv_size' => ['rv_ap'],
        ];

        $row = [];
        foreach ($mappedKeyMap as $col => $keys) {
            $val = $this->num($d, $keys);
            if ($val !== null) {
                $row[$col] = $val;
            }
        }

        if (! empty($d['findings'])) {
            // `valve_findings` is a TEXT column and the form now offers a full
            // narrative + impression template, so the old 800-char cap would
            // silently drop the conclusion.
            $row['valve_findings'] = $this->sanitizeText((string) $d['findings'], 5000);
        }
        if (! empty($d['tricuspid_valve'])) {
            $row['wall_motion'] = $this->sanitizeText('Tricuspid valve: '.$d['tricuspid_valve'], 800);
        }

        $peri = $d['peri_ant'] ?? $d['peri_post'] ?? '';
        $row['pericardial_effusion'] = $peri !== '' && $peri !== null;

        $row['notes'] = $this->sanitizeText(trim(
            ($d['conclusion'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    /**
     * Normalize the free-form Manual Draft blob into manual_drafts columns.
     * Attachments keep only the fields the UI needs (id, url, name).
     */
    private function mapManualDraftData(array $d): array
    {
        $images = [];
        foreach ($d['images'] ?? [] as $image) {
            $url = trim((string) ($image['url'] ?? ''));
            if ($url === '' || str_starts_with($url, 'blob:') || str_starts_with($url, 'data:')) {
                continue;
            }

            $entry = [
                'url' => $url,
                'name' => $this->sanitizeText((string) ($image['name'] ?? ''), 255),
            ];

            if (! empty($image['id'])) {
                $entry['id'] = $image['id'];
            }

            $images[] = $entry;
        }

        return [
            'requesting_physician' => $this->sanitizeText($d['requesting_physician'] ?? '', 255),
            'title' => $this->sanitizeText($d['title'] ?? '', 255),
            'draft' => $this->sanitizeText($d['draft'] ?? '', 5000),
            'conclusion' => $this->sanitizeText($d['conclusion'] ?? '', 5000),
            'attachments' => $images ?: null,
        ];
    }

    /**
     * Normalize the legacy tmt_data form blob into treadmill_tests columns.
     */
    private function mapTmtData(array $d): array
    {
        $used = ['protocol', 'max_stage', 'peak_heart_rate_bpm', 'peak_bp_systolic',
            'mets_achieved', 'exercise_duration_seconds', 'recovery_time_minutes',
            'st_segment_changes', 'arrhythmias', 'symptoms_during_test',
            'reason_for_termination', 'notes', 'conclusion'];

        $row = [];

        if (! empty($d['protocol'])) {
            $row['protocol'] = $this->sanitizeText((string) $d['protocol'], 50);
        }
        if (($stage = $this->num($d, ['peak_stage', 'max_stage'])) !== null) {
            $row['max_stage'] = $stage;
        }
        if (($hr = $this->num($d, ['peak_hr', 'peak_heart_rate_bpm'])) !== null) {
            $row['peak_heart_rate_bpm'] = (int) $hr;
        }
        if (($sbp = $this->num($d, ['peak_sbp', 'peak_bp_systolic'])) !== null) {
            $row['peak_bp_systolic'] = (int) $sbp;
        }
        if (($mets = $this->num($d, ['peak_mets', 'mets_achieved'])) !== null) {
            $row['mets_achieved'] = $mets;
        }
        if (($mins = $this->num($d, ['peak_minutes'])) !== null) {
            $row['exercise_duration_seconds'] = (int) ($mins * 60);
        }

        $stParts = array_filter([$d['st_shape'] ?? null, $d['st_displacement'] ?? null, $d['st_duration'] ?? null]);
        if ($stParts) {
            $row['st_segment_changes'] = $this->sanitizeText(implode(' | ', $stParts), 800);
        }
        if (! empty($d['arrhythmias'])) {
            $row['arrhythmias'] = $this->sanitizeText((string) $d['arrhythmias'], 800);
        }
        if (! empty($d['response_to_exercise'])) {
            $row['symptoms_during_test'] = $this->sanitizeText((string) $d['response_to_exercise'], 800);
        }
        if (! empty($d['termination_reason'])) {
            $row['reason_for_termination'] = $this->sanitizeText((string) $d['termination_reason'], 100);
        }

        $row['notes'] = $this->sanitizeText(trim(
            ($d['conclusion'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    /**
     * Normalize the legacy doppler_data form blob into doppler_studies columns.
     */
    private function mapDopplerData(array $d): array
    {
        $used = ['ea_ratio', 'tissue_doppler_e', 'tissue_doppler_a',
            'mitral_gradient', 'aortic_gradient', 'tricuspid_gradient',
            'pulmonic_gradient', 'mitral_regurgitation', 'aortic_regurgitation',
            'tricuspid_regurgitation', 'valve_findings', 'notes', 'conclusion'];

        $row = [];

        if (($ea = $this->num($d, ['mv_ea_ratio', 'ea_ratio'])) !== null) {
            $row['ea_ratio'] = $ea;
        }
        if (($e = $this->num($d, ['td_septal_e', 'tissue_doppler_e'])) !== null) {
            $row['tissue_doppler_e'] = $e;
        }
        if (($a = $this->num($d, ['td_septal_a', 'tissue_doppler_a'])) !== null) {
            $row['tissue_doppler_a'] = $a;
        }
        if (($mg = $this->num($d, ['mv_mean_pressure', 'mv_peak_grad_e', 'mitral_gradient'])) !== null) {
            $row['mitral_gradient'] = $mg;
        }
        if (($ag = $this->num($d, ['av_mean_grad', 'av_press_grad_after', 'aortic_gradient'])) !== null) {
            $row['aortic_gradient'] = $ag;
        }
        if (($tg = $this->num($d, ['tv_peak_tr', 'tricuspid_gradient'])) !== null) {
            $row['tricuspid_gradient'] = $tg;
        }
        if (($pg = $this->num($d, ['pv_press_grad', 'pulmonic_gradient'])) !== null) {
            $row['pulmonic_gradient'] = $pg;
        }

        $notable = [];
        foreach (['mv_dectime', 'mv_ivrt', 'mv_regurg_frac', 'td_septal_ee', 'tv_e_wave', 'pv_accel_time', 'qp_qs'] as $k) {
            if (! empty($d[$k])) {
                $notable[] = "{$k}: {$d[$k]}";
            }
        }
        if ($notable) {
            $row['valve_findings'] = $this->sanitizeText(implode(' | ', $notable), 800);
        }

        $row['notes'] = $this->sanitizeText(trim(
            ($d['conclusion'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    // 8. Transfer patient to a different doctor
    public function transfer(Request $request, Appointment $appointment)
    {
        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment cannot be transferred to another doctor.');

        $request->validate(['doctor_id' => 'required|exists:providers,id']);

        $doctorId = (int) $request->doctor_id;
        $date = $appointment->scheduled_date->format('Y-m-d');
        $time = $appointment->scheduled_time;

        DB::transaction(function () use ($appointment, $doctorId, $date, $time) {
            $this->lockProvidersForSlot($date, $time, $doctorId);

            if ($this->slotTaken($doctorId, $date, $time, $appointment->id)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'That doctor already has an appointment at this time.',
                ]);
            }

            $appointment->update(['provider_id' => $doctorId]);
        });

        // Update the Google Calendar event to reflect the new doctor
        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Transferred Appointment #'.$appointment->id.' to new Doctor.',
        ]);

        return back()->with('success', 'Patient successfully transferred!');
    }

    /**
     * Map the shift choice (or legacy start/end pair) to a stored time range.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string}
     */
    private function scheduleRange(array $validated): array
    {
        $shift = $validated['shift'] ?? null;

        if ($shift !== null) {
            $window = AppointmentSlot::all()[$shift];

            return [$window['start'], $window['end']];
        }

        return [
            $this->normalizeTimeInput($validated['start_time']) ?? $validated['start_time'],
            $this->normalizeTimeInput($validated['end_time']) ?? $validated['end_time'],
        ];
    }

    // 9. Doctor Schedule Plotter
    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:providers,id',
            'day_of_week' => 'required|array',
            'shift' => 'nullable|in:morning,afternoon',
            'start_time' => 'nullable|required_without:shift|date_format:H:i',
            'end_time' => 'nullable|required_without:shift|date_format:H:i|after:start_time',
        ]);

        [$startTime, $endTime] = $this->scheduleRange($validated);

        DoctorSchedule::create([
            'provider_id' => $validated['doctor_id'],
            'day_of_week' => $this->normalizeDays($validated['day_of_week']),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Plotted recurring schedule for Doctor ID: '.$request->doctor_id,
        ]);

        return redirect()->route('receptionist.dashboard')
            ->with('success', 'Doctor availability successfully plotted!');
    }

    // 10. Update an existing schedule
    public function updateSchedule(Request $request, DoctorSchedule $schedule)
    {
        $validated = $request->validate([
            'doctor_id' => 'sometimes|exists:providers,id',
            'day_of_week' => 'required|array',
            'shift' => 'nullable|in:morning,afternoon',
            'start_time' => 'nullable|required_without:shift|date_format:H:i',
            'end_time' => 'nullable|required_without:shift|date_format:H:i|after:start_time',
        ]);

        [$startTime, $endTime] = $this->scheduleRange($validated);

        $schedule->update([
            'provider_id' => $validated['doctor_id'] ?? $schedule->provider_id,
            'day_of_week' => $this->normalizeDays($validated['day_of_week']),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated recurring schedule for Doctor ID: '.$schedule->provider_id,
        ]);

        return redirect()->route('receptionist.dashboard')
            ->with('success', 'Schedule successfully updated!');
    }

    // 11. Delete an existing schedule
    public function deleteSchedule(DoctorSchedule $schedule)
    {
        $schedule->delete();

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        return back()->with('success', 'Schedule successfully deleted!');
    }

    // 12. Auto-assign helper (returns the provider id)
    /**
     * Doctors (users with role `doctor`) who are scheduled for the given slot
     * and have no conflicting appointment in it. Slot times must fall inside
     * the schedule's [start, end) window — end_time is exclusive so a doctor
     * who works until 12:00 is never offered a 12:00 slot.
     */
    public static function providersAvailableAt($date, $time)
    {
        $dayInt = Carbon::parse($date)->dayOfWeekIso; // 1 = Monday .. 7 = Sunday

        return User::with('provider:id,user_id')
            ->where('role', 'doctor')
            ->whereHas('schedules', function ($q) use ($dayInt, $time) {
                $q->whereJsonContains('day_of_week', $dayInt)
                    ->whereTime('start_time', '<=', $time)
                    ->whereTime('end_time', '>', $time);
            })
            ->whereDoesntHave('doctorAppointments', function ($q) use ($date, $time) {
                $q->whereDate('scheduled_date', $date)
                    ->whereTime('scheduled_time', $time)
                    ->whereNotIn('status', ['Cancelled', 'No-Show']);
            })
            ->get();
    }

    private static function autoAssignDoctor($date, $time)
    {
        $availableDoctor = self::providersAvailableAt($date, $time)->first();

        if (! $availableDoctor || ! $availableDoctor->provider) {
            throw ValidationException::withMessages([
                'time' => 'No doctors are scheduled or available at this specific date and time.',
            ]);
        }

        return $availableDoctor->provider->id;
    }

    /**
     * Coerce day identifiers to ISO day numbers (1=Mon..7=Sun).
     * Accepts "Monday" or 1..7, matching the doctor_schedules jsonb column.
     */
    private static function normalizeDays(array $days): array
    {
        return collect($days)
            ->map(function ($day) {
                if (is_numeric($day)) {
                    return (int) $day;
                }

                return Carbon::parse((string) $day)->dayOfWeekIso;
            })
            ->unique()
            ->values()
            ->all();
    }

    // 13b. Patient submits a reschedule REQUEST (receptionist must approve)
    public function requestReschedule(Request $request, Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment can no longer be rescheduled.');

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        if ($exactTime !== null) {
            if ($request->date === now()->toDateString()
                && Carbon::parse($request->date.' '.$exactTime)->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time has already passed. Please select a future time.',
                ]);
            }

            $requestedTime = $exactTime;
        } else {
            $requestedTime = $this->firstAvailableTime($request->date, $slot, $appointment->provider_id);

            if ($requestedTime === null) {
                throw ValidationException::withMessages([
                    'time' => 'Your doctor has no free time left in that slot. Please choose another slot.',
                ]);
            }
        }

        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $request->date,
            'reschedule_requested_time' => $requestedTime,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Patient requested reschedule for appointment #'.$appointment->id.' to '.$request->date.' ('.$requestedTime.')',
        ]);

        $appointment->refresh();
        event(new RescheduleRequested($appointment));
        event(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'Reschedule request submitted. The receptionist will review and confirm availability.');
    }

    // 13c. Receptionist approves a reschedule request
    public function approveReschedule(Appointment $appointment)
    {
        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment cannot be rescheduled.');

        if (! $appointment->reschedule_requested_date) {
            return back()->withErrors(['error' => 'No pending reschedule request found.']);
        }

        // H6 FIX: verify the doctor is scheduled to work on the requested day/time
        $dayInt = Carbon::parse($appointment->reschedule_requested_date)->dayOfWeekIso;
        $dayLabel = Carbon::parse($appointment->reschedule_requested_date)->format('l');
        $doctorScheduled = DoctorSchedule::where('provider_id', $appointment->provider_id)
            ->whereJsonContains('day_of_week', $dayInt)
            ->whereTime('start_time', '<=', $appointment->reschedule_requested_time)
            ->whereTime('end_time', '>=', $appointment->reschedule_requested_time)
            ->exists();

        if (! $doctorScheduled) {
            return back()->withErrors([
                'time' => 'The doctor is not scheduled to work on '.$dayLabel
                    .' at '.Carbon::parse($appointment->reschedule_requested_time)->format('h:i A')
                    .'. Please deny the request and ask the patient to select a valid slot.',
            ]);
        }

        // Verify and move inside a provider lock so a simultaneous booking
        // cannot claim the requested slot between the check and the update.
        DB::transaction(function () use ($appointment) {
            $requestedDate = $appointment->reschedule_requested_date->format('Y-m-d');
            $requestedTime = $appointment->reschedule_requested_time;

            $this->lockProvidersForSlot($requestedDate, $requestedTime, $appointment->provider_id);

            if ($this->slotTaken($appointment->provider_id, $requestedDate, $requestedTime, $appointment->id)) {
                throw ValidationException::withMessages([
                    'time' => 'This slot is already taken. Please deny the request and ask the patient to choose another time.',
                ]);
            }

            $appointment->update([
                'scheduled_date' => $appointment->reschedule_requested_date,
                'scheduled_time' => $appointment->reschedule_requested_time,
                'status' => 'Scheduled',
                'reschedule_requested_date' => null,
                'reschedule_requested_time' => null,
            ]);
        });

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Approved reschedule for appointment #'.$appointment->id,
        ]);

        $appointment->refresh();
        event(new AppointmentRescheduled($appointment));
        event(new AppointmentStatusChanged($appointment));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Reschedule approved — appointment updated.');
    }

    // 13d. Receptionist edits and approves a reschedule request directly
    public function editReschedule(Request $request, Appointment $appointment)
    {
        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment cannot be rescheduled.');

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        if ($exactTime !== null) {
            $requestTime = $exactTime;
        } else {
            $requestTime = $this->firstAvailableTime($request->date, $slot, $appointment->provider_id);

            if ($requestTime === null) {
                throw ValidationException::withMessages([
                    'time' => 'This doctor has no free time left in that slot.',
                ]);
            }
        }

        $oldDate = $appointment->scheduled_date instanceof Carbon
            ? $appointment->scheduled_date->format('Y-m-d')
            : $appointment->scheduled_date;
        $oldTime = $appointment->scheduled_time;

        if ($oldDate === $request->date && $oldTime === $requestTime) {
            return back()->with('success', 'Schedule remained the same.');
        }

        DB::transaction(function () use ($appointment, $request, $requestTime) {
            $this->lockProvidersForSlot($request->date, $requestTime, $appointment->provider_id);

            if ($this->slotTaken($appointment->provider_id, $request->date, $requestTime, $appointment->id)) {
                throw ValidationException::withMessages([
                    'time' => 'The doctor already has an appointment at this time.',
                ]);
            }

            $appointment->update([
                'scheduled_date' => $request->date,
                'scheduled_time' => $requestTime,
                'status' => 'Scheduled',
                'reschedule_requested_date' => null,
                'reschedule_requested_time' => null,
            ]);
        });

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Receptionist rescheduled appointment #'.$appointment->id
                        .' for Patient ID: '.$appointment->patient_id
                        .' from '.$oldDate.' '.$oldTime
                        .' to '.$request->date.' '.$requestTime,
        ]);

        $appointment->refresh();
        $appointment->load('provider.user:id,name');
        event(new AppointmentRescheduled($appointment));
        event(new AppointmentStatusChanged($appointment));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Appointment rescheduled successfully!');
    }

    // 13e. Receptionist denies a reschedule request
    public function denyReschedule(Appointment $appointment)
    {
        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment has no pending schedule to deny.');

        $appointment->update([
            'status' => 'Scheduled',
            'reschedule_requested_date' => null,
            'reschedule_requested_time' => null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Denied reschedule request for appointment #'.$appointment->id.' — original schedule kept.',
        ]);

        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));
        event(new RescheduleDenied($appointment));

        return back()->with('success', 'Reschedule request denied. The original schedule is kept.');
    }

    // 14. Doctor books a follow-up appointment after consultation
    public function storeFollowUp(Request $request, Appointment $appointment)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today', // H5 FIX: consistent with patient booking
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        $followUp = DB::transaction(function () use ($request, $appointment, $slot, $exactTime) {
            $this->lockProvidersForSlot($request->date, $exactTime ?? '00:00:00', $appointment->provider_id);

            if ($slot !== null) {
                $time = $this->firstAvailableTime($request->date, $slot, $appointment->provider_id);

                if ($time === null) {
                    throw ValidationException::withMessages([
                        'time' => 'You have no free time left in that slot. Please choose another slot.',
                    ]);
                }
            } else {
                $time = $exactTime;

                if ($this->slotTaken($appointment->provider_id, $request->date, $time)) {
                    throw ValidationException::withMessages([
                        'time' => 'The doctor already has an appointment at this time. Please choose a different slot.',
                    ]);
                }
            }

            return Appointment::create([
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'scheduled_date' => $request->date,
                'scheduled_time' => $time,
                'duration_minutes' => 30,
                'service_type' => 'Consultation',
                'status' => 'Scheduled',
                'notes' => 'Follow-up from consultation on '.
                                      Carbon::parse($appointment->scheduled_date)->format('M d, Y'),
            ]);
        });

        $event = $this->calendar->createEvent($followUp);
        if ($event) {
            $followUp->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Doctor scheduled follow-up for Patient #'.$appointment->patient_id.' from Appointment #'.$appointment->id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Follow-up appointment scheduled for '.$request->date.'.');
    }

    // 15. Patient reschedules their appointment (KEPT for backward compat — direct reschedule)
    public function reschedule(Request $request, Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        $this->assertNotNoShow($appointment);
        $this->assertNotCompleted($appointment, 'A completed appointment can no longer be rescheduled.');

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|in:morning,afternoon',
            'time' => 'required_without:time_slot',
        ]);

        $slot = $this->requestedSlot($request);
        $exactTime = $this->requestedExactTime($request);

        if ($slot === null && $exactTime === null) {
            throw ValidationException::withMessages([
                'time' => 'Please select a time slot.',
            ]);
        }

        if ($exactTime !== null
            && $appointment->scheduled_date->toDateString() === $request->date
            && $appointment->scheduled_time === $exactTime) {
            return back()->with('success', 'Schedule remained the same.');
        }

        // Lock every provider working the new window before re-running
        // auto-assignment, so two simultaneous reschedules cannot claim the
        // same doctor.
        $providerId = DB::transaction(function () use ($request, $appointment, $slot, $exactTime) {
            if ($slot !== null) {
                $this->lockProvidersForDate($request->date);
                $time = $this->firstAvailableTime($request->date, $slot);

                if ($time === null) {
                    throw ValidationException::withMessages([
                        'time' => 'No doctor is available in that time slot. Please choose another slot.',
                    ]);
                }

                $providerId = self::autoAssignDoctor($request->date, $time);
            } else {
                $time = $exactTime;
                $this->lockProvidersForSlot($request->date, $time);
                $providerId = self::autoAssignDoctor($request->date, $time);
            }

            $appointment->update([
                'scheduled_date' => $request->date,
                'scheduled_time' => $time,
                'provider_id' => $providerId,
                'status' => 'Scheduled',
            ]);

            return $providerId;
        });

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Rescheduled appointment #'.$appointment->id.' to '.$request->date,
        ]);

        $appointment->refresh();
        event(new AppointmentRescheduled($appointment));
        event(new AppointmentStatusChanged($appointment));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Appointment successfully rescheduled!');
    }
}
