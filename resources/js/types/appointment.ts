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
}
