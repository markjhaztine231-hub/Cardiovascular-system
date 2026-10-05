<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Encounter extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'provider_id',
        'encounter_type',
        'status',
        'chief_complaint',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function soapNote(): HasOne
    {
        return $this->hasOne(SoapNote::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function echocardiogram(): HasOne
    {
        return $this->hasOne(Echocardiogram::class);
    }

    public function treadmillTest(): HasOne
    {
        return $this->hasOne(TreadmillTest::class);
    }

    public function dopplerStudy(): HasOne
    {
        return $this->hasOne(DopplerStudy::class);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
