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
        $response->assertSee('data-update-route="admin/registrations/'.$registration->id.'/update-status"', false);
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
}
