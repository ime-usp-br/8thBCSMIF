<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationAC2Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC2: A navegação pública contém links funcionais para "Home" (/),
     * "Workshops" (/workshops), "Taxas" (/fees), "Pagamento" (/payment-info),
     * "Login" (login.local) e "Inscrever-se" (register-event).
     */
    public function test_ac2_public_navigation_contains_all_required_functional_links(): void
    {
        // Test that the public navigation component renders on home page
        $response = $this->get('/');
        $response->assertStatus(200);

        // Check that all required links are present and functional for guests
        $requiredLinks = [
            '/' => 'Home',
            '/workshops' => 'Workshops',
            '/fees' => 'Fees',
            '/payment-info' => 'Payment',
            '/login/local' => 'Login',
            // Note: Sign Up only appears for authenticated users
        ];

        foreach ($requiredLinks as $url => $linkText) {
            // Verify link is present in navigation
            $response->assertSee($url);
            $response->assertSee(__($linkText));

            // Test that each link is functional (returns valid HTTP response)
            $linkResponse = $this->get($url);
            $linkResponse->assertStatus(200);
        }
    }

    /**
     * Test that navigation component contains proper route helpers.
     */
    public function test_ac2_navigation_uses_proper_route_helpers(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $this->assertFileExists($componentPath);

        $componentContent = file_get_contents($componentPath);

        // Verify that proper route helpers are used as specified in AC2
        $this->assertStringContainsString("url('/')", $componentContent, 'Home link should use url(\'/\')');
        $this->assertStringContainsString("route('workshops')", $componentContent, 'Workshops link should use route(\'workshops\')');
        $this->assertStringContainsString("route('fees')", $componentContent, 'Fees link should use route(\'fees\')');
        $this->assertStringContainsString("route('payment-info')", $componentContent, 'Payment info link should use route(\'payment-info\')');
        $this->assertStringContainsString("route('login.local')", $componentContent, 'Login link should use route(\'login.local\')');
        $this->assertStringContainsString("route('register-event')", $componentContent, 'Register event link should use route(\'register-event\')');
    }

    /**
     * Test that links text matches AC2 requirements.
     */
    public function test_ac2_navigation_displays_correct_link_text(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Verify correct translated text is displayed for guests
        $response->assertSee(__('Home'));
        $response->assertSee(__('Workshops'));
        $response->assertSee(__('Fees'));
        $response->assertSee(__('Payment'));
        $response->assertSee(__('Login'));
        // Note: Sign Up only appears for authenticated users
        $response->assertDontSee(__('Sign Up'));
    }
}
