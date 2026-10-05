<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreadmillTest extends Model
{
    protected $fillable = [
        'encounter_id',
        'protocol',
        'max_stage',
        'peak_heart_rate_bpm',
        'peak_bp_systolic',
        'peak_bp_diastolic',
        'mets_achieved',
        'exercise_duration_seconds',
        'recovery_time_minutes',
        'st_segment_changes',
        'arrhythmias',
        'symptoms_during_test',
        'reason_for_termination',
        'notes',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function getIsPositiveAttribute(): ?bool
    {
        if (! $this->st_segment_changes) {
            return null;
        }

        return (bool) preg_match('/depression|flat|descent/i', $this->st_segment_changes);
    }
}
