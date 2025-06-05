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
}
