<?php

namespace Tests\Feature;

use App\Events\NewRegistrationCreated;
use App\Mail\NewRegistrationNotification;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class to document and reproduce the email listener bug.
 *
 * BUG IDENTIFIED: The EventServiceProvider has an empty $listen array and
 * shouldDiscoverEvents() returns false, causing the SendNewRegistrationNotifications
 * listener to never be registered. This results in NO emails being sent regardless
 * of COORDINATOR_EMAIL configuration.
 *
 * ROOT CAUSE:
 * - EventServiceProvider.php:18 → protected $listen = [];
 * - EventServiceProvider.php:42 → shouldDiscoverEvents(): bool { return false; }
 * - NewRegistrationCreated event is dispatched but has no listeners
 *
 * CURRENT BEHAVIOR: No emails sent in any scenario
 * EXPECTED BEHAVIOR: Emails should be sent based on COORDINATOR_EMAIL config
 */
#[Group('bug-reproduction')]
#[Group('email-listener-bug')]
class EmailListenerBugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class);
        $this->seed(FeesTableSeeder::class);
    }

    #[Test]
    public function bug_fix_validation_emails_sent_when_coordinator_email_is_empty(): void
    {
        // Setup: COORDINATOR_EMAIL is empty (current .env state)
        config(['mail.coordinator_email' => '']);

        Mail::fake();
        // Don't fake events - we want the real event flow to work to validate the fix

        $user = User::factory()->create([
            'email' => 'participant@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);
        $event = Event::first();

        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'graduate_student',
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Act: Create registration via HTTP request (complete flow)
        $response = $this->post(route('event-registrations.store'), $registrationData);

        // Assert: Registration creation succeeds
        $response->assertRedirect(route('registrations.my'));
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'full_name' => $user->name,
        ]);

        // Assert: Registration created successfully (events work in background)
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'full_name' => $user->name,
        ]);

        // BUG FIX VALIDATION: Emails ARE now sent because listener is registered
        // This validates the fix - event fires and listener executes
        Mail::assertQueued(NewRegistrationNotification::class); // Emails are being sent (fix working)

        // Verify participant email is sent
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === false;
        });
    }

    #[Test]
    public function bug_fix_validation_emails_sent_when_coordinator_email_is_configured(): void
    {
        // Setup: COORDINATOR_EMAIL is configured (should trigger coordinator email)
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        Mail::fake();
        // Don't fake events - we want the real event flow to work to validate the fix

        $user = User::factory()->create([
            'email' => 'participant@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);
        $event = Event::first();

        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'graduate_student',
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Act: Create registration via HTTP request (complete flow)
        $response = $this->post(route('event-registrations.store'), $registrationData);

        // Assert: Registration creation succeeds
        $response->assertRedirect(route('registrations.my'));
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'full_name' => $user->name,
        ]);

        // Assert: Registration created successfully (events work in background)
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'full_name' => $user->name,
        ]);

        // BUG FIX VALIDATION: Emails ARE now sent because listener is registered
        // This validates the fix for scenario with coordinator email configured
        Mail::assertQueued(NewRegistrationNotification::class); // Emails are being sent (fix working)

        // Verify participant email is sent
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === false;
        });

        // Verify coordinator email is sent
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === true;
        });
    }

    #[Test]
    public function bug_fix_validation_emails_now_work_with_deduplication(): void
    {
        // This test validates the final bug fix solution

        config(['mail.coordinator_email' => 'coordinator@example.com']);
        Mail::fake();

        $user = User::factory()->create(['email' => 'participant@example.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'position' => 'graduate_student',
        ]);

        // Act: Dispatch event directly (simulates controller behavior)
        $event = new \App\Events\NewRegistrationCreated($registration);
        event($event);

        // Assert: Emails are sent (bug fixed) but without duplication
        Mail::assertQueued(NewRegistrationNotification::class, 2); // 1 participant + 1 coordinator

        // Verify participant email
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === false;
        });

        // Verify coordinator email
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === true;
        });

        // This validates the complete fix:
        // 1. Listener is registered and working ✅
        // 2. No email duplication ✅
        // 3. Both participant and coordinator receive emails ✅
    }

    #[Test]
    public function bug_reproduction_direct_listener_call_works_emails_are_sent(): void
    {
        // This test proves the Mailable and Listener code is correct
        // The problem is only in the registration/discovery

        config(['mail.coordinator_email' => 'coordinator@example.com']);
        Mail::fake();

        $user = User::factory()->create(['email' => 'participant@example.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'position' => 'graduate_student',
        ]);

        // Manually instantiate and call the listener (bypassing registration)
        $listener = new \App\Listeners\SendNewRegistrationNotifications;
        $event = new NewRegistrationCreated($registration);

        // Act: Call listener directly
        $listener->handle($event);

        // Assert: When called directly, the listener DOES work
        Mail::assertQueued(NewRegistrationNotification::class, 2); // User + Coordinator

        // Verify participant email
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === false;
        });

        // Verify coordinator email
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === true;
        });

        // This proves the Mailable and Listener logic is correct
        // The bug is purely in the EventServiceProvider registration
    }

    /**
     * Helper to get valid registration data for testing.
     */
    private function getValidRegistrationData(User $user, array $overrides = []): array
    {
        $event = Event::first();

        return array_merge([
            'full_name' => $user->name,
            'nationality' => 'Brazilian',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'document_country_origin' => 'Brazil',
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
            'affiliation' => 'University of Example',
            'position' => 'graduate_student',
            'is_abe_member' => false,
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-10-03',
            'selected_event_codes' => [$event->code],
            'participation_format' => 'in-person',
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => false,
            'dietary_restrictions' => 'none',
            'other_dietary_restrictions' => null,
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_relationship' => 'Friend',
            'emergency_contact_phone' => '+55 11 912345678',
            'requires_visa_letter' => false,
            'sou_da_usp' => false,
            'codpes' => null,
            'confirm_information_accuracy' => true,
            'confirm_data_processing_consent' => true,
        ], $overrides);
    }
}
