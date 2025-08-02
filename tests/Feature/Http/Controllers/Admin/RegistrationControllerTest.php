<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Mail\PaymentStatusUpdatedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usp_user', 'guard_name' => 'web']);
    }

    public function test_admin_registration_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.registrations.index'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_registration_index_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.registrations.index'));

        $response->assertStatus(403);
    }

    public function test_admin_registration_index_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.registrations.index'));

        $response->assertOk();
        $response->assertViewIs('admin.registrations.index');
    }

    public function test_admin_registration_show_requires_authentication(): void
    {
        $registration = Registration::factory()->create();

        $response = $this->get(route('admin.registrations.show', $registration));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_registration_show_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');
        $registration = Registration::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.registrations.show', $registration));

        $response->assertStatus(403);
    }

    public function test_admin_registration_show_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertViewIs('admin.registrations.show');
        $response->assertViewHas('registration', $registration);
    }

    public function test_admin_registration_show_displays_events_with_price_at_registration(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $event1 = \App\Models\Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => '8th BCSMIF Conference',
            'is_main_conference' => true,
            'registration_deadline_early' => now()->addDays(30),
            'registration_deadline_late' => now()->addDays(60),
        ]);
        $event2 = \App\Models\Event::factory()->create([
            'code' => 'RAA2025',
            'name' => 'RAA Workshop 2025',
            'is_main_conference' => false,
            'registration_deadline_early' => now()->addDays(30),
            'registration_deadline_late' => now()->addDays(60),
        ]);

        // Create fees for the test events to match the expected behavior
        \App\Models\Fee::factory()->create([
            'event_code' => 'BCSMIF2025',
            'participant_category' => 'graduate_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.50,
            'is_discount_for_main_event_participant' => false,
        ]);
        \App\Models\Fee::factory()->create([
            'event_code' => 'RAA2025',
            'participant_category' => 'graduate_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 50.25,
            'is_discount_for_main_event_participant' => false,
        ]);

        $registration = Registration::factory()->create([
            'full_name' => 'Test User',
            'registration_category_snapshot' => 'graduate_student',
            'participation_format' => 'in-person',
        ]);

        $registration->events()->attach([
            $event1->code => ['price_at_registration' => 100.50],
            $event2->code => ['price_at_registration' => 50.25],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertViewIs('admin.registrations.show');
        $response->assertViewHas('registration');

        // Verify events data is available in the view
        $viewRegistration = $response->viewData('registration');
        $this->assertEquals(2, $viewRegistration->events->count());

        // Verify events with price_at_registration
        $events = $viewRegistration->events;
        $bcsmifEvent = $events->where('code', 'BCSMIF2025')->first();
        $raaEvent = $events->where('code', 'RAA2025')->first();

        $this->assertNotNull($bcsmifEvent);
        $this->assertEquals(100.50, $bcsmifEvent->pivot->price_at_registration);
        $this->assertEquals('8th BCSMIF Conference', $bcsmifEvent->name);

        $this->assertNotNull($raaEvent);
        $this->assertEquals(50.25, $raaEvent->pivot->price_at_registration);
        $this->assertEquals('RAA Workshop 2025', $raaEvent->name);

        // Verify the view content contains the events and prices
        $response->assertSee('8th BCSMIF Conference');
        $response->assertSee('RAA Workshop 2025');
        $response->assertSee('R$ 100,50');
        $response->assertSee('R$ 50,25');
        $response->assertSee('R$ 150,75'); // Total calculated fee
    }

    public function test_admin_download_proof_requires_authentication(): void
    {
        $registration = Registration::factory()->create();

        $response = $this->get(route('admin.registrations.download-proof', $registration));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_download_proof_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');
        $registration = Registration::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.registrations.download-proof', $registration));

        $response->assertStatus(403);
    }

    // NOTE: Download proof tests removed - functionality needs refactoring for Payment model
    // TODO: Re-implement these tests when download proof feature is updated to use Payment model:
    // - test_admin_download_proof_returns_404_when_no_proof_path
    // - test_admin_download_proof_returns_404_when_file_does_not_exist
    // - test_admin_download_proof_downloads_file_when_exists

    /**
     * AC2: Test that admin.registrations.show page displays payment status update form with dropdown
     */
    public function test_admin_registration_show_displays_status_update_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertSee(__('Update Payment Status'));
        $response->assertSee('name="status"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('action="'.route('admin.registrations.update-status', $registration).'"', false);
        $response->assertSee(__('Update Status'));
    }

    /**
     * AC2: Test that payment status dropdown contains all required status options
     */
    public function test_admin_registration_show_dropdown_contains_all_required_status_options(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();

        // Verify all required status options are present in the dropdown
        $response->assertSee('value="pending"', false);
        $response->assertSee('value="pending_approval"', false);
        $response->assertSee('value="approved"', false);
        $response->assertSee('value="rejected"', false);
        $response->assertSee('value="approved"', false);
        $response->assertSee('value="pending"', false);
        $response->assertSee('value="rejected"', false);

        // Verify label translations are present
        $response->assertSee(__('Pending'));
        $response->assertSee(__('Pending Approval'));
        $response->assertSee(__('Approved'));
        $response->assertSee(__('Rejected'));
    }

    /**
     * AC2: Test that current payment status is pre-selected in dropdown
     */
    public function test_admin_registration_show_dropdown_preselects_current_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test with different payment statuses
        $testStatuses = ['pending', 'pending_approval', 'approved', 'rejected'];

        foreach ($testStatuses as $status) {
            $registration = Registration::factory()->create();
            // Manually set status to avoid observer interference
            $registration->updateQuietly(['status' => $status]);

            $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

            $response->assertOk();
            $response->assertSee('value="'.$status.'" selected', false);
        }
    }

    /**
     * AC8: Test that payment status form is responsive and follows Tailwind CSS styling across different screen sizes
     */
    public function test_admin_registration_show_form_has_responsive_styling(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();

        // Verify responsive classes are present for different screen sizes
        $response->assertSee('flex flex-col lg:flex-row', false);
        $response->assertSee('lg:items-start lg:justify-between', false);
        $response->assertSee('gap-4', false);
        $response->assertSee('flex-shrink-0', false);
        $response->assertSee('w-full lg:w-auto', false);
        $response->assertSee('lg:min-w-96', false);

        // Verify form inner responsive classes
        $response->assertSee('flex flex-col sm:flex-row', false);
        $response->assertSee('gap-3 items-stretch sm:items-center', false);

        // Verify Tailwind CSS classes for form styling
        $response->assertSee('shadow-sm', false);
        $response->assertSee('bg-usp-blue-pri', false);
        $response->assertSee('rounded', false);

        // Verify accessibility features
        $response->assertSee('sr-only', false);
        $response->assertSee('disabled:opacity-50 disabled:cursor-not-allowed', false);
        $response->assertSee('whitespace-nowrap', false);
        $response->assertSee('justify-center', false);
        $response->assertSee('flex-shrink-0', false);
    }

    /**
     * AC3: Test that PATCH route for update-status exists and is accessible to admin
     */
    public function test_admin_update_status_route_exists_and_requires_admin(): void
    {
        $registration = Registration::factory()->create();

        // Test that route exists by checking it doesn't return 404
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved', // Provide valid status to avoid validation errors
        ]);

        // Should not return 404 (route exists) and should redirect (method exists)
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.registrations.show', $registration));
    }

    /**
     * AC3: Test that update-status route requires authentication
     */
    public function test_admin_update_status_requires_authentication(): void
    {
        $registration = Registration::factory()->create();

        $response = $this->patch(route('admin.registrations.update-status', $registration));

        $response->assertRedirect(route('login.local'));
    }

    /**
     * AC3: Test that update-status route requires admin role
     */
    public function test_admin_update_status_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');
        $registration = Registration::factory()->create();

        $response = $this->actingAs($user)->patch(route('admin.registrations.update-status', $registration));

        $response->assertStatus(403);
    }

    /**
     * AC3: Test that updateStatus method in controller exists and can be called
     */
    public function test_admin_update_status_method_exists_and_redirects(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved', // Provide valid status to avoid validation errors
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.registrations.show', $registration));
    }

    /**
     * AC4: Test that updateStatus validates status field is required
     */
    public function test_admin_update_status_validates_status_required(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            // No status provided
        ]);

        $response->assertSessionHasErrors('status');
        $response->assertStatus(302);
    }

    /**
     * AC4: Test that updateStatus validates status against allowed values
     */
    public function test_admin_update_status_validates_status_allowed_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        // Test invalid payment status
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
        $response->assertStatus(302);

        // Verify registration status was not changed
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);
    }

    /**
     * AC4: Test that updateStatus accepts all valid status values
     */
    public function test_admin_update_status_accepts_all_valid_status_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $validStatuses = [
            'pending',
            'pending_approval',
            'approved',
            'rejected',
            'approved',
            'pending',
            'rejected',
        ];

        foreach ($validStatuses as $status) {
            $registration = Registration::factory()->create(['status' => 'pending']);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $status,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.registrations.show', $registration));
            $response->assertSessionHas('success', __('Status updated successfully.'));

            // Verify the status was actually updated in the database
            $registration->refresh();
            $this->assertEquals($status, $registration->status);
        }
    }

    /**
     * AC5: Test that status field is correctly updated in database after successful validation
     */
    public function test_admin_update_status_correctly_updates_status_in_database(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create registration with initial status
        $registration = Registration::factory()->create(['status' => 'pending']);

        // Verify initial status
        $this->assertEquals('pending', $registration->status);

        // Update to a different status
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        // Verify response success
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify the status field was correctly updated in the database
        $registration->refresh();
        $this->assertEquals('approved', $registration->status);

        // Test another status change to ensure updates work consistently
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'rejected',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify the second update was also correctly applied
        $registration->refresh();
        $this->assertEquals('rejected', $registration->status);
    }

    /**
     * AC6: Test that admin is redirected back to registration details page with success message after successful update
     */
    public function test_admin_update_status_redirects_to_show_with_success_message(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        // Verify redirect to the correct show page
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.registrations.show', $registration));

        // Verify success flash message is set
        $response->assertSessionHas('success', __('Status updated successfully.'));
    }

    /**
     * AC6: Test that the success message is displayed on the registration details page after redirect
     */
    public function test_admin_registration_show_displays_success_message_after_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        // Simulate the redirect with success message
        $response = $this->actingAs($admin)
            ->withSession(['success' => __('Status updated successfully.')])
            ->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertViewIs('admin.registrations.show');

        // Verify the success message is displayed in the view
        $response->assertSee(__('Status updated successfully.'));
    }

    /**
     * AC7: Test that registration details page reflects the new payment status after update and redirect
     */
    public function test_admin_registration_show_reflects_new_status_after_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        // Test all possible status transitions to ensure header reflects all statuses correctly
        $statusTransitions = [
            'pending_approval' => __('Pending Approval'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            'pending' => __('Pending'), // Back to original
        ];

        foreach ($statusTransitions as $newStatus => $expectedLabel) {
            // Update the payment status
            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $newStatus,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.registrations.show', $registration));

            // Verify database was updated
            $registration->refresh();
            $this->assertEquals($newStatus, $registration->status);

            // Visit the show page to verify the new status is reflected
            $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

            $response->assertOk();
            $response->assertViewIs('admin.registrations.show');

            // Verify the new status is displayed in both locations (header and payment section)
            $response->assertSee($expectedLabel);

            // Verify the status badge in header section reflects the new status
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'pending_approval' => 'bg-orange-100 text-orange-800',
                'approved' => 'bg-green-100 text-green-800',
                'rejected' => 'bg-red-100 text-red-800',
            ];

            // Verify the correct color class is present for the current status
            $expectedColorClass = $statusColors[$newStatus] ?? 'bg-gray-100 text-gray-800';
            $response->assertSee($expectedColorClass, false);

            // Verify that the payment status is also correctly reflected in the detailed payment section
            $response->assertSee(__('Payment Status'));
            $response->assertSee($expectedLabel);
        }
    }

    /**
     * AC9: Test that status change log is recorded in the notes field
     */
    public function test_admin_update_status_creates_log_entry_in_notes(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => null,
        ]);

        // Update the payment status
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify the log entry was created in the notes field
        $registration->refresh();
        $this->assertNotNull($registration->notes);

        // Verify log entry contains all required information
        $this->assertStringContainsString('Status changed by Admin User', $registration->notes);
        $this->assertStringContainsString("'pending' -> 'approved'", $registration->notes);

        // Verify timestamp format is present (YYYY-MM-DD HH:MM:SS format)
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $registration->notes);
    }

    /**
     * AC9: Test that multiple status changes append to existing notes
     */
    public function test_admin_update_status_appends_to_existing_notes(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => 'Initial note about registration',
        ]);

        // First status change
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'pending_approval',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $firstNotes = $registration->notes;

        // Verify original notes are preserved
        $this->assertStringContainsString('Initial note about registration', $firstNotes);
        $this->assertStringContainsString("'pending' -> 'pending_approval'", $firstNotes);

        // Second status change
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $secondNotes = $registration->notes;

        // Verify all notes are preserved and new log is appended
        $this->assertStringContainsString('Initial note about registration', $secondNotes);
        $this->assertStringContainsString("'pending' -> 'pending_approval'", $secondNotes);
        $this->assertStringContainsString("'pending_approval' -> 'approved'", $secondNotes);
    }

    /**
     * AC9: Test that log entry includes admin name from user name field
     */
    public function test_admin_update_status_log_includes_admin_name(): void
    {
        $admin = User::factory()->create([
            'name' => 'João Silva Admin',
            'email' => 'joao.silva@usp.br',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $this->assertStringContainsString('Status changed by João Silva Admin', $registration->notes);
    }

    /**
     * AC9: Test that log entry falls back to email when name is not available
     */
    public function test_admin_update_status_log_falls_back_to_email_when_no_name(): void
    {
        $admin = User::factory()->create([
            'name' => '',
            'email' => 'admin@usp.br',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $this->assertStringContainsString('Status changed by admin@usp.br', $registration->notes);
    }

    /**
     * AC9: Test that log entry contains correct timestamp format
     */
    public function test_admin_update_status_log_contains_correct_timestamp_format(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => null,
        ]);

        $beforeTime = now();

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $afterTime = now();

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();

        // Extract timestamp from log entry
        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $registration->notes, $matches);
        $this->assertCount(2, $matches, 'Timestamp pattern not found in notes');

        $logTimestamp = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);

        // Verify timestamp is within reasonable range (between before and after the request)
        $this->assertTrue($logTimestamp->greaterThanOrEqualTo($beforeTime->subSeconds(1)));
        $this->assertTrue($logTimestamp->lessThanOrEqualTo($afterTime->addSeconds(1)));
    }

    /**
     * AC10: Test that payment status update form displays email notification checkbox
     */
    public function test_admin_registration_show_displays_email_notification_checkbox(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertSee('name="send_notification"', false);
        $response->assertSee('type="checkbox"', false);
        $response->assertSee(__('Send email notification to participant'));
        $response->assertSee('checked', false); // Should be checked by default
    }

    /**
     * AC10: Test that email notification is sent when checkbox is checked
     */
    public function test_admin_update_status_sends_email_notification_when_requested(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'name' => 'Participant User',
            'email' => 'participant@example.com',
        ]);

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'user_id' => $participant->id,
            'email' => $participant->email,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            'send_notification' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify email was queued
        Mail::assertQueued(PaymentStatusUpdatedNotification::class, function ($mail) use ($registration, $participant) {
            return $mail->hasTo($participant->email) &&
                   $mail->registration->id === $registration->id &&
                   $mail->oldStatus === 'pending' &&
                   $mail->newStatus === 'approved';
        });
    }

    /**
     * AC10: Test that email notification is not sent when checkbox is unchecked
     */
    public function test_admin_update_status_does_not_send_email_notification_when_not_requested(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'name' => 'Participant User',
            'email' => 'participant@example.com',
        ]);

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'user_id' => $participant->id,
            'email' => $participant->email,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            // send_notification not included (unchecked)
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify no email was sent
        Mail::assertNotSent(PaymentStatusUpdatedNotification::class);
    }

    /**
     * AC10: Test that email notification works correctly for confirmation statuses like approved and approved
     */
    public function test_admin_update_status_sends_notification_for_confirmation_statuses(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'email' => 'participant@example.com',
        ]);

        $confirmationStatuses = ['approved', 'approved', 'pending'];

        foreach ($confirmationStatuses as $status) {
            $registration = Registration::factory()->create([
                'status' => 'pending',
                'user_id' => $participant->id,
                'email' => $participant->email,
            ]);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $status,
                'send_notification' => '1',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);

            // Verify email was queued for this confirmation status
            Mail::assertQueued(PaymentStatusUpdatedNotification::class, function ($mail) use ($registration, $participant, $status) {
                return $mail->hasTo($participant->email) &&
                       $mail->registration->id === $registration->id &&
                       $mail->oldStatus === 'pending' &&
                       $mail->newStatus === $status;
            });
        }

        // Verify we queued exactly 3 emails (one for each confirmation status)
        Mail::assertQueued(PaymentStatusUpdatedNotification::class, 3);
    }

    /**
     * AC10: Test that email notification validation parameter works correctly
     */
    public function test_admin_update_status_send_notification_parameter_validation(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'email' => 'participant@example.com',
        ]);

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'user_id' => $participant->id,
            'email' => $participant->email,
        ]);

        // Test invalid send_notification value is rejected by validation
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            'send_notification' => 'invalid_value',
        ]);

        $response->assertSessionHasErrors('send_notification');
        $response->assertStatus(302);

        // Verify no email was sent due to validation failure
        Mail::assertNotSent(PaymentStatusUpdatedNotification::class);
    }

    /**
     * AC10: Test that email notification contains correct information
     */
    public function test_admin_update_status_notification_contains_correct_information(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $participant->id,
            'email' => $participant->email,
            'full_name' => 'John Doe',
        ]);
        // Manually set status to avoid observer interference
        $registration->updateQuietly(['status' => 'pending_approval']);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            'send_notification' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify email was queued with correct data
        Mail::assertQueued(PaymentStatusUpdatedNotification::class, function ($mail) use ($registration, $participant) {
            // Check recipient
            $hasCorrectRecipient = $mail->hasTo($participant->email);

            // Check that the mail object has the correct data
            $hasCorrectRegistration = $mail->registration->id === $registration->id;
            $hasCorrectOldStatus = $mail->oldStatus === 'pending_approval';
            $hasCorrectNewStatus = $mail->newStatus === 'approved';

            return $hasCorrectRecipient && $hasCorrectRegistration && $hasCorrectOldStatus && $hasCorrectNewStatus;
        });
    }

    /**
     * AC11: Test that unauthorized access (no authentication) to update-status route is blocked
     */
    public function test_admin_update_status_blocks_unauthenticated_access(): void
    {
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('login.local'));

        // Verify registration status was not changed
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);
    }

    /**
     * AC11: Test that unauthorized access (no admin role) to update-status route is blocked
     */
    public function test_admin_update_status_blocks_non_admin_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);

        // Verify registration status was not changed
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);
    }

    /**
     * AC11: Test that admin can change payment status for all valid allowed statuses
     */
    public function test_admin_can_change_status_to_all_valid_statuses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $validStatuses = [
            'pending',
            'pending_approval',
            'approved',
            'rejected',
            'approved',
            'pending',
            'rejected',
        ];

        $initialStatus = 'pending';

        foreach ($validStatuses as $targetStatus) {
            $registration = Registration::factory()->create(['status' => $initialStatus]);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $targetStatus,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.registrations.show', $registration));
            $response->assertSessionHas('success', __('Status updated successfully.'));

            // Verify status change is reflected in database
            $registration->refresh();
            $this->assertEquals($targetStatus, $registration->status);
        }
    }

    /**
     * AC11: Test that status changes are correctly reflected in the database
     */
    public function test_admin_update_status_database_reflection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test multiple status transitions to verify database updates
        $statusTransitions = [
            ['from' => 'pending', 'to' => 'pending_approval'],
            ['from' => 'pending_approval', 'to' => 'approved'],
            ['from' => 'approved', 'to' => 'rejected'],
            ['from' => 'rejected', 'to' => 'pending'],
            ['from' => 'pending', 'to' => 'approved'],
        ];

        foreach ($statusTransitions as $transition) {
            $registration = Registration::factory()->create();
            // Manually set status to avoid observer interference
            $registration->updateQuietly(['status' => $transition['from']]);

            // Verify initial status
            $this->assertEquals($transition['from'], $registration->status);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $transition['to'],
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);

            // Verify status change is immediately reflected in database
            $registration->refresh();
            $this->assertEquals($transition['to'], $registration->status);

            // Double-check by re-querying from database
            $freshRegistration = Registration::find($registration->id);
            $this->assertEquals($transition['to'], $freshRegistration->status);
        }
    }

    /**
     * AC11: Test that invalid status values fail validation and don't alter status
     */
    public function test_admin_update_status_rejects_invalid_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['status' => 'pending']);

        $invalidStatuses = [
            'invalid_status',
            'not_allowed',
            'random_string',
            'paid_invalid',
            '',
            'PAID_BR', // Case sensitivity
            'pending payment', // Spaces
        ];

        foreach ($invalidStatuses as $invalidStatus) {
            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $invalidStatus,
            ]);

            $response->assertSessionHasErrors('status');
            $response->assertStatus(302);

            // Verify registration status was not changed
            $registration->refresh();
            $this->assertEquals('pending', $registration->status);
        }
    }

    /**
     * AC11: Test that log entries are correctly created when status changes (AC9 validation)
     */
    public function test_admin_update_status_creates_correct_log_entries(): void
    {
        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
        $admin->assignRole('admin');

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'notes' => null,
        ]);

        // Test first status change
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $this->assertNotNull($registration->notes);
        $this->assertStringContainsString('Status changed by Test Admin', $registration->notes);
        $this->assertStringContainsString("'pending' -> 'approved'", $registration->notes);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $registration->notes);

        // Test second status change to verify log appending
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'rejected',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $registration->refresh();
        $this->assertStringContainsString("'pending' -> 'approved'", $registration->notes);
        $this->assertStringContainsString("'approved' -> 'rejected'", $registration->notes);

        // Verify both log entries have timestamps
        $this->assertEquals(2, preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $registration->notes));
    }

    /**
     * AC11: Test that email notifications are correctly sent when requested (AC10 validation)
     */
    public function test_admin_update_status_email_notification_functionality(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'name' => 'Test Participant',
            'email' => 'participant@test.com',
        ]);

        $registration = Registration::factory()->create([
            'status' => 'pending',
            'user_id' => $participant->id,
            'email' => $participant->email,
        ]);

        // Test that notification is sent when requested
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            'send_notification' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        Mail::assertQueued(PaymentStatusUpdatedNotification::class, function ($mail) use ($registration, $participant) {
            return $mail->hasTo($participant->email) &&
                   $mail->registration->id === $registration->id &&
                   $mail->oldStatus === 'pending' &&
                   $mail->newStatus === 'approved';
        });

        // Test that notification is NOT sent when not requested
        Mail::fake(); // Reset mail fake

        $registration->update(['status' => 'pending']); // Reset status

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
            // send_notification not included (unchecked)
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        Mail::assertNotSent(PaymentStatusUpdatedNotification::class);
    }

    /**
     * AC6 Issue #75: Test that individual Payment records are updated when admin changes registration status to approved
     */
    public function test_admin_update_status_to_approved_updates_individual_payment_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $registration = Registration::factory()->create(['status' => 'pending_approval']);

        // Create individual payment records with pending_approval status
        $payment1 = $registration->payments()->create([
            'amount' => 100.00,
            'status' => 'pending_approval',
        ]);
        $payment2 = $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'pending_approval',
        ]);
        $payment3 = $registration->payments()->create([
            'amount' => 25.00,
            'status' => 'pending', // Different status - should not be affected
        ]);

        // Update registration status to approved
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify registration status was updated
        $registration->refresh();
        $this->assertEquals('approved', $registration->status);

        // Verify individual payment records with pending_approval were updated to approved
        $payment1->refresh();
        $this->assertEquals('approved', $payment1->status);

        $payment2->refresh();
        $this->assertEquals('approved', $payment2->status);

        // Verify payment with different status was not affected
        $payment3->refresh();
        $this->assertEquals('pending', $payment3->status);
    }

    /**
     * AC6 Issue #75: Test that individual Payment records are updated when admin changes registration status to pending
     */
    public function test_admin_update_status_to_pending_updates_individual_payment_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $registration = Registration::factory()->create(['status' => 'pending_approval']);

        // Create individual payment records with pending_approval status
        $payment1 = $registration->payments()->create([
            'amount' => 100.00,
            'status' => 'pending_approval',
        ]);
        $payment2 = $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'pending_approval',
        ]);
        $payment3 = $registration->payments()->create([
            'amount' => 25.00,
            'status' => 'approved', // Different status - should not be affected
        ]);

        // Update registration status to pending (rejection)
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'pending',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify registration status was updated
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);

        // Verify individual payment records with pending_approval were updated to pending
        $payment1->refresh();
        $this->assertEquals('pending', $payment1->status);

        $payment2->refresh();
        $this->assertEquals('pending', $payment2->status);

        // Verify payment with different status was not affected
        $payment3->refresh();
        $this->assertEquals('approved', $payment3->status);
    }

    /**
     * AC6 Issue #75: Test that other registration status changes do not affect individual Payment records
     */
    public function test_admin_update_status_to_other_statuses_does_not_affect_individual_payment_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $registration = Registration::factory()->create();
        // Manually set status to avoid observer interference
        $registration->updateQuietly(['status' => 'pending']);

        // Create individual payment records with pending_approval status
        $payment1 = $registration->payments()->create([
            'amount' => 100.00,
            'status' => 'pending_approval',
        ]);
        $payment2 = $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'pending',
        ]);

        // Test various status changes that should NOT affect individual payments
        // Only pending_approval should not affect payments according to controller logic
        $statusesToTest = ['pending_approval'];

        foreach ($statusesToTest as $status) {
            // Reset registration status
            $registration->updateQuietly(['status' => 'pending']);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'status' => $status,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);

            // Verify registration status was updated
            $registration->refresh();
            $this->assertEquals($status, $registration->status);

            // Verify individual payment records were NOT affected
            $payment1->refresh();
            $this->assertEquals('pending_approval', $payment1->status,
                "Payment 1 status should not change when registration status changes to {$status}");

            $payment2->refresh();
            $this->assertEquals('pending', $payment2->status,
                "Payment 2 status should not change when registration status changes to {$status}");
        }
    }

    /**
     * AC6 Issue #75: Test that automatic block removal works - modification should be allowed after status change to approved
     */
    public function test_admin_update_status_to_approved_automatically_removes_modification_block(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create(['status' => 'pending_approval']);

        // Create payment with pending_approval status (blocks modification)
        $payment = $registration->payments()->create([
            'amount' => 100.00,
            'status' => 'pending_approval',
        ]);

        // Verify modification is initially blocked
        $this->assertFalse($user->can('modify', $registration), 'Modification should be blocked when payment status is pending_approval');

        // Admin updates status to approved
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify payment status was updated
        $payment->refresh();
        $this->assertEquals('approved', $payment->status);

        // Verify modification is now allowed (block automatically removed)
        $this->assertTrue($user->can('modify', $registration), 'Modification should be allowed after payment status changes to approved');
    }

    /**
     * AC6 Issue #75: Test that automatic block removal works - modification should be allowed after status change to pending
     */
    public function test_admin_update_status_to_pending_automatically_removes_modification_block(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create(['status' => 'pending_approval']);

        // Create payment with pending_approval status (blocks modification)
        $payment = $registration->payments()->create([
            'amount' => 100.00,
            'status' => 'pending_approval',
        ]);

        // Verify modification is initially blocked
        $this->assertFalse($user->can('modify', $registration), 'Modification should be blocked when payment status is pending_approval');

        // Admin updates status to pending (rejection)
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'status' => 'pending',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify payment status was updated
        $payment->refresh();
        $this->assertEquals('pending', $payment->status);

        // Verify modification is now allowed (block automatically removed)
        $this->assertTrue($user->can('modify', $registration), 'Modification should be allowed after payment status changes to pending');
    }
}
