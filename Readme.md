**Tool Call: Map Laravel models**
Status: Completed

task_id: ses_18c8d7d9bffeBBoQ7KxXTtQfZm (for resuming to continue this task if needed)

I have read all \*\*7 model files\*\*. Here is the complete structured summary:

## Complete Model Summary -- app/Models/

### 1\. User.php

| **Attribute** | **Detail**                                                  |
| ------------- | ----------------------------------------------------------- |
| **Namespace** | App\\Models                                                 |
| **Class**     | User extends Authenticatable                                |
| **Table**     | Conventions → users (Laravel default; Authenticatable base) |
| **Traits**    | HasFactory, Notifiable, TwoFactorAuthenticatable            |

**Fillable:**
name, email, password, role, specialty, allergies, age, sex, contact

**Hidden (serialization):**
password, two_factor_secret, two_factor_recovery_codes, remember_token

**Casts:**

| **attribute**           | **cast** |
| ----------------------- | -------- |
| email_verified_at       | datetime |
| password                | hashed   |
| two_factor_confirmed_at | datetime |

**Appends:**
patient_id (virtual accessor returning "PT-XXXXX" for patients or "STAFF-X" for others)

**Relationships:**

| **Method**            | **Type**                                                                                                 | **Foreign Key** |
| --------------------- | -------------------------------------------------------------------------------------------------------- | --------------- |
| schedules()           | hasMany(DoctorSchedule::class)                                                                           | doctor_id       |
| doctorAppointments()  | hasMany(Appointment::class)                                                                              | doctor_id       |
| patientAppointments() | hasMany(Appointment::class)                                                                              | patient_id      |
| appointments()        | hasMany(Appointment::class) - **dynamic**: uses doctor_id if role is 'doctor', otherwise uses patient_id | conditional     |

### 2\. Appointment.php

| **Attribute** | **Detail**                 |
| ------------- | -------------------------- |
| **Namespace** | App\\Models                |
| **Class**     | Appointment extends Model  |
| **Table**     | Conventions → appointments |

**Fillable:**
patient_id, doctor_id, date, time, service_type, status, notes, google_event_id, google_event_url, reschedule_requested_date, reschedule_requested_time, symptoms, vitals, diagnosis, consultation_type, prescriptions, ai_summary, echo_data, tmt_data, doppler_data, duration_minutes

**Casts:**

| **attribute**     | **cast** |
| ----------------- | -------- |
| date              | date     |
| consultation_type | array    |
| prescriptions     | array    |

**Custom Attribute Accessors (XML/JSON transparent conversion):**

| **Accessor**  | **Getter**                                              | **Setter**                                   |
| ------------- | ------------------------------------------------------- | -------------------------------------------- |
| echoData()    | decodes via ClinicalJsonService::decode(\$v, 'echo')    | encodes via ClinicalJsonService::encode(\$v) |
| tmtData()     | decodes via ClinicalJsonService::decode(\$v, 'tmt')     | encodes via ClinicalJsonService::encode(\$v) |
| dopplerData() | decodes via ClinicalJsonService::decode(\$v, 'doppler') | encodes via ClinicalJsonService::encode(\$v) |

**Relationships:**

| **Method**       | **Type**                      | **Foreign Key / Pivot**  |
| ---------------- | ----------------------------- | ------------------------ |
| patient()        | belongsTo(User::class)        | patient_id               |
| doctor()         | belongsTo(User::class)        | doctor_id                |
| medicationLogs() | hasMany(MedicationLog::class) | (default appointment_id) |

### 3\. AuditLog.php

| **Attribute** | **Detail**               |
| ------------- | ------------------------ |
| **Namespace** | App\\Models              |
| **Class**     | AuditLog extends Model   |
| **Table**     | Conventions → audit_logs |

**Fillable:**
user_id, action, description, ip_address

**Casts:** _(none declared)_

**Relationships:**

| **Method** | **Type**               | **Foreign Key**   |
| ---------- | ---------------------- | ----------------- |
| user()     | belongsTo(User::class) | (default user_id) |

### 4\. DoctorSchedule.php

| **Attribute** | **Detail**                     |
| ------------- | ------------------------------ |
| **Namespace** | App\\Models                    |
| **Class**     | DoctorSchedule extends Model   |
| **Table**     | Conventions → doctor_schedules |

**Fillable:**
doctor_id, day_of_week, start_time, end_time

**Casts:**

| **attribute** | **cast** |
| ------------- | -------- |
| day_of_week   | array    |

**Relationships:**

| **Method** | **Type**               | **Foreign Key** |
| ---------- | ---------------------- | --------------- |
| doctor()   | belongsTo(User::class) | doctor_id       |

### 5\. MedicationLog.php

| **Attribute** | **Detail**                    |
| ------------- | ----------------------------- |
| **Namespace** | App\\Models                   |
| **Class**     | MedicationLog extends Model   |
| **Table**     | Conventions → medication_logs |

**Fillable:**
appointment_id, prescription_index, medicine, log_date, scheduled_time, taken_at

**Casts:**

| **attribute** | **cast** |
| ------------- | -------- |
| log_date      | date     |
| taken_at      | datetime |

**Relationships:**

| **Method**    | **Type**                      | **Foreign Key**          |
| ------------- | ----------------------------- | ------------------------ |
| appointment() | belongsTo(Appointment::class) | (default appointment_id) |

### 6\. MonthlyReport.php

| **Attribute** | **Detail**                    |
| ------------- | ----------------------------- |
| **Namespace** | App\\Models                   |
| **Class**     | MonthlyReport extends Model   |
| **Table**     | Conventions → monthly_reports |

**Fillable:**
doctor_id, month, report_text, stats

**Casts:**

| **attribute** | **cast** |
| ------------- | -------- |
| stats         | array    |

**Relationships:**

| **Method** | **Type**               | **Foreign Key** |
| ---------- | ---------------------- | --------------- |
| doctor()   | belongsTo(User::class) | doctor_id       |

**Local Scopes:**

- scopeForDoctor(Builder \$query, int \$doctorId) → filters by doctor_id
- scopeForMonth(Builder \$query, string \$month) → filters by month

### 7\. RAGReport.php

| **Attribute** | **Detail**                                          |
| ------------- | --------------------------------------------------- |
| **Namespace** | App\\Models                                         |
| **Class**     | RAGReport extends Model                             |
| **Table**     | **Explicit:** rag*reports *(overrides convention)\_ |

**Fillable:**
patient_id, report_type, month, year, content, metadata

**Casts:**

| **attribute** | **cast** |
| ------------- | -------- |
| metadata      | array    |

**Relationships:**

| **Method** | **Type**               | **Foreign Key** |
| ---------- | ---------------------- | --------------- |
| patient()  | belongsTo(User::class) | patient_id      |

**Local Scopes:**

- scopeMonthlyNarrative(\$query) → where('report_type', 'monthly_narrative')
- scopeAbstractSummary(\$query) → where('report_type', 'abstract_summary')
- scopeForPatient(\$query, \$patientId) → where('patient_id', \$patientId)
- scopeLatest(\$query) → orderBy('created_at', 'desc')

## Entity-Relationship Overview

Below is a concise view of how the models connect:

User (Authenticatable)
├── hasMany → DoctorSchedule (doctor_id)
├── hasMany → Appointment (doctor_id) \[via doctorAppointments()\]
├── hasMany → Appointment (patient_id) \[via patientAppointments()\]
├── hasMany → Appointment (conditional) \[via appointments()\]
├── hasMany → AuditLog (user_id)
├── hasMany → MonthlyReport (doctor_id)
└── hasMany → RAGReport (patient_id)
<br/>Appointment
├── belongsTo → User (patient_id)
├── belongsTo → User (doctor_id)
└── hasMany → MedicationLog (appointment_id)
<br/>MedicationLog
└── belongsTo → Appointment
<br/>AuditLog
└── belongsTo → User
<br/>DoctorSchedule
└── belongsTo → User (doctor_id)
<br/>MonthlyReport
└── belongsTo → User (doctor_id)
<br/>RAGReport
└── belongsTo → User (patient_id)

### Key observations

1\. **User** is the central hub - it acts as both patient and doctor (role-based polymorphic-like logic via explicit foreign keys), and nearly every other model relates back to it.

2\. **Appointment** is the second most connected model, bridging patients, doctors, and medication logs.

