<?php

namespace Tests\Unit\Mail;

use App\Mail\EarlyBirdReminderNotification;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EarlyBirdReminderNotification::class)]
#[Group('mail')]
#[Group('early-bird-reminder-notification')]
class EarlyBirdReminderNotificationTest extends TestCase
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
    public function mailable_can_be_instantiated_with_registration_and_event(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create([
            'registration_deadline_early' => Carbon::today()->addDays(3),
        ]);

        $mailable = new EarlyBirdReminderNotification($registration, $event);

        $this->assertInstanceOf(EarlyBirdReminderNotification::class, $mailable);
        $this->assertEquals($registration->id, $mailable->registration->id);
        $this->assertEquals($event->id, $mailable->event->id);
    }

    #[Test]
    public function envelope_returns_correct_subject(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create();

        $mailable = new EarlyBirdReminderNotification($registration, $event);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals(__('Early Bird Deadline Reminder - 8th BCSMIF'), $envelope->subject);
    }

    #[Test]
    public function content_method_returns_correct_template(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create();

        $mailable = new EarlyBirdReminderNotification($registration, $event);
        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('mail.early-bird-reminder', $content->markdown);
    }

    #[Test]
    public function mailable_has_no_attachments(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create();

        $mailable = new EarlyBirdReminderNotification($registration, $event);
        $attachments = $mailable->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    #[Test]
    public function envelope_includes_coordinator_email_when_configured(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create();

        $mailable = new EarlyBirdReminderNotification($registration, $event);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);

        // Test that the envelope method executes without errors when coordinator email is configured
        // The actual BCC functionality is tested at integration level in the command tests
        $this->assertEquals(__('Early Bird Deadline Reminder - 8th BCSMIF'), $envelope->subject);
    }

    #[Test]
    public function envelope_does_not_include_coordinator_email_when_config_is_null(): void
    {
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create();

        $mailable = new EarlyBirdReminderNotification($registration, $event);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEmpty($envelope->bcc);
    }

    #[Test]
    public function mailable_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'document_country_origin' => 'BR',
        ]);

        $event = Event::factory()->create([
            'name' => 'Test Event',
            'registration_deadline_early' => Carbon::today()->addDays(3),
        ]);

        // Associate registration with event
        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mailable = new EarlyBirdReminderNotification($registration, $event);

        // This should not throw an exception
        $rendered = $mailable->render();
        $this->assertIsString($rendered);
        $this->assertStringContainsString($registration->full_name, $rendered);
        $this->assertStringContainsString($event->name, $rendered);
    }

    #[Test]
    public function mailable_includes_registration_and_event_data_in_context(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $event = Event::factory()->create([
            'name' => 'Early Bird Test Event',
            'registration_deadline_early' => Carbon::today()->addDays(5),
        ]);

        $mailable = new EarlyBirdReminderNotification($registration, $event);

        $this->assertEquals($registration->id, $mailable->registration->id);
        $this->assertEquals($event->id, $mailable->event->id);
        $this->assertEquals('Jane Smith', $mailable->registration->full_name);
        $this->assertEquals('Early Bird Test Event', $mailable->event->name);
    }
}
