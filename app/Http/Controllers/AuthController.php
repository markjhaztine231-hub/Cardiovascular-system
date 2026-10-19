<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordMail;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Support\TermsOfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. Show the Login Page (THIS IS WHAT WAS MISSING!)
    public function create()
    {
        return inertia('Auth/Login');
    }

    // 2. Show the Registration Page
    public function showRegistrationForm()
    {
        return inertia('Auth/Register', [
            'terms' => [
                'title' => TermsOfService::shortTitle(),
                'version' => TermsOfService::version(),
                'effective_date' => TermsOfService::payload()['effective_date'] ?? null,
                'url' => route('terms'),
            ],
        ]);
    }

    // 2b. Show the published Terms of Service and Patient Agreement.
    // Deliberately reachable by guests and signed-in users alike: a patient who
    // is already registered must still be able to read the terms they accepted.
    public function showTerms()
    {
        return inertia('Legal/TermsOfService', [
            'terms' => TermsOfService::payload(),
            'version' => TermsOfService::version(),
        ]);
    }

    // 2. Process the Login Form
    public function store(Request $request)
    {
        // 🔒 SECURITY FIX: Never trust user-submitted role data
        // Normalise before lookup. Postgres compares varchar case- and
        // whitespace-sensitively, so "AICardiovascularManagement@gmail.com"
        // would miss the stored "aicardiovascularmanagement@gmail.com" and
        // surface as the generic "Authentication failed" message. Email
        // domains are case-insensitive in practice, so users reasonably
        // expect any casing to work.
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));

        // Case-insensitive, so accounts written before normalisation existed (any
        // casing, from the register form or the admin panel) still resolve.
        $user = User::whereEmail($credentials['email'])->first();

        // 🔒 If the account has two-factor authentication enabled, validate
        // the credentials first but require the 2FA challenge before login.
        if ($user && $user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)) {
            if (! Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'Authentication failed. Please check your credentials.',
                ]);
            }

            $this->assertAccountNotBlocked($user);

            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.login');
        }

        // Verify the password against the account we already resolved. This is used
        // instead of Auth::attempt() because the session guard's own credential
        // lookup is an exact match on email, which would reintroduce the
        // case-sensitivity we just removed.
        if ($user && Hash::check($credentials['password'], $user->password)) {
            $this->assertAccountNotBlocked($user);

            Auth::login($user, $request->boolean('remember'));
            $user = Auth::user();

            // 🔒 SECURITY FIX: Get role from database only, never from request
            if (! in_array($user->role, ['receptionist', 'doctor', 'patient', 'admin'])) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Invalid user role configuration.',
                ]);
            }

            // 🔒 SECURITY FIX: Regenerate session to prevent session fixation
            $request->session()->regenerate();

            // Log successful authentication
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'User logged in from IP: '.$request->ip(),
            ]);

            // Route to appropriate dashboard based on database role
            $dashboardRoute = $user->role.'.dashboard';

            return redirect()->route($dashboardRoute);
        }

        // 🔒 SECURITY FIX: Generic error message prevents user enumeration
        throw ValidationException::withMessages([
            'email' => 'Authentication failed. Please check your credentials.',
        ]);
    }

    // 3. Register a new Patient
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            // Demographics (sobriety of patients; dob approximated from age like storeManual)
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'sex' => ['nullable', 'string', 'in:Male,Female'],

            // 🔒 LEGAL FIX: Section 1.2 of the Agreement makes the checked box
            // the moment assent becomes binding, so it is enforced here rather
            // than in the form. The `accepted` rule rejects a missing key, a
            // false boolean, and the usual truthy strings ("false", "0"), none
            // of which an HTML `required` attribute alone would stop.
            'terms_accepted' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'patient',
            'terms_accepted_at' => now(),
            'terms_version' => TermsOfService::version(),
        ]);

        // auth-only users table: patient demographics live on the patients record
        Patient::create([
            'user_id' => $user->id,
            // `$validated` only carries keys that were actually present in the
            // request, so a client that omits age/sex entirely (rather than
            // sending null) would otherwise raise an undefined-array-key error.
            'date_of_birth' => ($validated['age'] ?? null) !== null
                ? now()->subYears((int) $validated['age'])
                : null,
            'sex' => $validated['sex'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'New patient registered: '.$user->email
                .' (accepted Terms v'.$user->terms_version.' from IP: '.$request->ip().')',
        ]);

        Auth::login($user);

        return redirect()->route('patient.dashboard');
    }

    // 4. Securely Sign Out
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditLog::create([
            'action' => 'User logged out from IP: '.$request->ip(),
        ]);

        return redirect('/');
    }

    // 🔒 SECURITY FIX: Show Password Reset Form
    public function showResetForm(Request $request, $token)
    {
        return inertia('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // 🔒 SECURITY FIX: Handle Password Reset Request
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email:rfc']]);

        $user = User::whereEmail($request->email)->first();

        if ($user) {
            // Generate reset token
            $token = Str::random(60);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send the password reset email synchronously. The app runs on
            // Elastic Beanstalk with QUEUE_CONNECTION=database and no queue
            // worker, so a queued mail would never be delivered.
            try {
                Mail::to($user->email)->send(new ForgotPasswordMail($user, $token));
            } catch (\Throwable $e) {
                Log::warning('Failed to send password reset email: '.$e->getMessage());
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'Password reset link requested from IP: '.$request->ip(),
            ]);
        }

        // Always return the same message — don't reveal whether the email exists
        return back()->with('status', 'If that email exists, a password reset link has been sent.');
    }

    // 🔒 SECURITY FIX: Reset Password with Token
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'token' => ['required'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = User::whereEmail($validated['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Email not found.']);
        }

        $resetRecord = DB::table('password_reset_tokens')
            ->whereEmail($validated['email'])
            ->first();

        if (! $resetRecord || ! Hash::check($validated['token'], $resetRecord->token)) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired reset token.']);
        }

        // Check token expiry against the configured password-reset window
        // (config/auth.php), which the reset email also advertises.
        $expireMinutes = (int) config('auth.passwords.users.expire', 60);
        if (Carbon::parse($resetRecord->created_at)->addMinutes($expireMinutes)->isPast()) {
            DB::table('password_reset_tokens')
                ->whereEmail($validated['email'])->delete();
            throw ValidationException::withMessages(['token' => 'Reset token has expired.']);
        }

        // Update password
        $user->update(['password' => Hash::make($validated['password'])]);

        // Delete reset token
        DB::table('password_reset_tokens')
            ->whereEmail($validated['email'])->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Password reset completed from IP: '.$request->ip(),
        ]);

        return redirect('/login')->with('status', 'Password reset successfully. Please log in.');
    }

    /**
     * Block authentication for suspended/inactive accounts. Called only after
     * the password has been verified so the status is not revealed to someone
     * guessing credentials.
     */
    private function assertAccountNotBlocked(User $user): void
    {
        if (! $user->isBlocked()) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Blocked login attempt for '.strtolower($user->status)
                .' account from IP: '.request()->ip(),
        ]);

        throw ValidationException::withMessages([
            'email' => 'Your account is '.strtolower($user->status).'. Please contact the administrator.',
        ]);
    }
}
