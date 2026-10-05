<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DopplerStudy extends Model
{
    protected $fillable = [
        'encounter_id',
        'ea_ratio',
        'tissue_doppler_e',
        'tissue_doppler_a',
        'mitral_gradient',
        'aortic_gradient',
        'tricuspid_gradient',
        'pulmonic_gradient',
        'mitral_regurgitation',
        'aortic_regurgitation',
        'tricuspid_regurgitation',
        'valve_findings',
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
}
