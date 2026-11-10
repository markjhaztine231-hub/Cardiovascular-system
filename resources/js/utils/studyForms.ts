import type {
    EchoData,
    TMTData,
    DopplerData,
    StressEchoData,
    AbpmData,
    VitalsData,
} from '@/types/appointment';

/**
 * Blank Echo / TMT / Doppler form schemas, kept in sync with the doctor's
 * consultation form defaults. Both the doctor dashboard (consultation modal)
 * and the receptionist study pre-fill modal bind against these shapes, so a
 * saved report still carries every key the forms render.
 */
export const DEFAULT_ECHO_DATA: EchoData = {
    requesting_physician: '',
    contact_no: '',
    age: '',
    sex: '',
    file_no: '',
    bsa: '',
    height: '',
    weight: '',
    mitral_ef: '',
    mitral_de: '',
    mitral_epss: '',
    mitral_area: '',
    ivs_dia: '',
    ivs_sys: '',
    ivs_exc: '',
    tricuspid_valve: 'normal',
    aortic_root: '',
    aortic_valve: '',
    aortic_excursion: '',
    lvpw_dia: '',
    lvpw_sys: '',
    lvpw_exc: '',
    ra_rl: '',
    pulmonic_root: '',
    pulmonic_notching: '',
    la_ap: '',
    la_rl: '',
    la_lavi: '',
    rv_ap: '',
    rv_rl: '',
    lv_dia: '',
    lv_sys: '',
    lv_lvedv: '',
    lv_lvesv: '',
    lv_mass: '',
    lv_rwt: '',
    lv_ef: '',
    lv_fs: '',
    hr: '',
    simpsons: '',
    tapse: '',
    peri_ant: '',
    peri_post: '',
    rvot: '',
    lvot: '',
    findings: '',
};

const DEFAULT_STAGES: TMTData['stages'] = [
    {
        name: 'Pre-test',
        speed: '0',
        grade: '0',
        mets: '0',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 1',
        speed: '1.7',
        grade: '10',
        mets: '4.6',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 2',
        speed: '2.5',
        grade: '12',
        mets: '7.0',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 3',
        speed: '3.4',
        grade: '14',
        mets: '10.2',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 4',
        speed: '4.2',
        grade: '16',
        mets: '13.5',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 5',
        speed: '5.0',
        grade: '18',
        mets: '17.2',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 6',
        speed: '5.5',
        grade: '20',
        mets: '20.4',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'STAGE 7',
        speed: '6.0',
        grade: '22',
        mets: '23.8',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: 'Immediate Recovery',
        speed: '-',
        grade: '-',
        mets: '-',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: '1 minutes',
        speed: '-',
        grade: '-',
        mets: '-',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: '3 minutes',
        speed: '-',
        grade: '-',
        mets: '-',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: '6 minutes',
        speed: '-',
        grade: '-',
        mets: '-',
        bp: '',
        hr: '',
        remarks: '',
    },
    {
        name: '9 minutes',
        speed: '-',
        grade: '-',
        mets: '-',
        bp: '',
        hr: '',
        remarks: '',
    },
];

export const DEFAULT_TMT_DATA: TMTData = {
    age: '',
    pmhr: '',
    height: '',
    bp: '',
    weight: '',
    cell_no: '',
    birthday: '',
    medicines: '',
    requesting_physician: '',
    procedure: 'Treadmill Exercise Test',
    attending_physician: '',
    residence: '',
    consent_to: '',
    consent_day: '',
    consent_month: '',
    consent_place: '',
    witness_name: '',
    witness_address: '',
    protocol: 'BRUCE, MODIFIED BRUCE, NIH',
    indication: '',
    stages: DEFAULT_STAGES.map((stage) => ({ ...stage })),
    peak_stage: '',
    peak_minutes: '',
    peak_sbp: '',
    peak_hr: '',
    peak_mets: '',
    termination_reason: '',
    st_shape: '',
    st_displacement: '',
    st_duration: '',
    resting_ecg: '',
    response_to_exercise: '',
    exercise_capacity: '',
    arrhythmias: '',
    probability_cad: '',
    conclusion: '',
    pre_ef: '',
    post_ef: '',
};

export const DEFAULT_DOPPLER_DATA: DopplerData = {
    mv_peak_vel_e: '',
    mv_peak_vel_a: '',
    mv_peak_grad_e: '',
    mv_peak_grad_a: '',
    mv_ivrt: '',
    mv_regurg_frac: '',
    mv_dectime: '',
    mv_ea_ratio: '',
    mv_area_pht: '',
    mv_mean_pressure: '',
    td_septal_e: '',
    td_septal_a: '',
    td_septal_ee: '',
    td_lateral_e: '',
    td_lateral_a: '',
    td_lateral_ee: '',
    tv_e_wave: '',
    tv_a_wave: '',
    tv_regurg_frac: '',
    tv_peak_tr: '',
    tv_sys_pap: '',
    av_peak_vel_before: '',
    av_peak_vel_after: '',
    av_press_grad_before: '',
    av_press_grad_after: '',
    av_area: '',
    av_regurg_frac: '',
    av_ar_pht: '',
    av_mean_grad: '',
    av_ar_jet: 'Faint',
    pv_accel_time: '',
    pv_press_grad: '',
    pv_regurg: '',
    pv_sys_pap: '',
    pv_pr_jet: 'Faint',
    pv_rvot_vti: '',
    qp_qs: '',
    conclusion: '',
};

export const DEFAULT_STRESS_ECHO_DATA: StressEchoData = {
    age: '',
    conclusion: '',
};

