<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mrn',
        'date_of_birth',
        'sex',
        'blood_type',
        'contact_phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'insurance_provider',
        'insurance_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function medicationLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            MedicationLog::class,
            Prescription::class,
            'patient_id',
            'prescription_id',
            'id',
            'id',
        );
    }
}