3\. **\`RAGReport\`** is the only model that explicitly declares a \$table property ('rag_reports'), meaning its table name deviates from the default plural snake_case convention.

4\. **Custom casts/accessors** are used heavily on Appointment for clinical data (echoData, tmtData, dopplerData) which transparently convert between stored XML/JSON and PHP arrays via ClinicalJsonService.

5\. **\`MedicationLog\`** and **\`MonthlyReport\`** use real date/datetime casts for their date columns.

6\. **\`DoctorSchedule\`** casts day_of_week as an array (likely storing multiple selected days).

7\. No model uses \$guarded - all rely on \$fillable exclusively for mass-assignment protection.&lt;/task_result&gt;

**Tool Call: Map Laravel controllers**
Status: Completed

task_id: ses_18c8d7975ffeA8eLQFK1GVew7i (for resuming to continue this task if needed)

I have read all 13 controller files. Here is the complete structured summary.

# Complete Controller Summary

## C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\

### 1\. Controller.php (Base Class)

| **Property**       | **Value**                                                         |
| ------------------ | ----------------------------------------------------------------- |
| **Namespace**      | App\\Http\\Controllers                                            |
| **Class**          | Controller                                                        |
| **Type**           | abstract class                                                    |
| **Public Methods** | _(none)_                                                          |
| **Description**    | Empty abstract base controller that all other controllers extend. |

### 2\. AdminController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | AdminController        |
| **Extends**   | Controller             |

| **Public Method**                          | **Route-like Name** | **Description**                                                                                                                                                                                                                    |
| ------------------------------------------ | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| index()                                    | index               | Loads the Admin Dashboard. Gathers stats (total users, doctors, patients, staff), lists all users (with security-limited fields), retrieves up to 100 recent audit logs, and renders the Dashboard/Admin Inertia page.             |
| storeUser(Request \$request)               | storeUser           | Creates a new clinic staff member or patient. Validates name, email, strong password (12+ chars, mixed case, numbers, symbols, uncompromised), role, specialty, age/sex. Prepends "Dr." to doctor names. Logs action in audit log. |
| updateUser(Request \$request, User \$user) | updateUser          | Edits an existing user's account details. Validates same fields as store. Only updates password if a new one is provided. Logs the update.                                                                                         |
| deleteUser(User \$user)                    | deleteUser          | Deletes a user account. Prevents self-deletion. Logs the deletion.                                                                                                                                                                 |

### 3\. AuthController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | AuthController         |
| **Extends**   | Controller             |

| **Public Method**                | **Route-like Name**  | **Description**                                                                                                                                                                                                                                           |
| -------------------------------- | -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| create()                         | create               | Shows the login page (renders Auth/Login Inertia page).                                                                                                                                                                                                   |
| showRegistrationForm()           | showRegistrationForm | Shows the registration page (renders Auth/Register).                                                                                                                                                                                                      |
| store(Request \$request)         | store                | Processes login form submission. Validates email/password, attempts authentication, regenerates session (anti-fixation), logs to audit log, and redirects to the user's role-specific dashboard. Uses generic error messages to prevent user enumeration. |
| register(Request \$request)      | register             | Registers a new patient account. Validates strong password (with confirmation), age, sex. Creates user with role "patient", logs them in, and redirects to patient dashboard.                                                                             |
| destroy(Request \$request)       | destroy              | Securely signs out the user: logs out, invalidates session, regenerates CSRF token, logs the logout.                                                                                                                                                      |
| showResetForm(\$token)           | showResetForm        | Shows the password reset form with the given token.                                                                                                                                                                                                       |
| sendResetLink(Request \$request) | sendResetLink        | Handles "forgot password" requests: generates a reset token, stores it, queues a ForgotPasswordMail. Always returns the same message regardless of whether the email exists (prevents enumeration).                                                       |
| resetPassword(Request \$request) | resetPassword        | Resets the password using a token. Validates token existence and 2-hour expiry. Updates password, deletes used token, redirects to login.                                                                                                                 |

### 4\. AppointmentController.php

| **Property**              | **Value**                        |
| ------------------------- | -------------------------------- |
| **Namespace**             | App\\Http\\Controllers           |
| **Class**                 | AppointmentController            |
| **Extends**               | Controller                       |
| **Constructor Injection** | GoogleCalendarService \$calendar |

| **Public Method**                                                  | **Route-like Name**  | **Description**                                                                                                                                                                                                                                                                                                                |
| ------------------------------------------------------------------ | -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| store(Request \$request)                                           | store                | Patient books a new appointment. Validates date/time/service, rejects past slots, enforces max 3 active appointments per day, auto-assigns a doctor, creates the appointment, syncs to Google Calendar, broadcasts queue update.                                                                                               |
| cancelAppointment(Appointment \$appointment)                       | cancelAppointment    | Patient cancels their own upcoming appointment. Checks ownership, prevents cancellation of terminal/in-progress statuses, updates status to "Cancelled", syncs Google Calendar.                                                                                                                                                |
| updateStatus(Request \$request, Appointment \$appointment)         | updateStatus         | Doctor/Receptionist updates an appointment's status to any valid value (Scheduled, Pending, Approved, Cancelled, No-Show, Completed, In Progress, Reschedule Requested). Syncs to Google Calendar.                                                                                                                             |
| create()                                                           | create               | Shows the manual booking page for receptionists. Loads list of doctors (with specialty) and renders Appointments/Create.                                                                                                                                                                                                       |
| storeManual(Request \$request)                                     | storeManual          | Handles walk-in (new patient) booking by receptionist. Creates a new patient account with a randomly-generated email/password, checks for time conflicts, creates appointment in "Pending" status, syncs to Google Calendar.                                                                                                   |
| createReturning()                                                  | createReturning      | Shows the returning patient booking page. Loads patients (capped at 500) and doctors list, renders Appointments/Returning.                                                                                                                                                                                                     |
| storeReturning(Request \$request)                                  | storeReturning       | Handles booking for an existing returning patient. Checks for time conflicts with the chosen doctor, creates appointment, syncs to Google Calendar.                                                                                                                                                                            |
| completeConsultation(Request \$request, Appointment \$appointment) | completeConsultation | Doctor saves the SOAP consultation record. Only the assigned doctor can complete. Validates symptoms, vitals, diagnosis, consultation types, prescriptions, echo/TMT/doppler data, and AI summary. Sanitizes text for XSS prevention. Persists clinical data only for selected consultation types. Sets status to "Completed". |
| transfer(Request \$request, Appointment \$appointment)             | transfer             | Transfers a patient to a different doctor. Updates the Google Calendar event.                                                                                                                                                                                                                                                  |
| storeSchedule(Request \$request)                                   | storeSchedule        | Doctor/Receptionist plots a recurring weekly schedule for a doctor (day_of_week array, start/end time).                                                                                                                                                                                                                        |
| updateSchedule(Request \$request, DoctorSchedule \$schedule)       | updateSchedule       | Updates an existing doctor schedule entry.                                                                                                                                                                                                                                                                                     |
| deleteSchedule(DoctorSchedule \$schedule)                          | deleteSchedule       | Deletes a doctor schedule entry.                                                                                                                                                                                                                                                                                               |
| requestReschedule(Request \$request, Appointment \$appointment)    | requestReschedule    | Patient submits a reschedule REQUEST (receptionist must approve). Sets status to "Reschedule Requested", stores requested date/time.                                                                                                                                                                                           |
| approveReschedule(Appointment \$appointment)                       | approveReschedule    | Receptionist approves a pending reschedule request. Verifies the doctor is scheduled to work at the requested time and that there is no conflict. Updates the appointment.                                                                                                                                                     |
| editReschedule(Request \$request, Appointment \$appointment)       | editReschedule       | Receptionist directly edits and approves a reschedule (bypassing the request if needed). Checks for conflicts.                                                                                                                                                                                                                 |
| denyReschedule(Appointment \$appointment)                          | denyReschedule       | Receptionist denies a reschedule request. Clears request fields, reverts status to "Scheduled".                                                                                                                                                                                                                                |
| storeFollowUp(Request \$request, Appointment \$appointment)        | storeFollowUp        | Doctor books a follow-up appointment after a consultation. Checks for conflicts, creates a new appointment with "Follow-up Consultation" service type.                                                                                                                                                                         |
| reschedule(Request \$request, Appointment \$appointment)           | reschedule           | (Legacy) Patient directly reschedules their own appointment. Auto-assigns a new doctor if needed.                                                                                                                                                                                                                              |

**Private helpers (notable):**

- autoAssignDoctor(\$date, \$time) -- Finds an available doctor with matching schedule and no time conflict.
- broadcastQueueUpdated(int \$doctorId) -- Broadcasts current queue via WebSocket.
- getDurationMinutes(int \$patientId) -- Returns 90 min for first visit, 30 min for follow-up.
- sanitizeText(string \$text, int \$maxLength) -- XSS prevention via htmlspecialchars.

### 5\. DoctorController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | DoctorController       |
| **Extends**   | Controller             |

| **Public Method**                | **Route-like Name** | **Description**                                                                                                                                                                                                        |
| -------------------------------- | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| dashboard()                      | dashboard           | Loads the Doctor Dashboard. Fetches today's queue for the logged-in doctor (appointments not yet completed/no-show), eager-loads patient info and last 5 completed visits. Renders Dashboard/Doctor.                   |
| emr()                            | emr                 | Loads the EMR (Electronic Medical Records) page. Lists all patients with their latest diagnosis and last visit date. Capped at 1000 patients. Renders Dashboard/EMR.                                                   |
| patientAnalytics(User \$patient) | patientAnalytics    | Returns JSON with trend data for a specific patient: parsed vitals over time, echo data metrics (LVEF, LVFS, LV mass, etc.), TMT data (peak HR, METs, etc.), and Doppler data (MV E/A ratio, etc.). Used for charting. |
| patientHistory(User \$patient)   | patientHistory      | Shows the complete consultation history for a patient. Loads all completed appointments with doctor info and full clinical data. Renders Dashboard/PatientHistory.                                                     |

### 6\. MedicationController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | MedicationController   |
| **Extends**   | Controller             |

| **Public Method**                                     | **Route-like Name** | **Description**                                                                                                                                                                                       |
| ----------------------------------------------------- | ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| logDose(Request \$request, Appointment \$appointment) | logDose             | Logs a medication dose taken by the patient. Validates prescription index, prevents duplicate logging for the same day. Returns JSON response.                                                        |
| adherence(Appointment \$appointment)                  | adherence           | Returns daily medication adherence data for an appointment. Calculates expected vs. taken doses per day (up to 90 days). Used for adherence tracking charts.                                          |
| todayMeds()                                           | todayMeds           | Returns the patient's medications scheduled for today. Aggregates from up to 5 most recent completed appointments, deduplicates by medicine name, and indicates which doses have been taken.          |
| history()                                             | history             | Returns the patient's medication log history for the last 30 days (up to 100 records).                                                                                                                |
| patientAdherence(User \$patient)                      | patientAdherence    | Doctor-facing: returns comprehensive adherence analysis for a specific patient across all their completed appointments. Includes per-medication adherence rates and daily breakdowns (up to 90 days). |

### 7\. PdfController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | PdfController          |
| **Extends**   | Controller             |

| **Public Method**                                  | **Route-like Name**   | **Description**                                                                                                                                                                                                                 |
| -------------------------------------------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| download(Appointment \$appointment, string \$type) | download              | Streams/downloads a PDF for clinical data. Only doctors or the owning patient may download. Supports 4 PDF types: echo (Echocardiography), tmt (Treadmill Test), doppler (Doppler), prescription. Uses DomPDF with Blade views. |
| downloadMonthlyReport(Request \$request)           | downloadMonthlyReport | Downloads a monthly clinical report as a PDF. Doctors only. Fetches from MonthlyReport model, renders via pdfs.monthly-report Blade view. Letter format.                                                                        |

### 8\. ProfileController.php (root level)

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | ProfileController      |
| **Extends**   | Controller             |

| **Public Method**                 | **Route-like Name** | **Description**                                                                                                   |
| --------------------------------- | ------------------- | ----------------------------------------------------------------------------------------------------------------- |
| show()                            | show                | Shows the current logged-in user's profile page (renders Profile Inertia page).                                   |
| update(Request \$request)         | update              | Updates the user's profile info: name, email (unique check), age, sex, contact.                                   |
| updatePassword(Request \$request) | updatePassword      | Changes the user's password. Validates current password, then updates with new password (min 8 chars, confirmed). |

### 9\. AiController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | AiController           |
| **Extends**   | Controller             |

| **Public Method**                                 | **Route-like Name**   | **Description**                                                                                                                                                                                                                                                                                                                                                                                                         |
| ------------------------------------------------- | --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| generateAbstract(User \$patient)                  | generateAbstract      | Generates a 3-sentence clinical abstract for a patient using AI. Uses RAG: retrieves PubMed literature (Europe PMC API, 5 papers) and Philippine Heart Association (PHA) CAD 2014 guidelines (via LlamaParse-chunked TF-scored retrieval). Anonymizes patient data (GDPR Privacy by Design). Calls AI via triple-provider chain (DeepSeek Direct -> HuggingFace -> OpenRouter). Persists summary to latest appointment. |
| showMonthlyReport(Request \$request)              | showMonthlyReport     | Displays the monthly performance report page. Loads a specific report or the latest one. Returns the Dashboard/MonthlyReport Inertia page with stats, AI narrative text, and report history.                                                                                                                                                                                                                            |
| generateMonthlyReport(Request \$request)          | generateMonthlyReport | Generates a new monthly report for a given month. Guards against rapid duplicate generation (30s cooldown) and empty months. Delegates to MonthlyReportService::generate() which produces stats and AI narrative.                                                                                                                                                                                                       |
| verifyAndLearnReport(Request \$request)           | verifyAndLearnReport  | Placeholder stub for the AI learning/feedback loop. Returns info message only.                                                                                                                                                                                                                                                                                                                                          |
| updateNarrative(Request \$request)                | updateNarrative       | Allows a doctor to edit and save a corrected version of the monthly report's narrative text. Prevents saving identical text.                                                                                                                                                                                                                                                                                            |
| submitFeedback(Request \$request, User \$patient) | submitFeedback        | Placeholder stub for patient-specific AI feedback. Returns info message only.                                                                                                                                                                                                                                                                                                                                           |
| analyzeDocument(Request \$request)                | analyzeDocument       | Uploads a PDF medical document to LlamaParse for parsing, then sends the parsed markdown + a user prompt to the AI provider for analysis. Supports up to 10 MB PDFs. Returns the analysis result.                                                                                                                                                                                                                       |

**Notable private helpers:**

- fetchPubMedLiterature(string \$query) -- Cached (24h) retrieval of up to 5 PubMed abstracts via Europe PMC API.
- downloadAndParsePhaGuidelines() -- Downloads PHA CAD 2014 PDF, parses via LlamaParse, returns markdown.
- getPhaGuidelineChunks() -- Cached (30 days) chunked sections of PHA guidelines for RAG index.
- chunkMarkdown(\$markdown, \$maxLen) -- Splits markdown by level-2 headers into chunks.
- retrieveRelevantGuidelines(\$query, \$topN) -- TF-scored retrieval of top-N guideline chunks relevant to a query.
- callAiApi(\$prompt) -- Triple-provider AI call chain: DeepSeek Direct -> HuggingFace -> OpenRouter.
- maskPii(\$text) -- Masks Philippine phone numbers, emails, and addresses from text (GDPR).
- anonymizeForAi(\$patient, \$history) -- Creates a deterministic pseudonym hash and PII-masks all medical records before sending to external AI.

### 10\. RAGReportController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | RAGReportController    |
| **Extends**   | Controller             |

| **Public Method**                          | **Route-like Name**     | **Description**                                                                                                                                  |
| ------------------------------------------ | ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| ingestPatientData(Request \$request)       | ingestPatientData       | Ingests patient data into an external RAG service (default: <http://localhost:5000>). Accepts patient_id and days (1-365). Proxies the response. |
| generateNarrativeReport(Request \$request) | generateNarrativeReport | Generates a narrative report via the external RAG service. First ingests data, then calls the /narrative-report endpoint.                        |
| generateAbstractSummary(Request \$request) | generateAbstractSummary | Generates an abstract summary via the external RAG service. Ingests data first, then calls /abstract-summary.                                    |
| retrieveContext(Request \$request)         | retrieveContext         | Retrieves relevant patient context for a given query from the external RAG service.                                                              |
| healthCheck()                              | healthCheck             | Health-check endpoint for the external RAG service. Returns 503 if unavailable.                                                                  |

### 11\. ReceptionistController.php

| **Property**  | **Value**              |
| ------------- | ---------------------- |
| **Namespace** | App\\Http\\Controllers |
| **Class**     | ReceptionistController |
| **Extends**   | Controller             |

| **Public Method** | **Route-like Name** | **Description**                                                                                                                                                                                                                                                                                                                                                          |
| ----------------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| dashboard()       | dashboard           | Loads the Receptionist Dashboard. Accessible by receptionists and admins. Gathers: stats (total patients, today's appointments/completed), doctors list, today's master queue (optimized with selective columns), future appointments (grouped by month), doctor schedules, recent audit logs (last 5), and pending reschedule requests. Renders Dashboard/Receptionist. |

### 12\. Settings\\ProfileController.php

| **Property**  | **Value**                        |
| ------------- | -------------------------------- |
| **Namespace** | App\\Http\\Controllers\\Settings |
| **Class**     | ProfileController                |
| **Extends**   | Controller                       |

| **Public Method**                       | **Route-like Name** | **Description**                                                                                                                          |
| --------------------------------------- | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| edit(Request \$request)                 | edit                | Shows the user's profile settings page (settings/Profile). Passes mustVerifyEmail status.                                                |
| update(ProfileUpdateRequest \$request)  | update              | Updates the user's profile information using a form request. Resets email_verified_at if email changed. Redirects to profile edit route. |
| destroy(ProfileDeleteRequest \$request) | destroy             | Deletes the user's account. Logs out, invalidates session, regenerates CSRF token. Redirects to /.                                       |

### 13\. Settings\\SecurityController.php

| **Property**   | **Value**                        |
| -------------- | -------------------------------- |
| **Namespace**  | App\\Http\\Controllers\\Settings |
| **Class**      | SecurityController               |
| **Extends**    | Controller                       |
| **Implements** | HasMiddleware                    |

| **Public Method**                              | **Route-like Name**         | **Description**                                                                                                                             |
| ---------------------------------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| middleware()                                   | _(static, middleware hook)_ | Conditionally applies password.confirm middleware to the edit method if two-factor authentication and confirm-password options are enabled. |
| edit(TwoFactorAuthenticationRequest \$request) | edit                        | Shows the user's security settings page (settings/Security). Passes whether 2FA can be managed, is enabled, and requires confirmation.      |
| update(PasswordUpdateRequest \$request)        | update                      | Updates the user's password using a dedicated form request.                                                                                 |

## Summary Statistics

| **Metric**                                      | **Count**                                                     |
| ----------------------------------------------- | ------------------------------------------------------------- |
| **Total controller files**                      | 13 (12 .php files + 1 abstract base)                          |
| **Controllers with public methods**             | 12                                                            |
| **Base/abstract controllers**                   | 1 (Controller.php)                                            |
| **Sub-namespace controllers**                   | 2 (Settings\\ProfileController, Settings\\SecurityController) |
| **Total public methods across all controllers** | 50                                                            |

## Complete File List (Absolute Paths)

1\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\Controller.php

2\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\AdminController.php

3\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\AuthController.php

4\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\AppointmentController.php

5\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\DoctorController.php

6\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\MedicationController.php

7\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\PdfController.php

8\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\ProfileController.php

9\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\AiController.php

10\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\RAGReportController.php

11\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\ReceptionistController.php

12\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\Settings\\ProfileController.php

13\. C:\\xampp\\htdocs\\Cardiovascular_system\\Cardiovascular_system\\app\\Http\\Controllers\\Settings\\SecurityController.php&lt;/task_result&gt;

**Tool Call: Map routes and middleware**
Status: Completed

task_id: ses_18c8d741effevNqyjqRyqshX21 (for resuming to continue this task if needed)

Now I have all the information needed. Here is the complete structured summary.

## Complete Route & Middleware Summary

**Framework:** Laravel 12.0 (with Inertia.js 2.0, Laravel Fortify 1.30, Reverb 1.10)
**PHP:** ^8.2
**Architecture:** Single-page application via Inertia.js (server-driven Vue SPA)

### 1\. ROUTE FILES

Five route files were found:

| **File**            | **Purpose**                                             |
| ------------------- | ------------------------------------------------------- |
| routes/web.php      | Main web routes (169 lines)                             |
| routes/api.php      | API routes, includes rag-routes.php (missing from disk) |
| routes/channels.php | WebSocket broadcast channels (16 lines)                 |
| routes/console.php  | Artisan commands and scheduled tasks (32 lines)         |
| routes/settings.php | User settings/profile routes (24 lines)                 |

**Note:** routes/api.php contains include \_\_DIR\_\_ . '/../rag-routes.php'; but this file does **not exist** on the filesystem, which would cause a fatal error if the API routes are loaded.

### 2\. ALL ROUTE DEFINITIONS

#### 2.1 Web Routes (routes/web.php)

##### Public / Guest Routes

| **Method** | **URI**                 | **Controller / Action**               | **Name**         | **Middleware**       |
| ---------- | ----------------------- | ------------------------------------- | ---------------- | -------------------- |
| GET        | /                       | Closure (renders Welcome via Inertia) | home             | None                 |
| GET        | /login                  | AuthController@create                 | login            | guest                |
| POST       | /login                  | AuthController@store                  | \--              | guest, throttle:5,1  |
| GET        | /register               | AuthController@showRegistrationForm   | register         | guest                |
| POST       | /register               | AuthController@register               | \--              | guest, throttle:5,10 |
| GET        | /forgot-password        | Closure (renders Auth/ForgotPassword) | password.request | guest                |
| POST       | /forgot-password        | AuthController@sendResetLink          | password.email   | guest, throttle:5,60 |
| GET        | /reset-password/{token} | AuthController@showResetForm          | password.reset   | guest                |
| POST       | /reset-password         | AuthController@resetPassword          | password.update  | guest                |
| POST       | /logout                 | AuthController@destroy                | logout           | auth                 |

##### Authenticated Routes (all under auth middleware group)

**Receptionist Dashboard & Schedule Management**

| **Method** | **URI**                            | **Controller / Action**              | **Name**               |
| ---------- | ---------------------------------- | ------------------------------------ | ---------------------- |
| GET        | /receptionist/dashboard            | ReceptionistController@dashboard     | receptionist.dashboard |
| POST       | /receptionist/schedules            | AppointmentController@storeSchedule  | schedules.store        |
| PUT        | /receptionist/schedules/{schedule} | AppointmentController@updateSchedule | schedules.update       |
| DELETE     | /receptionist/schedules/{schedule} | AppointmentController@deleteSchedule | schedules.delete       |

**Doctor Dashboard & EMR**

| **Method** | **URI**                                         | **Controller / Action**               | **Name**                      |
| ---------- | ----------------------------------------------- | ------------------------------------- | ----------------------------- |
| GET        | /doctor/dashboard                               | DoctorController@dashboard            | doctor.dashboard              |
| GET        | /doctor/emr                                     | DoctorController@emr                  | doctor.emr                    |
| GET        | /doctor/patients/{patient}/history              | DoctorController@patientHistory       | doctor.patient.history        |
| GET        | /doctor/patients/{patient}/analytics            | DoctorController@patientAnalytics     | doctor.patient.analytics      |
| GET        | /doctor/patients/{patient}/medication-adherence | MedicationController@patientAdherence | medications.patient-adherence |

**Patient Dashboard**

| **Method** | **URI**            | **Controller / Action**                              | **Name**          |
| ---------- | ------------------ | ---------------------------------------------------- | ----------------- |
| GET        | /patient/dashboard | Closure (renders Dashboard/Patient, with role check) | patient.dashboard |

**AI & Reporting Routes**

| **Method** | **URI**                           | **Controller / Action**            | **Name**                         | **Middleware** |
| ---------- | --------------------------------- | ---------------------------------- | -------------------------------- | -------------- |
| POST       | /api/ai/abstract/{patient}        | AiController@generateAbstract      | \--                              | None           |
| POST       | /api/ai/feedback/{patient}        | AiController@submitFeedback        | \--                              | None           |
| POST       | /api/ai/verify-report             | AiController@verifyAndLearnReport  | \--                              | None           |
| GET        | /doctor/reports/monthly           | AiController@showMonthlyReport     | doctor.reports.monthly           | None           |
| POST       | /doctor/reports/monthly           | AiController@generateMonthlyReport | \--                              | throttle:3,1   |
| PUT        | /doctor/reports/monthly/narrative | AiController@updateNarrative       | doctor.reports.monthly.narrative | throttle:10,1  |

**System Admin**

| **Method** | **URI**             | **Controller / Action**    | **Name**           |
| ---------- | ------------------- | -------------------------- | ------------------ |
| GET        | /admin/dashboard    | AdminController@index      | admin.dashboard    |
| POST       | /admin/users        | AdminController@storeUser  | admin.users.store  |
| PUT        | /admin/users/{user} | AdminController@updateUser | admin.users.update |
| DELETE     | /admin/users/{user} | AdminController@deleteUser | admin.users.delete |

**User Profile (all roles)**

| **Method** | **URI**           | **Controller / Action**          | **Name**         |
| ---------- | ----------------- | -------------------------------- | ---------------- |
| GET        | /profile          | ProfileController@show           | profile.show     |
| PUT        | /profile          | ProfileController@update         | profile.update   |
| PUT        | /profile/password | ProfileController@updatePassword | profile.password |

**Appointment Actions**

| **Method** | **URI**                                                     | **Controller / Action**                    | **Name**                        |
| ---------- | ----------------------------------------------------------- | ------------------------------------------ | ------------------------------- |
| POST       | /patient/appointments                                       | AppointmentController@store                | appointments.store              |
| POST       | /patient/appointments/{appointment}/cancel                  | AppointmentController@cancelAppointment    | appointments.cancel             |
| POST       | /patient/appointments/{appointment}/request-reschedule      | AppointmentController@requestReschedule    | appointments.request-reschedule |
| POST       | /receptionist/appointments/{appointment}/approve-reschedule | AppointmentController@approveReschedule    | appointments.approve-reschedule |
| POST       | /receptionist/appointments/{appointment}/deny-reschedule    | AppointmentController@denyReschedule       | appointments.deny-reschedule    |
| POST       | /receptionist/appointments/{appointment}/edit-reschedule    | AppointmentController@editReschedule       | appointments.edit-reschedule    |
| POST       | /appointments/{appointment}/follow-up                       | AppointmentController@storeFollowUp        | appointments.follow-up          |
| PUT        | /appointments/{appointment}/status                          | AppointmentController@updateStatus         | appointments.status             |
| GET        | /appointments/create                                        | AppointmentController@create               | appointments.create             |
| POST       | /appointments/manual                                        | AppointmentController@storeManual          | appointments.manual             |
| GET        | /appointments/returning                                     | AppointmentController@createReturning      | appointments.returning          |
| POST       | /appointments/returning                                     | AppointmentController@storeReturning       | \--                             |
| POST       | /appointments/{appointment}/complete                        | AppointmentController@completeConsultation | appointments.complete           |
| PUT        | /appointments/{appointment}/transfer                        | AppointmentController@transfer             | appointments.transfer           |
| PUT        | /appointments/{appointment}/reschedule                      | AppointmentController@reschedule           | appointments.reschedule         |

**Medication Adherence**

| **Method** | **URI**                              | **Controller / Action**        | **Name**              |
| ---------- | ------------------------------------ | ------------------------------ | --------------------- |
| POST       | /medications/{appointment}/log-dose  | MedicationController@logDose   | medications.log-dose  |
| GET        | /medications/{appointment}/adherence | MedicationController@adherence | medications.adherence |
| GET        | /patient/medications/today           | MedicationController@todayMeds | medications.today     |
| GET        | /patient/medications/history         | MedicationController@history   | medications.history   |

**Audit & PDF**

| **Method** | **URI**                          | **Controller / Action**                                                | **Name**           | **Middleware** |
| ---------- | -------------------------------- | ---------------------------------------------------------------------- | ------------------ | -------------- |
| POST       | /audit/medication-viewed         | Closure (creates AuditLog record)                                      | \--                | None           |
| GET        | /doctor/reports/monthly/pdf      | PdfController@downloadMonthlyReport                                    | pdf.monthly-report | throttle:6,1   |
| GET        | /doctor/pdf/{appointment}/{type} | PdfController@download (where type = echo\|tmt\|doppler\|prescription) | pdf.download       | None           |

#### 2.2 API Routes (routes/api.php)

All API routes are loaded under the api middleware group (set via RouteServiceProvider or bootstrap/app.php).

| **Method** | **URI**                   | **Controller / Action**                     | **Middleware**   |
| ---------- | ------------------------- | ------------------------------------------- | ---------------- |
| GET        | /api/user                 | Closure (returns \$request->user())         | auth:sanctum     |
| POST       | /api/rag/ingest           | RAGReportController@ingestPatientData       | None (api group) |
| POST       | /api/rag/narrative-report | RAGReportController@generateNarrativeReport | None (api group) |
| POST       | /api/rag/abstract-summary | RAGReportController@generateAbstractSummary | None (api group) |
| POST       | /api/rag/retrieve         | RAGReportController@retrieveContext         | None (api group) |
| GET        | /api/rag/health           | RAGReportController@healthCheck             | None (api group) |

**Broken include:** api.php tries to include \_\_DIR\_\_ . '/../rag-routes.php' but this file does not exist.

#### 2.3 Settings Routes (routes/settings.php)

| **Method**     | **URI**              | **Controller / Action**              | **Name**             | **Middleware**               |
| -------------- | -------------------- | ------------------------------------ | -------------------- | ---------------------------- |
| GET (redirect) | /settings            | Redirects to /settings/profile       | \--                  | auth                         |
| GET            | /settings/profile    | Settings\\ProfileController@edit     | profile.edit         | auth                         |
| PATCH          | /settings/profile    | Settings\\ProfileController@update   | profile.update       | auth                         |
| DELETE         | /settings/profile    | Settings\\ProfileController@destroy  | profile.destroy      | auth, verified               |
| GET            | /settings/security   | Settings\\SecurityController@edit    | security.edit        | auth, verified               |
| PUT            | /settings/security   | Settings\\SecurityController@update  | user-password.update | auth, verified, throttle:6,1 |
| GET            | /settings/appearance | Inertia render (settings/Appearance) | appearance.edit      | auth, verified               |

#### 2.4 Broadcasting Channels (routes/channels.php)

| **Channel Name** | **Authorization Logic**                     | **Guard** |
| ---------------- | ------------------------------------------- | --------- |
| doctor.{id}      | User ID must match and role must be doctor  | web       |
| receptionist     | Role must be receptionist                   | web       |
| patient.{id}     | User ID must match and role must be patient | web       |

#### 2.5 Console / Schedules (routes/console.php)

| **Artisan Command**      | **Schedule**               | **Purpose**                                               |
| ------------------------ | -------------------------- | --------------------------------------------------------- |
| inspire                  | None (manual)              | Displays an inspiring quote                               |
| reports:generate-monthly | 1st of each month at 00:05 | Generates monthly performance statistics for every doctor |

### 3\. ROUTE GROUPS, PREFIXES, AND MIDDLEWARE STACKS

#### Groups defined in route files

| **Group**            | **Middleware**  | **Routes Contained**                                                                      |
| -------------------- | --------------- | ----------------------------------------------------------------------------------------- |
| web.php line 27      | guest           | Login, Register, Forgot/Reset Password                                                    |
| web.php line 50      | auth            | All authenticated routes (dashboards, appointments, medications, admin, AI, PDF, profile) |
| api.php line 24      | prefix rag      | All RAG report endpoints (/api/rag/...)                                                   |
| settings.php line 7  | auth            | Settings profile routes                                                                   |
| settings.php line 14 | auth + verified | Settings security, appearance, profile deletion                                           |

#### Fortify Routes (configured via config/fortify.php)

| **Setting**      | **Value**                                                        |
| ---------------- | ---------------------------------------------------------------- |
| Prefix           | \`\` (empty -- same as web routes)                               |
| Domain           | null                                                             |
| Middleware       | \['web'\]                                                        |
| Guard            | web                                                              |
| Home path        | /dashboard                                                       |
| Features enabled | resetPasswords(), emailVerification(), twoFactorAuthentication() |
| Rate limiters    | login (5/min per email+IP), two-factor (5/min)                   |

### 4\. MIDDLEWARE FILES

#### 4.1 Custom Application Middleware

##### app/Http/Middleware/HandleInertiaRequests.php

- **Purpose:** Inertia.js server-side integration.
- **Extends:** Inertia\\Middleware
- **Root view:** app (renders resources/views/app.blade.php)
- **Shared data globally to Vue:**
  - name (app name from config)
  - auth.user (currently logged-in user)
  - flash.success / flash.error (session flash messages)
  - sidebarOpen (derived from sidebar_state cookie, defaults to true)
- **Registered in:** web middleware group via bootstrap/app.php

##### app/Http/Middleware/SecurityHeaders.php

- **Purpose:** Hardens HTTP response headers against common web attacks.
- **Actions:**
  - Sets X-Frame-Options: DENY (prevents clickjacking)
  - Sets X-Content-Type-Options: nosniff (prevents MIME-sniffing)
  - Sets X-XSS-Protection: 1; mode=block (basic XSS filter)
  - Sets Referrer-Policy: strict-origin-when-cross-origin
  - Removes X-Powered-By header (hides server tech stack)
- **Note:** This middleware is defined but **not registered** in bootstrap/app.php -- it may be applied manually in controllers or not currently active.

##### app/Http/Middleware/HandleAppearance.php

- **Purpose:** Reads the appearance cookie and shares its value (system, light, or dark) globally to all Blade views via View::share().
- **Fallback:** Defaults to 'system' if cookie is absent.
- **Registered in:** web middleware group via bootstrap/app.php

#### 4.2 Third-party / Laravel Core Middleware Referenced

| **Middleware Alias**             | **Class / Origin**                                             | **Usage**                                                                               |
| -------------------------------- | -------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| guest                            | Illuminate\\Auth\\Middleware\\RedirectIfAuthenticated          | Login/register pages                                                                    |
| auth                             | Illuminate\\Auth\\Middleware\\Authenticate                     | All protected routes                                                                    |
| verified                         | Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified            | Settings security routes                                                                |
| throttle:N,M                     | Illuminate\\Routing\\Middleware\\ThrottleRequests              | Rate-limited endpoints (login, registration, password reset, AI reports, PDF downloads) |
| auth:sanctum                     | Laravel Sanctum                                                | API auth for /api/user                                                                  |
| AddLinkHeadersForPreloadedAssets | Illuminate\\Http\\Middleware\\AddLinkHeadersForPreloadedAssets | Inertia asset preloading                                                                |

### 5\. MIDDLEWARE CONFIGURATION (bootstrap/app.php)

Since this is Laravel 12 (no app/Http/Kernel.php), middleware configuration lives in bootstrap/app.php:

\->withRouting(
web: \__DIR_\_.'/../routes/web.php',
commands: \__DIR_\_.'/../routes/console.php',
health: '/up',
)
\->withMiddleware(function (Middleware \$middleware): void {
// Exclude these cookies from encryption
\$middleware->encryptCookies(except: \['appearance', 'sidebar_state'\]);
<br/>// Append custom middleware to the 'web' group
\$middleware->web(append: \[
HandleAppearance::class,
HandleInertiaRequests::class,
AddLinkHeadersForPreloadedAssets::class,
\]);
})
\->withExceptions(function (Exceptions \$exceptions): void {
// Redirect 419 (CSRF token expiry) to login instead of showing "Page Expired"
\$exceptions->render(function (HttpException \$e, Request \$request) {
if (\$e->getStatusCode() === 419) {
if (\$request->expectsJson() || \$request->isMethod('POST')) {
return redirect()->guest(route('login'))
\->with('message', 'Your session has expired. Please log in again.');
}
}
});
})

**Key observations:**

- No api routes are registered in bootstrap/app.php routing section (only web and commands). The api.php file may be loaded via a service provider or is currently **not loaded**.
- No global middleware is defined outside the web group.
- SecurityHeaders middleware is **not registered** in any global or group middleware stack.
- The health route at /up is registered for application health checks.

### 6\. SERVICE PROVIDERS

| **Provider**                             | **Role**                                                                                                                                                        |
| ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| App\\Providers\\AppServiceProvider       | Configures Carbon dates, prohibits destructive DB commands in production, sets password validation rules                                                        |
| App\\Providers\\BroadcastServiceProvider | Registers broadcasting routes, loads routes/channels.php                                                                                                        |
| App\\Providers\\FortifyServiceProvider   | Configures Fortify views (login, password reset, 2FA, email verification), rate limiters, and disables Fortify's registration in favor of custom AuthController |

### 7\. CONTROLLERS USED

| **Controller**               | **Namespace**                    | **Routes Served**                                                                      |
| ---------------------------- | -------------------------------- | -------------------------------------------------------------------------------------- |
| AuthController               | App\\Http\\Controllers           | Login, register, password reset, logout                                                |
| ReceptionistController       | App\\Http\\Controllers           | Receptionist dashboard                                                                 |
| DoctorController             | App\\Http\\Controllers           | Doctor dashboard, EMR, patient history, analytics                                      |
| ProfileController            | App\\Http\\Controllers           | User profile CRUD, password change                                                     |
| AppointmentController        | App\\Http\\Controllers           | Full appointment lifecycle (create, cancel, reschedule, transfer, complete, follow-up) |
| AdminController              | App\\Http\\Controllers           | Admin dashboard, user management CRUD                                                  |
| MedicationController         | App\\Http\\Controllers           | Dose logging, adherence tracking, patient medication views                             |
| AiController                 | App\\Http\\Controllers           | AI abstract generation, feedback, monthly report generation/verification               |
| PdfController                | App\\Http\\Controllers           | PDF exports (monthly report, echo, TMT, doppler, prescription)                         |
| RAGReportController          | App\\Http\\Controllers           | RAG ingestion, narrative reports, abstract summaries, context retrieval                |
| Settings\\ProfileController  | App\\Http\\Controllers\\Settings | Settings profile edit/update/destroy                                                   |
| Settings\\SecurityController | App\\Http\\Controllers\\Settings | Settings security/password update                                                      |

### 8\. ISSUES FOUND

1\. **Missing file \`rag-routes.php\`** -- Referenced via include in routes/api.php (line 17) but does not exist on disk. This will cause a fatal error if the API routes group is loaded.

2\. **\`api\` routes not registered** in bootstrap/app.php -- The withRouting() call only registers web and commands route files, not api. Unless another provider loads them, the API routes (including the broken include) are not active.

3\. **\`SecurityHeaders\` middleware defined but never registered** -- It exists in app/Http/Middleware/SecurityHeaders.php but is not appended to any middleware group in bootstrap/app.php nor used as route middleware. It is effectively dead code.

4\. **Role-based authorization is minimal** -- Only the patient dashboard closure performs an explicit abort(403) role check. All other routes rely on implicit role-based guards (if any), which are not visible in the route definitions.&lt;/task_result&gt;

**Tool Call: Map migrations and services**
Status: Completed

task_id: ses_18c8d6d60ffekToitSMKyeCDyL (for resuming to continue this task if needed)

I have now read all files. Here is the complete structured summary.

# COMPLETE STRUCTURED SUMMARY

## PART 1: MIGRATIONS (24 files)

### Table: users (and related tables password_reset_tokens, sessions)

| **File**                                                    | **Action**                       | **Columns**                                                                                                                                                                                                                                                          |
| ----------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0001_01_01_000000_create_users_table.php                    | **CREATE** users                 | id (bigIncrements), name (string), email (string, unique), email_verified_at (timestamp, nullable), password (string), role (string, default:'patient'), specialty (string, nullable), allergies (text, nullable), rememberToken, created_at/updated_at (timestamps) |
| ^^                                                          | **CREATE** password_reset_tokens | email (string, primary), token (string), created_at (timestamp, nullable)                                                                                                                                                                                            |
| ^^                                                          | **CREATE** sessions              | id (string, primary), user_id (foreignId, nullable, indexed), ip_address (string(45), nullable), user_agent (text, nullable), payload (longText), last_activity (integer, indexed)                                                                                   |
| 2025_08_14_170933_add_two_factor_columns_to_users_table.php | **ALTER** users -- ADD           | two_factor_secret (text, nullable, after password), two_factor_recovery_codes (text, nullable), two_factor_confirmed_at (timestamp, nullable)                                                                                                                        |
| 2026_05_04_132316_add_profile_fields_to_users_table.php     | **ALTER** users -- ADD           | age (unsignedTinyInteger, nullable, after allergies), sex (string(10), nullable, values: Male/Female/Other), contact (string(30), nullable)                                                                                                                          |

### Table: cache (and cache_locks)

| **File**                                 | **Action**             | **Columns**                                                              |
| ---------------------------------------- | ---------------------- | ------------------------------------------------------------------------ |
| 0001_01_01_000001_create_cache_table.php | **CREATE** cache       | key (string, primary), value (mediumText), expiration (integer, indexed) |
| ^^                                       | **CREATE** cache_locks | key (string, primary), owner (string), expiration (integer, indexed)     |

### Table: jobs (and job_batches, failed_jobs)

| **File**                                | **Action**             | **Columns**                                                                                                                                                                                                                                                  |
| --------------------------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 0001_01_01_000002_create_jobs_table.php | **CREATE** jobs        | id (bigIncrements), queue (string), payload (longText), attempts (unsignedTinyInteger), reserved_at (unsignedInteger, nullable), available_at (unsignedInteger), created_at (unsignedInteger); Index: \[queue, reserved_at, available_at\]                   |
| ^^                                      | **CREATE** job_batches | id (string, primary), name (string), total_jobs (integer), pending_jobs (integer), failed_jobs (integer), failed_job_ids (longText), options (mediumText, nullable), cancelled_at (integer, nullable), created_at (integer), finished_at (integer, nullable) |
| ^^                                      | **CREATE** failed_jobs | id (bigIncrements), uuid (string, unique), connection (text), queue (text), payload (longText), exception (longText), failed_at (timestamp, useCurrent)                                                                                                      |

### Table: rag_reports

| **File**                                       | **Action**             | **Columns**                                                                                                                                                                                                                                                                                      |
| ---------------------------------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2024_01_01_000000_create_rag_reports_table.php | **CREATE** rag_reports | id (bigIncrements), patient_id (unsignedBigInteger, FK->users, cascade), report_type (enum: monthly_narrative, abstract_summary), month (integer, nullable), year (integer, nullable), content (longText), metadata (json, nullable), timestamps; Index: \[patient_id, report_type, created_at\] |

### Table: appointments

| **File**                                                           | **Action**              | **Columns**                                                                                                                                                                                                                                                          |
| ------------------------------------------------------------------ | ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026_03_17_041133_create_appointments_table.php                    | **CREATE** appointments | id (bigIncrements), patient_id (foreignId->users, cascade), doctor_id (foreignId->users, cascade), date (date), time (time, nullable), service_type (string, default:'General Cardiology'), status (string, default:'Scheduled'), notes (text, nullable), timestamps |
| 2026_03_21_135241_add_soap_notes_to_appointments_table.php         | **ALTER** -- ADD        | symptoms (text, nullable), vitals (text, nullable), diagnosis (text, nullable), consultation_type (string, nullable, for Type 1/2/3), prescriptions (json, nullable), ai_summary (text, nullable)                                                                    |
| 2026_04_08_094701_add_echo_data_to_appointments.php                | **ALTER** -- ADD        | echo_data (json, nullable)                                                                                                                                                                                                                                           |
| 2026_04_10_103111_add_new_forms_to_appointments.php                | **ALTER** -- ADD        | tmt_data (json, nullable), doppler_data (json, nullable)                                                                                                                                                                                                             |
| 2026_05_03_132437_add_google_event_to_appointments_table.php       | **ALTER** -- ADD        | google_event_id (string, nullable, after notes), google_event_url (string, nullable, after google_event_id)                                                                                                                                                          |
| 2026_05_04_130959_add_reschedule_request_to_appointments_table.php | **ALTER** -- ADD        | reschedule_requested_date (date, nullable, after google_event_url), reschedule_requested_time (time, nullable)                                                                                                                                                       |
| 2026_05_10_082229_convert_clinical_columns_to_xml.php              | **ALTER** -- MODIFY     | Changed echo_data, tmt_data, doppler_data from **JSON to LONGTEXT** (MySQL) or **TEXT** (PostgreSQL). Also converts existing JSON rows to XML using ClinicalXmlService.                                                                                              |
| 2026_05_29_062804_add_duration_to_appointments_table.php           | **ALTER** -- ADD        | duration_minutes (unsignedSmallInteger, default:30, after time)                                                                                                                                                                                                      |

### Table: audit_logs

| **File**                                      | **Action**            | **Columns**                                                                                                                                                      |
| --------------------------------------------- | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026_03_17_041353_create_audit_logs_table.php | **CREATE** audit_logs | id (bigIncrements), user_id (foreignId->users, nullable, nullOnDelete), action (string), description (text, nullable), ip_address (string, nullable), timestamps |

### Performance Indexes (applied to multiple tables)

| **File**                                         | **Action**      | **Details**                                                                                                                                       |
| ------------------------------------------------ | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026_03_18_000000_create_performance_indexes.php | **ADD INDEXES** | appointments: composite \[patient_id, date, status\] + \[doctor_id, date\]; audit_logs: \[created_at\] + \[user_id, created_at\]; users: \[role\] |

### Table: doctor_schedules

| **File**                                                         | **Action**                  | **Columns**                                                                                                                   |
| ---------------------------------------------------------------- | --------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| 2026_04_09_055349_create_doctor_schedules_table.php              | **CREATE** doctor_schedules | id (bigIncrements), doctor_id (foreignId->users, cascade), day_of_week (json), start_time (time), end_time (time), timestamps |
| 2026_05_17_010438_add_slot_duration_to_doctor_schedules.php      | **ALTER** -- ADD            | slot_duration_minutes (unsignedSmallInteger, default:60, after end_time)                                                      |
| 2026_05_28_050027_remove_slot_duration_from_doctor_schedules.php | **ALTER** -- DROP           | Removed slot_duration_minutes                                                                                                 |

### Table: monthly_reports

| **File**                                                           | **Action**                           | **Columns**                                                                                                                                                                                                       |
| ------------------------------------------------------------------ | ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026_04_17_084836_create_monthly_reports_table.php                 | **CREATE** monthly_reports           | id (bigIncrements), timestamps (only initial skeleton)                                                                                                                                                            |
| 2026_05_03_133739_add_columns_to_monthly_reports_table.php         | **ALTER** -- ADD                     | doctor_id (foreignId->users, cascade, after id), month (string(7), after doctor_id, e.g. "2026-05"), report_text (longText, after month), stats (json, nullable, after report_text); Unique: \[doctor_id, month\] |
| 2026_05_08_135616_make_report_text_nullable_in_monthly_reports.php | **ALTER** -- MODIFY                  | Changed report_text from longText (required) to longText **nullable**                                                                                                                                             |
| 2026_05_22_000001_drop_unique_on_monthly_reports.php               | **ALTER** -- DROP UNIQUE / ADD INDEX | Dropped unique \[doctor_id, month\] constraint, replaced with plain index \[doctor_id, month\]                                                                                                                    |

### Table: medication_logs

| **File**                                           | **Action**                 | **Columns**                                                                                                                                                                                                                                                                                                                          |
| -------------------------------------------------- | -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2026_05_28_074446_create_medication_logs_table.php | **CREATE** medication_logs | id (bigIncrements), appointment_id (foreignId->appointments, cascade), prescription_index (unsignedTinyInteger), medicine (string), log_date (date), scheduled_time (time, nullable), taken_at (timestamp, useCurrent), timestamps; Unique: \[appointment_id, prescription_index, log_date, scheduled_time\] (named unique_dose_log) |

## PART 2: SERVICES (4 files in app/Services/)

### 1\. GoogleCalendarService.php

**Class:** App\\Services\\GoogleCalendarService

| **Public Method**                              | **Description**                                                                                                                                                                                                                                                                                                              |
| ---------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| createEvent(Appointment \$appointment): ?array | Creates a Google Calendar event for an appointment. Returns array with id and url on success, null on failure. Builds title ("{service} -- {patient} with {doctor}"), description with clinic details, and sets start/end DateTime based on appointment date/time + duration_minutes. Uses Spatie's Google Calendar package. |
| updateEvent(Appointment \$appointment): void   | Updates an existing Google Calendar event (e.g., after reschedule or transfer). Finds event by google_event_id, updates name, description, start/end. Returns silently if not configured or no event ID.                                                                                                                     |
| updateStatus(Appointment \$appointment): void  | Updates the event title/description to reflect a new status (Cancelled, No-Show, Completed). Prepends \[STATUS\] prefix to the title for non-Scheduled statuses.                                                                                                                                                             |
| deleteEvent(string \$eventId): void            | Deletes a Google Calendar event entirely by its ID. Silently handles cases where the event does not exist.                                                                                                                                                                                                                   |

**Private helpers:** isConfigured() (checks google-calendar.calendar_id config), buildTitle(), buildDescription(), buildStart(), buildEnd().

### 2\. MonthlyReportService.php

**Class:** App\\Services\\MonthlyReportService

| **Public Method**                                      | **Description**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| ------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| static generate(int \$doctorId, string \$month): array | **Core method.** Builds aggregated stats for a doctor's month (appointments filtered by doctor_id + date LIKE "\$month%"). Computes: total/completed/no-shows/cancelled/pending counts, completion/no-show rates, demographics (unique patients, male/female/other split, avg age), procedure counts (Type 1=Echo, Type 2=TMT, Type 3=Doppler), top diagnoses (top 5), top services (top 5). Also fetches previous month's report for comparison deltas. Optionally generates an AI narrative via OpenRouter API (DeepSeek model) with a structured prompt. Persists results as a new MonthlyReport row. Returns \`\['stats' => array, 'report_text' => string |

**Private helpers:** generateNarrative() (calls OpenRouter API with a detailed prompt instructing AI to write formal paragraphs using only the provided data; includes pre-computed month-over-month deltas; returns null if API key missing), emptyStats() (returns zeroed-out stats skeleton).

### 3\. ClinicalJsonService.php

**Class:** App\\Services\\ClinicalJsonService

| **Public Method**                                   | **Description**                                                                                                                                                                                                                                                                         |
| --------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| static encode(array \$data): string                 | Encodes a clinical data array as compact JSON for DB storage (uses JSON_UNESCAPED_UNICODE).                                                                                                                                                                                             |
| static decode(string \$value, string \$type): array | Smart decoder that handles both JSON and XML formats. First checks if data starts with { or \[ (JSON) and decodes it. If it starts with < (XML legacy), delegates to ClinicalXmlService::decode(). Returns empty array on failure. The \$type parameter is 'echo', 'tmt', or 'doppler'. |

### 4\. ClinicalXmlService.php

**Class:** App\\Services\\ClinicalXmlService

**Purpose:** Converts clinical form data between PHP arrays and structured XML for three procedure types.

| **Public Method**                                   | **Description**                                                                                                                                                                                                                                                                            |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| static echoToXml(array \$d): string                 | Converts an echo (Type 1) data array to &lt;EchocardiographicReport&gt; XML. Sections: PatientInfo, MitralValve, IVSeptum, TricuspidValve, AorticValve, LVPosteriorWall, RightAtrium, PulmonicValve, LeftAtrium, RightVentricle, LeftVentricle, PericardialSpace, OutflowTracts, Findings. |
| static xmlToEcho(string \$xml): array               | Converts &lt;EchocardiographicReport&gt; XML back to flat associative array via flattenLeaves().                                                                                                                                                                                           |
| static tmtToXml(array \$d): string                  | Converts TMT (Type 2) data array to &lt;TreadmillExerciseTest&gt; XML. Sections: PatientInfo, Consent, Protocol, Stages (each Stage has attributes name/speed/grade/mets + child elements bp/hr/remarks), PeakExercise, STSegment, Interpretation.                                         |
| static xmlToTmt(string \$xml): array                | Converts &lt;TreadmillExerciseTest&gt; XML back to array. Uses flattenLeaves() for simple fields, then separately parses &lt;Stages&gt; elements (which use XML attributes) into a structured stages array.                                                                                |
| static dopplerToXml(array \$d): string              | Converts Doppler (Type 3) data array to &lt;ColorFlowDopplerStudy&gt; XML. Sections: MitralValve, TissueDoppler, TricuspidValve, AorticValve, PulmonicValve, Ratio, Conclusion.                                                                                                            |
| static xmlToDoppler(string \$xml): array            | Converts &lt;ColorFlowDopplerStudy&gt; XML back to flat array via flattenLeaves().                                                                                                                                                                                                         |
| static decode(string \$value, string \$type): array | Unified decoder: if input starts with <, routes to the appropriate xmlTo\* method by type (echo, tmt, doppler). If input starts with { or \[, falls back to JSON decoding for legacy data.                                                                                                 |

**Private helpers:** createDoc() (DOMDocument factory), section() (appends named section with leaf fields), appendLeaves() (appends text child elements), flattenLeaves() (recursively traverses XML collecting leaf values), traverseLeaves() (recursive XML walker that skips &lt;Stage&gt; nodes).

## PART 3: PROVIDERS (3 files in app/Providers/)

### 1\. AppServiceProvider.php

**Class:** App\\Providers\\AppServiceProvider

| **Method**          | **What it registers / boots**                                                                                                                                                                                                                                                                                      |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| register()          | Empty -- no custom bindings.                                                                                                                                                                                                                                                                                       |
| boot()              | Calls configureDefaults().                                                                                                                                                                                                                                                                                         |
| configureDefaults() | Sets Date::use(CarbonImmutable::class) for immutable dates. Prohibits destructive DB commands (migrations truncate/drop) in production. Configures password defaults: in production, requires min 12 chars with mixed case, letters, numbers, symbols, and uncompromised check; in non-production, no constraints. |

### 2\. BroadcastServiceProvider.php

**Class:** App\\Providers\\BroadcastServiceProvider

| **Method** | **What it registers / boots**                                                                                                    |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------- |
| boot()     | Registers broadcast routes via Broadcast::routes(). Requires the routes/channels.php file for channel authorization definitions. |

### 3\. FortifyServiceProvider.php

**Class:** App\\Providers\\FortifyServiceProvider

**Purpose:** Configures Laravel Fortify (authentication backend).

| **Method**              | **What it registers / boots**                                                                                                                                                                                                                                                                                                               |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| register()              | Empty.                                                                                                                                                                                                                                                                                                                                      |
| boot()                  | Calls configureActions(), configureViews(), configureRateLimiting().                                                                                                                                                                                                                                                                        |
| configureActions()      | Registers ResetUserPassword action for password resets. Notes that CreateNewUser is disabled (commented out) in favor of a custom AuthController@register.                                                                                                                                                                                  |
| configureViews()        | Configures Inertia views for: Login (auth/Login), Reset Password (auth/ResetPassword), Forgot Password (auth/ForgotPassword), Email Verification (auth/VerifyEmail), Registration (Auth/Register), Two-Factor Challenge (auth/TwoFactorChallenge), Confirm Password (auth/ConfirmPassword). Passes relevant session data and feature flags. |
| configureRateLimiting() | Sets rate limits: 5 attempts per minute for login (keyed by username+IP) and 5 per minute for two-factor authentication (keyed by session login.id).                                                                                                                                                                                        |

## SUMMARY OF DATABASE SCHEMA (8 core tables)

| **Table**                        | **Primary Purpose**                                                                                                                                 |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| users                            | All users (patients, doctors, receptionists, admins) with role-based fields, 2FA, and profile fields                                                |
| password_reset_tokens            | Password reset tokens                                                                                                                               |
| sessions                         | User sessions                                                                                                                                       |
| cache / cache_locks              | Cache storage                                                                                                                                       |
| jobs / job_batches / failed_jobs | Queue job management                                                                                                                                |
| appointments                     | Core clinical appointments with SOAP notes, clinical data (echo/TMT/Doppler as XML), Google Calendar integration, rescheduling fields, and duration |
| audit_logs                       | HIPAA-compliant audit trail of user actions                                                                                                         |
| doctor_schedules                 | Doctor availability with day-of-week JSON and time ranges                                                                                           |
| monthly_reports                  | AI-generated monthly performance reports with aggregated stats per doctor                                                                           |
| medication_logs                  | Medication adherence tracking per prescription per appointment                                                                                      |
| rag_reports                      | RAG (Retrieval-Augmented Generation) reports: monthly narratives and abstract summaries per patient                                                 |

**Total: 8 application tables** (plus 6 Laravel framework tables: password_reset_tokens, sessions, cache, cache_locks, job_batches, failed_jobs) = **14 total tables** managed by 24 migration files.
&lt;/task_result&gt;

## Assistant

Now I have all the data I need to compile a comprehensive Laravel backend map. Let me organize it into a clear, readable format.

Here is the complete Laravel backend map:

## 🗺️ LARAVEL BACKEND MAP - Cardiovascular Clinic Management System

**Stack:** Laravel 12.0 · PHP 8.2 · PostgreSQL · Inertia.js 2.0 · Vue 3 · Reverb (WebSockets) · Sanctum · Fortify 1.30 · DomPDF

### 1\. DATABASE SCHEMA (8 application tables + 6 framework tables)

| **Table**                                                                                        | **Purpose**                                   | **Key Columns**                                                                                                                                                                                                                          |
| ------------------------------------------------------------------------------------------------ | --------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **users**                                                                                        | All users (patient/doctor/receptionist/admin) | name, email, password, role, specialty, allergies, age, sex, contact, two_factor_secret                                                                                                                                                  |
| **appointments**                                                                                 | Core clinical appointments                    | patient_id, doctor_id, date, time, duration_minutes, service_type, status, symptoms, vitals, diagnosis, consultation_type, prescriptions, echo_data, tmt_data, doppler_data, ai_summary, google_event_id, reschedule_requested_date/time |
| **doctor_schedules**                                                                             | Recurring weekly schedules                    | doctor_id, day_of_week (json), start_time, end_time                                                                                                                                                                                      |
| **audit_logs**                                                                                   | HIPAA-compliant audit trail                   | user_id, action, description, ip_address                                                                                                                                                                                                 |
| **monthly_reports**                                                                              | AI-generated monthly stats per doctor         | doctor_id, month, report_text, stats (json)                                                                                                                                                                                              |
| **medication_logs**                                                                              | Medication adherence tracking                 | appointment_id, prescription_index, medicine, log_date, scheduled_time, taken_at                                                                                                                                                         |
| **rag_reports**                                                                                  | RAG-generated patient narratives/summaries    | patient_id, report_type, month, year, content, metadata                                                                                                                                                                                  |
| _Framework:_ password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs |                                               |                                                                                                                                                                                                                                          |

### 2\. MODELS (7) - app/Models/

| **Model**                  | **Table**        | **Fillable Highlights**                                                                                                                                                                        | **Key Relationships**                                                                                                         |
| -------------------------- | ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **User** (Authenticatable) | users            | name, email, role, specialty, age, sex, contact                                                                                                                                                | hasMany schedules, doctorAppointments, patientAppointments, appointments (conditional), auditLogs, monthlyReports, RAGReports |
| **Appointment**            | appointments     | patient_id, doctor_id, date, time, duration_minutes, service_type, status, notes, symptoms, vitals, diagnosis, consultation_type, prescriptions, echo_data, tmt_data, doppler_data, ai_summary | belongsTo patient (User), doctor (User); hasMany medicationLogs                                                               |
| **DoctorSchedule**         | doctor_schedules | doctor_id, day_of_week, start_time, end_time                                                                                                                                                   | belongsTo doctor (User)                                                                                                       |
| **AuditLog**               | audit_logs       | user_id, action, description, ip_address                                                                                                                                                       | belongsTo user (User)                                                                                                         |
| **MonthlyReport**          | monthly_reports  | doctor_id, month, report_text, stats                                                                                                                                                           | belongsTo doctor (User); scopes: forDoctor, forMonth                                                                          |
| **MedicationLog**          | medication_logs  | appointment_id, prescription_index, medicine, log_date, scheduled_time                                                                                                                         | belongsTo appointment                                                                                                         |
| **RAGReport**              | rag_reports      | patient_id, report_type, month, year, content, metadata                                                                                                                                        | belongsTo patient (User); scopes: monthlyNarrative, abstractSummary, forPatient, latest                                       |

### 3\. CONTROLLERS (12 concrete) - app/Http/Controllers/

| **Controller**                   | **Methods**                                                                                                                                                                                                                                                                                            | **Role Access**                          |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------- |
| **AuthController**               | create (login page), store (login), showRegistrationForm, register, destroy (logout), sendResetLink (forgot password), showResetForm, resetPassword                                                                                                                                                    | Guest / All                              |
| **AdminController**              | index (dashboard), storeUser, updateUser, deleteUser                                                                                                                                                                                                                                                   | Admin                                    |
| **ReceptionistController**       | dashboard (stats, queue, schedules, reschedule requests, future appointments)                                                                                                                                                                                                                          | Receptionist, Admin                      |
| **AppointmentController**        | store (patient books), storeManual (walk-in), storeReturning, storeFollowUp, create, createReturning, cancelAppointment, reschedule, requestReschedule, approveReschedule, denyReschedule, editReschedule, updateStatus, completeConsultation, transfer, storeSchedule, updateSchedule, deleteSchedule | Multi-role (patient/doctor/receptionist) |
| **DoctorController**             | dashboard, emr (patient list), patientHistory, patientAnalytics (JSON chart data)                                                                                                                                                                                                                      | Doctor                                   |
| **MedicationController**         | logDose, adherence, todayMeds, history, patientAdherence                                                                                                                                                                                                                                               | Patient, Doctor                          |
| **PdfController**                | download (echo/tmt/doppler/prescription PDFs), downloadMonthlyReport                                                                                                                                                                                                                                   | Doctor                                   |
| **AiController**                 | generateAbstract (AI + PubMed + PHA guidelines RAG), generateMonthlyReport, showMonthlyReport, updateNarrative, analyzeDocument (LlamaParse PDF), verifyAndLearnReport, submitFeedback                                                                                                                 | Doctor                                   |
| **RAGReportController**          | ingestPatientData, generateNarrativeReport, generateAbstractSummary, retrieveContext, healthCheck                                                                                                                                                                                                      | API (external RAG service proxy)         |
| **ProfileController** (root)     | show, update, updatePassword                                                                                                                                                                                                                                                                           | All                                      |
| **Settings\\ProfileController**  | edit, update, destroy (account deletion)                                                                                                                                                                                                                                                               | All                                      |
| **Settings\\SecurityController** | edit (2FA + password page), update (password change)                                                                                                                                                                                                                                                   | All                                      |

### 4\. SERVICES (4) - app/Services/

| **Service**               | **Public API**                                              | **Purpose**                                                                            |
| ------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| **GoogleCalendarService** | createEvent(), updateEvent(), updateStatus(), deleteEvent() | Syncs appointments to Google Calendar via service account (Spatie package)             |
| **MonthlyReportService**  | generate(doctorId, month) → \['stats','report_text'\]       | Aggregates monthly stats, calls OpenRouter (DeepSeek) for AI narrative, persists to DB |
| **ClinicalJsonService**   | encode(), decode(value, type)                               | Smart encoder/decoder: prefers JSON, fallback to XML for legacy data                   |
| **ClinicalXmlService**    | echoToXml(), tmtToXml(), dopplerToXml() + reverse decoders  | Converts clinical forms (echo/TMT/Doppler) to/from structured XML (DOMDocument)        |

### 5\. ROUTE MAP - routes/web.php (169 lines, 42 named routes)

GUEST (no middleware)
GET / → Welcome page
GET /login → AuthController@create
POST /login → AuthController@store \[throttle:5,1\]
GET /register → AuthController@showRegistrationForm
POST /register → AuthController@register \[throttle:5,10\]
GET /forgot-password → ForgotPassword page
POST /forgot-password → AuthController@sendResetLink \[throttle:5,60\]
GET /reset-password/{token} → AuthController@showResetForm
POST /reset-password → AuthController@resetPassword
<br/>AUTHENTICATED (all under \`auth\` middleware)
Receptionist:
GET /receptionist/dashboard → ReceptionistController@dashboard
POST /receptionist/schedules → AppointmentController@storeSchedule
PUT /receptionist/schedules/{schedule} → AppointmentController@updateSchedule
DELETE /receptionist/schedules/{schedule} → AppointmentController@deleteSchedule
<br/>Doctor:
GET /doctor/dashboard → DoctorController@dashboard
GET /doctor/emr → DoctorController@emr
GET /doctor/patients/{patient}/history → DoctorController@patientHistory
GET /doctor/patients/{patient}/analytics → DoctorController@patientAnalytics
GET /doctor/patients/{patient}/medication-adherence → MedicationController@patientAdherence
GET /doctor/reports/monthly → AiController@showMonthlyReport
POST /doctor/reports/monthly → AiController@generateMonthlyReport \[throttle:3,1\]
PUT /doctor/reports/monthly/narrative → AiController@updateNarrative \[throttle:10,1\]
GET /doctor/reports/monthly/pdf → PdfController@downloadMonthlyReport \[throttle:6,1\]
GET /doctor/pdf/{appointment}/{type} → PdfController@download
<br/>Patient:
GET /patient/dashboard → Closure (Patient dashboard)
GET /patient/medications/today → MedicationController@todayMeds
GET /patient/medications/history → MedicationController@history
POST /patient/appointments → AppointmentController@store
POST /patient/appointments/{appt}/cancel → AppointmentController@cancelAppointment
POST /patient/appointments/{appt}/request-reschedule → AppointmentController@requestReschedule
<br/>Admin:
GET /admin/dashboard → AdminController@index
POST /admin/users → AdminController@storeUser
PUT /admin/users/{user} → AdminController@updateUser
DELETE /admin/users/{user} → AdminController@deleteUser
<br/>Appointments (shared):
GET /appointments/create → AppointmentController@create
POST /appointments/manual → AppointmentController@storeManual
GET /appointments/returning → AppointmentController@createReturning
POST /appointments/returning → AppointmentController@storeReturning
POST /appointments/{appt}/follow-up → AppointmentController@storeFollowUp
POST /appointments/{appt}/complete → AppointmentController@completeConsultation
PUT /appointments/{appt}/status → AppointmentController@updateStatus
PUT /appointments/{appt}/transfer → AppointmentController@transfer
PUT /appointments/{appt}/reschedule → AppointmentController@reschedule
POST /receptionist/appointments/{appt}/approve-reschedule → AppointmentController@approveReschedule
POST /receptionist/appointments/{appt}/deny-reschedule → AppointmentController@denyReschedule
POST /receptionist/appointments/{appt}/edit-reschedule → AppointmentController@editReschedule
<br/>Other:
GET /profile → ProfileController@show
PUT /profile → ProfileController@update
PUT /profile/password → ProfileController@updatePassword
POST /medications/{appt}/log-dose → MedicationController@logDose
GET /medications/{appt}/adherence → MedicationController@adherence
<br/>AI (unauthenticated):
POST /api/ai/abstract/{patient} → AiController@generateAbstract
POST /api/ai/feedback/{patient} → AiController@submitFeedback
POST /api/ai/verify-report → AiController@verifyAndLearnReport
POST /api/ai/upload-analyze → AiController@analyzeDocument

#### API Routes (routes/api.php)

POST /api/rag/ingest → RAGReportController@ingestPatientData
POST /api/rag/narrative-report → RAGReportController@generateNarrativeReport
POST /api/rag/abstract-summary → RAGReportController@generateAbstractSummary
POST /api/rag/retrieve → RAGReportController@retrieveContext
GET /api/rag/health → RAGReportController@healthCheck
GET /api/user → return \$request->user() \[auth:sanctum\]

#### Settings Routes (routes/settings.php)

GET /settings → redirect to /settings/profile
GET /settings/profile → Settings\\ProfileController@edit
PATCH /settings/profile → Settings\\ProfileController@update
DELETE /settings/profile → Settings\\ProfileController@destroy \[verified\]
GET /settings/security → Settings\\SecurityController@edit \[verified\]
PUT /settings/security → Settings\\SecurityController@update \[verified, throttle:6,1\]
GET /settings/appearance → Appearance page \[verified\]

#### Broadcasting Channels (routes/channels.php)

- doctor.{id} - user is doctor + matches ID
- receptionist - role is receptionist
- patient.{id} - user is patient + matches ID

### 6\. MIDDLEWARE & PROVIDERS

**Custom Middleware:**

| **Middleware**        | **Status**                        | **Purpose**                                            |
| --------------------- | --------------------------------- | ------------------------------------------------------ |
| HandleInertiaRequests | ✅ Active (web group)             | Shares auth.user, flash messages, sidebar state to Vue |
| HandleAppearance      | ✅ Active (web group)             | Reads appearance cookie → shared to Blade views        |
| SecurityHeaders       | ❌ Defined but **not registered** | Sets X-Frame-Options, X-XSS-Protection, etc.           |

**Service Providers:**

| **Provider**             | **Registers**                                                                                              |
| ------------------------ | ---------------------------------------------------------------------------------------------------------- |
| AppServiceProvider       | Immutable Carbon dates, prod DB protection, password rules                                                 |
| BroadcastServiceProvider | WebSocket broadcast channels                                                                               |
| FortifyServiceProvider   | Login/password-reset/2FA views, rate limiters (disables Fortify registration - uses custom AuthController) |

**Global Middleware Stack (web group):**
EncryptCookies → AddQueuedCookiesToResponse → StartSession → ShareErrorsFromSession → SubstituteBindings → **\`HandleAppearance\`** → **\`HandleInertiaRequests\`** → AddLinkHeadersForPreloadedAssets

**Rate Limits:** Login (5/min), Register (5/10min), Password Reset (5/60min), AI Reports (3/min), Narrative edits (10/min), PDF downloads (6/min), Password updates (6/min)

### 7\. IMPORTED/REFERENCED PACKAGES

| **Package**                    | **Usage**                                                      |
| ------------------------------ | -------------------------------------------------------------- |
| laravel/fortify                | Auth backend (login, 2FA, password reset, email verification)  |
| laravel/sanctum                | API token auth                                                 |
| laravel/reverb                 | WebSocket broadcasting                                         |
| inertiajs/inertia-laravel      | Server-side Inertia rendering                                  |
| spatie/laravel-google-calendar | Google Calendar API integration                                |
| barryvdh/laravel-dompdf        | PDF generation (echo/TMT/Doppler/prescription/monthly reports) |
| laravel/tinker                 | Artisan REPL                                                   |
| laravel/sail                   | Dev environment                                                |

### 8\. NOTABLE ARCHITECTURE DECISIONS

- **Clinical data storage**: echo_data, tmt_data, doppler_data stored as **XML in TEXT columns** (migrated from JSON). Transparently decoded in model accessors via ClinicalJsonService/ClinicalXmlService.
- **Appointment duration**: First visit = 90 min, follow-up = 30 min (determined by getDurationMinutes() helper in AppointmentController).
- **Google Calendar sync**: Service account (no OAuth) - attendee invites disabled permanently.
- **AI pipeline**: Triple-provider chain (DeepSeek Direct → HuggingFace → OpenRouter) with PubMed RAG and PHA guideline chunked retrieval.
- **Mail**: MailerSend via SMTP (commented out for production), MAIL_MAILER=log for local dev.
- **No authentication middleware on most routes** - access control relies on role-based logic inside controllers (only the patient dashboard closure explicitly abort(403) if wrong role).
