<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Mail\PaymentApprovedNotification;
use App\Mail\PaymentRejectedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AdminRegistrationController Feature Tests
 *
 * Tests for AC3: Fee exemption flow and administrative logging
 */
class AdminRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usp_user', 'guard_name' => 'web']);

        // Create admin user
        $this->admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
        $this->admin->assignRole('admin');

        // Create regular user
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('usp_user');
    }

    public function test_approvals_page_requires_authentication(): void
    {
        $response = $this->get(route('admin.approvals'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_approvals_page_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.approvals'));

        $response->assertStatus(403);
    }

    public function test_approvals_page_allows_admin_access(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.approvals'));

        $response->assertOk();
        $response->assertViewIs('admin.approvals');
    }

    public function test_approve_requires_authentication(): void
    {
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING,
        ]);

        $response = $this->post(route('admin.registrations.approve', $registration));

        $response->assertRedirect(route('login.local'));
    }

    public function test_approve_requires_admin_role(): void
    {
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('admin.registrations.approve', $registration));

        $response->assertStatus(403);
    }

    public function test_fee_exemption_approval_for_pending_registration_without_payments(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING,
            'full_name' => 'John Doe',
            'email' => 'john@test.com',
            'notes' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.approve', $registration));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'exemption',
            ])
            ->assertJsonFragment(['message' => __('Fee exemption approved successfully for :name', ['name' => 'John Doe'])]);

        // Check registration status updated
        $registration->refresh();
        $this->assertEquals(Registration::STATUS_APPROVED, $registration->status);

        // Check administrative logging
        $this->assertStringContainsString('Fee exemption granted by Test Admin', $registration->notes);
        $this->assertStringContainsString(now()->format('Y-m-d'), $registration->notes);

        // Check email queued
        Mail::assertQueued(PaymentApprovedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->approvalType === 'exemption';
        });
    }

    public function test_regular_approval_for_pending_approval_registration(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create([
            'full_name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'notes' => null,
        ]);

        // Manually set status to avoid observer interference
        $registration->updateQuietly(['status' => Registration::STATUS_PENDING_APPROVAL]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.approve', $registration));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'approval',
            ])
            ->assertJsonFragment(['message' => __('Registration approved successfully for :name', ['name' => 'Jane Smith'])]);

        // Check registration status updated
        $registration->refresh();
        $this->assertEquals(Registration::STATUS_APPROVED, $registration->status);

        // Check administrative logging
        $this->assertStringContainsString('Registration approved by Test Admin', $registration->notes);

        // Check email queued
        Mail::assertQueued(PaymentApprovedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->approvalType === 'approval';
        });
    }

    public function test_cannot_approve_already_approved_registration(): void
    {
        $registration = Registration::factory()->create();

        // Manually set status to avoid observer interference
        $registration->updateQuietly(['status' => Registration::STATUS_APPROVED]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.approve', $registration));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment(['message' => __('Registration cannot be approved from current status: :status', ['status' => 'approved'])]);
    }

    public function test_cannot_approve_rejected_registration(): void
    {
        $registration = Registration::factory()->create();

        // Manually set status to avoid observer interference
        $registration->updateQuietly(['status' => Registration::STATUS_REJECTED]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.approve', $registration));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_reject_registration_with_reason(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
            'full_name' => 'Bob Wilson',
            'email' => 'bob@test.com',
            'notes' => null,
        ]);

        $rejectionReason = 'Documentation is incomplete';

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), [
                'reason' => $rejectionReason,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonFragment(['message' => __('Registration rejected successfully for :name', ['name' => 'Bob Wilson'])]);

        // Check registration status updated
        $registration->refresh();
        $this->assertEquals(Registration::STATUS_REJECTED, $registration->status);

        // Check administrative logging with reason
        $this->assertStringContainsString('Registration rejected by Test Admin', $registration->notes);
        $this->assertStringContainsString($rejectionReason, $registration->notes);

        // AC4: Check rejection email queued
        Mail::assertQueued(PaymentRejectedNotification::class, function ($mail) use ($registration, $rejectionReason) {
            return $mail->registration->id === $registration->id &&
                   $mail->rejectionReason === $rejectionReason;
        });
    }

    public function test_reject_requires_reason(): void
    {
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reject_reason_must_not_be_empty(): void
    {
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), [
                'reason' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reject_reason_cannot_exceed_max_length(): void
    {
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
        ]);

        $longReason = str_repeat('A', 501); // Exceeds 500 character limit

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), [
                'reason' => $longReason,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_exemption_preserves_existing_notes(): void
    {
        $existingNotes = 'Previous note from registration';

        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING,
            'notes' => $existingNotes,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.approve', $registration));

        $registration->refresh();

        // Check both existing and new notes are present
        $this->assertStringContainsString($existingNotes, $registration->notes);
        $this->assertStringContainsString('Fee exemption granted by Test Admin', $registration->notes);
    }

    /**
     * AC4: Test rejection notification email is sent with correct reason and recipient
     */
    public function test_rejection_notification_email_sent_with_reason(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
            'full_name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'notes' => 'Initial notes',
        ]);

        $rejectionReason = 'Payment proof image is too blurry to verify details. Please upload a clearer image.';

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), [
                'reason' => $rejectionReason,
            ]);

        $response->assertOk();

        // AC4: Verify rejection notification email was queued
        Mail::assertQueued(PaymentRejectedNotification::class, function ($mail) use ($registration, $rejectionReason) {
            return $mail->registration->id === $registration->id &&
                   $mail->registration->email === 'jane.doe@example.com' &&
                   $mail->registration->full_name === 'Jane Doe' &&
                   $mail->rejectionReason === $rejectionReason;
        });

        // Ensure only one rejection email was queued
        Mail::assertQueued(PaymentRejectedNotification::class, function ($mail) {
            return $mail->registration->email === 'jane.doe@example.com';
        });

        // Verify no other emails were queued
        Mail::assertNotQueued(PaymentApprovedNotification::class);
    }

    /**
     * AC4: Test rejection notification preserves existing notes and adds admin logging
     */
    public function test_rejection_preserves_existing_notes_and_adds_admin_logging(): void
    {
        Mail::fake();

        $existingNotes = 'User submitted initial documentation';
        $registration = Registration::factory()->create([
            'status' => Registration::STATUS_PENDING_APPROVAL,
            'full_name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'notes' => $existingNotes,
        ]);

        $rejectionReason = 'Missing required signature on payment receipt';

        $this->actingAs($this->admin)
            ->postJson(route('admin.registrations.reject', $registration), [
                'reason' => $rejectionReason,
            ]);

        $registration->refresh();

        // Check existing notes are preserved
        $this->assertStringContainsString($existingNotes, $registration->notes);

        // Check administrative logging
        $this->assertStringContainsString('Registration rejected by Test Admin', $registration->notes);
        $this->assertStringContainsString($rejectionReason, $registration->notes);
        $this->assertStringContainsString(now()->format('Y-m-d'), $registration->notes);

        // AC4: Verify email queued with correct data
        Mail::assertQueued(PaymentRejectedNotification::class, function ($mail) use ($registration, $rejectionReason) {
            return $mail->registration->id === $registration->id &&
                   $mail->rejectionReason === $rejectionReason;
        });
    }
}
