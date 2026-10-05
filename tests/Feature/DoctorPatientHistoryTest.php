<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\TreadmillTest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DoctorPatientHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeEncounter(): array
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        // Create a throwaway appointment so the consultation's appointment id
        // and encounter id diverge the way they do in production. Any assertion
        // that "encounter id 404s" only has teeth when the two id spaces differ.
        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::yesterday()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'No-Show',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'service_type' => 'Consultation',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Echocardiogram::create([
            'encounter_id' => $encounter->id,
            'lvef_percent' => 34.0,
            'valve_findings' => 'Normal valve findings',
            'pericardial_effusion' => false,
        ]);

        TreadmillTest::create([
            'encounter_id' => $encounter->id,
            'protocol' => 'Bruce',
            'peak_heart_rate_bpm' => 145,
        ]);

        DopplerStudy::create([
            'encounter_id' => $encounter->id,
            'ea_ratio' => 1.15,
            'notes' => 'Normal study.',
        ]);

        return [$patient, $appointment, $encounter];
    }

    public function test_history_page_labels_types_and_maps_legacy_study_keys()
    {
        [$patient, $appointment] = $this->makeEncounter();
        $doctor = User::factory()->role('doctor')->create();

        $response = $this->actingAs($doctor)->get(
            '/doctor/patients/'.$patient->user_id.'/history'
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/PatientHistory')
            ->where('history.0.appointment_id', $appointment->id)
            // The 1/2/3 numbering was previously inverted.
            ->where('history.0.consultation_type', 'Type 1 - Echocardiographic')
            // Raw DB columns (`lvef_percent`, `valve_findings`) were being
            // sent, so every legacy-keyed detail table rendered blank.
            ->where('history.0.echo_data.findings', 'Normal valve findings')
            ->where('history.0.doppler_data.conclusion', 'Normal study.')
            ->whereNotNull('history.0.echo_data.lv_ef')
            ->whereNotNull('history.0.tmt_data.protocol')
        );
    }

    public function test_pdf_route_uses_appointment_id_not_encounter_id()
    {
        [$patient, $appointment, $encounter] = $this->makeEncounter();
        $doctor = User::factory()->role('doctor')->create();

        // The encounter id is no longer the document identifier.
        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$encounter->id.'/echo')
            ->assertNotFound();

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/echo')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_route_404s_when_appointment_has_no_consultation()
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();
        $doctor = User::factory()->role('doctor')->create();

        $empty = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'Pending',
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$empty->id.'/echo')
            ->assertNotFound();
    }

    public function test_dashboard_queue_exposes_the_user_id_needed_by_history_links()
    {
        // The dashboard "View History" links were built from patients.id, but
        // /doctor/patients/{patient}/history binds a User. The queue now
        // carries patient_user_id so the link can target the right row.
        $provider = Provider::factory()->create();
        $doctor = $provider->user;
        $patient = Patient::factory()->create();

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '10:00:00',
            'service_type' => 'Consultation',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Doctor')
            ->where('queue.0.patient_user_id', $patient->user_id)
        );
    }
}
