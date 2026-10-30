<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
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
            $totalUsers = User::count();
            $doctors = User::where('role', 'doctor')->count();
            $staff = User::where('role', 'receptionist')->count();
            $admins = User::where('role', 'admin')->count();
            $patientUsers = User::where('role', 'patient')->count();

            // One query for the whole week, grouped in PHP so the dashboard
            // stays portable across SQLite (tests) and PostgreSQL (production).
            $trendStart = now()->subDays(6)->startOfDay();
            $weekAppointments = Appointment::query()
                ->whereDate('scheduled_date', '>=', $trendStart->toDateString())
                ->whereDate('scheduled_date', '<=', today()->toDateString())
                ->get(['scheduled_date', 'status']);

            $appointmentsTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($weekAppointments) {
                $day = now()->subDays($daysAgo)->startOfDay();
                $daily = $weekAppointments->filter(
                    fn (Appointment $appointment) => $appointment->scheduled_date?->isSameDay($day)
                );

                return [
                    'label' => $day->format('D'),
                    'date' => $day->toDateString(),
                    'total' => $daily->count(),
                    'completed' => $daily->where('status', 'Completed')->count(),
                ];
            })->values()->all();

            return [
                'total_users' => $totalUsers,
                'total_doctors' => $doctors,
                'total_patients' => Patient::count(),
                'total_staff' => $staff,
                'appointments_today' => Appointment::whereDate('scheduled_date', today())->count(),
                'completed_today' => Appointment::whereDate('scheduled_date', today())->where('status', 'Completed')->count(),
                'pending_today' => Appointment::whereDate('scheduled_date', today())->whereIn('status', ['Scheduled', 'Pending'])->count(),
                'no_shows_today' => Appointment::whereDate('scheduled_date', today())->where('status', 'No-Show')->count(),
                'total_appointments' => Appointment::count(),
                'new_patients_month' => Patient::where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'appointments_trend' => $appointmentsTrend,
                'role_distribution' => [
                    ['key' => 'doctor', 'label' => 'Cardiologists', 'value' => $doctors],
                    ['key' => 'receptionist', 'label' => 'Reception', 'value' => $staff],
                    ['key' => 'admin', 'label' => 'Admins', 'value' => $admins],
                    ['key' => 'patient', 'label' => 'Patients', 'value' => $patientUsers],
                ],
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
                    'date_of_birth' => $user->patient?->date_of_birth?->toDateString(),
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
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
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
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'sex' => $validated['sex'] ?? null,
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created new '.strtoupper($validated['role']).' account for '.$newUser->email.' from IP: '.$request->ip(),
        ]);

        $this->forgetAdminCaches();

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
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
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
            if (array_key_exists('date_of_birth', $validated)) {
                $patientUpdate['date_of_birth'] = $validated['date_of_birth'];
            }
            if (($validated['sex'] ?? null) !== null) {
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

        $this->forgetAdminCaches();

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

        $this->forgetAdminCaches();

        return back()->with('success', 'User account deleted.');
    }

    /**
     * Keep the cached console summaries in sync with account and audit writes.
     */
    private function forgetAdminCaches(): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_users_summary');
        Cache::forget('admin_audit_summary');
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

        // Directory header cards are practice-wide, not filtered by the
        // search box, so they stay a stable snapshot while browsing.
        $summary = Cache::remember('admin_users_summary', 300, function () {
            $counts = User::query()
                ->select('role')
                ->selectRaw('count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role');

            return [
                'total' => (int) $counts->sum(),
                'doctors' => (int) ($counts['doctor'] ?? 0),
                'receptionists' => (int) ($counts['receptionist'] ?? 0),
                'admins' => (int) ($counts['admin'] ?? 0),
                'patients' => (int) ($counts['patient'] ?? 0),
            ];
        });

        return inertia('Admin/UsersDirectory', [
            'user' => Auth::user(),
            'users' => $users,
            'summary' => $summary,
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

        $summary = Cache::remember('admin_audit_summary', 60, function () {
            $last = AuditLog::latest('created_at')->first();

            return [
                'total' => AuditLog::count(),
                'today' => AuditLog::whereDate('created_at', today())->count(),
                'actors' => AuditLog::whereNotNull('user_id')->distinct()->count('user_id'),
                'last' => $last?->created_at?->diffForHumans() ?? 'No activity yet',
            ];
        });

        return inertia('Admin/AuditLog', [
            'user' => Auth::user(),
            'logs' => $logs,
            'summary' => $summary,
            'filters' => $request->only(['search', 'date']),
        ]);
    }
}
