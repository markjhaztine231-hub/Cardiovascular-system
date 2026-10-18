<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAndIdorMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    private User $receptionist;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->active()->create();
        $this->doctor = User::factory()->doctor()->active()->create();
        Provider::factory()->create(['user_id' => $this->doctor->id]);
        $this->receptionist = User::factory()->receptionist()->active()->create();
        $this->patient = Patient::factory()->create()->user;
    }

    public function test_role_portal_access_matrix(): void
    {
        $users = [
            'admin' => $this->admin,
            'doctor' => $this->doctor,
            'receptionist' => $this->receptionist,
            'patient' => $this->patient,
        ];

        $portals = [
            'admin' => route('admin.dashboard'),
            'receptionist' => route('receptionist.dashboard'),
            'doctor' => route('doctor.emr'),
            'patient' => route('patient.dashboard'),
        ];

        // Which role is allowed into which portal (admin may also run reception).
        $allowed = [
            'admin' => ['admin', 'receptionist'],
            'doctor' => ['doctor'],
            'receptionist' => ['receptionist'],
            'patient' => ['patient'],
        ];

        foreach ($users as $role => $user) {
            foreach ($portals as $portal => $url) {
                $status = $this->actingAs($user)->get($url)->getStatusCode();

                if (in_array($portal, $allowed[$role], true)) {
                    $this->assertNotSame(403, $status, "{$role} should be able to reach the {$portal} portal");
                } else {
                    $this->assertSame(403, $status, "{$role} must not reach the {$portal} portal");
                }
            }
        }
    }

    public function test_patient_cannot_cancel_another_patients_appointment(): void
    {
        [$otherPatient, $appointment] = $this->appointmentForAnotherPatient();

        $this->actingAs($this->patient)
            ->post(route('appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame('Scheduled', $appointment->fresh()->status);
        $this->assertSame($otherPatient->id, $appointment->fresh()->patient_id);
    }

    public function test_patient_cannot_request_reschedule_for_another_patients_appointment(): void
    {
        [, $appointment] = $this->appointmentForAnotherPatient();

        $this->actingAs($this->patient)
            ->post(route('appointments.request-reschedule', $appointment), [
                'date' => Carbon::today()->addDays(4)->toDateString(),
                'time' => '11:00',
            ])
            ->assertForbidden();

        $this->assertSame('Scheduled', $appointment->fresh()->status);
    }

    public function test_calendar_download_only_contains_the_requesting_patients_data(): void
    {
        [$otherPatient] = $this->appointmentForAnotherPatient('OtherPatientOnlyMarker');

        $body = $this->actingAs($this->patient)
            ->get(route('patient.calendar.download'))
            ->getContent();

        $this->assertStringNotContainsString('OtherPatientOnlyMarker', $body);
    }

    public function test_doctor_cannot_use_patient_only_appointment_actions(): void
    {
        [, $appointment] = $this->appointmentForAnotherPatient();

        $this->actingAs($this->doctor)
            ->post(route('appointments.cancel', $appointment))
            ->assertForbidden();
    }

    /**
     * @return array{0: Patient, 1: Appointment}
     */
    private function appointmentForAnotherPatient(string $serviceType = 'Consultation'): array
    {
        $otherPatient = Patient::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $otherPatient->id,
            'provider_id' => Provider::factory()->create()->id,
            'scheduled_date' => Carbon::today()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00:00',
            'service_type' => $serviceType,
            'status' => 'Scheduled',
        ]);

        return [$otherPatient, $appointment];
    }
}
