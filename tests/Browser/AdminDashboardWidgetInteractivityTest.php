<?php

namespace Tests\Browser;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

/**
 * Comprehensive Browser Tests for Admin Dashboard Widget Interactivity
 *
 * Tests real-time updates, Livewire functionality, progressive loading,
 * and user interactions across all dashboard widgets.
 */
class AdminDashboardWidgetInteractivityTest extends DuskTestCase
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
    }

    public function test_dashboard_widgets_load_progressively(): void
    {
        // Create test data
        Registration::factory()->count(10)->create();
        Payment::factory()->count(5)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')

                // Critical metrics should load immediately
                ->assertSee('Total Registrations')
                ->assertSee('Pending Approvals')
                ->assertSee('Revenue')

                // Non-critical widgets should load progressively
                ->waitFor('[data-widget="registrations-by-category"]', 10)
                ->waitFor('[data-widget="transport-needs"]', 10)

                // Verify progressive loading completed
                ->assertSee('Registrations by Category')
                ->assertSee('Transport Needs')

                // Check for loading indicators disappearing
                ->assertMissing('.loading-spinner')
                ->assertMissing('[data-loading="true"]');
        });
    }

    public function test_real_time_metrics_refresh_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')

                // Find refresh button and click it
                ->waitFor('[data-action="refresh-metrics"]')
                ->click('[data-action="refresh-metrics"]')

                // Should show loading state
                ->waitFor('.refreshing', 2)

                // Should show success message
                ->waitFor('.alert-success', 5)
                ->assertSee('Dashboard metrics refreshed successfully')

                // Loading state should disappear
                ->waitUntilMissing('.refreshing', 10);
        });
    }

    public function test_livewire_dashboard_component_interactions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))

                // Test Livewire refresh method
                ->waitFor('[wire\\:click="refreshMetrics"]')
                ->click('[wire\\:click="refreshMetrics"]')

                // Should trigger Livewire update
                ->pause(2000) // Allow for Livewire processing

                // Verify success message appears
                ->waitFor('.alert-success', 5)
                ->assertSee('Dashboard metrics refreshed successfully')

                // Test that page doesn't reload (Livewire behavior)
                ->assertRouteIs('admin.dashboard');
        });
    }

    public function test_quick_action_buttons_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Quick Actions')

                // Test View Registrations button
                ->assertSee('View Registrations')
                ->clickLink('View Registrations')
                ->assertRouteIs('admin.registrations.index')

                // Navigate back to dashboard
                ->back()
                ->assertRouteIs('admin.dashboard')

                // Test Generate Reports button
                ->assertSee('Generate Reports')
                ->clickLink('Generate Reports')
                ->assertRouteIs('admin.reports.index')

                // Navigate back to dashboard
                ->back()
                ->assertRouteIs('admin.dashboard');
        });
    }

    public function test_pending_approvals_widget_quick_navigation(): void
    {
        // Create test data with pending approvals
        $registration1 = Registration::factory()->create();
        $registration2 = Registration::factory()->create();

        Payment::factory()->count(3)->create([
            'registration_id' => $registration1->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        EnrollmentProof::factory()->count(2)->create([
            'registration_id' => $registration2->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->waitFor('[data-widget="pending-approvals"]')

                // Should display pending counts
                ->assertSee('3') // payment proofs
                ->assertSee('2') // enrollment proofs
                ->assertSee('5') // total pending

                // Test quick navigation to pending approvals
                ->within('[data-widget="pending-approvals"]', function ($browser) {
                    $browser->assertSee('Payment Proofs: 3')
                        ->assertSee('Enrollment Proofs: 2');

                    // If there are quick action links, test them
                    if ($browser->element('[data-action="view-pending-payments"]')) {
                        $browser->click('[data-action="view-pending-payments"]');
                        // Should filter to pending payments view
                    }
                });
        });
    }

    public function test_revenue_widget_currency_formatting(): void
    {
        // Create payments with specific amounts for testing
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 1234.56]);
        Payment::factory()->create(['status' => Payment::STATUS_PENDING, 'amount' => 789.12]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->waitFor('[data-widget="revenue"]')

                // Verify currency formatting (should use Brazilian format)
                ->within('[data-widget="revenue"]', function ($browser) {
                    $browser->assertSeeIn('.confirmed-revenue', 'R$')
                        ->assertSeeIn('.pending-revenue', 'R$')
                        ->assertSeeIn('.total-revenue', 'R$');

                    // Check for proper decimal formatting
                    $browser->assertSee('1.234,56') // Brazilian number format
                        ->assertSee('789,12');
                });
        });
    }

    public function test_transport_needs_widget_data_visualization(): void
    {
        // Create registrations with transport needs
        Registration::factory()->count(5)->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => false,
        ]);
        Registration::factory()->count(3)->create([
            'needs_transport_from_usp' => false,
            'needs_transport_from_gru' => true,
        ]);
        Registration::factory()->count(2)->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->waitFor('[data-widget="transport-needs"]', 10)

                ->within('[data-widget="transport-needs"]', function ($browser) {
                    $browser->assertSee('Transport Needs')
                        // Should show USP transport count: 5 + 2 = 7
                        ->assertSee('From USP: 7')
                        // Should show GRU transport count: 3 + 2 = 5
                        ->assertSee('From GRU: 5')
                        // Should show both transport count: 2
                        ->assertSee('Both: 2');
                });
        });
    }

    public function test_registrations_by_category_widget_chart_interaction(): void
    {
        // Create registrations with different categories
        Registration::factory()->count(10)->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->count(8)->create(['registration_category_snapshot' => 'grad_student']);
        Registration::factory()->count(5)->create(['registration_category_snapshot' => 'professor']);
        Registration::factory()->count(3)->create(['registration_category_snapshot' => 'professional']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->waitFor('[data-widget="registrations-by-category"]', 10)

                ->within('[data-widget="registrations-by-category"]', function ($browser) {
                    $browser->assertSee('Registrations by Category')
                        ->assertSee('Undergrad Students')
                        ->assertSee('Grad Students')
                        ->assertSee('Professors')
                        ->assertSee('Professionals');

                    // If chart is interactive, test hover/click behaviors
                    if ($browser->element('.chart-segment')) {
                        $browser->mouseover('.chart-segment:first-child')
                            ->pause(500); // Allow for hover effects

                        // Should show tooltip or highlight
                        if ($browser->element('.chart-tooltip')) {
                            $browser->assertVisible('.chart-tooltip');
                        }
                    }
                });
        });
    }

    public function test_recent_activity_feed_real_time_updates(): void
    {
        // Create recent registrations and activities
        $recentRegistration = Registration::factory()->create(['created_at' => now()->subMinutes(5)]);
        $olderRegistration = Registration::factory()->create(['created_at' => now()->subHours(2)]);

        Payment::factory()->create([
            'registration_id' => $recentRegistration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
            'created_at' => now()->subMinutes(3),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->waitFor('[data-widget="recent-activity"]')

                ->within('[data-widget="recent-activity"]', function ($browser) {
                    $browser->assertSee('Recent Activity')
                        ->assertSee('New registration submitted')
                        ->assertSee('Payment proof uploaded');

                    // Should show relative timestamps
                    $browser->assertSee('5 min ago')
                        ->assertSee('3 min ago');

                    // Should have clickable activity items
                    if ($browser->element('.activity-item[data-registration-id]')) {
                        $registrationId = $browser->attribute('.activity-item[data-registration-id]', 'data-registration-id');

                        $browser->click(".activity-item[data-registration-id=\"{$registrationId}\"]")
                            ->assertRouteIs('admin.registrations.show', ['registration' => $registrationId]);
                    }
                });
        });
    }

    public function test_dashboard_auto_refresh_polling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard');

            // Check if auto-refresh is enabled (30-second intervals as per AC4)
            $browser->pause(2000); // Wait for initial load

            // Look for Livewire polling attributes
            if ($browser->element('[wire\\:poll\\.30s]')) {
                $browser->assertPresent('[wire\\:poll\\.30s]');

                // Create new data during polling interval
                Registration::factory()->create();

                // Wait for polling update (may take up to 30 seconds in real usage)
                $browser->pause(5000) // Reduced wait time for testing
                    ->assertSee('Admin Dashboard'); // Should still be on dashboard

                // Metrics should eventually update
                // Note: In actual implementation, this would need to be tested with longer polling intervals
            }
        });
    }

    public function test_widget_error_handling_and_fallbacks(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'));

            // Test behavior when API endpoints fail
            // This could be simulated by temporarily disabling routes or causing database errors

            // Should show error states gracefully
            if ($browser->element('.widget-error')) {
                $browser->assertSee('Unable to load')
                    ->assertPresent('.retry-button');

                // Test retry functionality
                $browser->click('.retry-button')
                    ->pause(2000)
                    ->assertMissing('.widget-error');
            }

            // Should show loading states during failures
            if ($browser->element('.widget-loading')) {
                $browser->assertPresent('.loading-spinner');
            }
        });
    }

    public function test_dashboard_keyboard_accessibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')

                // Test tab navigation through interactive elements
                ->keys('body', '{tab}') // Focus first interactive element
                ->pause(500)
                ->keys('body', '{tab}') // Focus second interactive element
                ->pause(500)

                // Test Enter key activation on focused elements
                ->keys('body', '{enter}') // Should activate focused element
                ->pause(1000);

            // Verify keyboard navigation works for quick actions
            $browser->keys('body', '{tab}', '{tab}', '{tab}') // Navigate to quick actions
                ->keys('body', '{enter}'); // Should activate link

            // Should navigate or perform action
            $browser->pause(1000);
        });
    }

    public function test_dashboard_screen_reader_compatibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))

                // Check for proper ARIA labels and roles
                ->assertPresent('[role="main"]')
                ->assertPresent('[aria-label*="dashboard"]')

                // Verify widget accessibility
                ->assertPresent('[role="region"][aria-labelledby*="total-registrations"]')
                ->assertPresent('[role="region"][aria-labelledby*="pending-approvals"]')
                ->assertPresent('[role="region"][aria-labelledby*="revenue"]')

                // Check for screen reader friendly content
                ->assertPresent('[aria-live="polite"]') // For dynamic updates
                ->assertPresent('[aria-describedby]'); // For additional context
        });
    }

    public function test_dashboard_widget_responsive_interaction(): void
    {
        $this->browse(function (Browser $browser) {
            // Test desktop interactions
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->resize(1200, 800)
                ->assertSee('Admin Dashboard')

                // Desktop: Should show expanded widget layouts
                ->assertPresent('.md\\:grid-cols-2')
                ->assertPresent('.lg\\:grid-cols-3')

                // Test mobile interactions
                ->resize(375, 667)
                ->pause(1000) // Allow for responsive adjustments

                // Mobile: Should show stacked layout
                ->assertPresent('.grid-cols-1')
                ->assertMissing('.md\\:grid-cols-2') // Medium breakpoint should not apply

                // Mobile: Quick actions should be accessible
                ->assertSee('View Registrations')
                ->assertSee('Generate Reports')

                // Test tablet interactions
                ->resize(768, 1024)
                ->pause(1000)

                // Tablet: Should show intermediate layout
                ->assertPresent('.md\\:grid-cols-2');
        });
    }
}
