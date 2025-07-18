<?php

namespace Tests\Feature\Mail;

use App\Mail\NewRegistrationNotification;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(NewRegistrationNotification::class)]
#[Group('mail')]
#[Group('feature')]
#[Group('new-registration-notification-feature')]
class NewRegistrationNotificationFeatureTest extends TestCase
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
    public function registration_creation_sends_email_with_cc_for_international_participants(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'international@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $event = Event::first();
        $registrationData = $this->getValidRegistrationData($user, [
            'document_country_origin' => 'US', // International participant
            'cpf' => null, // International doesn't have CPF
            'rg_number' => null, // International doesn't have RG
            'passport_number' => 'ABC123456', // International needs passport
            'passport_expiry_date' => '2030-12-31', // Passport expiry
            'position' => 'graduate_student',
            'selected_event_codes' => [$event->code],
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify that emails were queued
        Mail::assertQueued(NewRegistrationNotification::class);

        // Get the queued emails and examine them
        $queuedEmails = collect(Mail::queued(NewRegistrationNotification::class));
        $participantEmail = $queuedEmails->where('forCoordinator', false)->first();
        $this->assertNotNull($participantEmail, 'Participant email should be queued');

        // Verify international participant details
        $this->assertEquals('US', $participantEmail->registration->document_country_origin);

        // AC2: Verify CC was added to international participant email
        $envelope = $participantEmail->envelope();
        $this->assertEquals(1, count($envelope->cc), 'International participant email should have CC');
        $this->assertEquals('assoc.bras.estatistica@gmail.com', $envelope->cc[0]->address);

        // AC1: Verify the email content contains international message
        $rendered = $participantEmail->render();
        $this->assertTrue(
            str_contains($rendered, 'invoice for international payment') && str_contains($rendered, 'assoc.bras.estatistica@gmail.com'),
            'Email should contain international message with invoice and email reference'
        );
    }

    #[Test]
    public function registration_creation_sends_email_without_cc_for_brazilian_participants(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'brazilian@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $event = Event::first();
        $registrationData = $this->getValidRegistrationData($user, [
            'document_country_origin' => 'Brazil', // Brazilian participant
            'position' => 'graduate_student',
            'selected_event_codes' => [$event->code],
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify that Brazilian participant email does NOT have CC
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $envelope = $mail->envelope();

            return count($envelope->cc) === 0;
        });

        // Verify the email does NOT contain international message
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $rendered = $mail->render();

            // Should NOT contain international text
            return ! str_contains($rendered, 'Thank you for your registration. Your invoice for international payment will be sent shortly from `assoc.bras.estatistica@gmail.com`. Please check your inbox and spam folder.');
        });
    }

    #[Test]
    public function registration_creation_sends_email_with_undergraduate_message_for_zero_fee(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'undergraduate@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $event = Event::first();
        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'undergraduate_student', // AC4: undergraduate_student position
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify email contains undergraduate message when position is undergraduate_student AND total_fee is zero
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            // AC4: Check that position is undergraduate_student and total_fee is zero
            if ($mail->registration->position !== 'undergraduate_student') {
                return true; // Not an undergraduate, skip
            }

            $totalFee = $mail->registration->calculateCorrectTotalFee();
            if ($totalFee > 0) {
                return true; // Has fee, skip
            }

            $rendered = $mail->render();

            // AC3: Check for exact undergraduate text
            return str_contains($rendered, 'As an undergraduate student, your tuition fee is zero. Instead of proof of payment, we ask that you submit a valid proof of enrollment.');
        });
    }

    #[Test]
    public function registration_creation_does_not_show_undergraduate_message_for_non_undergraduate(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'graduate@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $event = Event::first();
        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'graduate_student', // Not undergraduate
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify email does NOT contain undergraduate message for non-undergraduate
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $rendered = $mail->render();

            // Should NOT contain undergraduate message for graduate student
            return ! str_contains($rendered, 'As an undergraduate student, your tuition fee is zero. Instead of proof of payment, we ask that you submit a valid proof of enrollment.');
        });
    }

    #[Test]
    public function registration_creation_shows_early_bird_reminder_during_early_bird_period_with_pending_amount(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'earlybird@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Set up event with early bird deadline in the future
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->addDays(5), // Early bird still active
        ]);

        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'graduate_student', // Position that has a fee
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // AC5: Verify email contains early bird reminder when conditions are met
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            // Check if there's an amount due
            $totalFee = $mail->registration->calculateCorrectTotalFee();
            if ($totalFee <= 0) {
                return true; // No amount due, skip
            }

            // Check if early bird deadline is in the future
            $earlyBirdDeadline = $mail->registration->events->first()?->registration_deadline_early;
            if (! $earlyBirdDeadline || Carbon::now() > $earlyBirdDeadline) {
                return true; // Early bird period over, skip
            }

            $rendered = $mail->render();

            // Should contain early bird reminder
            return str_contains($rendered, 'early bird') || str_contains($rendered, 'Early Bird');
        });
    }

    #[Test]
    public function registration_creation_does_not_show_early_bird_reminder_after_deadline(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'late@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Set up event with early bird deadline in the past
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->subDays(5), // Early bird period over
        ]);

        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'graduate_student', // Position that has a fee
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify email does NOT contain early bird reminder after deadline
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $rendered = $mail->render();

            // Should NOT contain early bird reminder after deadline
            return ! str_contains($rendered, 'early bird') && ! str_contains($rendered, 'Early Bird');
        });
    }

    #[Test]
    public function registration_creation_does_not_show_early_bird_reminder_with_zero_amount(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'zerofee@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Set up event with early bird deadline in the future
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->addDays(5), // Early bird still active
        ]);

        $registrationData = $this->getValidRegistrationData($user, [
            'position' => 'undergraduate_student', // Position with zero fee
            'selected_event_codes' => [$event->code],
            'document_country_origin' => 'Brazil',
        ]);

        // Create registration via HTTP request to trigger the complete flow
        $response = $this->post(route('event-registrations.store'), $registrationData);

        $response->assertRedirect(route('registrations.my'));

        // Verify email does NOT contain early bird reminder when amount is zero
        Mail::assertQueued(NewRegistrationNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            // Check if there's no amount due
            $totalFee = $mail->registration->calculateCorrectTotalFee();
            if ($totalFee > 0) {
                return true; // Has amount due, skip this check
            }

            $rendered = $mail->render();

            // Should NOT contain early bird reminder when no amount due
            return ! str_contains($rendered, 'early bird') && ! str_contains($rendered, 'Early Bird');
        });
    }

    /**
     * Helper to get valid data for registration.
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
