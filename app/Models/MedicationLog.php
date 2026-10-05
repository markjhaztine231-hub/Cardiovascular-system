<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    protected $fillable = [
        'prescription_id',
        'log_date',
        'scheduled_time',
        'status',
        'adherence_flag',
        'taken_at',
    ];

    protected $casts = [
        'log_date' => 'date',
        'taken_at' => 'datetime',
        'status' => 'string',
        'adherence_flag' => 'string',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('log_date', $date);
    }

    public function scopeTaken($query)
    {
        return $query->whereNotNull('taken_at');
    }
}
