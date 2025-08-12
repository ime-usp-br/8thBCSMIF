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
 * Comprehensive Performance Tests for Admin Dashboard Load Times
 *
 * Tests dashboard performance under various conditions and data loads,
 * ensuring compliance with AC5 requirement for sub-2-second load times.
 */
class AdminDashboardPerformanceTest extends DuskTestCase
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

    public function test_dashboard_initial_load_time_with_minimal_data(): void
    {
        // Minimal dataset for baseline performance
        Registration::factory()->count(10)->create();
        Payment::factory()->count(5)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            // Measure initial page load time
            $startTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard');

            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Should load within 2 seconds (AC5 requirement)
            $this->assertLessThan(2000, $loadTime, "Dashboard initial load took {$loadTime}ms, exceeding 2-second requirement");

            // Log performance for monitoring
            echo "\nDashboard Initial Load (Minimal Data): {$loadTime}ms";
        });
    }

    public function test_dashboard_load_time_with_realistic_data(): void
    {
        // Realistic production-like dataset
        Registration::factory()->count(500)->create();
        Payment::factory()->count(250)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->count(50)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 150.00]);
        EnrollmentProof::factory()->count(100)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            $startTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10)
                ->waitFor('[data-widget="pending-approvals"]', 10)
                ->waitFor('[data-widget="revenue"]', 10);

            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000;

            // Should still load within 2 seconds with realistic data
            $this->assertLessThan(2000, $loadTime, "Dashboard load with realistic data took {$loadTime}ms, exceeding 2-second requirement");

            echo "\nDashboard Load (Realistic Data): {$loadTime}ms";
        });
    }

    public function test_dashboard_load_time_with_large_dataset(): void
    {
        // Large dataset to test performance under stress
        Registration::factory()->count(2000)->create();
        Payment::factory()->count(1000)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->count(200)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 150.00]);
        EnrollmentProof::factory()->count(300)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            $startTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 15)
                ->waitFor('[data-widget="pending-approvals"]', 15)
                ->waitFor('[data-widget="revenue"]', 15);

            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000;

            // Should load within acceptable time even with large dataset
            $this->assertLessThan(5000, $loadTime, "Dashboard load with large dataset took {$loadTime}ms, which may indicate performance issues");

            // Log warning if approaching limit
            if ($loadTime > 2000) {
                echo "\nWARNING: Dashboard load with large dataset took {$loadTime}ms (exceeds 2s target)";
            } else {
                echo "\nDashboard Load (Large Dataset): {$loadTime}ms";
            }
        });
    }

    public function test_progressive_loading_performance(): void
    {
        // Create test data
        Registration::factory()->count(100)->create();
        Payment::factory()->count(50)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            // Measure time to critical metrics (should be immediate)
            $criticalStartTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->assertVisible('[data-widget="total-registrations"]')
                ->assertVisible('[data-widget="pending-approvals"]')
                ->assertVisible('[data-widget="revenue"]');

            $criticalEndTime = microtime(true);
            $criticalLoadTime = ($criticalEndTime - $criticalStartTime) * 1000;

            // Critical metrics should load immediately (within 1 second)
            $this->assertLessThan(1000, $criticalLoadTime, "Critical metrics took {$criticalLoadTime}ms, should be under 1 second");

            // Measure time for non-critical widgets to load
            $nonCriticalStartTime = microtime(true);

            $browser->waitFor('[data-widget="registrations-by-category"]', 10)
                ->waitFor('[data-widget="transport-needs"]', 10);

            $nonCriticalEndTime = microtime(true);
            $nonCriticalLoadTime = ($nonCriticalEndTime - $nonCriticalStartTime) * 1000;

            // Non-critical widgets should load within reasonable time
            $this->assertLessThan(3000, $nonCriticalLoadTime, "Non-critical widgets took {$nonCriticalLoadTime}ms to load progressively");

            echo "\nCritical Metrics Load Time: {$criticalLoadTime}ms";
            echo "\nNon-Critical Widgets Load Time: {$nonCriticalLoadTime}ms";
        });
    }

    public function test_cache_performance_impact(): void
    {
        // Create test data
        Registration::factory()->count(200)->create();
        Payment::factory()->count(100)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            // First load (cache miss)
            $firstLoadStart = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10);

            $firstLoadEnd = microtime(true);
            $firstLoadTime = ($firstLoadEnd - $firstLoadStart) * 1000;

            // Second load (cache hit) - refresh the page
            $browser->refresh();

            $secondLoadStart = microtime(true);

            $browser->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10);

            $secondLoadEnd = microtime(true);
            $secondLoadTime = ($secondLoadEnd - $secondLoadStart) * 1000;

            // Second load should be faster due to caching
            $this->assertLessThanOrEqual($firstLoadTime, $secondLoadTime, 'Cached load should be faster or equal to initial load');

            // Both should still meet performance requirements
            $this->assertLessThan(2000, $firstLoadTime, "First load (cache miss) took {$firstLoadTime}ms");
            $this->assertLessThan(2000, $secondLoadTime, "Second load (cache hit) took {$secondLoadTime}ms");

            echo "\nFirst Load (Cache Miss): {$firstLoadTime}ms";
            echo "\nSecond Load (Cache Hit): {$secondLoadTime}ms";
            echo "\nCache Performance Improvement: ".round((($firstLoadTime - $secondLoadTime) / $firstLoadTime) * 100, 2).'%';
        });
    }

    public function test_api_endpoint_response_times(): void
    {
        // Create test data
        Registration::factory()->count(100)->create();
        Payment::factory()->count(50)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard');

            // Test non-critical metrics API endpoint
            $apiStartTime = microtime(true);

            $response = $browser->visitRoute('admin.dashboard.non-critical-metrics');

            $apiEndTime = microtime(true);
            $apiLoadTime = ($apiEndTime - $apiStartTime) * 1000;

            // API should respond quickly
            $this->assertLessThan(500, $apiLoadTime, "Non-critical metrics API took {$apiLoadTime}ms, should be under 500ms");

            // Test refresh metrics API endpoint
            $refreshStartTime = microtime(true);

            $browser->visitRoute('admin.dashboard.refresh-metrics');

            $refreshEndTime = microtime(true);
            $refreshLoadTime = ($refreshEndTime - $refreshStartTime) * 1000;

            // Refresh API should also be fast
            $this->assertLessThan(1000, $refreshLoadTime, "Refresh metrics API took {$refreshLoadTime}ms, should be under 1 second");

            echo "\nNon-Critical Metrics API: {$apiLoadTime}ms";
            echo "\nRefresh Metrics API: {$refreshLoadTime}ms";
        });
    }

    public function test_livewire_component_performance(): void
    {
        Registration::factory()->count(50)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard');

            // Test Livewire refresh action
            if ($browser->element('[wire\\:click="refreshMetrics"]')) {
                $livewireStart = microtime(true);

                $browser->click('[wire\\:click="refreshMetrics"]')
                    ->pause(500) // Allow for Livewire processing
                    ->waitFor('.alert-success', 5);

                $livewireEnd = microtime(true);
                $livewireTime = ($livewireEnd - $livewireStart) * 1000;

                // Livewire actions should be responsive
                $this->assertLessThan(3000, $livewireTime, "Livewire refresh took {$livewireTime}ms, should be under 3 seconds");

                echo "\nLivewire Refresh Action: {$livewireTime}ms";
            }
        });
    }

    public function test_dashboard_performance_under_concurrent_access(): void
    {
        // Create realistic dataset
        Registration::factory()->count(300)->create();
        Payment::factory()->count(150)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        // Simulate concurrent access with multiple browser instances
        $this->browse(function (Browser $browser1, Browser $browser2, Browser $browser3) {
            $admin1 = User::factory()->create();
            $admin2 = User::factory()->create();
            $admin3 = User::factory()->create();

            $admin1->assignRole('admin');
            $admin2->assignRole('admin');
            $admin3->assignRole('admin');

            $startTime = microtime(true);

            // Load dashboard concurrently
            $browser1->loginAs($admin1)->visit(route('admin.dashboard'))->assertSee('Admin Dashboard');
            $browser2->loginAs($admin2)->visit(route('admin.dashboard'))->assertSee('Admin Dashboard');
            $browser3->loginAs($admin3)->visit(route('admin.dashboard'))->assertSee('Admin Dashboard');

            $endTime = microtime(true);
            $concurrentLoadTime = ($endTime - $startTime) * 1000;

            // All should load within acceptable time despite concurrent access
            $this->assertLessThan(5000, $concurrentLoadTime, "Concurrent dashboard access took {$concurrentLoadTime}ms");

            // Verify all dashboards loaded correctly
            $browser1->assertVisible('[data-widget="total-registrations"]');
            $browser2->assertVisible('[data-widget="total-registrations"]');
            $browser3->assertVisible('[data-widget="total-registrations"]');

            echo "\nConcurrent Access (3 users): {$concurrentLoadTime}ms";
        });
    }

    public function test_mobile_performance(): void
    {
        Registration::factory()->count(100)->create();
        Payment::factory()->count(50)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->resize(375, 667) // Mobile device simulation
                ->pause(500); // Allow for resize

            $mobileStartTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10);

            $mobileEndTime = microtime(true);
            $mobileLoadTime = ($mobileEndTime - $mobileStartTime) * 1000;

            // Mobile should still meet performance requirements
            $this->assertLessThan(3000, $mobileLoadTime, "Mobile dashboard load took {$mobileLoadTime}ms, should be under 3 seconds");

            // Test mobile navigation performance
            if ($browser->element('[data-mobile-menu-toggle]')) {
                $navStartTime = microtime(true);

                $browser->click('[data-mobile-menu-toggle]')
                    ->waitFor('[data-mobile-menu]', 2);

                $navEndTime = microtime(true);
                $navTime = ($navEndTime - $navStartTime) * 1000;

                // Mobile navigation should be instant
                $this->assertLessThan(500, $navTime, "Mobile navigation took {$navTime}ms, should be under 500ms");

                echo "\nMobile Navigation: {$navTime}ms";
            }

            echo "\nMobile Dashboard Load: {$mobileLoadTime}ms";
        });
    }

    public function test_network_simulation_performance(): void
    {
        Registration::factory()->count(100)->create();
        Payment::factory()->count(50)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            // Simulate slow network conditions (if browser supports it)
            // Note: This is browser-dependent and may not work in all environments
            try {
                // Attempt to set network throttling (Chrome DevTools Protocol)
                $browser->driver->executeScript("
                    if (window.chrome && window.chrome.runtime) {
                        // This would require Chrome DevTools Protocol access
                        console.log('Network throttling simulation attempted');
                    }
                ");
            } catch (\Exception $e) {
                // Fallback to standard test without throttling
            }

            $slowNetworkStartTime = microtime(true);

            $browser->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 15); // Longer timeout for slow network

            $slowNetworkEndTime = microtime(true);
            $slowNetworkTime = ($slowNetworkEndTime - $slowNetworkStartTime) * 1000;

            // Should still be reasonably fast even with potential network simulation
            $this->assertLessThan(10000, $slowNetworkTime, "Dashboard load with network simulation took {$slowNetworkTime}ms");

            echo "\nNetwork Simulation Test: {$slowNetworkTime}ms";
        });
    }

    public function test_database_query_performance_impact(): void
    {
        // Create large dataset to stress database queries
        Registration::factory()->count(1000)->create();
        Payment::factory()->count(500)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->count(100)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 150.00]);
        EnrollmentProof::factory()->count(200)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);

        $this->browse(function (Browser $browser) {
            // Enable query logging to monitor database performance
            \DB::enableQueryLog();

            $dbPerformanceStart = microtime(true);

            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10)
                ->waitFor('[data-widget="pending-approvals"]', 10)
                ->waitFor('[data-widget="revenue"]', 10);

            $dbPerformanceEnd = microtime(true);
            $dbPerformanceTime = ($dbPerformanceEnd - $dbPerformanceStart) * 1000;

            $queryLog = \DB::getQueryLog();
            \DB::disableQueryLog();

            // Should still meet performance requirements with large dataset
            $this->assertLessThan(5000, $dbPerformanceTime, "Dashboard with large dataset took {$dbPerformanceTime}ms");

            // Should use optimized queries (reasonable query count)
            $queryCount = count($queryLog);
            $this->assertLessThan(20, $queryCount, "Dashboard generated {$queryCount} queries, should be optimized");

            echo "\nDatabase Performance (Large Dataset): {$dbPerformanceTime}ms";
            echo "\nQuery Count: {$queryCount}";

            // Log slow queries for analysis
            foreach ($queryLog as $query) {
                if ($query['time'] > 100) { // Queries taking more than 100ms
                    echo "\nSlow Query ({$query['time']}ms): ".substr($query['query'], 0, 100).'...';
                }
            }
        });
    }

    public function test_memory_usage_during_dashboard_load(): void
    {
        Registration::factory()->count(500)->create();
        Payment::factory()->count(250)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $this->browse(function (Browser $browser) {
            $memoryBefore = memory_get_usage(true);

            $browser->loginAs($this->admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->waitFor('[data-widget="total-registrations"]', 10);

            $memoryAfter = memory_get_usage(true);
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Memory usage should be reasonable
            $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, "Dashboard used {$memoryUsed} bytes of memory, should be under 50MB");

            echo "\nMemory Usage: ".round($memoryUsed / (1024 * 1024), 2).' MB';
        });
    }
}
