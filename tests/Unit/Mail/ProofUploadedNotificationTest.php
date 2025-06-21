<?php

namespace Tests\Unit\Mail;

use App\Mail\ProofUploadedNotification;
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

#[CoversClass(ProofUploadedNotification::class)]
#[Group('mail')]
#[Group('proof-uploaded-notification')]
class ProofUploadedNotificationTest extends TestCase
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
    public function mailable_can_be_instantiated_with_registration(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);

        $this->assertInstanceOf(ProofUploadedNotification::class, $mailable);
        $this->assertSame($registration, $mailable->registration);
    }

    #[Test]
    public function envelope_returns_correct_subject(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals(__('Payment Proof Uploaded - 8th BCSMIF'), $envelope->subject);
    }

    #[Test]
    public function content_method_returns_correct_template(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);
        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('emails.registration.proof-uploaded', $content->markdown);
    }

    #[Test]
    public function mailable_has_no_attachments(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);
        $attachments = $mailable->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    #[Test]
    public function proof_uploaded_view_contains_correct_content(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'João Silva',
            'cpf' => '12345678901',
            'document_country_origin' => 'BR',
        ]);

        $mailable = new ProofUploadedNotification($registration);
        $rendered = $mailable->render();

        // Verifica que contém informações do participante
        $this->assertStringContainsString('João Silva', $rendered);
        $this->assertStringContainsString('user@example.com', $rendered);
        $this->assertStringContainsString('#'.$registration->id, $rendered);

        // Verifica que contém link para o painel admin
        $adminUrl = config('app.url').'/admin/registrations/'.$registration->id;
        $this->assertStringContainsString($adminUrl, $rendered);

        // Verifica que contém texto sobre o upload
        $this->assertStringContainsString(__('anexou um comprovante de pagamento'), $rendered);

        // Verifica que contém botão para visualizar comprovante
        $this->assertStringContainsString(__('Visualizar Comprovante no Painel Admin'), $rendered);
    }

    #[Test]
    public function envelope_includes_coordinator_email_when_configured(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertNotEmpty($envelope->to);
        $this->assertEquals('coordinator@example.com', $envelope->to[0]->address);
    }

    #[Test]
    public function envelope_does_not_include_coordinator_email_when_config_is_null(): void
    {
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new ProofUploadedNotification($registration);
        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEmpty($envelope->to);
    }

    #[Test]
    public function get_coordinator_email_returns_configured_email(): void
    {
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();

        $this->assertEquals('coordinator@example.com', $coordinatorEmail);
    }

    #[Test]
    public function get_coordinator_email_returns_null_when_not_configured(): void
    {
        config(['mail.coordinator_email' => null]);

        $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();

        $this->assertNull($coordinatorEmail);
    }
}
