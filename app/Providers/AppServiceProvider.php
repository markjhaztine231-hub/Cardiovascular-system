<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A stray APP_DEBUG=true must never expose stack traces, environment
        // values or framework versions on the production host. The error
        // renderer reads this value at render time, so overriding it here is
        // enough even before the response reaches the exception handler.
        if (config('app.env') === 'production') {
            config(['app.debug' => false]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureRateLimiters();
    }

    /**
     * Rate limits are keyed per authenticated user (not per IP), so the whole
     * clinic sharing one public WiFi address does not trip each other's limits.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('doctor-ai', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('doctor-narrative', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('doctor-pdf', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('settings-password', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->id ?: $request->ip()));

        // Per-account in addition to per-IP: the clinic shares one public WiFi
        // address, so an IP-only limit is either too loose for one account or
        // too tight for the whole staff. Ten failures per email per minute
        // stops online password guessing without punishing the shared address.
        // Deliberately not named `login`: Fortify registers its own limiter
        // under that name and provider boot order would silently override it.
        RateLimiter::for('login-attempts', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(10)->by('login-email:'.$email),
                Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            ];
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Centralize role-based and data-level authorization so controllers no
     * longer need to repeat `if ($user->role !== 'x') abort(403)`.
     */
    protected function configureGates(): void
    {
        $hasRole = fn (User $user, array $roles): bool => in_array($user->role, $roles, true);

        Gate::define('access-doctor-portal', fn (User $user) => $user->role === 'doctor');
        Gate::define('access-receptionist-portal', fn (User $user) => in_array($user->role, ['receptionist', 'admin'], true));
        Gate::define('access-admin-portal', fn (User $user) => $user->role === 'admin');
        Gate::define('access-patient-portal', fn (User $user) => $user->role === 'patient');

        Gate::define('manage-users', fn (User $user) => $user->role === 'admin');
        Gate::define('view-audit-logs', fn (User $user) => $user->role === 'admin');

        Gate::define('view-emr', fn (User $user) => $user->role === 'doctor');

        Gate::define('view-patient', fn (User $user, ?User $patient = null) => $hasRole($user, ['admin', 'doctor', 'receptionist'])
            || ($user->role === 'patient' && $patient?->is($user)));

        Gate::define('manage-appointments', fn (User $user) => $hasRole($user, ['receptionist', 'admin', 'doctor']));
    }
}
