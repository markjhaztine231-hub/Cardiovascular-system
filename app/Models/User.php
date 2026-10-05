<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\AnonymizableName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use AnonymizableName, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',   // admin | doctor | receptionist | patient
        'status', // Register | Active | Inactive | Suspended
        'terms_accepted_at',
        'terms_version',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Store emails lowercased and trimmed.
     *
     * Postgres compares varchar case-sensitively, so storing whatever casing
     * the user typed makes the exact-match login lookup fail for any other
     * casing. Normalising once on write means the read paths can rely on a
     * canonical form.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : Str::lower(trim($value)),
        );
    }

    /** Case-insensitive lookup for the read paths. */
    public function scopeWhereEmail(Builder $query, string $email): Builder
    {
        return $query->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    protected $appends = ['patient_id'];

    public function getPatientIdAttribute()
    {
        if ($this->role === 'patient') {
            return 'PT-'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
        }

        return 'STAFF-'.$this->id;
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            DoctorSchedule::class,
            Provider::class,
            'user_id',      // FK on providers
            'provider_id',  // FK on doctor_schedules
            'id',           // local key on users
            'id',           // local key on providers
        );
    }

    public function doctorAppointments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Appointment::class,
            Provider::class,
            'user_id',      // FK on providers
            'provider_id',  // FK on appointments
            'id',           // local key on users
            'id',           // local key on providers
        );
    }

    public function patientAppointments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Appointment::class,
            Patient::class,
            'user_id',      // FK on patients
            'patient_id',   // FK on appointments
            'id',           // local key on users
            'id',           // local key on patients
        );
    }

    public function appointments()
    {
        if ($this->role === 'doctor') {
            return $this->doctorAppointments();
        }

        return $this->patientAppointments();
    }
}
