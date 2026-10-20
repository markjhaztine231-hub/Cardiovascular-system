<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DoctorQueueMedicationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_exposes_allergies_and_only_in_course_medications(): void
    {
        $patient = Patient::factory()->create(['allergies' => 'Penicillin, shellfish']);
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '09:00:00',
            'service_type' => 'Consultation',
            'status' => 'Scheduled',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '3x a day',
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(5),
            'status' => 'active',
        ]);

        // Course ended yesterday: must not be surfaced as a current medication.
        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Amoxicillin',
            'dosage' => '500 mg',
            'frequency' => '3x a day',
            'start_date' => Carbon::today()->subDays(20),
            'end_date' => Carbon::today()->subDay(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($provider->user)->get(route('doctor.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Doctor')
            ->where('queue.0.allergies', 'Penicillin, shellfish')
            ->has('queue.0.current_medications', 1)
            ->where('queue.0.current_medications.0.medicine', 'Metoprolol')
            ->where('queue.0.current_medications.0.dosage', '25 mg')
            ->where('queue.0.current_medications.0.frequency', '3x a day')
        );
    }
}
