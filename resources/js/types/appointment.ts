export interface Prescription {
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string | number;
}

export interface Appointment {
    id: string | number;
    name: string;
    time: string;
    service?: string;
    status?: string;
    patient_id?: string | number;
    /**
     * patients.user_id - the identifier the /doctor/patients/{patient}/history
     * and /analytics routes expect, since they bind a User.
     */
    patient_user_id?: string | number;
    patient?: { id: string | number; user_id?: string | number };
    history?: Array<{
        id?: string | number;
        date: string;
        service_type: string;
        diagnosis?: string;
        echo_data?: Record<string, any>;
        tmt_data?: Record<string, any>;
        doppler_data?: Record<string, any>;
        manual_draft_data?: Record<string, any>;
        stress_echo_data?: Record<string, any>;
        abpm_data?: Record<string, any>;
    }>;
}

export interface EchoData {
    requesting_physician: string;
    contact_no: string;
    age: string;
    sex: string;
    file_no: string;
    bsa: string;
    height: string;
    weight: string;
    mitral_ef: string;
    mitral_de: string;
    mitral_epss: string;
    mitral_area: string;
    ivs_dia: string;
    ivs_sys: string;
    ivs_exc: string;
    tricuspid_valve: string;
    aortic_root: string;
    aortic_valve: string;
    aortic_excursion: string;
    lvpw_dia: string;
    lvpw_sys: string;
    lvpw_exc: string;
    ra_rl: string;
    pulmonic_root: string;
    pulmonic_notching: string;
    la_ap: string;
    la_rl: string;
    la_lavi: string;
    rv_ap: string;
    rv_rl: string;
    lv_dia: string;
    lv_sys: string;
    lv_lvedv: string;
    lv_lvesv: string;
    lv_mass: string;
    lv_rwt: string;
    lv_ef: string;
    lv_fs: string;
    hr: string;
    simpsons: string;
    tapse: string;
    peri_ant: string;
    peri_post: string;
    rvot: string;
    lvot: string;
    findings: string;
}

export interface TMTStage {
    name: string;
    speed: string;
    grade: string;
    mets: string;
    bp: string;
    hr: string;
    remarks: string;
}

export interface TMTData {
    age: string;
    pmhr: string;
    height: string;
    bp: string;
    weight: string;
    cell_no: string;
    birthday: string;
    medicines: string;
    requesting_physician: string;
    procedure: string;
    attending_physician: string;
    residence: string;
    consent_to: string;
    consent_day: string;
    consent_month: string;
    consent_place: string;
    witness_name: string;
    witness_address: string;
    protocol: string;
    indication: string;
    stages: TMTStage[];
    peak_stage: string;
    peak_minutes: string;
    peak_sbp: string;
    peak_hr: string;
    peak_mets: string;
    termination_reason: string;
    st_shape: string;
    st_displacement: string;
    st_duration: string;
    resting_ecg: string;
    response_to_exercise: string;
    exercise_capacity: string;
    arrhythmias: string;
    probability_cad: string;
    conclusion: string;
    pre_ef: string;
    post_ef: string;
}

export interface DopplerData {
    mv_peak_vel_e: string;
    mv_peak_vel_a: string;
    mv_peak_grad_e: string;
    mv_peak_grad_a: string;
    mv_ivrt: string;
    mv_regurg_frac: string;
    mv_dectime: string;
    mv_ea_ratio: string;
    mv_area_pht: string;
    mv_mean_pressure: string;
    td_septal_e: string;
    td_septal_a: string;
    td_septal_ee: string;
    td_lateral_e: string;
    td_lateral_a: string;
    td_lateral_ee: string;
    tv_e_wave: string;
    tv_a_wave: string;
    tv_regurg_frac: string;
    tv_peak_tr: string;
    tv_sys_pap: string;
    av_peak_vel_before: string;
    av_peak_vel_after: string;
    av_press_grad_before: string;
    av_press_grad_after: string;
    av_area: string;
    av_regurg_frac: string;
    av_ar_pht: string;
    av_mean_grad: string;
    av_ar_jet: string;
    pv_accel_time: string;
    pv_press_grad: string;
    pv_regurg: string;
    pv_sys_pap: string;
    pv_pr_jet: string;
    pv_rvot_vti: string;
    qp_qs: string;
    conclusion: string;
}

/**
 * One image dropped into the Manual Draft form. Dragged scans are the OCR
 * documents from the Scans panel, so `id` refers to the document row they came
 * from (kept so the backend could re-resolve the bytes when exporting), and
 * `url` is the public storage URL shown by the doctor.
 */
export interface ManualDraftImage {
    id?: string | number;
    url: string;
    name: string;
}

export interface ManualDraftData {
    requesting_physician: string;
    title: string;
    draft: string;
    conclusion: string;
    images: ManualDraftImage[];
}

export interface StressEchoStage {
    stage: string;
    time: string;
    speed: string;
    grade: string;
    hr: string;
    bp: string;
    ecg: string;
    symptoms: string;
}

export interface StressEchoData {
    requesting_physician: string;
    file_no: string;
    age: string;
    sex: string;
    indication: string;
    medications: string;
    pre_bp: string;
    pre_hr: string;
    pre_ecg: string;
    pre_symptoms: string;
    protocol: string;
    stages: StressEchoStage[];
    peak_hr: string;
    target_hr: string;
    peak_bp: string;
    mets: string;
    reached_target: string;
    rest_ef: string;
    rest_wma: string;
    peak_ef: string;
    peak_wma: string;
    ischaemia: string;
    findings: string;
    conclusion: string;
}

export interface AbpmData {
    requesting_physician: string;
    file_no: string;
    age: string;
    sex: string;
    indication: string;
    monitor_device: string;
    monitor_model: string;
    cuff_size: string;
    bp_medications: string;
    start_date: string;
    start_time: string;
    end_date: string;
    end_time: string;
    total_duration: string;
    wake_from: string;
    wake_to: string;
    sleep_from: string;
    sleep_to: string;
    day_sbp: string;
    day_dbp: string;
    day_hr: string;
    night_sbp: string;
    night_dbp: string;
    night_hr: string;
    avg_sbp: string;
    avg_dbp: string;
    avg_hr: string;
    systolic_load: string;
    diastolic_load: string;
    baseline_bp: string;
    dipping: string;
    findings: string;
    interpretation: string;
    recommendations: string;
    conclusion: string;
}

export interface ConsultForm {
    symptoms: string;
    vitals: string;
    diagnosis: string;
    plan: string;
    consultation_type: string[];
    ai_summary: string;
    prescriptions: Prescription[];
    echo_data: EchoData;
    tmt_data: TMTData;
    doppler_data: DopplerData;
    manual_draft_data: ManualDraftData;
    stress_echo_data: StressEchoData;
    abpm_data: AbpmData;
}
