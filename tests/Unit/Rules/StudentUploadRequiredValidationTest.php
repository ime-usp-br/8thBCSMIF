<?php

namespace Tests\Unit\Rules;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use App\Models\User;
use App\Rules\StudentUploadRequiredValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentUploadRequiredValidationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_undergraduate_student_has_no_upload(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'undergrad_student',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Students must upload enrollment proof documents', $failMessage);
        $this->assertStringContainsString('Both undergraduate and graduate students are required', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_graduate_student_has_no_upload(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'grad_student',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('enrollment_proof', '', function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Students must upload enrollment proof documents', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_student_has_upload_file(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'undergrad_student',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', 'some_file.pdf', function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_for_non_student_categories(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_user_has_approved_enrollment_proof(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);

        $rule = new StudentUploadRequiredValidation($user);
        $rule->setData([
            'registration_category_snapshot' => 'grad_student',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_user_has_pending_enrollment_proof(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        $rule = new StudentUploadRequiredValidation($user);
        $rule->setData([
            'registration_category_snapshot' => 'undergrad_student',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_user_has_rejected_enrollment_proof_only(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        $rule = new StudentUploadRequiredValidation($user);
        $rule->setData([
            'registration_category_snapshot' => 'grad_student',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Students must upload enrollment proof documents before completing registration', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_accompanying_person_category(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_professor_abe_member_category(): void
    {
        $rule = new StudentUploadRequiredValidation;
        $rule->setData([
            'registration_category_snapshot' => 'professor_abe_member',
        ]);

        $failed = false;

        $rule->validate('enrollment_proof', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }
}
