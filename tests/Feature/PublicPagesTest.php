<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /**
     * Test that all public pages return successful response (status 200).
     * This test specifically addresses AC9 requirements.
     */
    public function test_all_public_pages_return_status_200(): void
    {
        $publicPages = [
            '/' => 'Home Page',
            '/workshops' => 'Workshops Page',
            '/fees' => 'Fees Page',
            '/payment-info' => 'Payment Info Page',
        ];

        foreach ($publicPages as $route => $pageName) {
            $response = $this->get($route);
            $response->assertStatus(200, "Failed to load {$pageName} at route '{$route}'");
        }
    }

    /**
     * Test that all public pages contain expected key text content.
     * This test specifically addresses AC9 requirements.
     */
    public function test_all_public_pages_contain_key_text(): void
    {
        $pageContentMapping = [
            '/' => [
                '8th BCSMIF',
                'Brazilian Conference on Statistical Modeling in Insurance and Finance',
                'September 28 to October 3, 2025',
            ],
            '/workshops' => [
                'Satellite Workshops',
                'Workshop on Risk Analysis and Applications (WRAA)',
                'Workshop on Dependence Analysis (WDA)',
            ],
            '/fees' => [
                __('Registration Fees'),
                __('8th BCSMIF Conference'),
                __('Satellite Workshops'),
            ],
            '/payment-info' => [
                'Payment Information',
                'For Brazilian Participants',
                'For International Participants',
            ],
        ];

        foreach ($pageContentMapping as $route => $expectedTexts) {
            $response = $this->get($route);
            $response->assertStatus(200);

            foreach ($expectedTexts as $text) {
                $response->assertSee($text, "Route '{$route}' should contain text: {$text}");
            }
        }
    }

    /**
     * Test that all public pages have proper navigation and layout structure.
     * Additional verification for AC9 completeness.
     */
    public function test_all_public_pages_have_proper_layout(): void
    {
        $publicRoutes = ['/', '/workshops', '/fees', '/payment-info'];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);

            // Verify basic layout elements exist
            $response->assertSee('<html', false);
            $response->assertSee('</html>', false);
            $response->assertSee('<title>', false);
            $response->assertSee('</title>', false);
        }
    }
}
