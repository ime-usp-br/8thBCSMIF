<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        ]);
        $event2 = \App\Models\Event::factory()->create([
            'code' => 'RAA2025',
            'name' => 'RAA Workshop 2025',
        ]);

        $registration = Registration::factory()->create([
            'full_name' => 'Test User',
            'calculated_fee' => 150.75,
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
        $registration = Registration::factory()->create(['payment_proof_path' => 'test.pdf']);

        $response = $this->get(route('admin.registrations.download-proof', $registration));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_download_proof_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');
        $registration = Registration::factory()->create(['payment_proof_path' => 'test.pdf']);

        $response = $this->actingAs($user)->get(route('admin.registrations.download-proof', $registration));

        $response->assertStatus(403);
    }

    public function test_admin_download_proof_returns_404_when_no_proof_path(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_proof_path' => null]);

        $response = $this->actingAs($admin)->get(route('admin.registrations.download-proof', $registration));

        $response->assertStatus(404);
    }

    public function test_admin_download_proof_returns_404_when_file_does_not_exist(): void
    {
        Storage::fake('private');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_proof_path' => 'nonexistent.pdf']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.download-proof', $registration));

        $response->assertStatus(404);
    }

    public function test_admin_download_proof_downloads_file_when_exists(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        Storage::disk('private')->put('proof.pdf', $file->getContent());

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_proof_path' => 'proof.pdf']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.download-proof', $registration));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * AC2: Test that admin.registrations.show page displays payment status update form with dropdown
     */
    public function test_admin_registration_show_displays_payment_status_update_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertSee(__('Update Payment Status'));
        $response->assertSee('name="payment_status"', false);
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
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();

        // Verify all required status options are present in the dropdown
        $response->assertSee('value="pending_payment"', false);
        $response->assertSee('value="pending_br_proof_approval"', false);
        $response->assertSee('value="paid_br"', false);
        $response->assertSee('value="invoice_sent_int"', false);
        $response->assertSee('value="paid_int"', false);
        $response->assertSee('value="free"', false);
        $response->assertSee('value="cancelled"', false);

        // Verify label translations are present
        $response->assertSee(__('Pending Payment'));
        $response->assertSee(__('Pending BR Proof Approval'));
        $response->assertSee(__('Paid (BR)'));
        $response->assertSee(__('Invoice Sent (International)'));
        $response->assertSee(__('Paid (International)'));
        $response->assertSee(__('Free'));
        $response->assertSee(__('Cancelled'));
    }

    /**
     * AC2: Test that current payment status is pre-selected in dropdown
     */
    public function test_admin_registration_show_dropdown_preselects_current_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test with different payment statuses
        $testStatuses = ['pending_payment', 'paid_br', 'paid_int', 'cancelled'];

        foreach ($testStatuses as $status) {
            $registration = Registration::factory()->create(['payment_status' => $status]);

            $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

            $response->assertOk();
            $response->assertSee('value="'.$status.'" selected', false);
        }
    }

    /**
     * AC2: Test that payment status form is responsive and follows Tailwind CSS styling
     */
    public function test_admin_registration_show_form_has_responsive_styling(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

        $response->assertOk();

        // Verify responsive classes are present
        $response->assertSee('flex flex-col sm:flex-row', false);
        $response->assertSee('gap-3 items-stretch sm:items-center', false);

        // Verify Tailwind CSS classes for form styling
        $response->assertSee('border-gray-300 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri', false);
        $response->assertSee('bg-usp-blue-pri hover:bg-usp-blue-pri/90', false);
        $response->assertSee('rounded-md', false);
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
            'payment_status' => 'paid_br', // Provide valid payment_status to avoid validation errors
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
            'payment_status' => 'paid_br', // Provide valid payment_status to avoid validation errors
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.registrations.show', $registration));
    }

    /**
     * AC4: Test that updateStatus validates payment_status field is required
     */
    public function test_admin_update_status_validates_payment_status_required(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            // No payment_status provided
        ]);

        $response->assertSessionHasErrors('payment_status');
        $response->assertStatus(302);
    }

    /**
     * AC4: Test that updateStatus validates payment_status against allowed values
     */
    public function test_admin_update_status_validates_payment_status_allowed_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        // Test invalid payment status
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'payment_status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('payment_status');
        $response->assertStatus(302);

        // Verify registration status was not changed
        $registration->refresh();
        $this->assertEquals('pending_payment', $registration->payment_status);
    }

    /**
     * AC4: Test that updateStatus accepts all valid payment_status values
     */
    public function test_admin_update_status_accepts_all_valid_payment_status_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $validStatuses = [
            'pending_payment',
            'pending_br_proof_approval',
            'paid_br',
            'invoice_sent_int',
            'paid_int',
            'free',
            'cancelled',
        ];

        foreach ($validStatuses as $status) {
            $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'payment_status' => $status,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.registrations.show', $registration));
            $response->assertSessionHas('success', __('Payment status updated successfully.'));

            // Verify the status was actually updated in the database
            $registration->refresh();
            $this->assertEquals($status, $registration->payment_status);
        }
    }

    /**
     * AC5: Test that payment_status field is correctly updated in database after successful validation
     */
    public function test_admin_update_status_correctly_updates_payment_status_in_database(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create registration with initial status
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        // Verify initial status
        $this->assertEquals('pending_payment', $registration->payment_status);

        // Update to a different status
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'payment_status' => 'paid_br',
        ]);

        // Verify response success
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify the payment_status field was correctly updated in the database
        $registration->refresh();
        $this->assertEquals('paid_br', $registration->payment_status);

        // Test another status change to ensure updates work consistently
        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'payment_status' => 'cancelled',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Verify the second update was also correctly applied
        $registration->refresh();
        $this->assertEquals('cancelled', $registration->payment_status);
    }

    /**
     * AC6: Test that admin is redirected back to registration details page with success message after successful update
     */
    public function test_admin_update_status_redirects_to_show_with_success_message(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
            'payment_status' => 'paid_br',
        ]);

        // Verify redirect to the correct show page
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.registrations.show', $registration));

        // Verify success flash message is set
        $response->assertSessionHas('success', __('Payment status updated successfully.'));
    }

    /**
     * AC6: Test that the success message is displayed on the registration details page after redirect
     */
    public function test_admin_registration_show_displays_success_message_after_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        // Simulate the redirect with success message
        $response = $this->actingAs($admin)
            ->withSession(['success' => __('Payment status updated successfully.')])
            ->get(route('admin.registrations.show', $registration));

        $response->assertOk();
        $response->assertViewIs('admin.registrations.show');

        // Verify the success message is displayed in the view
        $response->assertSee(__('Payment status updated successfully.'));
        $response->assertSee('bg-green-100 border border-green-400 text-green-700', false);
    }

    /**
     * AC7: Test that registration details page reflects the new payment status after update and redirect
     */
    public function test_admin_registration_show_reflects_new_payment_status_after_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create(['payment_status' => 'pending_payment']);

        // Test all possible status transitions to ensure header reflects all statuses correctly
        $statusTransitions = [
            'pending_br_proof_approval' => __('Pending BR Proof Approval'),
            'paid_br' => __('Paid (BR)'),
            'invoice_sent_int' => __('Invoice Sent (International)'),
            'paid_int' => __('Paid (International)'),
            'free' => __('Free'),
            'cancelled' => __('Cancelled'),
            'pending_payment' => __('Pending Payment'), // Back to original
        ];

        foreach ($statusTransitions as $newStatus => $expectedLabel) {
            // Update the payment status
            $response = $this->actingAs($admin)->patch(route('admin.registrations.update-status', $registration), [
                'payment_status' => $newStatus,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.registrations.show', $registration));

            // Verify database was updated
            $registration->refresh();
            $this->assertEquals($newStatus, $registration->payment_status);

            // Visit the show page to verify the new status is reflected
            $response = $this->actingAs($admin)->get(route('admin.registrations.show', $registration));

            $response->assertOk();
            $response->assertViewIs('admin.registrations.show');

            // Verify the new status is displayed in both locations (header and payment section)
            $response->assertSee($expectedLabel);

            // Verify the status badge in header section reflects the new status
            $statusColors = [
                'pending_payment' => 'bg-yellow-100 text-yellow-800',
                'pending_br_proof_approval' => 'bg-orange-100 text-orange-800',
                'paid_br' => 'bg-green-100 text-green-800',
                'invoice_sent_int' => 'bg-blue-100 text-blue-800',
                'paid_int' => 'bg-green-100 text-green-800',
                'free' => 'bg-purple-100 text-purple-800',
                'cancelled' => 'bg-red-100 text-red-800',
            ];

            // Verify the correct color class is present for the current status
            $expectedColorClass = $statusColors[$newStatus] ?? 'bg-gray-100 text-gray-800';
            $response->assertSee($expectedColorClass, false);

            // Verify that the payment status is also correctly reflected in the detailed payment section
            $response->assertSee(__('Payment Status'));
            $response->assertSee($expectedLabel);
        }
    }
}
