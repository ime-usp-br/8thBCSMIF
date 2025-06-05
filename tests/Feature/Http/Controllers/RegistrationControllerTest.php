<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(\App\Http\Controllers\RegistrationController::class)]
#[Group('controller')]
#[Group('registration-controller')]
class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class); // Ensures event codes exist for validation
        $this->seed(FeesTableSeeder::class); // Ensures fees exist for FeeCalculationService (AC7)
    }

    /**
     * Helper to get valid data for registration.
     */
    private function getValidRegistrationData(User $user, array $overrides = []): array
    {
        $event = Event::firstOrFail(); // Get a valid event code

        return array_merge([
            'full_name' => $user->name,
            'nationality' => 'Brazilian',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'document_country_origin' => 'Brasil', // For CPF/RG
            'cpf' => '123.456.789-00',
            'rg_number' => '1234567',
            'passport_number' => null,
            'passport_expiry_date' => null,
            'email' => $user->email,
            'phone_number' => '+55 11 987654321',
            'address_street' => 'Rua Exemplo, 123',
            'address_city' => 'São Paulo',
            'address_state_province' => 'SP',
            'address_country' => 'Brasil',
            'address_postal_code' => '01000-000',
            'affiliation' => 'University of Example',
            'position' => 'grad_student', // Default position for fee calculation
            'is_abe_member' => false,      // Default ABE status
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-10-03',
            'selected_event_codes' => [$event->code],
            'participation_format' => 'in-person', // Default participation format
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => false,
            'dietary_restrictions' => 'none',
            'other_dietary_restrictions' => null,
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_relationship' => 'Friend',
            'emergency_contact_phone' => '+55 11 912345678',
            'requires_visa_letter' => false,
            'sou_da_usp' => false, // For non-USP user by default
            'codpes' => null,
            'confirm_information_accuracy' => true,
            'confirm_data_processing_consent' => true,
        ], $overrides);
    }

    #[Test]
    public function store_validates_calculates_fee_and_creates_registration_successfully(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        $mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $event = Event::where('code', $mainConferenceCode)->firstOrFail();

        $earlyBirdDate = Carbon::parse($event->registration_deadline_early)->subDay();
        Carbon::setTestNow($earlyBirdDate);

        $validData = $this->getValidRegistrationData($user, [
            'selected_event_codes' => [$event->code],
            'position' => 'grad_student',
            'is_abe_member' => false,
            'participation_format' => 'in-person',
        ]);

        $response = $this->post(route('event-registrations.store'), $validData);

        $response->assertOk();
        $response->assertJsonPath('message', __('registrations.validation_successful'));

        // AC7: Assert fee_data structure and content
        $response->assertJsonStructure([
            'message',
            'registration_id', // Added for AC8
            'data',
            'fee_data' => [
                'details',
                'total_fee',
            ],
        ]);
        $this->assertEquals(600.00, $response->json('fee_data.total_fee'));
        $this->assertEquals($mainConferenceCode, $response->json('fee_data.details.0.event_code'));
        $this->assertEquals(600.00, $response->json('fee_data.details.0.calculated_price'));

        // AC8: Assert registration_id is present and registration is in database
        $registrationId = $response->json('registration_id');
        $this->assertNotNull($registrationId);
        $this->assertIsInt($registrationId);

        $this->assertDatabaseHas('registrations', [
            'id' => $registrationId,
            'user_id' => $user->id,
            'full_name' => $validData['full_name'],
            'email' => $validData['email'],
            'registration_category_snapshot' => 'grad_student', // based on 'position' => 'grad_student'
            'calculated_fee' => 600.00, // Expected fee from FeesTableSeeder for this setup
            'position' => $validData['position'],
            'is_abe_member' => $validData['is_abe_member'],
            'participation_format' => $validData['participation_format'],
            'document_country_origin' => $validData['document_country_origin'],
            'cpf' => $validData['cpf'],
            // payment_status will be checked in AC9 tests
        ]);

        // Verify some nullable fields are correctly stored if provided
        $this->assertDatabaseHas('registrations', [
            'id' => $registrationId,
            'nationality' => $validData['nationality'],
            'date_of_birth' => Carbon::parse($validData['date_of_birth'])->format('Y-m-d H:i:s'),
        ]);

        $registration = Registration::find($registrationId);
        $this->assertNotNull($registration);
        // Check boolean casts (example)
        $this->assertFalse($registration->is_abe_member); // From $validData
        $this->assertFalse($registration->needs_transport_from_gru); // From $validData

        Carbon::setTestNow(); // Reset Carbon mock
    }

    #[Test]
    public function store_fails_validation_via_store_registration_request_with_missing_full_name(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        $invalidData = $this->getValidRegistrationData($user, ['full_name' => '']);

        $response = $this->post(route('event-registrations.store'), $invalidData);

        $response->assertStatus(302); // Default FormRequest validation failure redirect
        $response->assertSessionHasErrors('full_name');
    }

    #[Test]
    public function store_fails_validation_via_store_registration_request_with_missing_selected_event_codes(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        $invalidData = $this->getValidRegistrationData($user, ['selected_event_codes' => []]);

        $response = $this->post(route('event-registrations.store'), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('selected_event_codes');
    }

    #[Test]
    public function store_fails_for_usp_user_if_codpes_is_required_but_missing_or_invalid(): void
    {
        $user = User::factory()->create(['email' => 'testusp@usp.br']); // USP Email
        $user->markEmailAsVerified();
        $this->actingAs($user);

        // StoreRegistrationRequest currently makes `codpes` nullable.
        // For this test, we rely on the `numeric` and `digits_between` rules from StoreRegistrationRequest
        // for 'codpes' when 'sou_da_usp' is true and an invalid 'codpes' is provided.
        // The livewire component `register.blade.php` has `Rule::requiredIf($this->sou_da_usp)` for `codpes`.
        // However, `StoreRegistrationRequest` itself doesn't have this explicit required_if for `codpes`
        // based on `sou_da_usp`. If it did, this test would need to check for `codpes.required`.
        // Currently, the validation will pass if `codpes` is null, even if `sou_da_usp` is true.
        // We test for an *invalid* non-null `codpes` to trigger other rules.

        $invalidData = $this->getValidRegistrationData($user, [
            'sou_da_usp' => true,
            'codpes' => 'ABCDEFG', // Invalid (non-numeric)
        ]);

        $response = $this->post(route('event-registrations.store'), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['codpes' => __('validation.custom.registration.codpes_numeric')]);

        $invalidDataShort = $this->getValidRegistrationData($user, [
            'sou_da_usp' => true,
            'codpes' => '123', // Invalid (too short)
        ]);
        $responseShort = $this->post(route('event-registrations.store'), $invalidDataShort);
        $responseShort->assertStatus(302);
        $responseShort->assertSessionHasErrors(['codpes' => __('validation.custom.registration.codpes_digits_between', ['min' => 6, 'max' => 8])]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_store_registration_route(): void
    {
        $response = $this->post(route('event-registrations.store'), []);
        $response->assertRedirect(route('login.local'));
    }

    #[Test]
    public function authenticated_but_unverified_user_is_redirected_from_store_registration_route(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $response = $this->post(route('event-registrations.store'), []);
        $response->assertRedirect(route('verification.notice'));
    }
}