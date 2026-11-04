<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.delete', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
