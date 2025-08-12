<?php

namespace Tests\Browser;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

/**
 * Comprehensive Browser Tests for Admin Dashboard Responsive Design
 *
 * Tests responsive behavior across multiple device sizes, orientations,
 * and breakpoints to ensure optimal user experience on all devices.
 */
class AdminDashboardResponsiveDesignTest extends DuskTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'usp_user', 'guard_name' => 'web']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create test data for consistent testing
        Registration::factory()->count(25)->create();
        Payment::factory()->count(10)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
    }

    public function test_desktop_large_screen_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(1920, 1080) // Large desktop
                ->pause(1000) // Allow for responsive adjustments
                ->assertSee('Admin Dashboard')

                // Should use full grid layout
                ->assertPresent('.container')
                ->assertPresent('.lg\\:grid-cols-3') // 3-column layout on large screens
                ->assertPresent('.xl\\:grid-cols-4') // 4-column layout on extra large screens

                // Widgets should be properly spaced
                ->within('.grid', function ($browser) {
                    $browser->assertPresent('.gap-6') // Should have proper gap spacing
                        ->assertPresent('.p-6'); // Should have proper padding
                })

                // All widgets should be visible without scrolling
                ->assertVisible('[data-widget="total-registrations"]')
                ->assertVisible('[data-widget="pending-approvals"]')
                ->assertVisible('[data-widget="revenue"]')
                ->assertVisible('[data-widget="transport-needs"]')
                ->assertVisible('[data-widget="registrations-by-category"]')
                ->assertVisible('[data-widget="recent-activity"]')

                // Navigation should be horizontal
                ->assertPresent('.flex-row')
                ->assertMissing('.flex-col'); // Should not use vertical navigation
        });
    }

    public function test_desktop_standard_screen_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(1366, 768) // Standard desktop
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should use 3-column layout
                ->assertPresent('.lg\\:grid-cols-3')
                ->assertMissing('.xl\\:grid-cols-4') // XL grid should not apply

                // Widgets should still be properly arranged
                ->assertVisible('[data-widget="total-registrations"]')
                ->assertVisible('[data-widget="pending-approvals"]')
                ->assertVisible('[data-widget="revenue"]')

                // Content should fit without horizontal scrolling
                ->script('return document.body.scrollWidth <= window.innerWidth;');

            // Verify no horizontal scroll
            $hasHorizontalScroll = $browser->driver->executeScript('return document.body.scrollWidth > window.innerWidth;');
            $this->assertFalse($hasHorizontalScroll, 'Dashboard should not require horizontal scrolling on standard desktop');
        });
    }

    public function test_laptop_screen_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(1280, 720) // Laptop screen
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should use medium breakpoint
                ->assertPresent('.md\\:grid-cols-2')
                ->assertPresent('.lg\\:grid-cols-3')

                // All critical widgets should be visible
                ->assertVisible('[data-widget="total-registrations"]')
                ->assertVisible('[data-widget="pending-approvals"]')
                ->assertVisible('[data-widget="revenue"]')

                // Quick actions should be accessible
                ->assertVisible('[data-section="quick-actions"]')
                ->assertSee('View Registrations')
                ->assertSee('Generate Reports')

                // Typography should be appropriate
                ->assertPresent('.text-lg') // Large text for headings
                ->assertPresent('.text-sm'); // Small text for details
        });
    }

    public function test_tablet_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(1024, 768) // Tablet landscape
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should use tablet-appropriate layout
                ->assertPresent('.md\\:grid-cols-2')
                ->assertMissing('.lg\\:grid-cols-3') // Large grid should not apply

                // Widgets should be touch-friendly
                ->within('[data-widget="total-registrations"]', function ($browser) {
                    $size = $browser->element('[data-widget="total-registrations"]')->getSize();
                    $this->assertGreaterThan(44, $size->getHeight(), 'Widget should be touch-friendly (min 44px height)');
                })

                // Quick actions should have adequate spacing for touch
                ->within('[data-section="quick-actions"]', function ($browser) {
                    $browser->assertPresent('.space-x-4'); // Should have spacing between buttons
                })

                // Navigation should work well with touch
                ->assertPresent('[data-touch-target]')
                ->assertMissing('.hover\\:opacity-75'); // Hover effects less relevant on tablet
        });
    }

    public function test_tablet_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(768, 1024) // Tablet portrait
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should stack widgets vertically
                ->assertPresent('.grid-cols-1')
                ->assertPresent('.md\\:grid-cols-2') // Should use 2-column for wider widgets

                // All widgets should be accessible via scrolling
                ->assertVisible('[data-widget="total-registrations"]')

                // Scroll down to check other widgets
                ->scrollIntoView('[data-widget="recent-activity"]')
                ->assertVisible('[data-widget="recent-activity"]')

                // Quick actions should be stacked or wrapped appropriately
                ->within('[data-section="quick-actions"]', function ($browser) {
                    $browser->assertPresent('.flex-wrap'); // Should wrap on smaller screens
                })

                // Charts should be readable at this size
                ->within('[data-widget="registrations-by-category"]', function ($browser) {
                    if ($browser->element('.chart-container')) {
                        $browser->assertVisible('.chart-container');
                    }
                });
        });
    }

    public function test_mobile_large_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(414, 896) // iPhone XR / Large mobile
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should use single column layout
                ->assertPresent('.grid-cols-1')
                ->assertMissing('.md\\:grid-cols-2') // Medium breakpoint should not apply

                // Navigation should be mobile-optimized
                ->assertPresent('.mobile-nav')
                ->assertPresent('[data-mobile-menu-toggle]')

                // Widgets should stack vertically with appropriate spacing
                ->within('.grid', function ($browser) {
                    $browser->assertPresent('.space-y-4'); // Should have vertical spacing
                })

                // Text should be sized for mobile readability
                ->assertPresent('.text-base') // Base text size for mobile
                ->assertMissing('.text-xs') // Should not use extra small text

                // All widgets should be accessible via vertical scrolling
                ->assertVisible('[data-widget="total-registrations"]')
                ->scrollIntoView('[data-widget="revenue"]')
                ->assertVisible('[data-widget="revenue"]')
                ->scrollIntoView('[data-widget="recent-activity"]')
                ->assertVisible('[data-widget="recent-activity"]')

                // Quick actions should be mobile-optimized
                ->within('[data-section="quick-actions"]', function ($browser) {
                    $browser->assertPresent('.w-full'); // Buttons should be full-width on mobile
                });
        });
    }

    public function test_mobile_standard_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(375, 667) // iPhone 6/7/8
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should use mobile-first responsive design
                ->assertPresent('.grid-cols-1')
                ->assertPresent('.px-4') // Should have mobile-appropriate padding

                // Typography should be mobile-optimized
                ->assertPresent('.text-sm') // Smaller text for mobile
                ->assertPresent('.leading-tight') // Tighter line height for mobile

                // Widgets should be touch-optimized
                ->within('[data-widget="total-registrations"]', function ($browser) {
                    $browser->assertPresent('.p-4'); // Adequate padding for touch
                })

                // Numbers and metrics should be clearly visible
                ->within('[data-widget="total-registrations"]', function ($browser) {
                    $browser->assertPresent('.text-2xl'); // Large numbers for readability
                })

                // Charts should be simplified or replaced with lists on mobile
                ->within('[data-widget="registrations-by-category"]', function ($browser) {
                    // Should either show simplified chart or list view
                    $hasChart = $browser->element('.chart-container') !== null;
                    $hasList = $browser->element('.category-list') !== null;
                    $this->assertTrue($hasChart || $hasList, 'Should show either chart or list view on mobile');
                })

                // No horizontal scrolling should be required
                ->script('return document.body.scrollWidth <= window.innerWidth;');

            $hasHorizontalScroll = $browser->driver->executeScript('return document.body.scrollWidth > window.innerWidth;');
            $this->assertFalse($hasHorizontalScroll, 'Mobile layout should not require horizontal scrolling');
        });
    }

    public function test_mobile_small_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(320, 568) // iPhone SE / Small mobile
                ->pause(1000)
                ->assertSee('Admin Dashboard')

                // Should handle very small screens gracefully
                ->assertPresent('.grid-cols-1')
                ->assertPresent('.px-2') // Minimal padding for small screens

                // Content should be readable despite small size
                ->assertVisible('[data-widget="total-registrations"]')
                ->within('[data-widget="total-registrations"]', function ($browser) {
                    $browser->assertPresent('.text-xl'); // Should still have readable numbers
                })

                // Navigation should be collapsed or simplified
                ->assertPresent('[data-mobile-menu-toggle]')
                ->click('[data-mobile-menu-toggle]')
                ->pause(500)
                ->assertVisible('[data-mobile-menu]')

                // Should maintain usability at this size
                ->within('[data-section="quick-actions"]', function ($browser) {
                    $browser->assertPresent('.flex-col'); // Should stack buttons vertically
                })

                // Text should not be too small to read
                ->script('
                    const elements = document.querySelectorAll("*");
                    let minFontSize = Infinity;
                    elements.forEach(el => {
                        const fontSize = parseFloat(window.getComputedStyle(el).fontSize);
                        if (fontSize > 0) minFontSize = Math.min(minFontSize, fontSize);
                    });
                    return minFontSize;
                ');

            $minFontSize = $browser->driver->executeScript('
                const elements = document.querySelectorAll("*");
                let minFontSize = Infinity;
                elements.forEach(el => {
                    const fontSize = parseFloat(window.getComputedStyle(el).fontSize);
                    if (fontSize > 0 && fontSize < minFontSize) minFontSize = fontSize;
                });
                return minFontSize >= 14 ? true : minFontSize;
            ');

            $this->assertTrue($minFontSize === true || $minFontSize >= 14, 'Minimum font size should be at least 14px for accessibility');
        });
    }

    public function test_orientation_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))

                // Start in portrait
                ->resize(768, 1024) // Tablet portrait
                ->pause(1000)
                ->assertSee('Admin Dashboard')
                ->assertPresent('.grid-cols-1') // Should use single column in portrait

                // Switch to landscape
                ->resize(1024, 768) // Tablet landscape
                ->pause(1000)
                ->assertPresent('.md\\:grid-cols-2') // Should use two columns in landscape

                // Verify content reflows properly
                ->assertVisible('[data-widget="total-registrations"]')
                ->assertVisible('[data-widget="pending-approvals"]')

                // Switch back to portrait
                ->resize(768, 1024)
                ->pause(1000)
                ->assertPresent('.grid-cols-1'); // Should revert to single column
        });
    }

    public function test_content_readability_across_breakpoints(): void
    {
        $breakpoints = [
            ['width' => 1920, 'height' => 1080, 'name' => 'Large Desktop'],
            ['width' => 1366, 'height' => 768, 'name' => 'Desktop'],
            ['width' => 1024, 'height' => 768, 'name' => 'Tablet Landscape'],
            ['width' => 768, 'height' => 1024, 'name' => 'Tablet Portrait'],
            ['width' => 414, 'height' => 896, 'name' => 'Mobile Large'],
            ['width' => 375, 'height' => 667, 'name' => 'Mobile Standard'],
            ['width' => 320, 'height' => 568, 'name' => 'Mobile Small'],
        ];

        $this->browse(function (Browser $browser) {
            foreach ($breakpoints as $breakpoint) {
                $browser->loginAs($this->admin)
                    ->visit(route('admin.dashboard'))
                    ->resize($breakpoint['width'], $breakpoint['height'])
                    ->pause(1000);

                // All critical content should be readable
                $browser->assertSee('Admin Dashboard')
                    ->assertSee('Total Registrations')
                    ->assertSee('Pending Approvals')
                    ->assertSee('Revenue')
                    ->assertSee('Quick Actions');

                // Numbers should be clearly visible
                $browser->within('[data-widget="total-registrations"]', function ($browser) {
                    $browser->assertPresent('[data-metric-value]');
                });

                // No content should be cut off horizontally
                $hasHorizontalScroll = $browser->driver->executeScript('return document.body.scrollWidth > window.innerWidth;');
                $this->assertFalse($hasHorizontalScroll, "Horizontal scrolling detected on {$breakpoint['name']} ({$breakpoint['width']}x{$breakpoint['height']})");
            }
        });
    }

    public function test_touch_target_sizes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(375, 667) // Mobile device
                ->pause(1000);

            // Check that all interactive elements meet touch target size requirements (44px minimum)
            $touchElements = [
                '[data-action="refresh-metrics"]',
                '[data-section="quick-actions"] a',
                '[data-mobile-menu-toggle]',
            ];

            foreach ($touchElements as $selector) {
                if ($browser->element($selector)) {
                    $size = $browser->element($selector)->getSize();
                    $this->assertGreaterThanOrEqual(44, $size->getHeight(), "Touch target {$selector} should be at least 44px tall");
                    $this->assertGreaterThanOrEqual(44, $size->getWidth(), "Touch target {$selector} should be at least 44px wide");
                }
            }
        });
    }

    public function test_image_and_icon_responsive_scaling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))

                // Test on large screen
                ->resize(1920, 1080)
                ->pause(1000);

            // Check that icons scale appropriately
            if ($browser->element('.widget-icon')) {
                $largeSize = $browser->element('.widget-icon')->getSize();

                // Switch to mobile
                $browser->resize(375, 667)
                    ->pause(1000);

                $mobileSize = $browser->element('.widget-icon')->getSize();

                // Icons should scale down on mobile but remain visible
                $this->assertLessThanOrEqual($largeSize->getWidth(), $mobileSize->getWidth());
                $this->assertGreaterThan(16, $mobileSize->getWidth(), 'Icons should remain visible on mobile');
            }
        });
    }

    public function test_navigation_responsiveness(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))

                // Desktop navigation
                ->resize(1366, 768)
                ->pause(1000)
                ->assertVisible('[data-desktop-nav]')
                ->assertMissing('[data-mobile-menu-toggle]')

                // Mobile navigation
                ->resize(375, 667)
                ->pause(1000)
                ->assertMissing('[data-desktop-nav]')
                ->assertVisible('[data-mobile-menu-toggle]')

                // Test mobile menu functionality
                ->click('[data-mobile-menu-toggle]')
                ->pause(500)
                ->assertVisible('[data-mobile-menu]')
                ->assertSee('Dashboard')
                ->assertSee('Registrations')
                ->assertSee('Reports')

                // Close mobile menu
                ->click('[data-mobile-menu-toggle]')
                ->pause(500)
                ->assertMissing('[data-mobile-menu]');
        });
    }
}