export const DEFAULT_VITALS_DATA: VitalsData = {
    bp_sys: null,
    bp_dia: null,
    hr: null,
    rr: null,
    temp: null,
    spo2: null,
};

export const DEFAULT_ABPM_DATA: AbpmData = {
    requesting_physician: '',
    file_no: '',
    age: '',
    sex: '',
    indication: '',
    monitor_device: '',
    monitor_model: '',
    cuff_size: '',
    bp_medications: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    total_duration: '',
    wake_from: '',
    wake_to: '',
    sleep_from: '',
    sleep_to: '',
    day_sbp: '',
    day_dbp: '',
    day_hr: '',
    night_sbp: '',
    night_dbp: '',
    night_hr: '',
    avg_sbp: '',
    avg_dbp: '',
    avg_hr: '',
    systolic_load: '',
    diastolic_load: '',
    baseline_bp: '',
    dipping: '',
    findings: '',
    interpretation: '',
    recommendations: '',
    conclusion: '',
};

/** Deep-copied blank set, so re-opening the modal never reuses a prior edit. */
export function blankStudyForms(): {
    echo_data: EchoData;
    tmt_data: TMTData;
    doppler_data: DopplerData;
    stress_echo_data: StressEchoData;
    abpm_data: AbpmData;
    vitals_data: VitalsData;
} {
    return {
        echo_data: { ...DEFAULT_ECHO_DATA },
        tmt_data: {
            ...DEFAULT_TMT_DATA,
            stages: DEFAULT_TMT_DATA.stages.map((stage) => ({ ...stage })),
        },
        doppler_data: { ...DEFAULT_DOPPLER_DATA },
        stress_echo_data: { ...DEFAULT_STRESS_ECHO_DATA },
        abpm_data: { ...DEFAULT_ABPM_DATA },
        vitals_data: { ...DEFAULT_VITALS_DATA },
    };
}

/**
 * Fields that are generated, injected or otherwise not typed by the user.
 * Keep in sync with App\Support\ClinicalFormData.
 */
const CONTENT_IGNORED_KEYS = new Set([
    'id',
    'encounter_id',
    'appointment_id',
    'patient_id',
    'provider_id',
    'created_by',
    'performed_at',
    'created_at',
    'updated_at',
    'deleted_at',
    'file_no',
    'age',
    'sex',
]);

/** Form defaults that must not count as user input. */
const CONTENT_DEFAULTS: Record<string, Record<string, string[]>> = {
    echo: {
        tricuspid_valve: ['normal', 'tricuspid valve: normal'],
        wall_motion: ['tricuspid valve: normal'],
        peri_ant: ['absent'],
        peri_post: ['absent'],
    },
    tmt: {
        procedure: ['treadmill exercise test'],
        protocol: ['bruce, modified bruce, nih'],
    },
    doppler: {
        av_ar_jet: ['faint'],
        pv_pr_jet: ['faint'],
    },
};

const MANUAL_DRAFT_TEXT_KEYS = [
    'requesting_physician',
    'title',
    'draft',
    'conclusion',
];

function textHasContent(value: unknown): boolean {
    if (typeof value === 'string') {
        return value.trim() !== '';
    }

    return value !== null && value !== undefined && value !== false;
}

function isDefaultFormValue(
    type: string,
    key: string,
    value: unknown,
): boolean {
    // A negative effusion is stored as false/0 and is the default state.
    if (type === 'echo' && key === 'pericardial_effusion') {
        return !value;
    }

    if (typeof value !== 'string') {
        return false;
    }

    const normalized = value.trim().toLowerCase();

    return (CONTENT_DEFAULTS[type]?.[key] ?? []).includes(normalized);
}

function tmtStagesHaveContent(stages: unknown[]): boolean {
    return stages.some((stage) => {
        if (!stage || typeof stage !== 'object') {
            return false;
        }

        const row = stage as Record<string, unknown>;

        return ['bp', 'hr', 'remarks'].some((key) => textHasContent(row[key]));
    });
}

function formValueHasContent(type: string, value: unknown): boolean {
    if (Array.isArray(value)) {
        return formArrayHasContent(type, value);
    }

    if (typeof value === 'boolean') {
        return value;
    }

    return textHasContent(value);
}

function formArrayHasContent(
    type: string,
    data: Record<string, unknown> | unknown[],
): boolean {
    const entries: Array<[string, unknown]> = Array.isArray(data)
        ? data.map((value, index) => [String(index), value])
        : Object.entries(data);

    for (const [key, value] of entries) {
        if (!Array.isArray(data) && CONTENT_IGNORED_KEYS.has(key)) {
            continue;
        }

        // TMT stage presets repeat on every row; only the measured columns
        // (BP / HR / remarks) tell us the technician actually used a stage.
        if (type === 'tmt' && key === 'stages' && Array.isArray(value)) {
            if (tmtStagesHaveContent(value)) {
                return true;
            }

            continue;
        }

        if (isDefaultFormValue(type, key, value)) {
            continue;
        }

        if (formValueHasContent(type, value)) {
            return true;
        }
    }

    return false;
}

/**
 * True when a study form contains at least one field the user actually filled
 * in. Mirrors App\Support\ClinicalFormData so the save dialog and the server
 * agree on what "blank" means.
 */
export function hasClinicalFormContent(
    type: string,
    data: Record<string, unknown> | null | undefined,
): boolean {
    if (!data) {
        return false;
    }

    if (type === 'manual_draft') {
        return MANUAL_DRAFT_TEXT_KEYS.some((key) => textHasContent(data[key]));
    }

    return formArrayHasContent(type, data);
}
