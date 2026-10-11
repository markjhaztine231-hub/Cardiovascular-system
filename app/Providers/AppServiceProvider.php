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
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
