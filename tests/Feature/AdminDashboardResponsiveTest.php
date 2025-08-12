<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminDashboardResponsiveTest
 *
 * Tests responsive design implementation for the Admin Dashboard
 */
class AdminDashboardResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    private $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database is properly migrated
        $this->ensureDatabaseMigrated();

        // Setup basic roles
        $this->setupBasicRoles();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /**
     * Test dashboard view contains proper responsive CSS classes
     */
    public function test_dashboard_contains_responsive_classes(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test grid responsive classes
            ->assertSee('grid-cols-1 sm:grid-cols-2 lg:grid-cols-4', false)
            ->assertSee('gap-4 sm:gap-6', false)
            // Test padding responsive classes
            ->assertSee('p-4 sm:p-6', false)
            ->assertSee('px-4 sm:px-6 lg:px-8', false)
            // Test spacing responsive classes
            ->assertSee('py-6 sm:py-8 lg:py-12', false)
            ->assertSee('space-y-4 sm:space-y-6', false)
            // Test text size responsive classes
            ->assertSee('text-base sm:text-lg', false)
            ->assertSee('text-xl sm:text-2xl sm:text-3xl', false)
            // Test layout responsive classes
            ->assertSee('flex flex-col sm:flex-row', false);
    }

    /**
     * Test dashboard contains mobile-first responsive breakpoints
     */
    public function test_dashboard_uses_mobile_first_approach(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test mobile-first grid (starts with 1 column)
            ->assertSee('grid-cols-1', false)
            // Test progressive enhancement for larger screens
            ->assertSee('sm:grid-cols-2', false)
            ->assertSee('lg:grid-cols-4', false)
            // Test mobile-first spacing
            ->assertSee('gap-3 sm:gap-4', false)
            // Test mobile-first text sizing
            ->assertSee('text-xs sm:text-sm', false);
    }

    /**
     * Test dashboard semantic HTML structure for all screen sizes
     */
    public function test_dashboard_semantic_structure(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test main semantic element
            ->assertSee('role="main"', false)
            ->assertSee('id="main-dashboard-content"', false)
            // Test section elements with proper ARIA
            ->assertSee('<section aria-labelledby="overview-heading">', false)
            ->assertSee('<nav aria-labelledby="quick-actions-heading" role="navigation">', false)
            // Test heading structure
            ->assertSee('id="overview-heading" class="sr-only"', false)
            ->assertSee('id="quick-actions-heading"', false);
    }

    /**
     * Test touch-friendly interactive elements
     */
    public function test_touch_friendly_elements(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test minimum touch target sizes (44px)
            ->assertSee('min-h-[44px]', false)
            ->assertSee('min-w-[44px]', false)
            // Test larger touch targets for complex actions
            ->assertSee('min-h-[80px]', false)
            ->assertSee('min-h-[88px]', false)
            // Test touch-friendly spacing
            ->assertSee('p-3 sm:p-4', false)
            ->assertSee('gap-3 sm:gap-4', false);
    }

    /**
     * Test responsive text content
     */
    public function test_responsive_text_content(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test responsive text visibility classes
            ->assertSee('hidden sm:inline', false)
            ->assertSee('sm:hidden', false)
            // Test responsive text for buttons
            ->assertSee(__('Refresh Metrics'), false) // Full text
            ->assertSee(__('Refresh'), false); // Shortened text
    }

    /**
     * Test responsive layout flexibility
     */
    public function test_responsive_layout_flexibility(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test flexible grid layouts
            ->assertSee('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4', false)
            ->assertSee('grid grid-cols-1 lg:grid-cols-2', false)
            ->assertSee('grid grid-cols-1 sm:grid-cols-3', false)
            // Test responsive flex layouts
            ->assertSee('flex flex-col sm:flex-row sm:justify-between sm:items-center', false)
            ->assertSee('flex flex-col sm:flex-row sm:items-center sm:justify-between', false);
    }

    /**
     * Test responsive spacing and sizing
     */
    public function test_responsive_spacing_and_sizing(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test responsive padding
            ->assertSee('p-4 sm:p-6', false)
            ->assertSee('px-4 py-3', false)
            ->assertSee('px-4 py-5 sm:p-6', false)
            // Test responsive margins
            ->assertSee('mt-1 sm:mt-2', false)
            ->assertSee('mt-4 sm:mt-0 sm:ml-4', false)
            ->assertSee('mt-6 sm:mt-8', false)
            // Test responsive gap spacing
            ->assertSee('gap-3 sm:gap-4', false)
            ->assertSee('gap-4 sm:gap-6', false)
            // Test responsive icon sizing
            ->assertSee('w-4 h-4', false)
            ->assertSee('w-6 w-6 sm:h-8 sm:w-8', false)
            ->assertSee('w-10 h-10 sm:w-12 sm:h-12', false);
    }

    /**
     * Test dashboard accessibility in responsive context
     */
    public function test_responsive_accessibility_features(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test focus management remains consistent across breakpoints
            ->assertSee('focus:outline-none focus:ring-2', false)
            ->assertSee('focus:ring-offset-2', false)
            // Test ARIA labels are preserved in responsive design
            ->assertSee('aria-labelledby=', false)
            ->assertSee('aria-describedby=', false)
            ->assertSee('aria-label=', false)
            // Test semantic structure maintained
            ->assertSee('role="main"', false)
            ->assertSee('role="navigation"', false)
            ->assertSee('role="group"', false)
            ->assertSee('role="status"', false);
    }

    /**
     * Test responsive color and theming consistency
     */
    public function test_responsive_theming_consistency(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test consistent color classes across breakpoints
            ->assertSee('text-usp-blue-pri', false)
            ->assertSee('border-usp-blue-pri', false)
            ->assertSee('bg-usp-blue-pri', false)
            ->assertSee('text-usp-yellow', false)
            ->assertSee('border-usp-yellow', false)
            // Test dark mode support
            ->assertSee('dark:bg-gray-800', false)
            ->assertSee('dark:text-gray-100', false)
            ->assertSee('dark:border-gray-600', false);
    }

    /**
     * Test responsive dashboard performance considerations
     */
    public function test_responsive_performance_features(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Test transition classes for smooth responsive behavior
            ->assertSee('transition-colors duration-200', false)
            ->assertSee('transition-shadow duration-200', false)
            // Test hover states that work well on touch devices
            ->assertSee('hover:bg-', false)
            ->assertSee('focus:bg-', false)
            // Test responsive loading states
            ->assertSee('tabular-nums', false); // Consistent number width
    }

    /**
     * Test that Livewire dashboard also has responsive improvements
     */
    public function test_livewire_dashboard_responsive_features(): void
    {
        // Test the Livewire component rendering (basic check)
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertOk()
            // Verify Livewire components exist
            ->assertSee('livewire:admin.recent-activity-feed', false);

        // Note: Full Livewire testing would require Browser tests
        // This test ensures the main dashboard still loads properly
        // and contains the responsive Livewire components
    }
}
