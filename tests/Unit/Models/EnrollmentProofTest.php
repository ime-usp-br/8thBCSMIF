<?php

namespace Tests\Unit\Models;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the EnrollmentProof model.
 */
#[CoversClass(EnrollmentProof::class)]
#[Group('model')]
#[Group('enrollment-proof-model')]
class EnrollmentProofTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function enrollment_proof_factory_can_create_model_instance(): void
    {
        $enrollmentProof = EnrollmentProof::factory()->create();

        $this->assertInstanceOf(EnrollmentProof::class, $enrollmentProof);
        $this->assertNotNull($enrollmentProof->id, 'EnrollmentProof ID should not be null after creation.');
        $this->assertNotNull($enrollmentProof->registration_id, 'registration_id should be filled by the factory.');
        $this->assertNotNull($enrollmentProof->status, 'status should be filled by the factory.');
    }

    #[Test]
    public function uploaded_at_is_casted_to_carbon_instance_or_null(): void
    {
        $dateTime = now();
        $enrollmentProofWithDate = EnrollmentProof::factory()->create(['uploaded_at' => $dateTime]);
        $this->assertInstanceOf(Carbon::class, $enrollmentProofWithDate->uploaded_at);
        $this->assertEquals($dateTime->toDateTimeString(), $enrollmentProofWithDate->uploaded_at->toDateTimeString());

        $enrollmentProofNullDate = EnrollmentProof::factory()->create(['uploaded_at' => null]);
        $this->assertNull($enrollmentProofNullDate->uploaded_at);
    }

    #[Test]
    public function approved_at_is_casted_to_carbon_instance_or_null(): void
    {
        $dateTime = now();
        $enrollmentProofWithDate = EnrollmentProof::factory()->create(['approved_at' => $dateTime]);
        $this->assertInstanceOf(Carbon::class, $enrollmentProofWithDate->approved_at);
        $this->assertEquals($dateTime->toDateTimeString(), $enrollmentProofWithDate->approved_at->toDateTimeString());

        $enrollmentProofNullDate = EnrollmentProof::factory()->create(['approved_at' => null]);
        $this->assertNull($enrollmentProofNullDate->approved_at);
    }

    #[Test]
    public function enrollment_proof_belongs_to_a_registration(): void
    {
        $registration = Registration::factory()->create();
        $enrollmentProof = EnrollmentProof::factory()->create(['registration_id' => $registration->id]);

        $this->assertInstanceOf(Registration::class, $enrollmentProof->registration);
        $this->assertEquals($registration->id, $enrollmentProof->registration->id);
    }

    #[Test]
    public function enrollment_proof_belongs_to_approved_by_user(): void
    {
        $admin = User::factory()->create();
        $enrollmentProof = EnrollmentProof::factory()->create(['approved_by' => $admin->id]);

        $this->assertInstanceOf(User::class, $enrollmentProof->approvedBy);
        $this->assertEquals($admin->id, $enrollmentProof->approvedBy->id);
    }

    #[Test]
    public function approved_by_can_be_null(): void
    {
        $enrollmentProof = EnrollmentProof::factory()->create(['approved_by' => null]);

        $this->assertNull($enrollmentProof->approved_by);
        $this->assertNull($enrollmentProof->approvedBy);
    }

    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $registration = Registration::factory()->create();
        $admin = User::factory()->create();
        $fillableAttributes = (new EnrollmentProof)->getFillable();
        $testData = EnrollmentProof::factory()->make([
            'registration_id' => $registration->id,
            'approved_by' => $admin->id,
        ])->toArray();

        // Remove attributes not in $fillable or handled by DB (id, timestamps)
        unset($testData['id'], $testData['created_at'], $testData['updated_at']);

        // Ensure all keys in testData are in fillable
        $validatedData = array_intersect_key($testData, array_flip($fillableAttributes));

        $enrollmentProof = EnrollmentProof::create($validatedData);
        $this->assertNotNull($enrollmentProof->id);

        foreach ($validatedData as $key => $value) {
            if (($enrollmentProof->getCasts()[$key] ?? null) === 'datetime') {
                if ($value === null) {
                    $this->assertNull($enrollmentProof->{$key});
                } else {
                    $this->assertInstanceOf(Carbon::class, $enrollmentProof->{$key}, "Attribute {$key} should be Carbon instance.");
                    $this->assertEquals(Carbon::parse($value)->toDateTimeString(), $enrollmentProof->{$key}->toDateTimeString());
                }
            } else {
                $this->assertEquals($value, $enrollmentProof->{$key});
            }
        }
    }

    #[Test]
    public function enrollment_proof_status_can_be_set_to_valid_values(): void
    {
        $validStatuses = ['pending_approval', 'approved', 'rejected'];

        foreach ($validStatuses as $status) {
            $enrollmentProof = EnrollmentProof::factory()->create(['status' => $status]);
            $this->assertEquals($status, $enrollmentProof->status);
        }
    }

    #[Test]
    public function file_path_can_be_null_or_string(): void
    {
        $enrollmentProofWithFile = EnrollmentProof::factory()->create(['file_path' => 'uploads/enrollment/proof.pdf']);
        $this->assertEquals('uploads/enrollment/proof.pdf', $enrollmentProofWithFile->file_path);

        $enrollmentProofWithoutFile = EnrollmentProof::factory()->create(['file_path' => null]);
        $this->assertNull($enrollmentProofWithoutFile->file_path);
    }

    #[Test]
    public function original_filename_can_be_null_or_string(): void
    {
        $enrollmentProofWithFilename = EnrollmentProof::factory()->create(['original_filename' => 'student_enrollment.pdf']);
        $this->assertEquals('student_enrollment.pdf', $enrollmentProofWithFilename->original_filename);

        $enrollmentProofWithoutFilename = EnrollmentProof::factory()->create(['original_filename' => null]);
        $this->assertNull($enrollmentProofWithoutFilename->original_filename);
    }

    #[Test]
    public function rejection_reason_can_be_null_or_string(): void
    {
        $enrollmentProofWithReason = EnrollmentProof::factory()->create(['rejection_reason' => 'Document not clear enough']);
        $this->assertEquals('Document not clear enough', $enrollmentProofWithReason->rejection_reason);

        $enrollmentProofWithoutReason = EnrollmentProof::factory()->create(['rejection_reason' => null]);
        $this->assertNull($enrollmentProofWithoutReason->rejection_reason);
    }

    #[Test]
    public function can_approve_pending_enrollment_proof(): void
    {
        $proof = EnrollmentProof::factory()->create(['status' => 'pending_approval']);
        $approver = User::factory()->create();

        $result = $proof->approve($approver);

        $this->assertTrue($result);
        $this->assertEquals(EnrollmentProof::STATUS_APPROVED, $proof->fresh()->status);
        $this->assertEquals($approver->id, $proof->fresh()->approved_by);
        $this->assertNotNull($proof->fresh()->approved_at);
        $this->assertNull($proof->fresh()->rejection_reason);
    }

    #[Test]
    public function cannot_approve_already_processed_proof(): void
    {
        $approvedProof = EnrollmentProof::factory()->create(['status' => 'approved']);
        $rejectedProof = EnrollmentProof::factory()->create(['status' => 'rejected']);
        $approver = User::factory()->create();

        $this->assertFalse($approvedProof->approve($approver));
        $this->assertFalse($rejectedProof->approve($approver));
    }

    #[Test]
    public function can_reject_pending_proof_with_reason(): void
    {
        $proof = EnrollmentProof::factory()->create(['status' => 'pending_approval']);
        $approver = User::factory()->create();
        $reason = 'Document quality is insufficient for verification';

        $result = $proof->reject($approver, $reason);

        $this->assertTrue($result);
        $this->assertEquals(EnrollmentProof::STATUS_REJECTED, $proof->fresh()->status);
        $this->assertEquals($approver->id, $proof->fresh()->approved_by);
        $this->assertEquals($reason, $proof->fresh()->rejection_reason);
        $this->assertNotNull($proof->fresh()->approved_at);
    }

    #[Test]
    public function status_check_helper_methods(): void
    {
        $pendingProof = EnrollmentProof::factory()->create(['status' => 'pending']);
        $pendingApprovalProof = EnrollmentProof::factory()->create(['status' => 'pending_approval']);
        $approvedProof = EnrollmentProof::factory()->create(['status' => 'approved']);
        $rejectedProof = EnrollmentProof::factory()->create(['status' => 'rejected']);

        // Test pending proof (not yet uploaded)
        $this->assertTrue($pendingProof->isPending());
        $this->assertFalse($pendingProof->isPendingApproval());
        $this->assertFalse($pendingProof->isApproved());
        $this->assertFalse($pendingProof->isRejected());

        // Test pending approval proof (uploaded, awaiting approval)
        $this->assertFalse($pendingApprovalProof->isPending());
        $this->assertTrue($pendingApprovalProof->isPendingApproval());
        $this->assertFalse($pendingApprovalProof->isApproved());
        $this->assertFalse($pendingApprovalProof->isRejected());

        // Test approved proof
        $this->assertTrue($approvedProof->isApproved());
        $this->assertFalse($approvedProof->isPending());
        $this->assertFalse($approvedProof->isPendingApproval());
        $this->assertFalse($approvedProof->isRejected());

        // Test rejected proof
        $this->assertTrue($rejectedProof->isRejected());
        $this->assertFalse($rejectedProof->isPending());
        $this->assertFalse($rejectedProof->isPendingApproval());
        $this->assertFalse($rejectedProof->isApproved());
    }

    #[Test]
    public function get_valid_statuses(): void
    {
        $statuses = EnrollmentProof::getValidStatuses();

        $this->assertContains('pending', $statuses);
        $this->assertContains('pending_approval', $statuses);
        $this->assertContains('approved', $statuses);
        $this->assertContains('rejected', $statuses);
        $this->assertCount(4, $statuses);
    }
}
