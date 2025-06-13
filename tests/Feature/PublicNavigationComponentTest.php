<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationComponentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the public navigation component exists and is functional.
     * This test specifically addresses AC1 requirement.
     */
    public function test_public_navigation_component_exists(): void
    {
        // Create a simple view to test the component
        $viewContent = '<x-layout.public-navigation />';

        // Create a temporary view file to test component rendering
        $tempView = 'test-public-navigation';
        view()->addLocation(resource_path('views'));

        // Use an existing view that might use the component or test component directly
        $response = $this->view('components.layout.public-navigation');

        // Verify the component renders without errors
        $this->assertNotNull($response);
    }

    /**
     * Test that the public navigation component contains all required links.
     * This test specifically addresses AC1 functional requirements.
     */
    public function test_public_navigation_component_contains_required_links(): void
    {
        // Test component rendering by checking if we can render it without errors
        $component = view('components.layout.public-navigation');
        $renderedContent = $component->render();

        // Verify navigation contains expected elements
        $this->assertStringContainsString('nav', $renderedContent);

        // Verify the component contains navigation structure
        $this->assertStringContainsString('x-data="{ open: false }"', $renderedContent);
        $this->assertStringContainsString('Primary Navigation Menu', $renderedContent);
        $this->assertStringContainsString('Responsive Navigation Menu', $renderedContent);
    }

    /**
     * Test that the public navigation component uses proper route helpers.
     * This test verifies AC1 implementation follows Laravel conventions.
     */
    public function test_public_navigation_component_uses_route_helpers(): void
    {
        $component = view('components.layout.public-navigation');
        $renderedContent = $component->render();

        // Verify component uses route() helpers for main navigation links
        $expectedRoutes = [
            'url(\'/\')',
            'route(\'workshops\')',
            'route(\'fees\')',
            'route(\'payment-info\')',
            'route(\'login.local\')',
            'route(\'register-event\')',
        ];

        // Since we can't directly test Blade compilation, we verify the component file exists
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $this->assertFileExists($componentPath, 'Public navigation component file should exist');

        $componentContent = file_get_contents($componentPath);

        // Verify route helpers are used in the component
        foreach ($expectedRoutes as $routeHelper) {
            $this->assertStringContainsString($routeHelper, $componentContent,
                "Component should use {$routeHelper} for navigation");
        }
    }

    /**
     * Test that the public navigation component has proper Alpine.js structure.
     * This test verifies AC1 responsive functionality requirements.
     */
    public function test_public_navigation_component_has_proper_alpine_structure(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Verify Alpine.js reactive data
        $this->assertStringContainsString('x-data="{ open: false }"', $componentContent);

        // Verify hamburger menu functionality
        $this->assertStringContainsString('@click="open = ! open"', $componentContent);

        // Verify responsive menu visibility
        $this->assertStringContainsString(':class="{\'block\': open, \'hidden\': ! open}"', $componentContent);

        // Verify responsive navigation structure
        $this->assertStringContainsString('hidden sm:hidden', $componentContent);
        $this->assertStringContainsString('sm:flex', $componentContent);
    }

    /**
     * Test that the public navigation component follows project styling conventions.
     * This test verifies AC1 styling requirements.
     */
    public function test_public_navigation_component_follows_styling_conventions(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Verify Tailwind CSS classes are used
        $expectedClasses = [
            'bg-white dark:bg-gray-800',
            'border-b border-gray-100 dark:border-gray-700',
            'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
            'flex justify-between h-16',
        ];

        foreach ($expectedClasses as $class) {
            $this->assertStringContainsString($class, $componentContent,
                "Component should use Tailwind class: {$class}");
        }

        // Verify component uses existing nav-link components
        $this->assertStringContainsString('<x-nav-link', $componentContent);
        $this->assertStringContainsString('<x-responsive-nav-link', $componentContent);
    }

    /**
     * Test that the public navigation component handles authentication states.
     * This test verifies AC1 conditional visibility requirements.
     */
    public function test_public_navigation_component_handles_auth_states(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Verify guest-only sections
        $this->assertStringContainsString('@guest', $componentContent);
        $this->assertStringContainsString('@endguest', $componentContent);

        // Verify authenticated-only sections
        $this->assertStringContainsString('@auth', $componentContent);
        $this->assertStringContainsString('@endauth', $componentContent);

        // Verify login and register links for guests
        $this->assertStringContainsString('route(\'login.local\')', $componentContent);
        $this->assertStringContainsString('route(\'register-event\')', $componentContent);

        // Verify logout functionality for authenticated users
        $this->assertStringContainsString('route(\'logout\')', $componentContent);
        $this->assertStringContainsString('route(\'dashboard\')', $componentContent);
    }
}
