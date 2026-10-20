<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PatientCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Patient, 1: Appointment}
     */
    private function makePatientWithData(): array
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->addDays(3)->toDateString(),
            'scheduled_time' => '09:30:00',
            'duration_minutes' => 30,
            'service_type' => 'Consultation',
            'status' => 'Scheduled',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => '2x a day',
            'duration_days' => 7,
            'status' => 'active',
        ]);

        return [$patient, $appointment];
    }

    public function test_patient_can_download_calendar(): void
    {
        [$patient] = $this->makePatientWithData();

        $response = $this->actingAs($patient->user)->get(route('patient.calendar.download'));

        $response->assertOk();
        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
        $this->assertStringContainsString('BEGIN:VEVENT', $body);
        $this->assertStringContainsString('Consultation', $body);
        $this->assertStringContainsString('Metoprolol', $body);
        $this->assertStringContainsString('RRULE:FREQ=DAILY;COUNT=7', $body);
    }

    public function test_guest_cannot_download_calendar(): void
    {
        $this->get(route('patient.calendar.download'))->assertRedirect(route('login'));
    }

    public function test_non_patient_cannot_download_calendar(): void
    {
        $doctor = User::factory()->doctor()->active()->create();

        $this->actingAs($doctor)->get(route('patient.calendar.download'))->assertForbidden();
    }

    public function test_signed_feed_can_be_fetched_without_a_session(): void
    {
        [$patient] = $this->makePatientWithData();

        $url = URL::signedRoute('patient.calendar.feed', ['user' => $patient->user_id]);

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
    }

    public function test_feed_rejects_invalid_signature(): void
    {
        [$patient] = $this->makePatientWithData();

        $url = URL::signedRoute('patient.calendar.feed', ['user' => $patient->user_id]);

        $this->get($url.'tampered')->assertForbidden();
        $this->get(route('patient.calendar.feed', ['user' => $patient->user_id]))->assertForbidden();
    }

    public function test_cancelled_appointments_are_excluded_from_calendar(): void
    {
        [$patient] = $this->makePatientWithData();

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => Provider::factory()->create()->id,
            'scheduled_date' => Carbon::today()->addDays(5)->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'Cancelled',
            'service_type' => 'CancelledCheckup',
        ]);

        $body = $this->actingAs($patient->user)
            ->get(route('patient.calendar.download'))
            ->getContent();

        $this->assertStringNotContainsString('CancelledCheckup', $body);
    }

    public function test_calendar_uses_floating_local_time_without_timezones(): void
    {
        [$patient] = $this->makePatientWithData();

        $body = $this->actingAs($patient->user)
            ->get(route('patient.calendar.download'))
            ->getContent();

        $date = Carbon::today()->addDays(3)->format('Ymd');

        // 09:30 appointment, floating wall-clock time (no trailing Z, no TZID).
        $this->assertStringContainsString('DTSTART:'.$date.'T093000', $body);
        $this->assertStringContainsString('DTEND:'.$date.'T100000', $body);
        $this->assertStringNotContainsString($date.'T093000Z', $body);
        $this->assertStringNotContainsString('TZID', $body);
        $this->assertStringNotContainsString('VTIMEZONE', $body);

        // 2x/day dosing slots.
        $this->assertStringContainsString('DTSTART:'.$date.'T080000', $body);
        $this->assertStringContainsString('DTSTART:'.$date.'T200000', $body);

        $this->assertStringContainsString('LOCATION:Cardiovascular Care Center', $body);
        $this->assertStringContainsString('CATEGORIES:Appointment', $body);
        $this->assertStringContainsString('CATEGORIES:Medication', $body);
        $this->assertStringContainsString('CALSCALE:GREGORIAN', $body);
        $this->assertStringContainsString('METHOD:PUBLISH', $body);
        $this->assertStringContainsString('X-WR-CALNAME:CardioCare - My Health Calendar', $body);
    }

    public function test_as_needed_prescription_is_an_all_day_event(): void
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'Scheduled',
            'service_type' => 'Consultation',
        ]);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => Carbon::now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Nitroglycerin',
            'dosage' => '0.4 mg',
            'frequency' => 'As needed',
            'instructions' => 'For chest pain',
            'route' => 'sublingual',
            'status' => 'active',
        ]);

        $body = $this->actingAs($patient->user)
            ->get(route('patient.calendar.download'))
            ->getContent();

        $this->assertStringContainsString('As needed: Nitroglycerin 0.4 mg', $body);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:'.$appointment->scheduled_date->format('Ymd'), $body);
        $this->assertStringContainsString('CATEGORIES:Medication', $body);
    }

    public function test_appointments_scope_excludes_medication_events(): void
    {
        [$patient] = $this->makePatientWithData();

        $response = $this->actingAs($patient->user)
            ->get(route('patient.calendar.download', ['scope' => 'appointments']));

        $response->assertOk();
        $this->assertStringContainsString(
            'cardiocare-appointments.ics',
            $response->headers->get('Content-Disposition'),
        );

        $body = $response->getContent();
        $this->assertStringContainsString('Consultation', $body);
        $this->assertStringContainsString('CATEGORIES:Appointment', $body);
        $this->assertStringNotContainsString('Metoprolol', $body);
        $this->assertStringNotContainsString('CATEGORIES:Medication', $body);
    }

    public function test_medications_scope_excludes_appointment_events(): void
    {
        [$patient] = $this->makePatientWithData();

        $response = $this->actingAs($patient->user)
            ->get(route('patient.calendar.download', ['scope' => 'medications']));

        $response->assertOk();
        $this->assertStringContainsString(
            'cardiocare-medications.ics',
            $response->headers->get('Content-Disposition'),
        );

        $body = $response->getContent();
        $this->assertStringContainsString('Metoprolol', $body);
        $this->assertStringContainsString('CATEGORIES:Medication', $body);
        $this->assertStringNotContainsString('Consultation', $body);
        $this->assertStringNotContainsString('CATEGORIES:Appointment', $body);
    }

    public function test_invalid_calendar_scope_is_rejected(): void
    {
        [$patient] = $this->makePatientWithData();

        $this->actingAs($patient->user)
            ->get(route('patient.calendar.download', ['scope' => 'everything']))
            ->assertNotFound();
    }
}
