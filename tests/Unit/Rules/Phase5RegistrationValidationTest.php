<?php

namespace Tests\Unit\Rules;

use App\Models\EnrollmentProof;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Rules\Phase5RegistrationValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Event $mainConferenceEvent;

    protected Event $workshopEvent;

    protected User $user;

    protected Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test events
        $this->mainConferenceEvent = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => 'Main Conference',
            'is_main_conference' => true,
        ]);

        $this->workshopEvent = Event::factory()->create([
            'code' => 'WORKSHOP_1',
            'name' => 'Test Workshop',
            'is_main_conference' => false,
        ]);

        // Create test user and registration
        $this->user = User::factory()->create();
        $this->registration = Registration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_accompanying_person_from_registering_for_workshops(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
            'selected_event_codes' => ['BCSMIF2025', 'WORKSHOP_1'],
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('registration', [], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Accompanying persons cannot register for workshops', $failMessage);
        $this->assertStringContainsString('Test Workshop', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_accompanying_person_to_register_for_main_conference_only(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_upload_for_undergraduate_students(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'undergrad_student',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('registration', [], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('All students (undergraduate and graduate) must upload enrollment proof', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_upload_for_graduate_students(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'grad_student',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('registration', [], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('All students (undergraduate and graduate) must upload enrollment proof', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_students_with_approved_enrollment_proof(): void
    {
        // Create approved enrollment proof
        EnrollmentProof::factory()->create([
            'registration_id' => $this->registration->id,
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);

        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'undergrad_student',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_students_with_pending_enrollment_proof(): void
    {
        // Create pending enrollment proof
        EnrollmentProof::factory()->create([
            'registration_id' => $this->registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'grad_student',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_brazilian_residents_must_use_domestic_payment(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'address_country' => 'Brazil',
            'payment_method' => 'international_card',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('registration', [], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Brazilian residents must use domestic payment methods', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_international_participants_must_use_international_payment(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'address_country' => 'United States',
            'payment_method' => 'pix',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('registration', [], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('International participants must use international payment methods', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_brazilian_residents_to_use_domestic_payment(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'address_country' => 'Brazil',
            'payment_method' => 'pix',
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_international_participants_to_use_international_payment(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'address_country' => 'United States',
            'payment_method' => 'paypal',
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_validate_non_student_categories_for_upload(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'selected_event_codes' => ['BCSMIF2025'],
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_non_accompanying_person_to_register_for_workshops(): void
    {
        $rule = new Phase5RegistrationValidation($this->user);
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
            'selected_event_codes' => ['BCSMIF2025', 'WORKSHOP_1'],
        ]);

        $failed = false;

        $rule->validate('registration', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }
}
