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
}
