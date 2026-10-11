<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\ManualDraft;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManualDraftConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): array
    {
        $doctor = User::factory()->role('doctor')->create();
        $provider = Provider::factory()->create(['user_id' => $doctor->id]);
        $patient = Patient::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_time' => '08:00:00',
            'service_type' => 'Consultation',
            'status' => 'Pending',
        ]);

        return [$doctor, $provider, $patient, $appointment];
    }

    public function test_type_4_consultation_persists_a_manual_draft_and_maps_it_to_history(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $response = $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Chest pain',
                'vitals' => 'BP: 120/80 mmHg | HR: 72 bpm',
                'diagnosis' => 'Stable angina',
                'plan' => 'Follow up in two weeks',
                'consultation_type' => ['Type 4'],
                'manual_draft_data' => [
                    'requesting_physician' => 'Dr. Reyes',
                    'title' => 'External echo review',
                    'draft' => 'Reviewed the attached scan.',
                    'conclusion' => 'No acute findings.',
                    'images' => [
                        ['id' => 7, 'url' => '/storage/scans/scan.png', 'name' => 'scan.png'],
                        // A locally-dropped blob URL cannot persist server-side.
                        ['url' => 'blob:http://localhost/abc', 'name' => 'dropped.png'],
                    ],
                ],
            ]
        );

        $response->assertRedirect();

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($encounter);
        $this->assertSame('manual_draft', $encounter->encounter_type);

        $draft = $encounter->manualDraft;
        $this->assertNotNull($draft);
        $this->assertSame('Dr. Reyes', $draft->requesting_physician);
        $this->assertSame('Reviewed the attached scan.', $draft->draft);
        $this->assertCount(1, $draft->attachments);
        $this->assertSame('/storage/scans/scan.png', $draft->attachments[0]['url']);

        // The history payload exposes the Type 4 label and the panel data.
        $this->actingAs($doctor)
            ->get('/doctor/patients/'.$patient->user_id.'/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.consultation_type', 'Type 4 - Manual Draft')
                ->where('history.0.manual_draft_data.draft', 'Reviewed the attached scan.')
                ->where('history.0.manual_draft_data.conclusion', 'No acute findings.')
                ->where('history.0.manual_draft_data.images.0.url', '/storage/scans/scan.png')
            );
    }

    public function test_type_4_consultation_clears_the_draft_when_not_selected(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $this->actingAs($doctor)->post(
            route('appointments.complete', $appointment),
            [
                'symptoms' => 'Checkup',
                'vitals' => 'BP: 118/76 mmHg',
                'diagnosis' => 'Routine',
                'consultation_type' => ['Type 1'],
                'manual_draft_data' => [
                    'requesting_physician' => 'Dr. Reyes',
                    'draft' => 'Should be ignored.',
                    'images' => [],
                ],
                'echo_data' => ['lv_ef' => '55'],
            ]
        );

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $this->assertSame('echo', $encounter->encounter_type);
        $this->assertNull($encounter->manualDraft);
    }

    public function test_manual_draft_pdf_route_streams_a_pdf(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'manual_draft',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        ManualDraft::create([
            'encounter_id' => $encounter->id,
            'requesting_physician' => 'Dr. Reyes',
            'title' => 'External echo review',
            'draft' => 'Reviewed the attached scan.',
            'conclusion' => 'No acute findings.',
            'attachments' => null,
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/manual_draft')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_manual_draft_pdf_route_404s_without_a_draft(): void
    {
        [$doctor, $provider, $patient, $appointment] = $this->makeAppointment();

        Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        $this->actingAs($doctor)
            ->get('/doctor/pdf/'.$appointment->id.'/manual_draft')
            ->assertNotFound();
    }
}
