<?php

namespace Tests\Feature;

use App\Events\AppointmentRescheduled;
use App\Events\RescheduleDenied;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RescheduleNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Appointment, 2: Patient, 3: Carbon}
     */
    private function makeRescheduleRequest(): array
    {
        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();
        $receptionist = User::factory()->receptionist()->create();
        $requested = Carbon::today()->addDays(5);

        DoctorSchedule::create([
            'provider_id' => $provider->id,
            'day_of_week' => [$requested->dayOfWeekIso],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'service_type' => 'Consultation',
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $requested->format('Y-m-d'),
            'reschedule_requested_time' => '10:00:00',
        ]);

        return [$receptionist, $appointment, $patient, $requested];
    }

    public function test_approval_notifies_the_patient_with_the_new_schedule(): void
    {
        [$receptionist, $appointment, $patient, $requested] = $this->makeRescheduleRequest();

        Event::fake([AppointmentRescheduled::class]);

        $this->actingAs($receptionist)
            ->post(route('appointments.approve-reschedule', $appointment))
            ->assertRedirect();

        Event::assertDispatched(
            AppointmentRescheduled::class,
            fn (AppointmentRescheduled $event): bool => $event->appointmentId === $appointment->id
                && $event->patientId === $patient->user_id
                && $event->newDate === $requested->format('M d, Y')
                && $event->newTime === '10:00 AM'
                && $event->broadcastOn()->name === 'patient.'.$patient->user_id
        );
    }

    public function test_denial_notifies_the_patient_that_the_original_schedule_is_kept(): void
    {
        [$receptionist, $appointment, $patient] = $this->makeRescheduleRequest();
        $originalDate = $appointment->scheduled_date;

        Event::fake([RescheduleDenied::class]);

        $this->actingAs($receptionist)
            ->post(route('appointments.deny-reschedule', $appointment))
            ->assertRedirect();

        Event::assertDispatched(
            RescheduleDenied::class,
            fn (RescheduleDenied $event): bool => $event->appointmentId === $appointment->id
                && $event->patientId === $patient->user_id
                && $event->scheduledDate === $originalDate->format('M d, Y')
                && $event->scheduledTime === '09:00 AM'
                && $event->broadcastOn()->name === 'patient.'.$patient->user_id
        );
    }

    public function test_rejected_approval_does_not_notify_the_patient(): void
    {
        [$receptionist, $appointment] = $this->makeRescheduleRequest();

        $appointment->update(['reschedule_requested_time' => '19:00:00']);

        Event::fake([AppointmentRescheduled::class]);

        $this->actingAs($receptionist)
            ->from(route('receptionist.dashboard'))
            ->post(route('appointments.approve-reschedule', $appointment))
            ->assertSessionHasErrors('time');

        Event::assertNotDispatched(AppointmentRescheduled::class);
    }
}
