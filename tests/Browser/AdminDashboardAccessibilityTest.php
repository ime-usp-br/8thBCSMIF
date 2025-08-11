<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * AdminDashboardAccessibilityTest
 *
 * Tests WCAG 2.1 AA compliance and accessibility features for the Admin Dashboard
 */
class AdminDashboardAccessibilityTest extends DuskTestCase
{
    /** @var User */
    private $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /**
     * Test skip navigation links for keyboard accessibility
     */
    public function test_skip_navigation_link_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->assertPresent('a[href="#main-dashboard-content"]')
                ->keys('body', '{tab}') // Tab to the skip link
                ->assertFocused('a[href="#main-dashboard-content"]')
                ->keys('a[href="#main-dashboard-content"]', '{enter}')
                ->pause(100)
                ->assertFocused('#main-dashboard-content');
        });
    }

    /**
     * Test semantic HTML structure for screen readers
     */
    public function test_semantic_html_structure(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->assertPresent('main[role="main"]')
                ->assertPresent('section[aria-labelledby="overview-heading"]')
                ->assertPresent('nav[role="navigation"]')
                ->assertAttribute('main', 'aria-label', __('Admin Dashboard Main Content'))
                ->assertPresent('h2#overview-heading.sr-only'); // Screen reader only heading
        });
    }

    /**
     * Test ARIA attributes and labels
     */
    public function test_aria_attributes_and_labels(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test statistics cards have proper ARIA labels
                ->assertPresent('[aria-labelledby="total-registrations-heading"]')
                ->assertPresent('[aria-labelledby="pending-approvals-heading"]')
                ->assertPresent('[aria-labelledby="revenue-heading"]')
                ->assertPresent('[aria-labelledby="transport-needs-heading"]')
                // Test live regions for dynamic content
                ->assertPresent('[aria-live="polite"]')
                ->assertPresent('[role="status"]')
                // Test group roles for related content
                ->assertPresent('[role="group"]');
        });
    }

    /**
     * Test focus indicators visibility and contrast
     */
    public function test_focus_indicators(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test focus styles on interactive elements
                ->click('a[href="'.route('admin.registrations.index').'"]')
                ->back()
                // Test focus styles include proper ring classes
                ->assertPresent('.focus\\:ring-2')
                ->assertPresent('.focus\\:ring-offset-2');
        });
    }

    /**
     * Test keyboard navigation through interactive elements
     */
    public function test_keyboard_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->keys('body', '{tab}') // Skip link
                ->keys('body', '{tab}') // First interactive element
                ->keys('body', '{tab}') // Second interactive element
                ->keys('body', '{tab}') // Third interactive element
                // Verify all quick action links are keyboard accessible
                ->assertPresent('a[tabindex="0"]');
        });
    }

    /**
     * Test color contrast and text alternatives for visual information
     */
    public function test_color_and_text_alternatives(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test trend indicators have text alternatives, not just color
                ->assertSeeIn('[role="status"]', __('Increase'))
                ->assertSeeIn('[role="status"]', __('Decrease'))
                // Test SVG icons have descriptive titles
                ->assertPresent('svg title')
                ->assertPresent('svg[aria-hidden="true"]');
        });
    }

    /**
     * Test touch target sizes meet minimum requirements (44px)
     */
    public function test_touch_target_sizes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test all interactive elements meet minimum touch target size
                ->assertPresent('.min-h-\\[44px\\]')
                ->assertPresent('.min-w-\\[44px\\]')
                ->assertPresent('.min-h-\\[80px\\]') // Larger touch targets for quick actions
                ->assertPresent('.min-h-\\[88px\\]');
        });
    }

    /**
     * Test screen reader announcements for dynamic content
     */
    public function test_screen_reader_announcements(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test aria-live regions exist for dynamic updates
                ->assertPresent('[aria-live="polite"]')
                ->assertPresent('[aria-atomic="true"]')
                // Test descriptive labels for data
                ->assertAttribute('[aria-label*="Payment proofs pending approval"]', 'aria-label')
                ->assertAttribute('[aria-label*="Enrollment proofs pending approval"]', 'aria-label')
                ->assertAttribute('[aria-label*="Total revenue"]', 'aria-label');
        });
    }

    /**
     * Test heading hierarchy for proper document structure
     */
    public function test_heading_hierarchy(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test proper heading levels (h1 -> h2 -> h3)
                ->assertPresent('h2') // Main page heading in app layout
                ->assertPresent('h2.sr-only') // Screen reader headings
                ->assertPresent('h3') // Section headings
                // Verify no heading level skipping
                ->assertDontSee('h4') // Should not have h4 without h3 context
                ->assertDontSee('h5'); // Should not have h5 without h4 context
        });
    }

    /**
     * Test form controls and interactive elements accessibility
     */
    public function test_form_controls_accessibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                // Test buttons have proper type attribute
                ->assertAttribute('button[type="button"]', 'type', 'button')
                // Test buttons have descriptive aria-labels
                ->assertAttribute('button[aria-label]', 'aria-label')
                // Test links have descriptive aria-describedby where needed
                ->assertPresent('a[aria-describedby]');
        });
    }

    /**
     * Test responsive accessibility across different viewport sizes
     */
    public function test_responsive_accessibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard');

            // Test tablet viewport
            $browser->resize(768, 1024)
                ->assertVisible('main')
                ->assertPresent('section')
                ->assertPresent('nav');

            // Test desktop viewport
            $browser->resize(1024, 768)
                ->assertVisible('main')
                ->assertPresent('section')
                ->assertPresent('nav')
                // Test responsive text changes
                ->assertSeeIn('button', __('Refresh Metrics')); // Full text on desktop

            // Test mobile viewport
            $browser->resize(375, 667)
                ->assertVisible('main')
                ->assertPresent('section')
                ->assertPresent('nav')
                // Test responsive text changes
                ->assertSeeIn('button', __('Refresh')); // Shortened text on mobile
        });
    }
}
