<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echocardiogram extends Model
{
    protected $fillable = [
        'encounter_id',
        'lvef_percent',
        'lvfs_percent',
        'lv_mass_grams',
        'tapse_mm',
        'ivs_mm',
        'lvpw_mm',
        'lvidd_mm',
        'lvids_mm',
        'aortic_root_mm',
        'la_diameter_mm',
        'rv_size',
        'pericardial_effusion',
        'valve_findings',
        'wall_motion',
        'notes',
        'performed_at',
    ];

    protected $casts = [
        'pericardial_effusion' => 'boolean',
        'performed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
