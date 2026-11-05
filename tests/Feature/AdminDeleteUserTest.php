<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDeleteUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function appointmentFor(Provider $provider, Patient $patient): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'service_type' => 'Consultation',
            'status' => 'Scheduled',
        ]);
    }

    public function test_admin_deletes_a_staff_account_without_clinical_history(): void
    {
        $target = User::factory()->receptionist()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.delete', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Deleted user account: '.$target->email,
        ]);
    }

    public function test_admin_deletes_a_doctor_without_patient_history(): void
    {
        $provider = Provider::factory()->create();
        $userId = $provider->user_id;

        $this->actingAs($this->admin())
            ->delete(route('admin.users.delete', $provider->user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_a_doctor_with_appointments_cannot_be_deleted(): void
    {
        $provider = Provider::factory()->create();
        $this->appointmentFor($provider, Patient::factory()->create());

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.delete', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
        $this->assertDatabaseHas('providers', ['id' => $provider->id]);
    }

    public function test_a_patient_with_appointments_cannot_be_deleted(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $this->appointmentFor($provider, $patient);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.delete', $patient->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $patient->user_id]);
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    public function test_an_account_that_uploaded_patient_documents_cannot_be_deleted(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create();

        Document::create([
            'patient_id' => $patient->id,
            'uploaded_by' => $receptionist->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.delete', $receptionist))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $receptionist->id]);
    }

    public function test_directory_explains_why_an_account_with_history_cannot_be_deleted(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $this->appointmentFor($provider, $patient);

        $this->actingAs($this->admin())
            ->get(route('admin.users.directory', ['search' => $patient->user->email]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->where('users.total', 1)
                ->where('users.data.0.id', $patient->user_id)
                ->where('users.data.0.is_self', false)
                ->where('users.data.0.delete_blocked_reason', fn ($reason) => is_string($reason)
                    && str_contains($reason, '1 appointment')
                    && str_contains($reason, 'Inactive'))
            );
    }

    public function test_directory_allows_deleting_an_account_without_clinical_history(): void
    {
        $receptionist = User::factory()->receptionist()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.directory', ['search' => $receptionist->email]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->where('users.total', 1)
                ->where('users.data.0.delete_blocked_reason', null)
            );
    }

    public function test_admin_can_set_an_account_with_history_to_inactive(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $this->appointmentFor($provider, $patient);

        $this->actingAs($this->admin())
            ->patch(route('admin.users.status', $patient->user), ['status' => 'Inactive'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'status' => 'Inactive',
        ]);
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Updated account status for '.$patient->user->email.' to Inactive',
        ]);
    }

    public function test_admin_cannot_change_their_own_status(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/admin/users-directory')
            ->patch(route('admin.users.status', $admin), ['status' => 'Inactive'])
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'status' => 'Active',
        ]);
    }

    public function test_non_admin_cannot_change_account_status(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs(User::factory()->receptionist()->create())
            ->patch(route('admin.users.status', $patient), ['status' => 'Inactive'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $patient->id,
            'status' => 'Active',
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.delete', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_purge_a_test_doctor_with_only_appointments(): void
    {
        $provider = Provider::factory()->create();
        $email = $provider->user->email;
        $appointment = $this->appointmentFor($provider, Patient::factory()->create());

        $this->actingAs($this->admin())
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $provider->user_id]);
        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Purged test doctor account: '.$email.' (1 appointment removed)',
        ]);
    }

    public function test_purge_is_refused_when_the_doctor_has_a_visit(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $appointment = $this->appointmentFor($provider, $patient);

        Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'consultation',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_purge_is_refused_when_the_doctor_has_a_prescription(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $appointment = $this->appointmentFor($provider, $patient);

        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Aspirin',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
        $this->assertDatabaseHas('prescriptions', ['provider_id' => $provider->id]);
    }

    public function test_purge_is_refused_when_the_doctor_completed_a_reading(): void
    {
        $provider = Provider::factory()->create();
        $appointment = $this->appointmentFor($provider, Patient::factory()->create());
        $appointment->update([
            'reading_status' => 'completed',
            'reading_completed_at' => now(),
            'reading_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
    }

    public function test_purge_is_refused_when_the_doctor_completed_a_reading_for_another_doctor(): void
    {
        $provider = Provider::factory()->create();
        $attending = Provider::factory()->create();
        $appointment = $this->appointmentFor($attending, Patient::factory()->create());
        $appointment->update([
            'reader_provider_id' => $provider->id,
            'reading_status' => 'completed',
            'reading_completed_at' => now(),
            'reading_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_purge_is_refused_when_an_appointment_has_patient_documents(): void
    {
        $provider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $appointment = $this->appointmentFor($provider, $patient);

        Document::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'uploaded_by' => User::factory()->receptionist()->create()->id,
            'type' => 'prescription',
            'original_filename' => 'rx.png',
            'image_path' => 'documents/rx.png',
            'ocr_status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $provider->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_purge_is_refused_for_a_patient_account(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->admin())
            ->from('/admin/users-directory')
            ->delete(route('admin.users.purge', $patient->user))
            ->assertRedirect('/admin/users-directory')
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('users', ['id' => $patient->user_id]);
    }

    public function test_non_admin_cannot_purge_accounts(): void
    {
        $provider = Provider::factory()->create();

        $this->actingAs(User::factory()->receptionist()->create())
            ->delete(route('admin.users.purge', $provider->user))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $provider->user_id]);
    }
}
