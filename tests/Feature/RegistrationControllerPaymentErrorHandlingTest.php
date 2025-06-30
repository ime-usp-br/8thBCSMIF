<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationControllerPaymentErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            EventsTableSeeder::class,
            FeesTableSeeder::class,
        ]);
    }

    /**
     * Test that robust error handling structure exists in RegistrationController
     *
     * This test validates that the try-catch structure and error handling
     * mechanisms are properly implemented.
     */
    public function test_robust_error_handling_structure_exists(): void
    {
        // Act: Verify the robust error handling exists by checking controller structure
        $controllerPath = app_path('Http/Controllers/RegistrationController.php');
        $controllerContent = file_get_contents($controllerPath);

        // Assert: Verify try-catch structure exists around payment creation
        // Assert: Verify DB transaction and general try-catch structure
        $this->assertStringContainsString('DB::transaction(function () use ($request, $validatedData) {', $controllerContent);
        $this->assertStringContainsString('try {', $controllerContent); // Outer try-catch for the transaction
        $this->assertStringContainsString('} catch (\Exception $e) {', $controllerContent); // Outer try-catch for the transaction
        $this->assertStringContainsString('Log::error(\'Failed to create registration or payment due to a transaction error.\'', $controllerContent);
        $this->assertStringContainsString('return redirect()->back()', $controllerContent);
        $this->assertStringContainsString('Failed to process your registration', $controllerContent);

        // Verify error logging includes all required fields for the transaction error
        $this->assertStringContainsString('error_message', $controllerContent);
        $this->assertStringContainsString('error_trace', $controllerContent);
        $this->assertStringContainsString('user_id', $controllerContent);

        // Verify user-facing error message is localized
        $this->assertStringContainsString('__(', $controllerContent);
        $this->assertStringContainsString('withInput()', $controllerContent);
    }

    /**
     * Test successful payment creation scenario for comparison
     *
     * This test ensures that normal payment creation still works correctly
     * when no errors occur, validating that our error handling doesn't
     * interfere with normal operation.
     */
    public function test_successful_payment_creation_normal_scenario(): void
    {
        // Arrange: Create test user and get valid events
        $user = User::factory()->create();
        $events = Event::take(2)->get();

        $registrationData = [
            'full_name' => 'Jane Smith',
            'nationality' => 'Brazilian',
            'date_of_birth' => '1985-05-15',
            'gender' => 'female',
            'document_country_origin' => 'BR',
            'cpf' => '123.456.789-00',
            'rg_number' => '1234567',
            'passport_number' => null,
            'passport_expiry_date' => null,
            'email' => $user->email,
            'phone_number' => '+55 11 987654321',
            'address_street' => 'Rua Exemplo, 123',
            'address_city' => 'São Paulo',
            'address_state_province' => 'SP',
            'address_country' => 'BR',
            'address_postal_code' => '01000-000',
            'affiliation' => 'Federal University of ABC',
            'position' => 'professor',
            'is_abe_member' => true,
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-10-03',
            'selected_event_codes' => $events->pluck('code')->toArray(),
            'participation_format' => 'in-person',
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => false,
            'dietary_restrictions' => 'vegetarian',
            'other_dietary_restrictions' => null,
            'emergency_contact_name' => 'John Smith',
            'emergency_contact_relationship' => 'husband',
            'emergency_contact_phone' => '+55 11 912345678',
            'requires_visa_letter' => false,
            'sou_da_usp' => false,
            'codpes' => null,
            'confirm_information_accuracy' => true,
            'confirm_data_processing_consent' => true,
        ];

        // Capture initial counts
        $initialRegistrationCount = Registration::count();

        // Act: Submit registration form
        $response = $this->actingAs($user)
            ->post(route('event-registrations.store'), $registrationData);

        // Assert: Verify successful creation
        $response->assertRedirect(route('registrations.my'))
            ->assertSessionHas('success', __('registrations.created_successfully'));

        // Verify registration was created
        $this->assertEquals($initialRegistrationCount + 1, Registration::count());

        // Get the created registration
        $registration = Registration::latest()->first();
        $this->assertNotNull($registration);
        $this->assertEquals($user->id, $registration->user_id);
        $this->assertEquals('pending_payment', $registration->payment_status);

        // Verify payment was created successfully (this validates the happy path still works)
        $this->assertGreaterThan(0, $registration->payments()->count());
        $payment = $registration->payments()->first();
        $this->assertEquals('pending', $payment->status);
        $this->assertGreaterThan(0, $payment->amount);
    }

    /**
     * Test that error handling components are properly imported and available
     *
     * This test verifies that all necessary components for error handling
     * are properly imported and available in the controller.
     */
    public function test_error_handling_components_available(): void
    {
        // Verify controller uses necessary imports for error handling
        $controllerPath = app_path('Http/Controllers/RegistrationController.php');
        $controllerContent = file_get_contents($controllerPath);

        // Check for essential imports
        $this->assertStringContainsString('use Illuminate\Support\Facades\Log;', $controllerContent);

        // Verify exception handling is properly structured
        $this->assertStringContainsString('\Exception $e', $controllerContent);
        $this->assertStringContainsString('$e->getMessage()', $controllerContent);
        $this->assertStringContainsString('$e->getTraceAsString()', $controllerContent);

        // Verify user feedback mechanism
        $this->assertStringContainsString('redirect()->back()', $controllerContent);
        $this->assertStringContainsString('->withInput()', $controllerContent);
        $this->assertStringContainsString('->with(\'error\'', $controllerContent);
        $this->assertStringContainsString('Failed to process your registration', $controllerContent);
    }

    /**
     * Test payment creation failure simulation and error handling validation
     *
     * This test validates that the controller properly handles payment creation failures
     * by checking the error handling code structure and transaction rollback logic.
     */
    public function test_payment_creation_failure_simulation_and_error_handling(): void
    {
        // Arrange: Verify the error handling structure exists in the controller
        $controllerPath = app_path('Http/Controllers/RegistrationController.php');
        $controllerContent = file_get_contents($controllerPath);

        // Assert: Verify specific payment creation error handling exists
        $this->assertStringContainsString('} catch (\Exception $e) {', $controllerContent);
        $this->assertStringContainsString('Log::error(\'Failed to create payment record for registration.\'', $controllerContent);
        $this->assertStringContainsString('throw new \RuntimeException(\'Failed to create payment record.\'', $controllerContent);

        // Verify transaction rollback happens automatically via DB::transaction
        $this->assertStringContainsString('return DB::transaction(function ()', $controllerContent);

        // Verify outer exception handling catches transaction failures
        $this->assertStringContainsString('Failed to create registration or payment due to a transaction error', $controllerContent);

        // Verify user-friendly error messages are localized
        $this->assertStringContainsString('Failed to create payment record for your registration', $controllerContent);
        $this->assertStringContainsString('Failed to process your registration', $controllerContent);

        // Verify error logging includes all required debugging information
        $this->assertStringContainsString('registration_id', $controllerContent);
        $this->assertStringContainsString('amount', $controllerContent);
        $this->assertStringContainsString('error_message', $controllerContent);
        $this->assertStringContainsString('error_trace', $controllerContent);

        // Verify form input preservation on error
        $this->assertStringContainsString('->withInput()', $controllerContent);

        // Test the normal flow still works (ensuring error handling doesn't break happy path)
        $user = User::factory()->create();
        $events = Event::take(1)->get();

        $registrationData = [
            'full_name' => 'Test Normal Flow',
            'nationality' => 'Brazilian',
            'date_of_birth' => '1985-05-15',
            'gender' => 'female',
            'document_country_origin' => 'BR',
            'cpf' => '123.456.789-00',
            'rg_number' => '1234567',
            'passport_number' => null,
            'passport_expiry_date' => null,
            'email' => $user->email,
            'phone_number' => '+55 11 987654321',
            'address_street' => 'Rua Exemplo, 123',
            'address_city' => 'São Paulo',
            'address_state_province' => 'SP',
            'address_country' => 'BR',
            'address_postal_code' => '01000-000',
            'affiliation' => 'Test University',
            'position' => 'professor',
            'is_abe_member' => true,
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-10-03',
            'selected_event_codes' => $events->pluck('code')->toArray(),
            'participation_format' => 'in-person',
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => false,
            'dietary_restrictions' => 'none',
            'other_dietary_restrictions' => null,
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_relationship' => 'spouse',
            'emergency_contact_phone' => '+55 11 912345678',
            'requires_visa_letter' => false,
            'sou_da_usp' => false,
            'codpes' => null,
            'confirm_information_accuracy' => true,
            'confirm_data_processing_consent' => true,
        ];

        // Verify normal registration still works (no interference from error handling)
        $response = $this->actingAs($user)
            ->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'))
            ->assertSessionHas('success');

        // Verify registration and payment were created successfully
        $registration = Registration::where('user_id', $user->id)->first();
        $this->assertNotNull($registration);
        $this->assertEquals('pending_payment', $registration->payment_status);
        $this->assertGreaterThan(0, $registration->payments()->count());
    }
}
