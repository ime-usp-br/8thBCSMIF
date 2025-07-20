<?php

namespace Tests\Feature;

use App\Mail\NewRegistrationNotification;
use App\Models\Event;
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
 * Test to verify that email duplication bug is fixed.
 *
 * This test ensures that exactly ONE email is sent to each recipient
 * (participant and coordinator) instead of duplicates.
 */
#[Group('email-duplication-fix')]
class EmailDuplicationFixTest extends TestCase
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
    public function registration_sends_exactly_one_email_to_participant_when_coordinator_email_empty(): void
    {
        // Setup: No coordinator email
        config(['mail.coordinator_email' => '']);

        Mail::fake();

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

        // Act: Create registration
        $response = $this->post(route('event-registrations.store'), $registrationData);

        // Assert: Registration successful
        $response->assertRedirect(route('registrations.my'));

        // Assert: EXACTLY 1 email sent (participant only, no coordinator)
        Mail::assertQueued(NewRegistrationNotification::class, 1);

        // Verify it's for participant
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === false;
        });

        // Verify NO coordinator email
        Mail::assertNotQueued(NewRegistrationNotification::class, function ($mail) {
            return $mail->forCoordinator === true;
        });
    }

    #[Test]
    public function registration_sends_exactly_two_emails_when_coordinator_email_configured(): void
    {
        // Setup: Coordinator email configured
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        Mail::fake();

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

        // Act: Create registration
        $response = $this->post(route('event-registrations.store'), $registrationData);

        // Assert: Registration successful
        $response->assertRedirect(route('registrations.my'));

        // Assert: EXACTLY 2 emails sent (1 participant + 1 coordinator)
        Mail::assertQueued(NewRegistrationNotification::class, 2);

        // Verify participant email (exactly 1)
        $participantEmails = collect(Mail::queued(NewRegistrationNotification::class))
            ->filter(fn ($mail) => $mail->forCoordinator === false);
        $this->assertCount(1, $participantEmails, 'Should send exactly 1 email to participant');

        // Verify coordinator email (exactly 1)
        $coordinatorEmails = collect(Mail::queued(NewRegistrationNotification::class))
            ->filter(fn ($mail) => $mail->forCoordinator === true);
        $this->assertCount(1, $coordinatorEmails, 'Should send exactly 1 email to coordinator');
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
