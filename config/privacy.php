<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Patient Name Anonymization
    |--------------------------------------------------------------------------
    |
    | When enabled (ANONYMIZE_PATIENT_NAMES=true), patient display names are
    | replaced with anonymized identifiers in dashboard queues, patient
    | registries and admin listings. Clinical staff can still re-identify a
    | record through its system ID / MRN.
    |
    | Supported modes:
    |   - "initials"  : "John Michael Doe" -> "J. M. D."
    |   - "pseudonym" : "John Michael Doe" -> "Patient #12"
    |
    */

    'anonymize_patient_names' => (bool) env('ANONYMIZE_PATIENT_NAMES', false),

    'patient_name_mode' => env('PATIENT_NAME_ANONYMIZATION_MODE', 'pseudonym'),
];