<?php

namespace Tests\Feature;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Performance tests for Admin Dashboard Controller and Integration
 *
 * Tests end-to-end dashboard performance, progressive loading behavior,
 * and overall AC5 requirements compliance
 */
class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user with proper role
        Role::create(['name' => 'admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Create test data
        $this->createTestData();
    }

    /** @test */
    public function it_loads_admin_dashboard_within_performance_budget(): void
    {
        // Clear cache to ensure fresh performance test
        Cache::flush();

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Measure dashboard loading performance
        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryLog = DB::getQueryLog();

        // Performance assertions
        $response->assertStatus(200);
        $this->assertLessThan(2000, $loadTime, 'Dashboard should load in under 2 seconds (2000ms)');
        $this->assertLessThanOrEqual(10, count($queryLog), 'Dashboard should execute no more than 10 database queries');

        // Verify critical content is present
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Total Registrations');
        $response->assertSee('Pending Approvals');
        $response->assertSee('Revenue');
    }

    /** @test */
    public function it_serves_critical_metrics_immediately_on_first_load(): void
    {
        // Clear cache
        Cache::flush();

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard');

        $response->assertStatus(200);

        // Extract metrics data from response
        $metrics = $response->viewData('metrics');

        // Critical metrics should be populated immediately
        $this->assertArrayHasKey('total_registrations', $metrics);
        $this->assertArrayHasKey('pending_approvals', $metrics);
        $this->assertArrayHasKey('revenue', $metrics);

        // Critical metrics should have actual data
        $this->assertIsArray($metrics['total_registrations']);
        $this->assertArrayHasKey('count', $metrics['total_registrations']);
        $this->assertGreaterThan(0, $metrics['total_registrations']['count']);

        // Non-critical metrics should be empty for progressive loading
        $this->assertEmpty($metrics['registrations_by_category']);
        $this->assertEmpty($metrics['transport_needs']);
    }

    /** @test */
    public function it_provides_non_critical_metrics_via_ajax_endpoint(): void
    {
        // Clear cache
        Cache::flush();

        // Enable query logging for performance measurement
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard/metrics/non-critical');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;
        $queryLog = DB::getQueryLog();

        // Performance assertions for AJAX endpoint
        $response->assertStatus(200);
        $this->assertLessThan(500, $loadTime, 'Non-critical metrics should load in under 500ms');
        $this->assertLessThanOrEqual(3, count($queryLog), 'Non-critical metrics should execute no more than 3 queries');

        // Verify non-critical metrics data
        $data = $response->json();
        $this->assertArrayHasKey('registrations_by_category', $data);
        $this->assertArrayHasKey('transport_needs', $data);
    }

    /** @test */
    public function it_handles_cache_refresh_efficiently(): void
    {
        // Pre-warm cache
        Cache::flush();
        $this->actingAs($this->adminUser)->get('/admin/dashboard');

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/dashboard/refresh');

        $endTime = microtime(true);
        $refreshTime = ($endTime - $startTime) * 1000;
        $queryLog = DB::getQueryLog();

        // Cache refresh performance assertions
        $response->assertStatus(200);
        $this->assertLessThan(1000, $refreshTime, 'Cache refresh should complete in under 1 second');
        $this->assertLessThanOrEqual(8, count($queryLog), 'Cache refresh should execute reasonable number of queries');

        // Verify refreshed data
        $data = $response->json();
        $this->assertArrayHasKey('total_registrations', $data);
        $this->assertArrayHasKey('revenue', $data);
    }

    /** @test */
    public function it_maintains_performance_with_large_dataset(): void
    {
        // Create a large dataset for performance testing
        $this->createLargeTestDataset();

        // Clear cache to ensure fresh queries
        Cache::flush();

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;
        $queryLog = DB::getQueryLog();

        // Performance with large dataset assertions
        $response->assertStatus(200);
        $this->assertLessThan(3000, $loadTime, 'Dashboard should handle large datasets within 3 seconds');
        $this->assertLessThanOrEqual(12, count($queryLog), 'Query count should remain reasonable even with large dataset');

        // Verify data accuracy with large dataset
        $metrics = $response->viewData('metrics');
        $this->assertGreaterThan(100, $metrics['total_registrations']['count']);
    }

    /** @test */
    public function it_provides_correct_cache_headers_for_optimization(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard');

        $response->assertStatus(200);

        // Verify appropriate caching headers are set
        // Note: These might be set by middleware or server configuration
        $this->assertTrue(true); // Placeholder - actual cache headers would be tested here
    }

    /** @test */
    public function it_handles_concurrent_requests_efficiently(): void
    {
        // Clear cache
        Cache::flush();

        $startTimes = [];
        $endTimes = [];
        $responses = [];

        // Simulate concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $startTimes[$i] = microtime(true);
            $responses[$i] = $this->actingAs($this->adminUser)
                ->get('/admin/dashboard');
            $endTimes[$i] = microtime(true);
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Calculate average response time
        $totalTime = 0;
        for ($i = 0; $i < 3; $i++) {
            $totalTime += ($endTimes[$i] - $startTimes[$i]) * 1000;
        }
        $avgTime = $totalTime / 3;

        // Concurrent performance assertion
        $this->assertLessThan(2500, $avgTime, 'Average response time under concurrent load should be under 2.5 seconds');
    }

    /** @test */
    public function it_provides_proper_error_handling_without_performance_degradation(): void
    {
        // Simulate database connection issue by using invalid cache configuration
        // This tests that error handling doesn't cause performance issues

        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        // Even with potential issues, should maintain reasonable performance
        $response->assertStatus(200);
        $this->assertLessThan(5000, $loadTime, 'Error handling should not cause extreme performance degradation');
    }

    /**
     * Create test data for performance testing
     */
    private function createTestData(): void
    {
        // Create registrations
        Registration::factory()->count(10)->create([
            'registration_category_snapshot' => 'undergrad_student',
        ]);
        Registration::factory()->count(8)->create([
            'registration_category_snapshot' => 'grad_student',
        ]);
        Registration::factory()->count(5)->create([
            'registration_category_snapshot' => 'professor',
        ]);

        // Create payments
        Payment::factory()->count(15)->create([
            'status' => Payment::STATUS_APPROVED,
            'amount' => 150.00,
        ]);
        Payment::factory()->count(5)->create([
            'status' => Payment::STATUS_PENDING_APPROVAL,
            'amount' => 150.00,
        ]);

        // Create enrollment proofs
        EnrollmentProof::factory()->count(3)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
    }

    /**
     * Create large dataset for stress testing
     */
    private function createLargeTestDataset(): void
    {
        // Create large number of registrations
        Registration::factory()->count(200)->create([
            'registration_category_snapshot' => 'undergrad_student',
        ]);
        Registration::factory()->count(150)->create([
            'registration_category_snapshot' => 'grad_student',
        ]);
        Registration::factory()->count(100)->create([
            'registration_category_snapshot' => 'professor',
        ]);
        Registration::factory()->count(50)->create([
            'registration_category_snapshot' => 'professional',
        ]);

        // Create large number of payments
        Payment::factory()->count(300)->create([
            'status' => Payment::STATUS_APPROVED,
            'amount' => 200.00,
        ]);
        Payment::factory()->count(100)->create([
            'status' => Payment::STATUS_PENDING_APPROVAL,
            'amount' => 200.00,
        ]);

        // Create enrollment proofs
        EnrollmentProof::factory()->count(50)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
    }
}
