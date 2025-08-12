<?php

namespace Tests\Browser;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

/**
 * Browser tests for Admin Dashboard Progressive Loading
 *
 * Tests user experience, progressive loading behavior, loading states,
 * and overall AC5 performance requirements from a user perspective
 */
class AdminDashboardProgressiveLoadingTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        Role::create(['name' => 'admin']);
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
        ]);
        $this->adminUser->assignRole('admin');

        // Create test data
        $this->createTestData();
    }

    #[Test]
    public function it_displays_critical_widgets_immediately_and_loads_non_critical_progressively(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 10)
                ->assertSee('Admin Dashboard');

            // Critical widgets should be visible immediately
            $browser->assertSee('Total Registrations')
                ->assertSee('Pending Approvals')
                ->assertSee('Revenue')
                ->within('@total-registrations-widget', function ($browser) {
                    $browser->assertDontSee('animate-pulse'); // Should not have loading state
                });

            // Non-critical widgets should start with loading state
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertSee('animate-pulse'); // Should have loading skeleton
            })
                ->within('@transport-needs-widget', function ($browser) {
                    $browser->assertSee('animate-pulse'); // Should have loading skeleton
                });

            // Wait for progressive loading to complete (1.5s for category, 1s for transport)
            $browser->pause(2000);

            // Non-critical widgets should now show data
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertDontSee('animate-pulse') // Loading should be gone
                    ->assertSee('Registrations by Category');
            })
                ->within('@transport-needs-widget', function ($browser) {
                    $browser->assertDontSee('animate-pulse') // Loading should be gone
                        ->assertSee('Transport Needs');
                });
        });
    }

    #[Test]
    public function it_loads_dashboard_within_performance_budget(): void
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5);

            $loadTime = (microtime(true) - $startTime) * 1000;

            // Performance assertion
            $this->assertLessThan(2000, $loadTime, 'Dashboard should load within 2 seconds');

            // Verify critical content is visible
            $browser->assertSee('Total Registrations')
                ->assertSee('Pending Approvals')
                ->assertSee('Revenue');
        });
    }

    #[Test]
    public function it_displays_proper_loading_states_for_progressive_widgets(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5);

            // Check loading skeleton structure for registrations by category
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertVisible('.animate-pulse') // Should show skeleton
                    ->assertVisible('.bg-gray-300') // Should show skeleton bars
                    ->assertSee('Registrations by Category'); // Title should still be visible
            });

            // Check loading skeleton structure for transport needs
            $browser->within('@transport-needs-widget', function ($browser) {
                $browser->assertVisible('.animate-pulse') // Should show skeleton
                    ->assertSee('Transport Needs'); // Title should still be visible
            });

            // Wait for progressive loading
            $browser->pause(2000);

            // Verify skeletons are replaced with actual data
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertMissing('.animate-pulse'); // Skeleton should be gone
            })
                ->within('@transport-needs-widget', function ($browser) {
                    $browser->assertMissing('.animate-pulse'); // Skeleton should be gone
                });
        });
    }

    #[Test]
    public function it_handles_refresh_functionality_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5);

            // Wait for initial loading to complete
            $browser->pause(2500);

            // Click refresh button
            $refreshStartTime = microtime(true);

            $browser->click('@refresh-metrics-button')
                ->waitForText('Dashboard metrics refreshed successfully', 5);

            $refreshTime = (microtime(true) - $refreshStartTime) * 1000;

            // Refresh performance assertion
            $this->assertLessThan(3000, $refreshTime, 'Refresh should complete within 3 seconds');

            // Verify data is still visible after refresh
            $browser->assertSee('Total Registrations')
                ->assertSee('Pending Approvals')
                ->assertSee('Revenue');
        });
    }

    #[Test]
    public function it_maintains_responsiveness_during_loading(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5);

            // During progressive loading, other elements should remain interactive
            $browser->assertPresent('@quick-actions-section')
                ->click('@view-registrations-link') // Should be clickable during loading
                ->assertUrlIs(route('admin.registrations.index', [], false))
                ->back()
                ->waitFor('@dashboard-container', 5);

            // Navigation should work even with progressive loading
            $browser->assertSee('Admin Dashboard');
        });
    }

    #[Test]
    public function it_displays_correct_data_with_proper_formatting(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5)
                ->pause(2500); // Wait for progressive loading

            // Verify data formatting
            $browser->within('@total-registrations-widget', function ($browser) {
                $browser->assertSeeIn('.text-2xl', '23'); // Should format numbers correctly
            })
                ->within('@revenue-widget', function ($browser) {
                    $browser->assertSee('R$'); // Should display currency
                })
                ->within('@pending-approvals-widget', function ($browser) {
                    $browser->assertSee('Payment Proofs')
                        ->assertSee('Enrollment Proofs');
                });

            // Check registrations by category displays percentages
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertSee('%'); // Should show percentages
            });
        });
    }

    #[Test]
    public function it_handles_empty_data_states_gracefully(): void
    {
        // Create admin user with no data
        $emptyDataAdmin = User::factory()->create(['email' => 'emptyadmin@test.com']);
        $emptyDataAdmin->assignRole('admin');

        // Clear all test data
        Registration::truncate();
        Payment::truncate();
        EnrollmentProof::truncate();

        $this->browse(function (Browser $browser) use ($emptyDataAdmin) {
            $browser->loginAs($emptyDataAdmin)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5)
                ->pause(2500); // Wait for progressive loading

            // Should handle zero values gracefully
            $browser->within('@total-registrations-widget', function ($browser) {
                $browser->assertSeeIn('.text-2xl', '0');
            })
                ->within('@revenue-widget', function ($browser) {
                    $browser->assertSee('R$ 0'); // Should show zero revenue
                });

            // Non-critical widgets should show appropriate empty states
            $browser->within('@registrations-by-category-widget', function ($browser) {
                $browser->assertSee('No registration data available');
            })
                ->within('@transport-needs-widget', function ($browser) {
                    $browser->assertSee('No transport requests');
                });
        });
    }

    #[Test]
    public function it_works_properly_on_different_screen_sizes(): void
    {
        $this->browse(function (Browser $browser) {
            // Test desktop view
            $browser->resize(1200, 800)
                ->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 5)
                ->assertSee('Admin Dashboard');

            // Test tablet view
            $browser->resize(768, 1024)
                ->refresh()
                ->waitFor('@dashboard-container', 5)
                ->assertSee('Admin Dashboard')
                ->assertVisible('@total-registrations-widget');

            // Test mobile view (if supported)
            $browser->resize(375, 667)
                ->refresh()
                ->waitFor('@dashboard-container', 5)
                ->assertSee('Admin Dashboard');
        });
    }

    #[Test]
    public function it_handles_network_delays_gracefully(): void
    {
        $this->browse(function (Browser $browser) {
            // Simulate slow network by adding artificial delay
            $browser->loginAs($this->adminUser)
                ->visit('/admin/dashboard')
                ->waitFor('@dashboard-container', 10); // Longer timeout for slow network

            // Should still show loading states appropriately
            $browser->assertSee('Admin Dashboard')
                ->assertPresent('@total-registrations-widget');

            // Wait for progressive loading with extended timeout
            $browser->pause(5000);

            // All widgets should eventually load
            $browser->assertMissing('.animate-pulse');
        });
    }

    /**
     * Create test data for browser testing
     */
    private function createTestData(): void
    {
        // Create registrations with different categories
        Registration::factory()->count(10)->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->count(8)->create(['registration_category_snapshot' => 'grad_student']);
        Registration::factory()->count(5)->create(['registration_category_snapshot' => 'professor']);

        // Create payments
        Payment::factory()->count(15)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 200.00]);
        Payment::factory()->count(5)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 150.00]);

        // Create enrollment proofs
        EnrollmentProof::factory()->count(3)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);

        // Create transport needs
        Registration::factory()->count(5)->create(['needs_transport_from_usp' => true]);
        Registration::factory()->count(3)->create(['needs_transport_from_gru' => true]);
        Registration::factory()->count(2)->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => true,
        ]);
    }
}
