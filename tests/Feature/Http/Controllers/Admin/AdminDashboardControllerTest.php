<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usp_user', 'guard_name' => 'web']);
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_admin_dashboard_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_admin_dashboard_renders_dashboard_view(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewIs('admin.dashboard');
    }

    public function test_admin_dashboard_passes_metrics_data_to_view(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('metrics');

        // Assert metrics structure contains expected keys
        $metrics = $response->viewData('metrics');

        $this->assertArrayHasKey('total_registrations', $metrics);
        $this->assertArrayHasKey('registrations_by_category', $metrics);
        $this->assertArrayHasKey('pending_approvals', $metrics);
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('transport_needs', $metrics);
    }

    public function test_admin_dashboard_metrics_structure_is_valid(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $metrics = $response->viewData('metrics');

        // Validate total_registrations structure
        $this->assertArrayHasKey('count', $metrics['total_registrations']);
        $this->assertArrayHasKey('trend', $metrics['total_registrations']);
        $this->assertArrayHasKey('change_percent', $metrics['total_registrations']);

        // Validate registrations_by_category structure
        $this->assertArrayHasKey('undergrad_student', $metrics['registrations_by_category']);
        $this->assertArrayHasKey('grad_student', $metrics['registrations_by_category']);
        $this->assertArrayHasKey('professor', $metrics['registrations_by_category']);
        $this->assertArrayHasKey('professional', $metrics['registrations_by_category']);

        // Validate pending_approvals structure
        $this->assertArrayHasKey('payment_proofs', $metrics['pending_approvals']);
        $this->assertArrayHasKey('enrollment_proofs', $metrics['pending_approvals']);

        // Validate revenue structure
        $this->assertArrayHasKey('confirmed', $metrics['revenue']);
        $this->assertArrayHasKey('pending', $metrics['revenue']);
        $this->assertArrayHasKey('currency', $metrics['revenue']);

        // Validate transport_needs structure
        $this->assertArrayHasKey('from_usp', $metrics['transport_needs']);
        $this->assertArrayHasKey('from_gru', $metrics['transport_needs']);
    }

    public function test_admin_dashboard_metrics_contain_valid_data_types(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $metrics = $response->viewData('metrics');

        // Validate data types
        $this->assertIsInt($metrics['total_registrations']['count']);
        $this->assertIsString($metrics['total_registrations']['trend']);
        $this->assertIsFloat($metrics['total_registrations']['change_percent']);

        $this->assertIsInt($metrics['pending_approvals']['payment_proofs']);
        $this->assertIsInt($metrics['pending_approvals']['enrollment_proofs']);

        $this->assertIsFloat($metrics['revenue']['confirmed']);
        $this->assertIsFloat($metrics['revenue']['pending']);
        $this->assertIsString($metrics['revenue']['currency']);

        $this->assertIsInt($metrics['transport_needs']['from_usp']);
        $this->assertIsInt($metrics['transport_needs']['from_gru']);
    }
}
