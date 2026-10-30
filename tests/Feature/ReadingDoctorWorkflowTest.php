<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the reception-desk completion and the reading-doctor hand-off:
 * a receptionist may close a visit without touching the EMR, a completed visit
 * is terminal for cancel/no-show/reschedule/transfer, and the assigned reading
 * doctor can review, edit, and sign off the reading with an audit trail.
 */
class ReadingDoctorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(Provider $provider, string $status = 'Scheduled', string $service = 'Consultation'): Appointment
    {
        return Appointment::create([
            'patient_id' => Patient::factory()->create()->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'service_type' => $service,
            'status' => $status,
        ]);
    }

    private function makeDoctor(): array
    {
        $user = User::factory()->doctor()->create();
        $provider = Provider::factory()->create(['user_id' => $user->id]);

        return [$user, $provider];
    }

    public function test_receptionist_can_complete_a_visit_without_writing_the_emr(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create());

        $this->actingAs($receptionist)
            ->post(route('appointments.complete', $appointment), [])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Completed',
        ]);

        $this->assertDatabaseMissing('encounters', [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_receptionist_clinical_payload_still_requires_doctor_authorization(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create());

        $this->actingAs($receptionist)
            ->post(route('appointments.complete', $appointment), [
                'symptoms' => 'Receptionist should not chart this',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Scheduled',
        ]);
    }

    public function test_completed_appointment_cannot_be_marked_no_show(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'Completed', 'Procedure');

        $this->actingAs($receptionist)
            ->put(route('appointments.status', $appointment), ['status' => 'No-Show'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Completed',
        ]);
    }

    public function test_completed_appointment_cannot_be_transferred(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $oldDoctor = Provider::factory()->create();
        $newDoctor = Provider::factory()->create();
        $appointment = $this->makeAppointment($oldDoctor, 'Completed', 'Procedure');

        $this->actingAs($receptionist)
            ->put(route('appointments.transfer', $appointment), ['doctor_id' => $newDoctor->id])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'provider_id' => $oldDoctor->id,
            'status' => 'Completed',
        ]);
    }

    public function test_completed_appointment_cannot_be_rescheduled(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $appointment = $this->makeAppointment(Provider::factory()->create(), 'Completed', 'Procedure');
        $originalDate = $appointment->scheduled_date->toDateString();

        $this->actingAs($receptionist)
            ->post(route('appointments.edit-reschedule', $appointment), [
                'date' => now()->addDays(3)->format('Y-m-d'),
                'time' => '15:30',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'scheduled_date' => $originalDate,
            'status' => 'Completed',
        ]);
    }

    public function test_reading_doctor_sees_pending_reading_on_their_dashboard(): void
    {
        [$readerUser, $readerProvider] = $this->makeDoctor();
        [$attendingUser, $attendingProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');
        $appointment->update([
            'reader_provider_id' => $readerProvider->id,
            'reading_status' => 'pending',
            'reading_date' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($readerUser)
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Doctor')
                ->has('readingQueue', 1)
                ->where('readingQueue.0.id', $appointment->id)
                ->where('readingQueue.0.requested_by', $attendingUser->name)
            );
    }

    public function test_reading_doctor_can_open_the_completed_consultation(): void
    {
        [$readerUser, $readerProvider] = $this->makeDoctor();
        [, $attendingProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');
        $appointment->update([
            'reader_provider_id' => $readerProvider->id,
            'reading_status' => 'pending',
        ]);

        $this->actingAs($readerUser)
            ->getJson(route('consultation.show', $appointment))
            ->assertOk()
            ->assertJsonPath('appointment.is_reading_doctor', true)
            ->assertJsonPath('appointment.can_edit_reading', true)
            ->assertJsonPath('appointment.reading_status', 'pending');
    }

    public function test_reading_doctor_cannot_open_another_doctors_appointment(): void
    {
        [$otherUser] = $this->makeDoctor();
        [, $attendingProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');

        $this->actingAs($otherUser)
            ->getJson(route('consultation.show', $appointment))
            ->assertForbidden();
    }

    public function test_reading_doctor_save_preserves_attending_soap_completes_reading_and_audits(): void
    {
        [$readerUser, $readerProvider] = $this->makeDoctor();
        [, $attendingProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');
        $appointment->update([
            'reader_provider_id' => $readerProvider->id,
            'reading_status' => 'pending',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'provider_id' => $attendingProvider->id,
            'encounter_type' => 'echo',
            'status' => 'completed',
            'chief_complaint' => 'Original complaint',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        SoapNote::create([
            'encounter_id' => $encounter->id,
            'subjective' => 'Original complaint',
            'objective' => 'BP: 120/80 mmHg',
            'assessment' => 'Original impression',
            'plan' => 'Original plan that must survive',
            'ai_summary' => 'Original summary',
        ]);

        $this->actingAs($readerUser)
            ->post(route('appointments.complete', $appointment), [
                'symptoms' => 'Original complaint',
                'diagnosis' => 'Reading impression revised',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('Completed', $appointment->status);
        $this->assertSame('completed', $appointment->reading_status);
        $this->assertNotNull($appointment->reading_completed_at);
        $this->assertSame($readerProvider->id, $appointment->reader_provider_id);

        $soap = $encounter->fresh()->soapNote;
        $this->assertSame('Original complaint', $soap->subjective);
        $this->assertSame('Reading impression revised', $soap->assessment);
        $this->assertSame('Original plan that must survive', $soap->plan);
        $this->assertSame('Original summary', $soap->ai_summary);

        $this->assertTrue(
            AuditLog::where('user_id', $readerUser->id)
                ->where('action', 'like', '%Reading doctor%')
                ->where('action', 'like', '%#'.$appointment->id.'%')
                ->exists(),
            'The reading-doctor save must leave an audit entry.'
        );
    }

    public function test_reading_doctor_cannot_reopen_a_completed_reading(): void
    {
        [$readerUser, $readerProvider] = $this->makeDoctor();
        [, $attendingProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');
        $appointment->update([
            'reader_provider_id' => $readerProvider->id,
            'reading_status' => 'completed',
            'reading_completed_at' => now(),
        ]);

        $this->actingAs($readerUser)
            ->post(route('appointments.complete', $appointment), [
                'symptoms' => 'Late change',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'reading_status' => 'completed',
        ]);
    }

    public function test_receptionist_can_assign_a_reading_doctor_to_a_completed_procedure(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        [, $attendingProvider] = $this->makeDoctor();
        [, $readerProvider] = $this->makeDoctor();

        $appointment = $this->makeAppointment($attendingProvider, 'Completed', 'Procedure');

        $this->actingAs($receptionist)
            ->put(route('appointments.reading', $appointment), [
                'reader_provider_id' => $readerProvider->id,
                'reading_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'reader_provider_id' => $readerProvider->id,
            'reading_status' => 'pending',
        ]);
    }
}
