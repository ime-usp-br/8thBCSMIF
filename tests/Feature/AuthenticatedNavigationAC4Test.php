<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedNavigationAC4Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the authenticated navigation component is modified successfully.
     * This test specifically addresses AC4 requirement.
     */
    public function test_authenticated_navigation_component_is_modified(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $this->assertFileExists($navigationPath, 'Authenticated navigation component file should exist');

        $navigationContent = file_get_contents($navigationPath);

        // Verify the component contains the required navigation links
        $expectedLinks = [
            'My Registrations',
            'Workshops',
            'Fees',
        ];

        foreach ($expectedLinks as $linkText) {
            $this->assertStringContainsString("{{ __('{$linkText}') }}", $navigationContent,
                "Navigation should contain link for {$linkText}");
        }
    }

    /**
     * Test that the authenticated navigation contains functional links to required routes.
     * This test verifies AC4 functional requirements.
     */
    public function test_authenticated_navigation_contains_functional_links(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $navigationContent = file_get_contents($navigationPath);

        // Verify route helpers for required links
        $expectedRoutes = [
            'route(\'registrations.my\')',
            'route(\'workshops\')',
            'route(\'fees\')',
        ];

        foreach ($expectedRoutes as $routeHelper) {
            $this->assertStringContainsString($routeHelper, $navigationContent,
                "Navigation should use {$routeHelper} for proper routing");
        }
    }

    /**
     * Test that authenticated navigation renders correctly for authenticated users.
     * This test verifies AC4 rendering requirements.
     */
    public function test_authenticated_navigation_renders_for_authenticated_users(): void
    {
        // Create a test user
        $user = User::factory()->create();

        // Act as the authenticated user
        $response = $this->actingAs($user)->get('/my-registration');

        // Verify the page loads successfully
        $response->assertOk();

        // Verify navigation links are present in the response
        $response->assertSee(__('My Registrations'));
        $response->assertSee(__('Workshops'));
        $response->assertSee(__('Fees'));
    }

    /**
     * Test that authenticated navigation uses proper active state detection.
     * This test verifies AC4 active link highlighting requirements.
     */
    public function test_authenticated_navigation_uses_proper_active_state(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $navigationContent = file_get_contents($navigationPath);

        // Verify active state detection for all required routes
        $expectedActiveStates = [
            'request()->routeIs(\'registrations.my\')',
            'request()->routeIs(\'workshops\')',
            'request()->routeIs(\'fees\')',
        ];

        foreach ($expectedActiveStates as $activeState) {
            $this->assertStringContainsString($activeState, $navigationContent,
                "Navigation should use {$activeState} for active link detection");
        }
    }

    /**
     * Test that authenticated navigation includes responsive navigation links.
     * This test verifies AC4 responsive menu requirements.
     */
    public function test_authenticated_navigation_includes_responsive_links(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $navigationContent = file_get_contents($navigationPath);

        // Verify responsive navigation section exists
        $this->assertStringContainsString('Responsive Navigation Menu', $navigationContent);

        // Count occurrences of each required link (should appear in both desktop and responsive)
        $expectedLinks = [
            'My Registrations',
            'Workshops',
            'Fees',
        ];

        foreach ($expectedLinks as $linkText) {
            $occurrences = substr_count($navigationContent, "{{ __('{$linkText}') }}");
            $this->assertGreaterThanOrEqual(2, $occurrences,
                "Link '{$linkText}' should appear at least twice (desktop and responsive)");
        }
    }

    /**
     * Test that authenticated navigation uses proper x-nav-link and x-responsive-nav-link components.
     * This test verifies AC4 component consistency requirements.
     */
    public function test_authenticated_navigation_uses_proper_nav_components(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $navigationContent = file_get_contents($navigationPath);

        // Verify usage of nav-link components
        $this->assertStringContainsString('<x-nav-link', $navigationContent);
        $this->assertStringContainsString('<x-responsive-nav-link', $navigationContent);

        // Count nav-link components (should have 3 for desktop navigation)
        $desktopNavLinks = substr_count($navigationContent, '<x-nav-link');
        $this->assertGreaterThanOrEqual(3, $desktopNavLinks,
            'Should have at least 3 desktop navigation links');

        // Count responsive nav-link components (should have 3 for responsive navigation)
        $responsiveNavLinks = substr_count($navigationContent, '<x-responsive-nav-link');
        $this->assertGreaterThanOrEqual(3, $responsiveNavLinks,
            'Should have at least 3 responsive navigation links');
    }

    /**
     * Test that authenticated navigation follows wire:navigate pattern.
     * This test verifies AC4 Livewire navigation requirements.
     */
    public function test_authenticated_navigation_uses_wire_navigate(): void
    {
        $navigationPath = resource_path('views/livewire/layout/navigation.blade.php');
        $navigationContent = file_get_contents($navigationPath);

        // Verify wire:navigate is used for navigation links
        $wireNavigateCount = substr_count($navigationContent, 'wire:navigate');
        $this->assertGreaterThanOrEqual(8, $wireNavigateCount,
            'Should use wire:navigate for navigation links (4 desktop + 4 responsive)');
    }

    /**
     * Test that authenticated users can access all navigation routes.
     * This test verifies AC4 route accessibility requirements.
     */
    public function test_authenticated_users_can_access_navigation_routes(): void
    {
        // Create a test user
        $user = User::factory()->create();
        // Create a registration for the user to bypass the registration middleware
        \App\Models\Registration::factory()->create(['user_id' => $user->id]);

        // Test access to all required routes
        $routes = [
            '/my-registration' => 'registrations.my',
            '/workshops' => 'workshops',
            '/fees' => 'fees',
        ];

        foreach ($routes as $url => $routeName) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
        }
    }
}
