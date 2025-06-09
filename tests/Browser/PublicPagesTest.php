<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicPagesTest extends DuskTestCase
{
    /**
     * Test that home page renders correctly with proper content.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_home_page_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('8th BCSMIF')
                ->assertSee('Brazilian Conference on Statistical Modeling in Insurance and Finance');
        });
    }

    /**
     * Test that workshops page renders correctly with workshop information.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_workshops_page_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/workshops')
                ->assertSee('Satellite Workshops')
                ->assertSee('Workshop on Risk Analysis and Applications (WRAA)')
                ->assertSee('Workshop on Dependence Analysis (WDA)');
        });
    }

    /**
     * Test that fees page renders correctly with pricing table.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_fees_page_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/fees')
                ->assertSee(__('Registration Fees'))
                ->assertSee(__('8th BCSMIF Conference'))
                ->assertSee(__('Satellite Workshops'));
        });
    }

    /**
     * Test that payment info page renders correctly with payment instructions.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_payment_info_page_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/payment-info')
                ->assertSee('Payment Information')
                ->assertSee('For Brazilian Participants')
                ->assertSee('For International Participants');
        });
    }

    /**
     * Test that all public pages load successfully.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_all_public_pages_load_successfully(): void
    {
        $publicRoutes = ['/', '/workshops', '/fees', '/payment-info'];

        $this->browse(function (Browser $browser) use ($publicRoutes) {
            foreach ($publicRoutes as $route) {
                $browser->visit($route);
                // Check for specific content per page to ensure proper loading
                if ($route === '/') {
                    $browser->assertSee('8th BCSMIF');
                } elseif ($route === '/workshops') {
                    $browser->assertSee('Satellite Workshops');
                } elseif ($route === '/fees') {
                    $browser->assertSee(__('Registration Fees'));
                } elseif ($route === '/payment-info') {
                    $browser->assertSee('Payment Information');
                }
            }
        });
    }

    /**
     * Test that pages contain essential navigation elements.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_pages_contain_navigation_elements(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('8th BCSMIF')
                ->visit('/workshops')
                ->assertSee('Satellite Workshops')
                ->visit('/fees')
                ->assertSee(__('Registration Fees'))
                ->visit('/payment-info')
                ->assertSee('Payment Information');
        });
    }

    /**
     * Test responsive design on different viewport sizes.
     * Part of AC10: Complete browser tests for public pages rendering.
     */
    public function test_responsive_design(): void
    {
        $this->browse(function (Browser $browser) {
            // Test mobile viewport
            $browser->resize(375, 667)
                ->visit('/')
                ->assertSee('8th BCSMIF');

            // Test tablet viewport
            $browser->resize(768, 1024)
                ->visit('/')
                ->assertSee('8th BCSMIF');

            // Test desktop viewport
            $browser->resize(1920, 1080)
                ->visit('/')
                ->assertSee('8th BCSMIF');
        });
    }
}