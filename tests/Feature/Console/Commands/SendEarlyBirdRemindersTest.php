<?php

namespace Tests\Feature\Console\Commands;

use App\Mail\EarlyBirdReminderNotification;
use App\Models\Event;
use App\Models\Payment;
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

#[CoversClass(\App\Console\Commands\SendEarlyBirdReminders::class)]
#[Group('console')]
#[Group('early-bird')]
class SendEarlyBirdRemindersTest extends TestCase
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
    public function command_finds_no_events_when_no_early_bird_deadlines_approaching(): void
    {
        // Create event with early bird deadline far in the future
        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(5),
        ]);

        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('No events with approaching early bird deadlines found.'))
            ->assertExitCode(0);
    }

    #[Test]
    public function command_identifies_events_with_approaching_early_bird_deadlines(): void
    {
        Mail::fake();

        // Create event with early bird deadline in 1 day (within 1-day window)
        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(1),
        ]);

        // Create registration with pending payment created before deadline
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::today()->subDays(5), // Created before deadline
        ]);

        // Associate registration with event
        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create pending payment
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending',
            'amount' => 500.00,
        ]);

        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('Processing event: :event', ['event' => $event->name]))
            ->expectsOutput(__('Reminder sent to: :email', ['email' => $registration->email]))
            ->expectsOutput(__('Early bird reminders sent: :count', ['count' => 1]))
            ->assertExitCode(0);

        Mail::assertQueued(EarlyBirdReminderNotification::class, 1);
        Mail::assertQueued(EarlyBirdReminderNotification::class, function ($mail) use ($registration, $event) {
            return $mail->registration->id === $registration->id &&
                   $mail->event->id === $event->id;
        });
    }

    #[Test]
    public function command_excludes_registrations_created_after_early_bird_deadline(): void
    {
        Mail::fake();

        // Create event with early bird deadline in 1 day
        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(1),
        ]);

        // Create registration created AFTER the early bird deadline
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::today()->addDays(5), // Created after deadline
        ]);

        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending',
            'amount' => 500.00,
        ]);

        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('Processing event: :event', ['event' => $event->name]))
            ->expectsOutput(__('Early bird reminders sent: :count', ['count' => 0]))
            ->assertExitCode(0);

        Mail::assertNotSent(EarlyBirdReminderNotification::class);
    }

    #[Test]
    public function command_excludes_registrations_without_pending_payments(): void
    {
        Mail::fake();

        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(1),
        ]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::today()->subDays(5),
        ]);

        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create confirmed payment (not pending)
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'confirmed',
            'amount' => 500.00,
        ]);

        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('Processing event: :event', ['event' => $event->name]))
            ->expectsOutput(__('Early bird reminders sent: :count', ['count' => 0]))
            ->assertExitCode(0);

        Mail::assertNotSent(EarlyBirdReminderNotification::class);
    }

    #[Test]
    public function command_only_processes_registrations_for_specific_event(): void
    {
        Mail::fake();

        // Create two events - only one with approaching deadline
        $eventWithDeadline = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(1),
        ]);

        $eventWithoutDeadline = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(10),
        ]);

        $user = User::factory()->create();

        // Registration for event WITH approaching deadline
        $registrationEligible = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::today()->subDays(5),
        ]);
        $registrationEligible->events()->attach($eventWithDeadline->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Payment::factory()->create([
            'registration_id' => $registrationEligible->id,
            'status' => 'pending',
            'amount' => 500.00,
        ]);

        // Registration for event WITHOUT approaching deadline
        $registrationNotEligible = Registration::factory()->create([
            'user_id' => User::factory()->create()->id,
            'created_at' => Carbon::today()->subDays(5),
        ]);
        $registrationNotEligible->events()->attach($eventWithoutDeadline->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Payment::factory()->create([
            'registration_id' => $registrationNotEligible->id,
            'status' => 'pending',
            'amount' => 500.00,
        ]);

        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('Processing event: :event', ['event' => $eventWithDeadline->name]))
            ->expectsOutput(__('Early bird reminders sent: :count', ['count' => 1]))
            ->assertExitCode(0);

        // Only one email should be queued (for the eligible registration)
        Mail::assertQueued(EarlyBirdReminderNotification::class, 1);
        Mail::assertQueued(EarlyBirdReminderNotification::class, function ($mail) use ($registrationEligible, $eventWithDeadline) {
            return $mail->registration->id === $registrationEligible->id &&
                   $mail->event->id === $eventWithDeadline->id;
        });
    }

    #[Test]
    public function command_handles_mail_sending_errors_gracefully(): void
    {
        Mail::fake();

        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(1),
        ]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::today()->subDays(5),
            'email' => 'invalid-email', // This might cause issues
        ]);

        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending',
            'amount' => 500.00,
        ]);

        // Even if there are mail errors, command should continue and report results
        $this->artisan('app:send-early-bird-reminders')
            ->expectsOutput(__('Processing event: :event', ['event' => $event->name]))
            ->assertExitCode(0);
    }
}
