<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'provider_id',
        'reader_provider_id',
        'reading_date',
        'reading_status',
        'reading_completed_at',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'service_type',
        'status',
        'reason_for_visit',
        'notes',
        'google_event_id',
        'google_event_url',
        'reschedule_requested_date',
        'reschedule_requested_time',
        'consultation_started_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'reschedule_requested_date' => 'date',
        'reading_date' => 'date',
        'reading_completed_at' => 'datetime',
        'consultation_started_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function readerProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'reader_provider_id');
    }

    /**
     * Only Procedure and Consultation and Procedure visits can be sent to a
     * reading doctor. Plain consultations are completed by the attending doctor.
     */
    public function supportsReadingAssignment(): bool
    {
        return in_array($this->service_type, ['Procedure', 'Consultation and Procedure'], true);
    }

    public function encounter(): HasOne
    {
        return $this->hasOne(Encounter::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function prescriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Prescription::class,
            Encounter::class,
            'appointment_id', // FK on encounters
            'encounter_id',   // FK on prescriptions
            'id',             // local key on appointments
            'id',             // local key on encounters
        );
    }

    public function scopeUpcoming($query)
    {
        return $query
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->whereIn('status', ['Scheduled', 'Confirmed', 'In Progress']);
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }
}
