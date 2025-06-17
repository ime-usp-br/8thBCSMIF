<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationIntegrationAC3Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the public navigation component is integrated in the welcome page.
     * This test specifically addresses AC3 requirement for welcome page.
     */
    public function test_public_navigation_integrated_in_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Verify the page contains navigation element
        $response->assertSee('nav', false);

        // Verify specific navigation links are present
        $response->assertSee('Home');
        $response->assertSee('Workshops');
        $response->assertSee('Fees');
        $response->assertSee('Payment');

        // Verify guest authentication links
        $response->assertSee('Login');
        // Note: Sign Up only appears for authenticated users
        $response->assertDontSee('Sign Up');
    }

    /**
     * Test that the public navigation component is integrated in the workshops page.
     * This test specifically addresses AC3 requirement for workshops page.
     */
    public function test_public_navigation_integrated_in_workshops_page(): void
    {
        $response = $this->get('/workshops');

        $response->assertStatus(200);

        // Verify the page contains navigation element
        $response->assertSee('nav', false);

        // Verify specific navigation links are present
        $response->assertSee('Home');
        $response->assertSee('Workshops');
        $response->assertSee('Fees');
        $response->assertSee('Payment');

        // Verify guest authentication links
        $response->assertSee('Login');
        // Note: Sign Up only appears for authenticated users
        $response->assertDontSee('Sign Up');
    }

    /**
     * Test that the public navigation component is integrated in the fees page.
     * This test specifically addresses AC3 requirement for fees page.
     */
    public function test_public_navigation_integrated_in_fees_page(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);

        // Verify the page contains navigation element
        $response->assertSee('nav', false);

        // Verify specific navigation links are present
        $response->assertSee('Home');
        $response->assertSee('Workshops');
        $response->assertSee('Fees');
        $response->assertSee('Payment');

        // Verify guest authentication links
        $response->assertSee('Login');
        // Note: Sign Up only appears for authenticated users
        $response->assertDontSee('Sign Up');
    }

    /**
     * Test that the public navigation component is integrated in the payment-info page.
     * This test specifically addresses AC3 requirement for payment-info page.
     */
    public function test_public_navigation_integrated_in_payment_info_page(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);

        // Verify the page contains navigation element
        $response->assertSee('nav', false);

        // Verify specific navigation links are present
        $response->assertSee('Home');
        $response->assertSee('Workshops');
        $response->assertSee('Fees');
        $response->assertSee('Payment');

        // Verify guest authentication links
        $response->assertSee('Login');
        // Note: Sign Up only appears for authenticated users
        $response->assertDontSee('Sign Up');
    }

    /**
     * Test that all public pages consistently render the same navigation component.
     * This test specifically addresses AC3 requirement for consistent integration.
     */
    public function test_all_public_pages_have_consistent_navigation(): void
    {
        $pages = [
            '/',
            '/workshops',
            '/fees',
            '/payment-info',
        ];

        foreach ($pages as $page) {
            $response = $this->get($page);

            $response->assertStatus(200);

            // Verify each page has the navigation component
            $response->assertSee('nav', false);

            // Verify the IME-USP logo is present (specific to public navigation)
            $response->assertSee('ime-logo-light', false);
            $response->assertSee('ime-logo-dark', false);

            // Verify navigation links structure is consistent
            $response->assertSee('Navigation Links', false);
            $response->assertSee('Authentication Links', false);
            $response->assertSee('Responsive Navigation Menu', false);
        }
    }

    /**
     * Test that the public navigation component is properly integrated via Blade component syntax.
     * This test specifically addresses AC3 implementation requirement.
     */
    public function test_public_navigation_integration_implementation(): void
    {
        $publicPages = [
            'welcome.blade.php',
            'workshops.blade.php',
            'fees.blade.php',
            'payment-info.blade.php',
        ];

        foreach ($publicPages as $page) {
            $filePath = resource_path("views/{$page}");
            $this->assertFileExists($filePath, "Public page {$page} should exist");

            $content = file_get_contents($filePath);

            // Verify the page includes the public navigation component
            $this->assertStringContainsString('<x-layout.public-navigation />', $content,
                "Page {$page} should include the public navigation component");
        }
    }

    /**
     * Test that navigation appears in the correct position on all public pages.
     * This test specifically addresses AC3 rendering requirement.
     */
    public function test_navigation_position_consistency_across_pages(): void
    {
        $publicPages = [
            'welcome.blade.php',
            'workshops.blade.php',
            'fees.blade.php',
            'payment-info.blade.php',
        ];

        foreach ($publicPages as $page) {
            $filePath = resource_path("views/{$page}");
            $content = file_get_contents($filePath);

            // Find the position of USP header and public navigation
            $uspHeaderPos = strpos($content, '<x-usp.header />');
            $publicNavPos = strpos($content, '<x-layout.public-navigation />');

            $this->assertNotFalse($uspHeaderPos, "Page {$page} should have USP header");
            $this->assertNotFalse($publicNavPos, "Page {$page} should have public navigation");

            // Verify navigation comes after USP header
            $this->assertGreaterThan($uspHeaderPos, $publicNavPos,
                "Public navigation should come after USP header in {$page}");
        }
    }
}
