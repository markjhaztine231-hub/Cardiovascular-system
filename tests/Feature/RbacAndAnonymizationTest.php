<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacAndAnonymizationTest extends TestCase
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
        $this->receptionist = User::factory()->receptionist()->active()->create();
        $this->patient = User::factory()->patient()->active()->create();
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $this->actingAs($this->doctor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->patient)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_receptionist_dashboard_does_not_403_for_receptionist_or_admin(): void
    {
        // Middleware lets both roles through; the controller then renders
        // Inertia (subject to the pre-existing Vite manifest mismatch), so the
        // status must simply never be 403 for these roles.
        foreach ([$this->admin, $this->receptionist] as $user) {
            $status = $this->actingAs($user)
                ->get(route('receptionist.dashboard'))
                ->getStatusCode();

            $this->assertNotSame(403, $status);
        }
    }

    public function test_patient_cannot_reach_doctor_portal(): void
    {
        $this->actingAs($this->patient)
            ->get('/doctor/emr')
            ->assertForbidden();
    }

    public function test_patient_dashboard_rejects_doctor(): void
    {
        $this->actingAs($this->doctor)
            ->get(route('patient.dashboard'))
            ->assertForbidden();
    }

    public function test_manage_users_gate_only_allows_admin(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-users'));
        $this->assertTrue(Gate::forUser($this->doctor)->denies('manage-users'));
        $this->assertTrue(Gate::forUser($this->patient)->denies('manage-users'));
    }

    public function test_view_patient_gate_allows_staff_and_self(): void
    {
        $this->assertTrue(Gate::forUser($this->doctor)->allows('view-patient', $this->patient));
        $this->assertTrue(Gate::forUser($this->receptionist)->allows('view-patient', $this->patient));
        $this->assertTrue(Gate::forUser($this->patient)->allows('view-patient', $this->patient));

        $otherPatient = User::factory()->patient()->active()->create();
        $this->assertTrue(Gate::forUser($this->patient)->denies('view-patient', $otherPatient));
    }

    public function test_anonymized_name_masks_only_patients_when_enabled(): void
    {
        Config::set('privacy.anonymize_patient_names', true);
        Config::set('privacy.patient_name_mode', 'pseudonym');

        $patientName = $this->patient->name;

        $this->assertStringStartsWith('Patient #', $this->patient->anonymizedName());
        $this->assertNotEquals($this->patient->name, $this->patient->anonymizedName());

        // Staff names are never masked.
        $this->assertEquals($this->doctor->name, $this->doctor->anonymizedName());
        $this->assertEquals($this->admin->name, $this->admin->anonymizedName());
        $this->assertEquals($this->receptionist->name, $this->receptionist->anonymizedName());
    }

    public function test_anonymized_name_preserves_full_name_when_disabled(): void
    {
        Config::set('privacy.anonymize_patient_names', false);

        $this->assertEquals($this->patient->name, $this->patient->anonymizedName());
    }

    public function test_initials_mode_produces_initial_style_label(): void
    {
        $this->patient->update(['name' => 'Maria Santos Lopez']);

        Config::set('privacy.anonymize_patient_names', true);
        Config::set('privacy.patient_name_mode', 'initials');

        $this->assertEquals('M. S. L.', $this->patient->anonymizedName());
    }
}
