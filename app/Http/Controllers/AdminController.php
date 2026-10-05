<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\MonthlyReport;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\MonthlyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * The audit log is append-only and reviewed a screen at a time, so a short
     * page keeps the table readable and the payload small.
     */
    private const AUDIT_LOG_PER_PAGE = 20;

    // 1. Load the Admin Dashboard
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'total_users' => User::count(),
                'total_doctors' => User::where('role', 'doctor')->count(),
                'total_patients' => Patient::count(),
                'total_staff' => User::whereIn('role', ['receptionist', 'admin'])->count(),
                'appointments_today' => Appointment::whereDate('scheduled_date', today())->count(),
                'completed_today' => Appointment::whereDate('scheduled_date', today())->where('status', 'Completed')->count(),
                'pending_today' => Appointment::whereDate('scheduled_date', today())->whereIn('status', ['Scheduled', 'Pending'])->count(),
                'no_shows_today' => Appointment::whereDate('scheduled_date', today())->where('status', 'No-Show')->count(),
                'total_appointments' => Appointment::count(),
                'new_patients_month' => Patient::where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ];
        });

        // Recent registrations (last 7 new users)
        $recentRegistrations = User::latest()->take(7)->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->anonymizedName(),
                'email' => $user->email,
                'role' => $user->role,
                'time' => $user->created_at->diffForHumans(),
            ];
        });

        // 🔒 SECURITY FIX: Data minimization - only show non-sensitive fields
        $users = User::with(['provider:id,user_id,specialty', 'patient:id,user_id,sex,date_of_birth'])
            ->orderBy('created_at', 'desc')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'system_id' => $user->patient_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'specialty' => $user->provider?->specialty ?? '',
                    'joined' => $user->created_at->format('M d, Y'),
                    'age' => $user->patient?->age,
                    'sex' => $user->patient?->sex,
                ];
            });

        // 🔒 SECURITY FIX: Extended audit log retention from 15 to 100 records
        // In production, this should be 5 years (365*5 days)
        $auditLogs = AuditLog::with('user:id,name')
            ->latest()
            ->take(100)  // Extended from 15
            ->get()
            ->map(function ($log) {
                return [
                    'user' => $log->user ? $log->user->name : 'System',
                    'action' => $log->action,
                    'time' => $log->created_at->diffForHumans(),
                    'timestamp' => $log->created_at->toDateTimeString(),  // HIPAA compliance
                ];
            });

        return inertia('Dashboard/Admin', [
            'user' => Auth::user(),
            'stats' => $stats,
            'recent' => $recentRegistrations,
        ]);
    }

    // 2. Create New Clinic Staff / Patients
    public function storeUser(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => ['required', Rule::in(['admin', 'receptionist', 'doctor', 'patient'])],
            'specialty' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:130'],
            'sex' => ['nullable', 'string', 'in:Male,Female'],
        ]);

        $newUser = User::create([
            'name' => ($validated['role'] === 'doctor' && ! str_starts_with($validated['name'], 'Dr.'))
                        ? 'Dr. '.$validated['name']
                        : $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Profile records mirror the auth-only user row.
        if ($validated['role'] === 'doctor') {
            Provider::create([
                'user_id' => $newUser->id,
                'specialty' => $validated['specialty'] ?? 'Cardiology',
            ]);
        } elseif ($validated['role'] === 'patient') {
            Patient::create([
                'user_id' => $newUser->id,
                'date_of_birth' => $validated['age'] !== null
                    ? now()->subYears((int) $validated['age'])
                    : null,
                'sex' => $validated['sex'] ?? null,
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created new '.strtoupper($validated['role']).' account for '.$newUser->email.' from IP: '.$request->ip(),
        ]);

        return back()->with('success', 'User account successfully created with secure password requirements!');
    }

    // 3. Edit an existing user
    public function updateUser(Request $request, User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'receptionist', 'doctor', 'patient'])],
            'specialty' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'age' => ['nullable', 'integer', 'min:1', 'max:130'],
            'sex' => ['nullable', 'string', 'in:Male,Female'],
            'status' => ['nullable', 'string', 'in:Register,Walk-in Register,Active,Inactive,Suspended'],
        ]);

        $data = [
            'name' => ($validated['role'] === 'doctor' && ! str_starts_with($validated['name'], 'Dr.'))
                           ? 'Dr. '.$validated['name']
                           : $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'] ?? $user->status,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        // Keep profile records in sync with the user's role.
        if ($validated['role'] === 'doctor') {
            if (! $user->provider) {
                Provider::create([
                    'user_id' => $user->id,
                    'specialty' => $validated['specialty'] ?? 'Cardiology',
                ]);
            } elseif (! empty($validated['specialty'])) {
                $user->provider->update(['specialty' => $validated['specialty']]);
            }
        } elseif ($validated['role'] === 'patient') {
            $patientUpdate = [];
            if ($validated['age'] !== null) {
                $patientUpdate['date_of_birth'] = now()->subYears((int) $validated['age']);
            }
            if ($validated['sex'] !== null) {
                $patientUpdate['sex'] = $validated['sex'];
            }
            if (! $user->patient) {
                Patient::create(array_merge(['user_id' => $user->id], $patientUpdate));
            } elseif (! empty($patientUpdate)) {
                $user->patient->update($patientUpdate);
            }
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated user account for '.$user->email,
        ]);

        return back()->with('success', 'User account updated successfully.');
    }

    // 4. Delete a user
    public function deleteUser(User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }
        if ($user->id === Auth::id()) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        $email = $user->email;
        // Remove profile rows first so FK constraints on related tables are satisfied.
        $user->patient?->delete();
        $user->provider?->delete();
        $user->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted user account: '.$email,
        ]);

        return back()->with('success', 'User account deleted.');
    }

    public function usersDirectory(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = User::with(['provider:id,user_id,specialty', 'patient:id,user_id,sex,date_of_birth'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term]);

                // patient_id is a computed accessor (PT-00001 / STAFF-n), not a DB column,
                // so match the numeric ID portion against the real `id` column.
                if (preg_match('/\d+/', $search, $m)) {
                    $q->orWhere('id', (int) $m[0]);
                }
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->through(function ($user) {
            return [
                'id' => $user->id,
                'system_id' => $user->patient_id,
                'name' => $user->anonymizedName(),
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'specialty' => $user->provider?->specialty ?? '',
                'joined' => $user->created_at->format('M d, Y'),
                'age' => $user->patient?->age,
                'sex' => $user->patient?->sex,
            ];
        });

        return inertia('Admin/UsersDirectory', [
            'user' => Auth::user(),
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function createAccount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        return inertia('Admin/CreateAccount', [
            'user' => Auth::user(),
        ]);
    }

    public function auditLog(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = AuditLog::with('user:id,name');

        if ($search = trim((string) $request->input('search'))) {
            $needle = '%'.mb_strtolower($search).'%';

            // Admins look up the log by what was done *or* who did it.
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(audit_logs.action) LIKE ?', [$needle])
                    ->orWhereHas('user', fn ($user) => $user->whereRaw('LOWER(users.name) LIKE ?', [$needle]));
            });
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        $logs = $query->latest()->paginate(self::AUDIT_LOG_PER_PAGE)->withQueryString()->through(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? $log->user->name : 'System',
                'action' => $log->action,
                'time' => $log->created_at->diffForHumans(),
                'timestamp' => $log->created_at->toDateTimeString(),
            ];
        });

        return inertia('Admin/AuditLog', [
            'user' => Auth::user(),
            'logs' => $logs,
            'filters' => $request->only(['search', 'date']),
        ]);
    }

    /**
     * Practice-wide monthly report: every doctor combined, plus a per-doctor
     * breakdown. Admin-only because the breakdown exposes colleagues'
     * individual activity.
     *
     * The route already carries role:admin; the explicit re-check guards
     * against the middleware ever being dropped from the route group.
     */
    public function practiceReport(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        // Viewing must never mutate data, so this reads the latest stored
        // practice row and only generates when the admin asks for it.
        $report = MonthlyReport::query()
            ->whereNull('provider_id')
            ->where('scope', MonthlyReport::SCOPE_PRACTICE)
            ->where('month', $month)
            ->latest()
            ->first();

        if (! $report) {
            return back()->with('error', "No practice report exists for {$month}. Generate one first.");
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => "Viewed practice-wide monthly report for {$month} from IP: ".$request->ip(),
        ]);

        $stats = $report->stats ?? [];

        return inertia('Admin/PracticeReport', [
            'user' => Auth::user(),
            'month' => $month,
            'stats' => $stats,
            'reportText' => $report->report_text,
            'generatedAt' => $report->created_at?->toDateTimeString(),
            // Recomputed on read so a doctor added after the report was written
            // still appears, without regenerating the narrative.
            'byProvider' => $stats['by_provider'] ?? MonthlyReportService::practiceBreakdown($month),
            'availableMonths' => MonthlyReport::query()
                ->whereNull('provider_id')
                ->where('scope', MonthlyReport::SCOPE_PRACTICE)
                ->distinct()
                ->orderByDesc('month')
                ->pluck('month'),
        ]);
    }

    /** Admin-triggered generation of the practice-wide narrative. */
    public function generatePracticeReport(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = $validated['month'];

        MonthlyReportService::generatePracticeReport($month);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => "Generated practice-wide monthly report for {$month} from IP: ".$request->ip(),
        ]);

        return back()->with('success', "Practice-wide report generated for {$month}.");
    }
}
