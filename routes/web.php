<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorConsultationController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientCalendarController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\ReceptionistDocumentController;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

// 1. PUBLIC HOMEPAGE
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'isAuthenticated' => Auth::check(),
        'userRole' => Auth::check() ? Auth::user()->role : null,
    ]);
})->name('home');

// 2. AUTHENTICATION ROUTES
// The published terms stay outside the `guest` group so an already-registered
// patient can read back the agreement they accepted at signup.
Route::get('/terms', [AuthController::class, 'showTerms'])->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    //  Rate limiting on login (5 attempts per 1 minute per IP) — clinic shares
    //  one public WiFi IP, so allow a burst for ~16 staff at shift start.
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:30,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    //  Rate limiting on registration (3 attempts per 1 hour per IP)
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,10');

    //  Password Reset Routes
    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session()->get('status'),
        ]);
    })->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,60')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Secure Logout
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Patient .ics calendar feed (signed URL so a calendar app can subscribe
// without a browser session).
Route::get('/calendar/feed/{user}.ics', [PatientCalendarController::class, 'feed'])
    ->middleware('signed')
    ->name('patient.calendar.feed');

// 3. SECURE PORTALS
Route::middleware('auth')->group(function () {

    // Generic dashboard landing that dispatches to the user's role dashboard
    // (also the Fortify `home` path used after email verification).
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $route = in_array($user->role, ['receptionist', 'doctor', 'patient', 'admin'])
            ? $user->role.'.dashboard'
            : 'home';

        return redirect()->route($route);
    })->name('dashboard');

    // ==========================================
    // --- RECEPTIONIST DASHBOARD ---
    // ==========================================
    Route::get('/receptionist/dashboard', [ReceptionistController::class, 'dashboard'])->middleware('role:receptionist,admin')->name('receptionist.dashboard');

    // RECEPTIONIST ACTIONS
    Route::post('/receptionist/schedules', [AppointmentController::class, 'storeSchedule'])->middleware('role:receptionist,admin')->name('schedules.store');
    Route::put('/receptionist/schedules/{schedule}', [AppointmentController::class, 'updateSchedule'])->middleware('role:receptionist,admin')->name('schedules.update');
    Route::delete('/receptionist/schedules/{schedule}', [AppointmentController::class, 'deleteSchedule'])->middleware('role:receptionist,admin')->name('schedules.delete');

    // --- DOCTOR DASHBOARD ---
    Route::get('/doctor/dashboard', [DoctorController::class, 'dashboard'])->middleware('role:doctor')->name('doctor.dashboard');

    // --- DOCTOR EMR (CENTRALIZED PATIENT REGISTRY) ---
    Route::get('/doctor/emr', [DoctorController::class, 'emr'])->middleware('role:doctor')->name('doctor.emr');

    // --- PATIENT HISTORY (COMPREHENSIVE EMR VIEW) ---
    Route::get('/doctor/patients/{patient}/history', [DoctorController::class, 'patientHistory'])->middleware('role:doctor')->name('doctor.patient.history');

    // --- PATIENT ANALYTICS (vitals & clinical trends) ---
    Route::get('/doctor/patients/{patient}/analytics', [DoctorController::class, 'patientAnalytics'])->middleware('role:doctor')->name('doctor.patient.analytics');

    // --- PATIENT DASHBOARD ---
    Route::get('/patient/dashboard', function () {
        if (Auth::user()->role !== 'patient') {
            abort(403);
        }

        $patientId = Auth::user()->patient?->id;
        if (! $patientId) {
            abort(403, 'No patient record linked to your account.');
        }

        $dashboardRelations = [
            'provider.user:id,name',
            'encounter.prescriptions',
            'encounter.soapNote:id,encounter_id,ai_summary',
        ];

        $nextAppt = Appointment::with($dashboardRelations)
            ->where('patient_id', $patientId)
            ->whereDate('scheduled_date', '>=', today())
            ->whereNotIn('status', ['Completed', 'Cancelled', 'No-Show'])
            ->orderBy('scheduled_date')->orderBy('scheduled_time')->first();

        $history = Appointment::with($dashboardRelations)
            ->where('patient_id', $patientId)
            ->where('status', 'Completed')
            ->orderBy('scheduled_date', 'desc')
            ->get();

        // The patient dashboard expects a flattened shape (date/time/doctor),
        // not the raw model with scheduled_* columns and the provider relation.
        // Prescriptions use the same field names as the doctor's prescription
        // builder so adherence, interaction checks and notifications keep working.
        $shape = function (Appointment $appointment): array {
            $encounter = $appointment->encounter;

            return [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'date' => $appointment->scheduled_date?->toDateString(),
                'time' => $appointment->scheduled_time,
                'status' => $appointment->status,
                'service_type' => $appointment->service_type,
                'prescriptions' => ($encounter?->prescriptions ?? collect())
                    ->map(fn (Prescription $prescription): array => [
                        'id' => $prescription->id,
                        'medicine' => $prescription->medication_name,
                        'dosage' => $prescription->dosage,
                        'frequency' => $prescription->frequency,
                        'duration' => $prescription->duration_days
                            ? $prescription->duration_days.' days'
                            : ($prescription->end_date
                                ? 'Until '.$prescription->end_date->format('M j, Y')
                                : 'Ongoing'),
                        'instructions' => $prescription->instructions,
                        'startDate' => $prescription->start_date?->toDateString(),
                        'endDate' => $prescription->end_date?->toDateString(),
                        'prescribedBy' => $appointment->provider?->user?->name,
                        'prescribedAt' => $prescription->created_at?->toDateString(),
                    ])
                    ->values()
                    ->all(),
                'ai_summary' => $encounter?->soapNote?->ai_summary,
                'doctor' => $appointment->provider?->user ? [
                    'id' => $appointment->provider->user->id,
                    'name' => $appointment->provider->user->name,
                    'specialty' => $appointment->provider->specialty,
                ] : null,
            ];
        };

        return Inertia::render('Dashboard/Patient', [
            'user' => Auth::user(),
            'nextAppointment' => $nextAppt ? $shape($nextAppt) : null,
            'history' => $history->map($shape)->values(),
            'calendarDownloadUrl' => URL::route('patient.calendar.download'),
            'calendarAppointmentDownloadUrl' => URL::route('patient.calendar.download', ['scope' => 'appointments']),
            'calendarMedicationDownloadUrl' => URL::route('patient.calendar.download', ['scope' => 'medications']),
            'hasActiveMedications' => Prescription::where('patient_id', $patientId)->where('status', 'active')->exists(),
            'calendarFeedUrl' => URL::signedRoute('patient.calendar.feed', ['user' => Auth::id()]),
        ]);
    })->name('patient.dashboard');

    // Patient .ics calendar download (session-authenticated).
    Route::get('/patient/calendar.ics', [PatientCalendarController::class, 'download'])
        ->middleware('role:patient')
        ->name('patient.calendar.download');

    // --- AI & REPORTING ROUTES ---
    // Generates the Patient Abstract (Gemini + PubMed)
    Route::post('/api/ai/abstract/{patient}', [AiController::class, 'generateAbstract'])->middleware('role:doctor');

    // Saves Doctor Feedback
    Route::post('/api/ai/feedback/{patient}', [AiController::class, 'submitFeedback'])->middleware('role:doctor');

    // Verifies the Monthly Report
    Route::post('/api/ai/verify-report', [AiController::class, 'verifyAndLearnReport'])->middleware('role:doctor');

    // Views the Monthly Report
    Route::get('/doctor/reports/monthly', [AiController::class, 'showMonthlyReport'])->middleware('role:doctor')->name('doctor.reports.monthly');

    // Generates the Monthly Report (Gemini) — per-user: 20 per minute
    Route::post('/doctor/reports/monthly', [AiController::class, 'generateMonthlyReport'])->middleware(['role:doctor', 'throttle:doctor-ai']);

    // Saves a doctor-edited narrative — per-user: 40 per minute
    Route::put('/doctor/reports/monthly/narrative', [AiController::class, 'updateNarrative'])->middleware(['role:doctor', 'throttle:doctor-narrative'])->name('doctor.reports.monthly.narrative');
    // --- SYSTEM ADMIN DASHBOARD ---
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->middleware('role:admin')->name('admin.dashboard');
    Route::get('/admin/users-directory', [AdminController::class, 'usersDirectory'])->middleware('role:admin')->name('admin.users.directory');
    Route::get('/admin/create-account', [AdminController::class, 'createAccount'])->middleware('role:admin')->name('admin.create-account');
    Route::get('/admin/audit-log', [AdminController::class, 'auditLog'])->middleware('role:admin')->name('admin.audit-log');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->middleware('role:admin')->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->middleware('role:admin')->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser'])->middleware('role:admin')->name('admin.users.delete');

    // --- USER PROFILE (all roles, legacy page) ---
    Route::get('/profile', [ProfileController::class, 'show'])->name('user.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('user.profile.password');

    // --- APPOINTMENT ACTIONS ---
    Route::post('/patient/appointments', [AppointmentController::class, 'store'])->middleware('role:patient')->name('appointments.store');
    Route::get('/patient/appointments/availability', [AppointmentController::class, 'availableSlots'])->middleware('role:patient')->name('appointments.availability');
    Route::post('/patient/appointments/{appointment}/cancel', [AppointmentController::class, 'cancelAppointment'])->middleware('role:patient')->name('appointments.cancel');
    Route::post('/patient/appointments/{appointment}/request-reschedule', [AppointmentController::class, 'requestReschedule'])->middleware('role:patient')->name('appointments.request-reschedule');
    Route::post('/receptionist/appointments/{appointment}/approve-reschedule', [AppointmentController::class, 'approveReschedule'])->middleware('role:receptionist,admin')->name('appointments.approve-reschedule');
    Route::post('/receptionist/appointments/{appointment}/deny-reschedule', [AppointmentController::class, 'denyReschedule'])->middleware('role:receptionist,admin')->name('appointments.deny-reschedule');
    Route::post('/receptionist/appointments/{appointment}/edit-reschedule', [AppointmentController::class, 'editReschedule'])->middleware('role:receptionist,admin')->name('appointments.edit-reschedule');
    Route::post('/appointments/{appointment}/follow-up', [AppointmentController::class, 'storeFollowUp'])->middleware('role:doctor')->name('appointments.follow-up');
    Route::put('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->middleware('role:receptionist,admin')->name('appointments.status');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->middleware('role:receptionist,admin')->name('appointments.create');
    Route::post('/appointments/manual', [AppointmentController::class, 'storeManual'])->middleware('role:receptionist,admin')->name('appointments.manual');
    Route::get('/appointments/returning', [AppointmentController::class, 'createReturning'])->middleware('role:receptionist,admin')->name('appointments.returning');
    Route::post('/appointments/returning', [AppointmentController::class, 'storeReturning'])->middleware('role:receptionist,admin');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'completeConsultation'])->middleware('role:doctor,receptionist,admin')->name('appointments.complete');
    Route::put('/appointments/{appointment}/transfer', [AppointmentController::class, 'transfer'])->middleware('role:receptionist,admin')->name('appointments.transfer');
    Route::put('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware('role:patient')->name('appointments.reschedule');

    // --- MEDICATION ADHERENCE ROUTES ---
    Route::post('/medications/{appointment}/log-dose', [MedicationController::class, 'logDose'])->middleware('role:patient,doctor')->name('medications.log-dose');
    Route::post('/medications/{appointment}/toggle-dose', [MedicationController::class, 'toggleDose'])->middleware('role:patient,doctor')->name('medications.toggle-dose');
    Route::get('/medications/{appointment}/adherence', [MedicationController::class, 'adherence'])->middleware('role:patient,doctor')->name('medications.adherence');
    Route::get('/patient/medications/today', [MedicationController::class, 'todayMeds'])->middleware('role:patient')->name('medications.today');
    Route::get('/patient/medications/history', [MedicationController::class, 'history'])->middleware('role:patient')->name('medications.history');
    Route::get('/doctor/patients/{patient}/medication-adherence', [MedicationController::class, 'patientAdherence'])->middleware('role:doctor')->name('medications.patient-adherence');

    // --- RECEPTIONIST SCANS -> QUEUED OCR (PP-OCRv5) ---
    Route::post('/appointments/{appointment}/documents', [ReceptionistDocumentController::class, 'store'])
        ->middleware('role:receptionist,admin')->name('documents.store');
    Route::get('/documents/pending', [ReceptionistDocumentController::class, 'pending'])
        ->middleware('role:receptionist,admin')->name('documents.pending');
    Route::get('/documents/{document}', [ReceptionistDocumentController::class, 'show'])
        ->middleware('role:receptionist,doctor,admin')->name('documents.show');
    Route::post('/documents/{document}/retry-ocr', [ReceptionistDocumentController::class, 'retryOcr'])
        ->middleware('role:receptionist,admin')->name('documents.retryOcr');

    // --- RECEPTIONIST STUDY DATA -> PRE-FILLED INTO THE DOCTOR'S CONSULTATION ---
    // The receptionist records the Echo/TMT/Doppler measurements on a draft
    // encounter; the doctor's consultation form then pre-fills from it.
    Route::get('/appointments/{appointment}/study-data', [AppointmentController::class, 'showStudyData'])
        ->middleware('role:receptionist,doctor,admin')->name('study-data.show');
    Route::put('/appointments/{appointment}/study-data', [AppointmentController::class, 'storeStudyData'])
        ->middleware('role:receptionist,doctor,admin')->name('study-data.update');
    Route::put('/appointments/{appointment}/reading', [AppointmentController::class, 'assignReading'])
        ->middleware('role:receptionist,doctor,admin')->name('appointments.reading');

    // --- DOCTOR CONSULTATION: patient + documents + history ---
    Route::get('/doctor/appointments/{appointment}/consultation', [DoctorConsultationController::class, 'show'])
        ->middleware('role:doctor')->name('consultation.show');
    Route::post('/doctor/appointments/{appointment}/consultation/start', [DoctorConsultationController::class, 'start'])
        ->middleware('role:doctor')->name('consultation.start');

    // --- HIPAA AUDIT LOG (medication viewed) ---
    Route::post('/audit/medication-viewed', function (Request $request) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Patient viewed medications: '.($request->medicationCount ?? 0).' prescriptions',
        ]);

        return response()->noContent();
    })->middleware('role:patient');

    // --- PDF EXPORTS (server-side via barryvdh/laravel-dompdf) ---
    Route::get('/doctor/reports/monthly/pdf', [PdfController::class, 'downloadMonthlyReport'])->middleware(['role:doctor', 'throttle:doctor-pdf'])->name('pdf.monthly-report');
    Route::get('/doctor/pdf/{appointment}/{type}', [PdfController::class, 'download'])
        ->where('type', 'echo|tmt|doppler|manual_draft|stress_echo|abpm|prescription')
        ->middleware('role:doctor,patient')
        ->name('pdf.download');

});
