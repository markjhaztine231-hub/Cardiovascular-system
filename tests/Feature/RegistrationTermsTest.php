<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Support\TermsOfService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTermsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A password that satisfies the controller's Password::min(12)
     * ->mixedCase()->numbers()->symbols() rule. `uncompromised()` reaches out
     * to the HIBP range API, which is faked below so the suite stays offline
     * and deterministic.
     */
    private const VALID_PASSWORD = 'Str0ng!Passw0rd2026';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.dela@clinic.test',
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'terms_accepted' => true,
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Content integrity
    // ------------------------------------------------------------------

    public function test_terms_content_file_is_present_and_structurally_sound()
    {
        $payload = TermsOfService::payload();

        $this->assertNotEmpty($payload['title']);
        $this->assertNotEmpty($payload['blocks']);

        $types = array_column($payload['blocks'], 'type');
        $this->assertContains('section', $types);
        $this->assertContains('subsection', $types);
        $this->assertContains('paragraph', $types);
        $this->assertContains('bullets', $types);
        $this->assertContains('warning', $types);

        // 12 numbered sections, 1 through 12.
        $sections = array_values(array_filter(
            $payload['blocks'],
            fn ($b) => $b['type'] === 'section'
        ));
        $this->assertCount(12, $sections);
        $this->assertStringStartsWith('1.', $sections[0]['heading']);
        $this->assertStringStartsWith('12.', $sections[11]['heading']);

        // Every bullet marker in the source document must survive extraction.
        $bullets = array_sum(array_map(
            fn ($b) => $b['type'] === 'bullets' ? count($b['items']) : 0,
            $payload['blocks']
        ));
        $this->assertSame(32, $bullets);

        // The Section 4.1 emergency notice is all-caps in the source and must be
        // lifted out as a warning rather than buried in ordinary body copy.
        $warnings = array_values(array_filter(
            $payload['blocks'],
            fn ($b) => $b['type'] === 'warning'
        ));
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString(
            'NOT A CRITICAL CARE DISPATCH PLATFORM',
            $warnings[0]['text']
        );

        // The document must not arrive with extraction whitespace damage.
        foreach ($payload['blocks'] as $block) {
            $texts = match ($block['type']) {
                'paragraph', 'warning' => [$block['text']],
                'bullets' => $block['items'],
                default => [$block['heading']],
            };

            foreach ($texts as $text) {
                $this->assertStringNotContainsString('  ', $text, 'Double space in: '.$text);
                $this->assertStringNotContainsString("\n", $text);
                $this->assertStringNotContainsString("\u{200B}", $text, 'Zero-width space in: '.$text);
            }
        }
    }

    public function test_terms_version_is_derived_from_the_effective_date()
    {
        $this->assertSame('2026-09-21', TermsOfService::version());
        $this->assertSame(
            'Terms of Service and Patient Agreement',
            TermsOfService::shortTitle()
        );
    }

    // ------------------------------------------------------------------
    // Public terms page
    // ------------------------------------------------------------------

    public function test_terms_page_is_publicly_viewable_and_renders_the_agreement()
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/TermsOfService')
                ->where('version', '2026-09-21')
                ->where('terms.title', 'TERMS OF SERVICE AND PATIENT AGREEMENT')
                ->where('terms.effective_date', 'September 21, 2026')
                ->where('terms.governing_jurisdiction', 'Republic of the Philippines')
                ->has('terms.blocks', 77)
                ->etc());
    }

    public function test_terms_page_remains_reachable_once_authenticated()
    {
        $user = User::factory()->role('patient')->create();

        $this->actingAs($user)->get(route('terms'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Registration form
    // ------------------------------------------------------------------

    public function test_registration_screen_offers_the_terms_with_version_and_link()
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('terms.title', 'Terms of Service and Patient Agreement')
                ->where('terms.version', '2026-09-21')
                ->where('terms.effective_date', 'September 21, 2026')
                ->where('terms.url', route('terms'))
                ->etc());
    }

    // ------------------------------------------------------------------
    // Server-side enforcement
    // ------------------------------------------------------------------

    public function test_registration_is_rejected_when_terms_are_not_accepted()
    {
        $payload = $this->registrationPayload();
        unset($payload['terms_accepted']);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('terms_accepted');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'juan.dela@clinic.test']);
    }

    public function test_registration_is_rejected_when_the_checkbox_is_explicitly_false()
    {
        $this->from(route('register'))
            ->post(route('register'), $this->registrationPayload([
                'terms_accepted' => false,
            ]))
            ->assertSessionHasErrors('terms_accepted');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'juan.dela@clinic.test']);
    }

    /**
     * The `accepted` rule also rejects the truthy strings a crafted request can
     * send, which an HTML checkbox plus `required` would not stop.
     */
    public function test_registration_is_rejected_for_string_falsey_values()
    {
        foreach (['false', '0'] as $value) {
            $this->from(route('register'))
                ->post(route('register'), $this->registrationPayload([
                    'terms_accepted' => $value,
                ]))
                ->assertSessionHasErrors('terms_accepted');
        }

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'juan.dela@clinic.test']);
    }

    // ------------------------------------------------------------------
    // Successful acceptance
    // ------------------------------------------------------------------

    public function test_accepted_registration_records_acceptance_and_version()
    {
        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('patient.dashboard'));

        $user = User::whereEmail('juan.dela@clinic.test')->firstOrFail();

        $this->assertSame('patient', $user->role);
        $this->assertNotNull($user->terms_accepted_at, 'Acceptance timestamp not recorded.');
        $this->assertSame('2026-09-21', $user->terms_version);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('patients', ['user_id' => $user->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'New patient registered: juan.dela@clinic.test'
                .' (accepted Terms v2026-09-21 from IP: 127.0.0.1)',
        ]);
    }

    public function test_acceptance_timestamp_is_a_cast_datetime()
    {
        $this->post(route('register'), $this->registrationPayload());

        $user = User::whereEmail('juan.dela@clinic.test')->firstOrFail();

        $this->assertInstanceOf(CarbonInterface::class, $user->terms_accepted_at);
        $this->assertTrue($user->terms_accepted_at->isToday());
    }

    /**
     * Accounts the Clinic creates from the admin panel never pass through the
     * public form, so they must not be forced to record acceptance.
     */
    public function test_admin_created_staff_accounts_do_not_require_terms_acceptance()
    {
        User::create([
            'name' => 'Staff Member',
            'email' => 'staff@clinic.test',
            'password' => self::VALID_PASSWORD,
            'role' => 'receptionist',
        ]);

        $staff = User::whereEmail('staff@clinic.test')->firstOrFail();

        $this->assertNull($staff->terms_accepted_at);
        $this->assertNull($staff->terms_version);
    }

    public function test_patient_demographics_still_apply_alongside_terms_acceptance()
    {
        $this->post(route('register'), $this->registrationPayload([
            'date_of_birth' => '1985-03-15',
            'sex' => 'Male',
        ]))->assertRedirect(route('patient.dashboard'));

        $user = User::whereEmail('juan.dela@clinic.test')->firstOrFail();
        $patient = Patient::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Male', $patient->sex);
        $this->assertSame('1985-03-15', $patient->date_of_birth?->toDateString());
    }

    public function test_future_date_of_birth_is_rejected()
    {
        $this->post(route('register'), $this->registrationPayload([
            'date_of_birth' => now()->addDay()->format('Y-m-d'),
        ]))->assertSessionHasErrors('date_of_birth');

        $this->assertDatabaseMissing('users', ['email' => 'juan.dela@clinic.test']);
    }
}
