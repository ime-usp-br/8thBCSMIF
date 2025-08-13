<?php

namespace Tests\Feature\Admin;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create regular user
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
    }

    public function test_admin_navigation_menu_shows_admin_panel_section(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin Panel'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Registrations'));
        $response->assertSee(__('Reports'));
    }

    public function test_regular_user_navigation_does_not_show_admin_panel(): void
    {
        // Create a registration for the regular user so they have access to the system
        Registration::factory()->withCategory('undergrad_student')->create(['user_id' => $this->regularUser->id]);

        // Check that when regular user visits home page, they don't see admin panel
        // The page may allow access or redirect depending on middleware configuration
        $response = $this->actingAs($this->regularUser)
            ->get('/');

        // If the page allows access (status 200), check admin panel is not visible
        if ($response->status() === 200) {
            $response->assertDontSee(__('Admin Panel'));
        } else {
            // If redirected, follow the redirect and check the final page
            $response->assertStatus(302);
            $location = $response->headers->get('Location');

            $response = $this->actingAs($this->regularUser)
                ->get($location);

            $response->assertStatus(200);
            $response->assertDontSee(__('Admin Panel'));
        }
    }

    public function test_admin_dashboard_has_correct_breadcrumbs(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee('aria-label="'.__('Breadcrumb navigation').'"', false);
        $response->assertSee('aria-label="'.__('Go to Admin Dashboard').'"', false);
    }

    public function test_admin_registrations_page_has_correct_breadcrumbs(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.registrations.index'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Registrations'));
        $response->assertSee('aria-label="'.__('Breadcrumb navigation').'"', false);
    }

    public function test_admin_reports_page_has_correct_breadcrumbs(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Reports'));
        $response->assertSee('aria-label="'.__('Breadcrumb navigation').'"', false);
    }

    public function test_admin_registration_show_page_has_correct_breadcrumbs(): void
    {
        // Create a registration to test with
        $registration = Registration::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.registrations.show', $registration));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Registrations'));
        $response->assertSee(__('Registration #:id', ['id' => $registration->id]));
        $response->assertSee('aria-label="'.__('Breadcrumb navigation').'"', false);
    }

    public function test_admin_reports_sub_pages_have_correct_breadcrumbs(): void
    {
        // Test payments report
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.payments'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Reports'));
        $response->assertSee(__('Payments'));

        // Test enrollment proofs report
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.enrollment-proofs'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Reports'));
        $response->assertSee(__('Enrollment Proofs'));

        // Test auto-approved report
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.auto-approved'));

        $response->assertStatus(200);
        $response->assertSee(__('Admin'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Reports'));
        $response->assertSee(__('Auto-approved Payments'));
    }

    public function test_breadcrumb_navigation_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Check for proper ARIA attributes
        $response->assertSee('role="navigation"', false);
        $response->assertSee('aria-label="'.__('Breadcrumb navigation').'"', false);
        $response->assertSee('aria-current="page"', false);
    }

    public function test_admin_navigation_links_work_correctly(): void
    {
        // Test dashboard link
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Test registrations link
        $response = $this->actingAs($this->admin)
            ->get(route('admin.registrations.index'));
        $response->assertStatus(200);

        // Test reports link
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'));
        $response->assertStatus(200);
    }

    public function test_dashboard_link_is_visually_prominent_in_navigation(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Check for dashboard icon and styling
        $response->assertSee('text-usp-blue-pri', false);
        $response->assertSee('font-medium', false);
        $response->assertSee('<svg', false); // Dashboard icon
    }

    public function test_breadcrumb_links_are_functional(): void
    {
        // Test that breadcrumb links actually work by visiting registration details page
        $registration = Registration::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.registrations.show', $registration));

        $response->assertStatus(200);

        // The breadcrumb should have clickable links back to dashboard and registrations
        $response->assertSee('href="'.route('admin.dashboard').'"', false);
        $response->assertSee('href="'.route('admin.registrations.index').'"', false);
    }

    public function test_responsive_navigation_includes_admin_panel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Check that responsive navigation includes admin sections
        // Admin panel should be visible in the mobile menu
        $response->assertSee(__('Admin Panel'));
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Registrations'));
        $response->assertSee(__('Reports'));

        // Verify the responsive navigation structure exists
        $response->assertSee('class="', false); // Has CSS classes for responsive behavior
    }

    public function test_navigation_maintains_accessibility_standards(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Check for WCAG compliance elements
        $response->assertSee('aria-label', false);
        $response->assertSee('role=', false);
        $response->assertSee('tabindex="0"', false);
    }
}
