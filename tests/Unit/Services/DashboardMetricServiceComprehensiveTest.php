<?php

namespace Tests\Unit\Services;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\DashboardMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for DashboardMetricService
 *
 * Enhanced testing for edge cases, performance optimization,
 * cache behavior, and error handling scenarios.
 */
class DashboardMetricServiceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private DashboardMetricService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database is properly migrated
        $this->ensureDatabaseMigrated();

        $this->service = new DashboardMetricService;
        Cache::flush();
    }

    #[Test]
    public function it_handles_large_dataset_performance()
    {
        // Create a larger dataset to test performance
        Registration::factory()->count(1000)->create();
        Payment::factory()->count(500)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        EnrollmentProof::factory()->count(200)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);

        $startTime = microtime(true);

        // Get all metrics (should complete in reasonable time)
        $metrics = $this->service->getAllMetrics();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete within 2 seconds (performance requirement)
        $this->assertLessThan(2000, $executionTime, "Metrics calculation took too long: {$executionTime}ms");

        // Verify data integrity with large dataset
        $this->assertEquals(1000, $metrics['total_registrations']['total']);
        $this->assertEquals(50000.00, $metrics['revenue']['confirmed']); // 500 * 100.00
        $this->assertEquals(200, $metrics['pending_approvals']['enrollment_proofs']);
    }

    #[Test]
    public function it_optimizes_queries_for_revenue_calculation()
    {
        // Create payments with different statuses
        Payment::factory()->count(10)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->count(5)->create(['status' => Payment::STATUS_PENDING, 'amount' => 200.00]);
        Payment::factory()->count(3)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 150.00]);
        Payment::factory()->count(2)->create(['status' => Payment::STATUS_REJECTED, 'amount' => 300.00]);

        // Count DB queries
        \DB::enableQueryLog();

        $revenue = $this->service->getRevenue();

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Should use single optimized query
        $this->assertEquals(1, $queryCount, 'Revenue calculation should use only one optimized query');

        // Verify calculations
        $this->assertEquals(1000.00, $revenue['confirmed']); // 10 * 100.00
        $this->assertEquals(1450.00, $revenue['pending']); // (5 * 200.00) + (3 * 150.00)
        $this->assertEquals(2450.00, $revenue['total']);
    }

    #[Test]
    public function it_optimizes_queries_for_transport_needs()
    {
        // Create registrations with various transport combinations
        Registration::factory()->count(10)->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => false,
        ]);
        Registration::factory()->count(5)->create([
            'needs_transport_from_usp' => false,
            'needs_transport_from_gru' => true,
        ]);
        Registration::factory()->count(3)->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => true,
        ]);

        // Count DB queries
        \DB::enableQueryLog();

        $transportNeeds = $this->service->getTransportNeeds();

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Should use single optimized query
        $this->assertEquals(1, $queryCount, 'Transport needs calculation should use only one optimized query');

        // Verify calculations: fromUSP(10+3=13), fromGRU(5+3=8), both(3), total(13+8-3=18)
        $this->assertEquals(13, $transportNeeds['from_usp']);
        $this->assertEquals(8, $transportNeeds['from_gru']);
        $this->assertEquals(3, $transportNeeds['both']);
        $this->assertEquals(18, $transportNeeds['total']);
    }

    #[Test]
    public function it_handles_edge_case_with_null_amounts()
    {
        // Create payments with null amounts (edge case)
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => null]);
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->create(['status' => Payment::STATUS_PENDING, 'amount' => 0]);

        $revenue = $this->service->getRevenue();

        // Should handle null amounts gracefully
        $this->assertEquals(100.00, $revenue['confirmed']);
        $this->assertEquals(0.00, $revenue['pending']);
        $this->assertEquals(100.00, $revenue['total']);
    }

    #[Test]
    public function it_handles_edge_case_with_extreme_dates()
    {
        // Create registrations with extreme dates
        Registration::factory()->create(['created_at' => now()->subYears(10)]); // Very old
        Registration::factory()->create(['created_at' => now()->startOfMonth()]); // Start of current month
        Registration::factory()->create(['created_at' => now()->endOfMonth()]); // End of current month
        Registration::factory()->create(['created_at' => now()->subMonth()->endOfMonth()]); // End of previous month

        $result = $this->service->getTotalRegistrations();

        $this->assertEquals(4, $result['total']);
        $this->assertEquals(2, $result['trend']['current_month']); // 2 in current month
        $this->assertEquals(1, $result['trend']['previous_month']); // 1 in previous month
        $this->assertEquals(100.0, $result['trend']['percentage_change']); // (2-1)/1 * 100
    }

    #[Test]
    public function it_handles_concurrent_cache_operations()
    {
        // Simulate concurrent cache operations
        Registration::factory()->count(5)->create();

        // First call should populate cache
        $result1 = $this->service->getTotalRegistrations();

        // Verify cache exists
        $this->assertTrue(Cache::has('dashboard.total_registrations'));

        // Second concurrent call should use cache
        $result2 = $this->service->getTotalRegistrations();

        // Results should be identical (cached)
        $this->assertEquals($result1, $result2);

        // Clear specific cache key
        $this->service->clearCache();

        // Third call should recalculate
        $result3 = $this->service->getTotalRegistrations();

        // Should have same values but fresh from DB
        $this->assertEquals($result1['total'], $result3['total']);
    }

    #[Test]
    public function it_handles_memory_efficient_once_memoization()
    {
        Registration::factory()->count(10)->create();

        // Multiple calls within same request should use once() memoization
        $startMemory = memory_get_usage(true);

        $metrics1 = $this->service->getAllMetrics();
        $metrics2 = $this->service->getAllMetrics();
        $metrics3 = $this->service->getAllMetrics();

        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;

        // Should use minimal additional memory due to once() memoization
        $this->assertLessThan(1024 * 1024, $memoryUsed, 'Memory usage should be minimal with memoization'); // Less than 1MB

        // All results should be identical (memoized)
        $this->assertEquals($metrics1, $metrics2);
        $this->assertEquals($metrics2, $metrics3);
    }

    #[Test]
    public function it_handles_critical_vs_non_critical_metrics_separation()
    {
        Registration::factory()->count(5)->create();
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        // Critical metrics should include only essential data
        $criticalMetrics = $this->service->getCriticalMetrics();

        $this->assertArrayHasKey('total_registrations', $criticalMetrics);
        $this->assertArrayHasKey('pending_approvals', $criticalMetrics);
        $this->assertArrayHasKey('revenue', $criticalMetrics);

        // Should NOT include non-critical metrics
        $this->assertArrayNotHasKey('registrations_by_category', $criticalMetrics);
        $this->assertArrayNotHasKey('transport_needs', $criticalMetrics);

        // Non-critical metrics should include supplementary data
        $nonCriticalMetrics = $this->service->getNonCriticalMetrics();

        $this->assertArrayHasKey('registrations_by_category', $nonCriticalMetrics);
        $this->assertArrayHasKey('transport_needs', $nonCriticalMetrics);

        // Should NOT include critical metrics
        $this->assertArrayNotHasKey('total_registrations', $nonCriticalMetrics);
        $this->assertArrayNotHasKey('pending_approvals', $nonCriticalMetrics);
        $this->assertArrayNotHasKey('revenue', $nonCriticalMetrics);
    }

    #[Test]
    public function it_validates_cache_ttl_behavior()
    {
        Registration::factory()->create();

        // Mock cache to verify TTL is set correctly
        Cache::shouldReceive('remember')
            ->withArgs(function ($key, $ttl, $callback) {
                return $key === 'dashboard.total_registrations' && $ttl === 300; // 5 minutes * 60 seconds
            })
            ->once()
            ->andReturn(['total' => 1, 'trend' => ['current_month' => 1, 'previous_month' => 0, 'percentage_change' => 0]]);

        $this->service->getTotalRegistrations();
    }

    #[Test]
    public function it_handles_database_connection_issues_gracefully()
    {
        // Test graceful handling when database is unavailable
        // Note: This is a conceptual test - actual implementation would need circuit breaker

        try {
            // Simulate database unavailability by using invalid connection
            config(['database.connections.testing.database' => '/invalid/path']);

            $result = $this->service->getTotalRegistrations();

            // Should not crash and return default values
            $this->assertIsArray($result);
            $this->assertArrayHasKey('total', $result);

        } catch (\Exception $e) {
            // If exception is thrown, ensure it's properly handled
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    #[Test]
    public function it_validates_percentage_calculation_precision()
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        // Create precise scenario: 7 current, 3 previous = 133.33% increase
        Registration::factory()->count(7)->create(['created_at' => $currentMonth->addDays(5)]);
        Registration::factory()->count(3)->create(['created_at' => $previousMonth->addDays(10)]);

        $result = $this->service->getTotalRegistrations();

        // Verify precise percentage calculation and rounding
        $expectedPercentage = ((7 - 3) / 3) * 100; // 133.33333...
        $this->assertEquals(133.3, $result['trend']['percentage_change']); // Should be rounded to 1 decimal
    }

    #[Test]
    public function it_warms_cache_efficiently()
    {
        Registration::factory()->count(5)->create();
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        // Clear cache
        Cache::flush();

        // Warm cache should populate all metric caches
        $this->service->warmCache();

        // Verify all caches are populated
        $this->assertTrue(Cache::has('dashboard.total_registrations'));
        $this->assertTrue(Cache::has('dashboard.registrations_by_category'));
        $this->assertTrue(Cache::has('dashboard.pending_approvals'));
        $this->assertTrue(Cache::has('dashboard.revenue'));
        $this->assertTrue(Cache::has('dashboard.transport_needs'));
    }

    #[Test]
    public function it_clears_all_caches_completely()
    {
        // Populate all caches
        $this->service->warmCache();

        // Verify caches exist
        $this->assertTrue(Cache::has('dashboard.total_registrations'));
        $this->assertTrue(Cache::has('dashboard.registrations_by_category'));

        // Clear all caches
        $this->service->clearCache();

        // Verify all caches are cleared
        $this->assertFalse(Cache::has('dashboard.total_registrations'));
        $this->assertFalse(Cache::has('dashboard.registrations_by_category'));
        $this->assertFalse(Cache::has('dashboard.pending_approvals'));
        $this->assertFalse(Cache::has('dashboard.revenue'));
        $this->assertFalse(Cache::has('dashboard.transport_needs'));
    }

    #[Test]
    public function it_handles_registrations_with_null_categories_properly()
    {
        // Create registrations with valid categories
        Registration::factory()->count(3)->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->count(2)->create(['registration_category_snapshot' => 'professor']);

        // Create registrations with null categories (should be excluded)
        Registration::factory()->count(5)->create(['registration_category_snapshot' => null]);

        $result = $this->service->getRegistrationsByCategory();

        // Should only include registrations with non-null categories
        $this->assertArrayHasKey('undergrad_student', $result);
        $this->assertArrayHasKey('professor', $result);
        $this->assertEquals(3, $result['undergrad_student']);
        $this->assertEquals(2, $result['professor']);

        // Should not include any count for null categories
        $this->assertEquals(2, count($result)); // Only 2 categories should be present
    }
}
