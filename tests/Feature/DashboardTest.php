<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('patient.dashboard', absolute: false));
    }

    public function test_patient_dashboard_exposes_completed_visit_prescriptions_and_ai_summary()
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->subDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'Completed',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '3x a day (8h)',
            'duration_days' => 30,
            'start_date' => Carbon::today()->subDay(),
            'end_date' => Carbon::today()->addDays(29),
            'instructions' => 'After meals',
            'status' => 'active',
        ]);

        SoapNote::create([
            'encounter_id' => $encounter->id,
            'subjective' => 'Chest pain on exertion',
            'assessment' => 'Stable angina',
            'ai_summary' => 'Patient is stable and responding to therapy.',
        ]);

        $response = $this->actingAs($patient->user)->get(route('patient.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Patient')
            ->where('history.0.prescriptions.0.medicine', 'Metoprolol')
            ->where('history.0.prescriptions.0.dosage', '25 mg')
            ->where('history.0.prescriptions.0.frequency', '3x a day (8h)')
            ->where('history.0.prescriptions.0.duration', '30 days')
            ->where('history.0.prescriptions.0.instructions', 'After meals')
            ->where('history.0.prescriptions.0.prescribedBy', $provider->user->name)
            ->where('history.0.ai_summary', 'Patient is stable and responding to therapy.')
        );
    }
}
