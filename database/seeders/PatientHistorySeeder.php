<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\MedicationLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\TreadmillTest;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientHistorySeeder extends Seeder
{
    public function run(): void
    {
        $doctor1 = Provider::whereHas('user', fn ($q) => $q->where('name', 'like', '%Chen%'))->first();
        $doctor2 = Provider::whereHas('user', fn ($q) => $q->where('name', 'like', '%Smith%'))->first();
        $patient = Patient::whereHas('user', fn ($q) => $q->where('email', 'patient@clinic.com'))->first();

        if (! $patient || ! $doctor1 || ! $doctor2) {
            $this->command->error('Required users not found. Run DatabaseSeeder first.');
            return;
        }

        // ─────────────────────────────────────────────────────────────
        // RECORD 1 — Echocardiogram (2 months ago)
        // ─────────────────────────────────────────────────────────────
        $this->record(
            patient: $patient,
            provider: $doctor1,
            date: Carbon::now()->subMonths(2),
            time: '09:00',
            serviceType: 'Consultation',
            encounterType: 'echo',
            objective: 'BP: 138/88 mmHg | HR: 82 bpm | RR: 18 | Temp: 36.7°C | SpO2: 97%',
            vitals: ['138', '88', '82', '18', '97'],
            diagnoses: [
                ['icd_code' => 'I11.9', 'description' => 'Hypertensive heart disease without heart failure', 'is_primary' => true],
                ['icd_code' => 'I51.9', 'description' => 'Mild left ventricular diastolic dysfunction', 'is_primary' => false],
            ],
            prescriptions: [
                ['name' => 'Amlodipine', 'dosage' => '5mg',       'frequency' => 'Once daily',           'days' => 30],
                ['name' => 'Losartan',   'dosage' => '50mg',      'frequency' => 'Once daily',           'days' => 30],
            ],
            study: fn (Encounter $encounter) => Echocardiogram::create([
                'encounter_id'        => $encounter->id,
                'ivs_mm'              => 10.0,
                'aortic_root_mm'      => 32.0,
                'lvpw_mm'             => 10.0,
                'la_diameter_mm'      => 36.0,
                'lvef_percent'        => 61.0,
                'lvfs_percent'        => 30.0,
                'lv_mass_grams'       => 210.0,
                'tapse_mm'            => 20.0,
                'lvidd_mm'            => 54.0,
                'lvids_mm'            => 38.0,
                'rv_size'             => 'Normal',
                'pericardial_effusion'=> false,
                'valve_findings'      => 'Tricuspid valve normal. Aortic valve tricuspid with calcified leaflets.',
                'wall_motion'         => 'Concentric LV hypertrophy. Mildly impaired diastolic relaxation (Grade I).',
                'notes'               => 'No pericardial effusion. Normal systolic function.',
                'performed_at'        => Carbon::now()->subMonths(2)->subHour(),
            ]),
            subjective: 'Patient reports exertional dyspnea, occasional chest tightness, and bilateral lower extremity edema.',
            assessment: 'Hypertensive heart disease with mild left ventricular diastolic dysfunction. Rule out early dilated cardiomyopathy.',
            plan: 'Continue antihypertensives. Repeat echo in 6 months. Encourage sodium restriction and daily walking.',
        );

        // ─────────────────────────────────────────────────────────────
        // RECORD 2 — Doppler Study (6 weeks ago)
        // ─────────────────────────────────────────────────────────────
        $this->record(
            patient: $patient,
            provider: $doctor2,
            date: Carbon::now()->subWeeks(6),
            time: '10:30',
            serviceType: 'Procedure',
            encounterType: 'doppler',
            objective: 'BP: 132/84 mmHg | HR: 78 bpm | RR: 16 | Temp: 36.5°C | SpO2: 98%',
            vitals: ['132', '84', '78', '16', '98'],
            diagnoses: [
                ['icd_code' => 'I51.9', 'description' => 'Grade I diastolic dysfunction', 'is_primary' => true],
                ['icd_code' => 'I34.0', 'description' => 'Nonrheumatic mitral valve insufficiency (mild)', 'is_primary' => false],
            ],
            prescriptions: [
                ['name' => 'Bisoprolol', 'dosage' => '2.5mg', 'frequency' => 'Once daily in the morning', 'days' => 60],
                ['name' => 'Furosemide', 'dosage' => '20mg',  'frequency' => 'Once daily',               'days' => 14],
            ],
            study: fn (Encounter $encounter) => DopplerStudy::create([
                'encounter_id'          => $encounter->id,
                'ea_ratio'              => 0.83,
                'tissue_doppler_e'      => 6.2,
                'tissue_doppler_a'      => 9.0,
                'mitral_gradient'       => 1.8,
                'aortic_gradient'       => 5.8,
                'tricuspid_gradient'    => null,
                'pulmonic_gradient'     => 4.0,
                'mitral_regurgitation'  => 'mild',
                'aortic_regurgitation'  => 'none',
                'tricuspid_regurgitation'=> 'trivial',
                'valve_findings'        => 'E/A 0.83. IVRT 95ms. DT 240ms. E/e\' 11.',
                'notes'                 => 'Grade I diastolic dysfunction. Mild MR with normal PAP. Repeat in 6 months.',
                'performed_at'          => Carbon::now()->subWeeks(6)->subHour(),
            ]),
            subjective: 'Follow-up for known diastolic dysfunction. Patient denies chest pain. Reports mild exertional fatigue.',
            assessment: 'Grade I diastolic dysfunction confirmed. Normal LV systolic function. Mild mitral regurgitation.',
            plan: 'Continue bisoprolol. Taper furosemide. Recheck in 6 months.',
        );

        // ─────────────────────────────────────────────────────────────
        // RECORD 3 — Treadmill TMT (3 weeks ago)
        // ─────────────────────────────────────────────────────────────
        $this->record(
            patient: $patient,
            provider: $doctor1,
            date: Carbon::now()->subWeeks(3),
            time: '08:00',
            serviceType: 'Procedure',
            encounterType: 'tmt',
            objective: 'BP: 136/86 mmHg | HR: 76 bpm | RR: 17 | Temp: 36.6°C | SpO2: 98%',
            vitals: ['136', '86', '76', '17', '98'],
            diagnoses: [
                ['icd_code' => 'Z03.89', 'description' => 'Encounter for observation of other suspected diseases (rule out CAD)', 'is_primary' => true],
            ],
            prescriptions: [
                ['name' => 'Aspirin', 'dosage' => '80mg', 'frequency' => 'Once daily after meals', 'days' => 90],
            ],
            study: fn (Encounter $encounter) => TreadmillTest::create([
                'encounter_id'             => $encounter->id,
                'protocol'                 => 'Bruce',
                'max_stage'                => 4.0,
                'peak_heart_rate_bpm'      => 154,
                'peak_bp_systolic'         => 182,
                'peak_bp_diastolic'        => 96,
                'mets_achieved'            => 13.5,
                'exercise_duration_seconds'=> 12 * 60,
                'recovery_time_minutes'    => 5,
                'st_segment_changes'       => 'Upsloping 0.5mm, no significant displacement.',
                'arrhythmias'              => 'None noted during exercise or recovery',
                'symptoms_during_test'     => 'Mild fatigue at peak exercise only',
                'reason_for_termination'   => 'Target heart rate achieved (91% PMHR)',
                'notes'                    => 'Negative ETT. Good functional capacity. Low probability of obstructive CAD.',
                'performed_at'             => Carbon::now()->subWeeks(3)->subHour(),
            ]),
            subjective: 'Chest discomfort on exertion. Referred for stress testing to rule out obstructive coronary artery disease.',
            assessment: 'Negative exercise tolerance test. No ST changes or anginal symptoms at peak exercise. Good functional capacity.',
            plan: 'Continue aspirin. Resume normal activity. Annual stress test if symptoms recur.',
        );

        // ─────────────────────────────────────────────────────────────
        // RECORD 4 — Consultation with Lab Exam (1 week ago)
        // ─────────────────────────────────────────────────────────────
        $encounter4 = $this->record(
            patient: $patient,
            provider: $doctor2,
            date: Carbon::now()->subWeek(),
            time: '11:00',
            serviceType: 'Consultation and Procedure',
            encounterType: 'consultation',
            objective: 'BP: 126/80 mmHg | HR: 72 bpm | RR: 15 | Temp: 36.5°C | SpO2: 99%',
            vitals: ['126', '80', '72', '15', '99'],
            diagnoses: [
                ['icd_code' => 'I11.9', 'description' => 'Hypertensive heart disease — stable and well-controlled', 'is_primary' => true],
            ],
            prescriptions: [
                ['name' => 'Amlodipine',  'dosage' => '5mg',   'frequency' => 'Once daily',                'days' => 30],
                ['name' => 'Losartan',    'dosage' => '50mg',  'frequency' => 'Once daily',                'days' => 30],
                ['name' => 'Bisoprolol',  'dosage' => '2.5mg', 'frequency' => 'Once daily in the morning', 'days' => 30],
                ['name' => 'Furosemide',  'dosage' => '20mg',  'frequency' => 'Once daily as needed',      'days' => 14],
            ],
            subjective: 'Monthly follow-up. Patient reports improved exercise tolerance. No chest pain. Mild ankle swelling by end of day.',
            assessment: 'Hypertensive heart disease — stable and well-controlled on current regimen.',
            plan: 'Continue medications. Monthly follow-up. Weight monitoring daily.',
        );

        // ── Adherence logs for the most recent prescription ──────────
        $aspirin = Prescription::find($encounter4->prescriptions()->first()->id ?? 0);
        if ($aspirin) {
            foreach (range(1, 3) as $dayOffset) {
                MedicationLog::create([
                    'prescription_id' => $aspirin->id,
                    'log_date'        => now()->subDays($dayOffset)->toDateString(),
                    'scheduled_time'  => '08:00:00',
                    'taken_at'        => now()->subDays($dayOffset)->setTime(8, 15),
                ]);
            }
        }

        $this->command->info('Patient history seeded: 4 completed encounters (Echo, Doppler, TMT, Consultation).');
    }

    /**
     * Build a full encounter chain: appointment → encounter → soap note →
     * vitals → diagnoses → prescriptions → clinical study.
     */
    private function record(
        Patient $patient,
        Provider $provider,
        Carbon $date,
        string $time,
        string $serviceType,
        string $encounterType,
        string $objective,
        array $vitals,
        array $diagnoses,
        array $prescriptions,
        string $subjective,
        string $assessment,
        string $plan,
        ?callable $study = null,
    ): Encounter {
        $appointment = DB::transaction(function () use ($patient, $provider, $date, $time, $serviceType, $encounterType, $objective, $vitals, $diagnoses, $prescriptions, $study, $subjective, $assessment, $plan) {
            $appointment = Appointment::create([
                'patient_id'     => $patient->id,
                'provider_id'    => $provider->id,
                'scheduled_date' => $date->toDateString(),
                'scheduled_time' => $time.':00',
                'service_type'   => $serviceType,
                'status'         => 'Completed',
                'reason_for_visit' => $subjective,
            ]);

            $encounter = Encounter::create([
                'appointment_id'  => $appointment->id,
                'patient_id'      => $patient->id,
                'provider_id'     => $provider->id,
                'encounter_type'  => $encounterType,
                'status'          => 'completed',
                'chief_complaint' => $subjective,
                'started_at'      => $date->setTimeFromTimeString($time.':00'),
                'ended_at'        => $date->setTimeFromTimeString($time.':30'),
            ]);

            SoapNote::create([
                'encounter_id' => $encounter->id,
                'subjective'   => $subjective,
                'objective'    => $objective,
                'assessment'   => $assessment,
                'plan'         => $plan,
                'ai_summary'   => mb_substr($assessment, 0, 180),
            ]);

            VitalSign::create([
                'encounter_id'                 => $encounter->id,
                'blood_pressure_systolic'      => $vitals[0],
                'blood_pressure_diastolic'     => $vitals[1],
                'heart_rate_bpm'               => $vitals[2],
                'respiratory_rate'             => $vitals[3],
                'oxygen_saturation_percent'    => $vitals[4],
                'temperature_celsius'          => 36.6,
                'weight_kg'                    => 78.0,
                'height_cm'                    => 168.0,
                'bmi'                          => 27.6,
                'recorded_at'                  => $date,
            ]);

            foreach ($diagnoses as $dx) {
                Diagnosis::create([
                    'encounter_id' => $encounter->id,
                    'icd_code'     => $dx['icd_code'],
                    'description'  => $dx['description'],
                    'is_primary'   => $dx['is_primary'],
                    'diagnosed_at' => $date,
                ]);
            }

            foreach ($prescriptions as $rx) {
                Prescription::create([
                    'encounter_id'    => $encounter->id,
                    'patient_id'      => $patient->id,
                    'provider_id'     => $provider->id,
                    'medication_name' => $rx['name'],
                    'dosage'          => $rx['dosage'],
                    'frequency'       => $rx['frequency'],
                    'duration_days'   => $rx['days'],
                    'start_date'      => $date->toDateString(),
                    'end_date'        => $date->copy()->addDays($rx['days'])->toDateString(),
                    'status'          => 'active',
                ]);
            }

            $study?->__invoke($encounter);

            return $appointment;
        });

        return $appointment->encounter;
    }
}