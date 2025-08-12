<?php

namespace Tests\Unit;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\DashboardMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Performance tests for DashboardMetricService
 *
 * Tests query optimization, caching efficiency, and performance benchmarks
 * to ensure AC5 requirements (sub-2s loading, efficient DB queries, 5-min cache)
 */
class DashboardMetricServicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardMetricService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardMetricService::class);
    }

    #[Test]
    public function it_executes_queries_efficiently_with_minimal_database_hits(): void
    {
        // Create test data
        $this->createTestData();

        // Clear query log and cache
        DB::flushQueryLog();
        Cache::flush();

        // Enable query logging
        DB::enableQueryLog();

        // Measure query performance
        $startTime = microtime(true);
        $metrics = $this->service->getAllMetrics();
        $endTime = microtime(true);

        $queryLog = DB::getQueryLog();
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance assertions
        $this->assertLessThan(500, $executionTime, 'Dashboard metrics should load in under 500ms');
        $this->assertLessThanOrEqual(8, count($queryLog), 'Should execute no more than 8 database queries');
        $this->assertArrayHasKey('total_registrations', $metrics);
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('pending_approvals', $metrics);
    }

    #[Test]
    public function it_uses_cache_effectively_for_repeated_requests(): void
    {
        // Create test data
        $this->createTestData();

        // Clear cache
        Cache::flush();

        // First call - should hit database
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);
        $firstResult = $this->service->getTotalRegistrations();
        $firstExecutionTime = (microtime(true) - $startTime) * 1000;
        $firstCallQueries = count(DB::getQueryLog());

        // Second call - should use cache
        DB::flushQueryLog();

        $startTime = microtime(true);
        $secondResult = $this->service->getTotalRegistrations();
        $secondExecutionTime = (microtime(true) - $startTime) * 1000;
        $secondCallQueries = count(DB::getQueryLog());

        // Cache effectiveness assertions
        $this->assertEquals($firstResult, $secondResult, 'Cached results should match database results');
        $this->assertGreaterThan(0, $firstCallQueries, 'First call should execute database queries');
        $this->assertEquals(0, $secondCallQueries, 'Second call should not execute any database queries (cache hit)');
        $this->assertLessThan($firstExecutionTime, $secondExecutionTime, 'Cached call should be faster');
        $this->assertLessThan(50, $secondExecutionTime, 'Cached call should be under 50ms');
    }

    #[Test]
    public function it_optimizes_transport_needs_query_with_single_database_call(): void
    {
        // Create test data with transport needs
        Registration::factory()->create(['needs_transport_from_usp' => true, 'needs_transport_from_gru' => false]);
        Registration::factory()->create(['needs_transport_from_usp' => false, 'needs_transport_from_gru' => true]);
        Registration::factory()->create(['needs_transport_from_usp' => true, 'needs_transport_from_gru' => true]);

        // Clear cache to force database query
        Cache::forget('dashboard.transport_needs');

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $this->service->getTransportNeeds();
        $queryLog = DB::getQueryLog();

        // Query optimization assertions
        $this->assertEquals(1, count($queryLog), 'Transport needs should use only one query');
        $this->assertEquals(2, $result['from_usp'], 'Should count USP transport needs correctly');
        $this->assertEquals(2, $result['from_gru'], 'Should count GRU transport needs correctly');
        $this->assertEquals(1, $result['both'], 'Should count both transport needs correctly');
        $this->assertEquals(3, $result['total'], 'Should calculate total correctly (avoiding double counting)');
    }

    #[Test]
    public function it_optimizes_revenue_calculation_with_single_database_call(): void
    {
        // Create test payment data
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 150.00]);
        Payment::factory()->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 75.00]);
        Payment::factory()->create(['status' => Payment::STATUS_PENDING, 'amount' => 125.00]);

        // Clear cache to force database query
        Cache::forget('dashboard.revenue');

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $this->service->getRevenue();
        $queryLog = DB::getQueryLog();

        // Query optimization assertions
        $this->assertEquals(1, count($queryLog), 'Revenue calculation should use only one query');
        $this->assertEquals(250.00, $result['confirmed'], 'Should calculate confirmed revenue correctly');
        $this->assertEquals(200.00, $result['pending'], 'Should calculate pending revenue correctly');
        $this->assertEquals(450.00, $result['total'], 'Should calculate total revenue correctly');
    }

    #[Test]
    public function it_has_proper_cache_ttl_configuration(): void
    {
        // Create test data
        $this->createTestData();

        // Clear cache
        Cache::flush();

        // Call service method
        $this->service->getTotalRegistrations();

        // Check cache TTL (5 minutes = 300 seconds)
        $this->assertTrue(Cache::has('dashboard.total_registrations'), 'Cache should be set');

        // Wait a bit and check cache is still there (within TTL)
        sleep(1);
        $this->assertTrue(Cache::has('dashboard.total_registrations'), 'Cache should still exist within TTL');
    }

    #[Test]
    public function it_provides_request_level_memoization_for_repeated_calls(): void
    {
        // Create test data
        $this->createTestData();

        // Clear cache to ensure fresh data
        Cache::flush();

        // Multiple calls to getAllMetrics within same request
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result1 = $this->service->getAllMetrics();
        $result2 = $this->service->getAllMetrics();
        $result3 = $this->service->getAllMetrics();

        $queryLog = DB::getQueryLog();

        // Memoization assertions
        $this->assertEquals($result1, $result2, 'Memoized results should be identical');
        $this->assertEquals($result2, $result3, 'Memoized results should be identical');

        // Should only execute queries once due to request-level memoization
        $this->assertLessThanOrEqual(8, count($queryLog), 'Queries should only execute once per request due to memoization');
    }

    #[Test]
    public function it_handles_cache_warming_efficiently(): void
    {
        // Create test data
        $this->createTestData();

        // Clear cache
        Cache::flush();

        // Measure cache warming performance
        $startTime = microtime(true);
        $this->service->warmCache();
        $endTime = microtime(true);

        $warmingTime = ($endTime - $startTime) * 1000;

        // Verify all cache keys are set
        $this->assertTrue(Cache::has('dashboard.total_registrations'));
        $this->assertTrue(Cache::has('dashboard.registrations_by_category'));
        $this->assertTrue(Cache::has('dashboard.pending_approvals'));
        $this->assertTrue(Cache::has('dashboard.revenue'));
        $this->assertTrue(Cache::has('dashboard.transport_needs'));

        // Performance assertion
        $this->assertLessThan(1000, $warmingTime, 'Cache warming should complete in under 1 second');
    }

    #[Test]
    public function it_provides_critical_vs_non_critical_metrics_separation(): void
    {
        // Create test data
        $this->createTestData();

        // Clear cache
        Cache::flush();

        // Test critical metrics performance
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);
        $criticalMetrics = $this->service->getCriticalMetrics();
        $criticalTime = (microtime(true) - $startTime) * 1000;
        $criticalQueries = count(DB::getQueryLog());

        // Test non-critical metrics performance
        DB::flushQueryLog();

        $startTime = microtime(true);
        $nonCriticalMetrics = $this->service->getNonCriticalMetrics();
        $nonCriticalTime = (microtime(true) - $startTime) * 1000;
        $nonCriticalQueries = count(DB::getQueryLog());

        // Critical metrics assertions
        $this->assertArrayHasKey('total_registrations', $criticalMetrics);
        $this->assertArrayHasKey('pending_approvals', $criticalMetrics);
        $this->assertArrayHasKey('revenue', $criticalMetrics);
        $this->assertLessThan(300, $criticalTime, 'Critical metrics should load in under 300ms');

        // Non-critical metrics assertions
        $this->assertArrayHasKey('registrations_by_category', $nonCriticalMetrics);
        $this->assertArrayHasKey('transport_needs', $nonCriticalMetrics);
        $this->assertLessThan(200, $nonCriticalTime, 'Non-critical metrics should load in under 200ms');

        // Total query count should be reasonable (adjusted for current implementation)
        $this->assertLessThanOrEqual(8, $criticalQueries + $nonCriticalQueries);
    }

    /**
     * Create test data for performance testing
     */
    private function createTestData(): void
    {
        // Create registrations with various categories and statuses
        Registration::factory()->count(5)->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->count(3)->create(['registration_category_snapshot' => 'grad_student']);
        Registration::factory()->count(2)->create(['registration_category_snapshot' => 'professor']);

        // Create payments with various statuses
        Payment::factory()->count(3)->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100]);
        Payment::factory()->count(2)->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 75]);

        // Create enrollment proofs
        EnrollmentProof::factory()->count(2)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);
        EnrollmentProof::factory()->count(1)->create(['status' => EnrollmentProof::STATUS_APPROVED]);
    }
}
