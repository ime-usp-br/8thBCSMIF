<?php

namespace Tests\Unit\Mail;

use App\Mail\NewRegistrationNotification;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(NewRegistrationNotification::class)]
#[Group('mail')]
#[Group('new-registration-notification')]
class NewRegistrationNotificationTest extends TestCase
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
    public function content_method_returns_user_template_by_default(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new NewRegistrationNotification($registration);
        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('emails.registration.new', $content->markdown);
    }

    #[Test]
    public function content_method_returns_coordinator_template_when_for_coordinator_is_true(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: true);
        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('emails.registration.new-coordinator', $content->markdown);
    }

    #[Test]
    public function coordinator_template_contains_required_elements(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = Event::first();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test Full Name',
            'cpf' => '123.456.789-00',
            'document_country_origin' => 'BR',
            'calculated_fee' => 500.00,
            'payment_status' => 'pending',
            'position' => 'student',
            'affiliation' => 'Test University',
            'dietary_restrictions' => 'vegetarian',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_relationship' => 'Mother',
            'emergency_contact_phone' => '+55 11 99999-9999',
            'requires_visa_letter' => false,
        ]);

        $registration->events()->attach($event->code, [
            'price_at_registration' => 500.00,
        ]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: true);

        // Test that the mailable can be rendered without errors
        $rendered = $mailable->render();

        // Verify key coordinator information is present
        $this->assertStringContainsString($registration->full_name, $rendered);
        $this->assertStringContainsString($user->email, $rendered);
        $this->assertStringContainsString($registration->cpf, $rendered);
        $this->assertStringContainsString('R$ 500,00', $rendered);
        $this->assertStringContainsString(__('Painel Administrativo'), $rendered);
        $this->assertStringContainsString('/admin/registrations/'.$registration->id, $rendered);
        $this->assertStringContainsString(__('Ver Inscrição no Painel Admin'), $rendered);
        $this->assertStringContainsString('#'.$registration->id, $rendered);
    }

    #[Test]
    public function coordinator_template_handles_missing_optional_fields(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'gender' => null,
            'date_of_birth' => null,
            'phone_number' => null,
            'affiliation' => null,
        ]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: true);

        // Should render without errors even with missing optional fields
        $rendered = $mailable->render();

        $this->assertStringContainsString(__('Não informado'), $rendered);
        $this->assertStringContainsString(__('Não informada'), $rendered);
    }

    #[Test]
    public function envelope_includes_coordinator_email_when_for_coordinator_is_true(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: true);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertNotEmpty($envelope->to);
        $this->assertEquals('coordinator@example.com', $envelope->to[0]->address);
    }

    #[Test]
    public function envelope_does_not_include_coordinator_email_when_for_coordinator_is_false(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: false);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEmpty($envelope->to);
    }

    #[Test]
    public function envelope_does_not_include_coordinator_email_when_config_is_null(): void
    {
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new NewRegistrationNotification($registration, forCoordinator: true);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEmpty($envelope->to);
    }

    #[Test]
    public function get_coordinator_email_returns_configured_email(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $coordinatorEmail = NewRegistrationNotification::getCoordinatorEmail();

        $this->assertEquals('coordinator@example.com', $coordinatorEmail);
    }

    #[Test]
    public function get_coordinator_email_returns_null_when_not_configured(): void
    {
        config(['mail.coordinator_email' => null]);

        $coordinatorEmail = NewRegistrationNotification::getCoordinatorEmail();

        $this->assertNull($coordinatorEmail);
    }
}
