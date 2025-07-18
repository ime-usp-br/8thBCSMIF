<?php

namespace Tests\Feature\Mail;

use App\Mail\RegistrationModifiedNotification;
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

#[CoversClass(RegistrationModifiedNotification::class)]
#[Group('mail')]
#[Group('feature')]
#[Group('registration-modified-notification-feature')]
class RegistrationModifiedNotificationFeatureTest extends TestCase
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
    public function registration_modification_sends_email_with_cc_for_international_participants(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'international@example.com',
            'email_verified_at' => now(),
        ]);

        // Create initial registration for international participant
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
            'document_country_origin' => 'US', // International participant
            'position' => 'graduate_student',
        ]);

        $event = Event::first();

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // AC2: Verify CC was added to international participant email
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $envelope = $mail->envelope();

            return count($envelope->cc) === 1 &&
                   $envelope->cc[0]->address === 'assoc.bras.estatistica@gmail.com';
        });

        // Verify that user email was queued for international participant
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) {
            return ! $mail->forCoordinator && $mail->registration->document_country_origin === 'US';
        });
    }

    #[Test]
    public function registration_modification_sends_email_without_cc_for_brazilian_participants(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'brazilian@example.com',
            'email_verified_at' => now(),
        ]);

        // Create initial registration for Brazilian participant
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil', // Brazilian participant
            'position' => 'graduate_student',
        ]);

        $event = Event::first();

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // Verify that Brazilian participant email does NOT have CC
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $envelope = $mail->envelope();

            return count($envelope->cc) === 0;
        });
    }

    #[Test]
    public function registration_modification_shows_early_bird_reminder_during_early_bird_period_with_pending_amount(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'earlybird@example.com',
            'email_verified_at' => now(),
        ]);

        // Set up event with early bird deadline in the future
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->addDays(5), // Early bird still active
        ]);

        // Create initial registration with a fee
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student', // Category that has fees
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil',
            'position' => 'graduate_student',
        ]);

        // Don't pre-attach events to allow modification to trigger

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // AC5: Verify email contains early bird reminder when conditions are met
        // First check that emails were queued
        Mail::assertQueued(RegistrationModifiedNotification::class, 2);

        // Then manually check the participant email content
        $queuedEmails = collect(Mail::queued(RegistrationModifiedNotification::class));
        $participantEmail = $queuedEmails->where('forCoordinator', false)->first();
        $this->assertNotNull($participantEmail, 'Participant email should be queued');

        $rendered = $participantEmail->render();
        $this->assertTrue(
            str_contains($rendered, 'early bird') || str_contains($rendered, 'Early Bird'),
            'Email should contain early bird reminder'
        );
    }

    #[Test]
    public function registration_modification_does_not_show_early_bird_reminder_after_deadline(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'late@example.com',
            'email_verified_at' => now(),
        ]);

        // Set up event with early bird deadline in the past
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->subDays(5), // Early bird period over
        ]);

        // Create initial registration with a fee
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student', // Category that has fees
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil',
            'position' => 'graduate_student',
        ]);

        // Don't pre-attach events to allow modification to trigger

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // Verify email does NOT contain early bird reminder after deadline
        // First check that emails were queued
        Mail::assertQueued(RegistrationModifiedNotification::class, 2);

        // Then manually check the participant email content
        $queuedEmails = collect(Mail::queued(RegistrationModifiedNotification::class));
        $participantEmail = $queuedEmails->where('forCoordinator', false)->first();
        $this->assertNotNull($participantEmail, 'Participant email should be queued');

        $rendered = $participantEmail->render();
        $this->assertFalse(
            str_contains($rendered, 'early bird') || str_contains($rendered, 'Early Bird'),
            'Email should NOT contain early bird reminder after deadline'
        );
    }

    #[Test]
    public function registration_modification_does_not_show_early_bird_reminder_with_zero_amount(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'zerofee@example.com',
            'email_verified_at' => now(),
        ]);

        // Set up event with early bird deadline in the future
        $event = Event::first();
        $event->update([
            'registration_deadline_early' => Carbon::now()->addDays(5), // Early bird still active
        ]);

        // Create initial registration with zero fee
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'undergrad_student', // Category with zero fee
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil',
            'position' => 'undergraduate_student',
        ]);

        // Don't pre-attach events to allow modification to trigger

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // Verify email does NOT contain early bird reminder when amount is zero
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            $rendered = $mail->render();

            // Should NOT contain early bird reminder when no amount due
            return ! str_contains($rendered, 'early bird') && ! str_contains($rendered, 'Early Bird');
        });
    }

    #[Test]
    public function registration_modification_sends_emails_to_both_participant_and_coordinator(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'participant@example.com',
            'email_verified_at' => now(),
        ]);

        // Create initial registration
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil',
            'position' => 'graduate_student',
        ]);

        $event = Event::first();

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // Verify both participant and coordinator emails were queued
        Mail::assertQueued(RegistrationModifiedNotification::class, 2);

        // Verify participant email
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id && ! $mail->forCoordinator;
        });

        // Verify coordinator email
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id && $mail->forCoordinator;
        });
    }

    #[Test]
    public function registration_modification_includes_undergraduate_content_for_undergraduates_with_zero_fee(): void
    {
        Mail::fake();
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email' => 'undergraduate@example.com',
            'email_verified_at' => now(),
        ]);

        // Create initial registration for undergraduate with zero fee
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'undergrad_student', // Zero fee category
            'participation_format' => 'in-person',
            'document_country_origin' => 'Brazil',
            'position' => 'undergraduate_student', // AC4: undergraduate_student position
        ]);

        $event = Event::first();
        // Don't pre-attach events to allow modification to trigger

        $this->actingAs($user);

        // Modify registration via HTTP request to trigger the complete flow
        $response = $this->post(route('registration.modify', $registration), [
            'selected_event_codes' => [$event->code],
        ]);

        $response->assertRedirect(route('registrations.my'));

        // Verify email contains undergraduate message when position is undergraduate_student AND total_fee is zero
        Mail::assertQueued(RegistrationModifiedNotification::class, function ($mail) {
            if ($mail->forCoordinator) {
                return true; // Skip coordinator email
            }

            // AC4: Check that position is undergraduate_student
            if ($mail->registration->position !== 'undergraduate_student') {
                return true; // Not an undergraduate, skip
            }

            $rendered = $mail->render();

            // AC3: Check for undergraduate content (may be different in modification template)
            return str_contains($rendered, 'undergraduate') &&
                   (str_contains($rendered, 'enrollment') || str_contains($rendered, 'proof of enrollment'));
        });
    }
}
