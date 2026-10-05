<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    private User $doctor;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->patient()->active()->create();
        $this->doctor = User::factory()->doctor()->active()->create();
        $this->receptionist = User::factory()->receptionist()->active()->create();
    }

    public function test_security_headers_are_sent_on_responses(): void
    {
        $response = $this->get('/login')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_strict_transport_security_only_over_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_guests_are_redirected_from_role_portals(): void
    {
        $this->get(route('receptionist.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get('/doctor/emr')->assertRedirect(route('login'));
    }

    public function test_csrf_mismatch_gracefully_sends_user_to_login(): void
    {
        // VerifyCsrfToken skips validation while running unit tests, so a real
        // request can never raise a 419 here. Drive the handler directly instead
        // to cover the mapping registered in bootstrap/app.php.
        $response = app(ExceptionHandler::class)->render(
            Request::create('/logout', 'POST'),
            new TokenMismatchException('CSRF token mismatch.')
        );

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame('Your session has expired. Please log in again.', session('message'));
    }

    public function test_patient_cannot_download_another_patients_clinical_pdf(): void
    {
        $patientA = User::factory()->patient()->active()->create();
        $recordA = Patient::factory()->for($patientA, 'user')->create();

        $patientB = User::factory()->patient()->active()->create();
        $recordB = Patient::factory()->for($patientB, 'user')->create();

        $provider = Provider::factory()->for(User::factory()->doctor(), 'user')->create();

        $appointment = Appointment::create([
            'patient_id' => $recordB->id,
            'provider_id' => $provider->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'service_type' => 'ECG & Heart Checkup',
            'status' => 'Completed',
        ]);

        // The download guard requires a completed consultation with an actual
        // document, otherwise every patient would see a 404 and the security
        // check below would no longer exercise a real download.
        $encounter = Encounter::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $recordB->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        Prescription::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $recordB->id,
            'provider_id' => $provider->id,
            'medication_name' => 'Metoprolol',
            'dosage' => '25 mg',
            'frequency' => 'once daily',
            'status' => 'active',
        ]);

        $this->actingAs($patientA)
            ->get(route('pdf.download', ['appointment' => $appointment->id, 'type' => 'prescription']))
            ->assertForbidden();

        $this->actingAs($patientB)
            ->get(route('pdf.download', ['appointment' => $appointment->id, 'type' => 'prescription']))
            ->assertSuccessful();
    }

    public function test_audit_log_probe_requires_patient_role(): void
    {
        $this->actingAs($this->doctor)
            ->post('/audit/medication-viewed', ['medicationCount' => 2])
            ->assertForbidden();

        $before = AuditLog::count();

        $this->actingAs($this->patient)
            ->post('/audit/medication-viewed', ['medicationCount' => 2])
            ->assertNoContent();

        $this->assertSame($before + 1, AuditLog::count());
    }

    public function test_sensitive_user_attributes_never_serialize(): void
    {
        $this->assertContains('password', $this->patient->getHidden());
        $this->assertContains('two_factor_secret', $this->patient->getHidden());
        $this->assertContains('two_factor_recovery_codes', $this->patient->getHidden());
        $this->assertContains('remember_token', $this->patient->getHidden());

        $array = $this->patient->toArray();
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
    }

    public function test_passwords_are_stored_hashed_not_plaintext(): void
    {
        $user = User::create([
            'name' => 'Secure User',
            'email' => 'secure@clinic.com',
            'password' => 'PlainText_Secret123',
            'role' => 'patient',
            'status' => 'Active',
        ]);

        $this->assertNotSame('PlainText_Secret123', $user->getAuthPassword());
        $this->assertTrue(Hash::check('PlainText_Secret123', $user->getAuthPassword()));
    }
}
