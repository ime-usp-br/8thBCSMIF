<?php

namespace Tests\Unit\Services;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\DashboardMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardMetricServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardMetricService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database is properly migrated
        $this->ensureDatabaseMigrated();

        $this->service = new DashboardMetricService;

        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function it_can_get_total_registrations_with_trend()
    {
        // Create registrations for current month
        Registration::factory()->count(5)->create(['created_at' => now()]);

        // Create registrations for previous month
        Registration::factory()->count(3)->create(['created_at' => now()->subMonth()]);

        // Create old registrations
        Registration::factory()->count(2)->create(['created_at' => now()->subMonths(3)]);

        $result = $this->service->getTotalRegistrations();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('trend', $result);
        $this->assertEquals(10, $result['total']); // Total all registrations
        $this->assertEquals(5, $result['trend']['current_month']);
        $this->assertEquals(3, $result['trend']['previous_month']);
        $this->assertEquals(66.7, $result['trend']['percentage_change']); // (5-3)/3 * 100
    }

    #[Test]
    public function it_handles_zero_previous_month_registrations_in_trend()
    {
        // Create registrations for current month only
        Registration::factory()->count(5)->create(['created_at' => now()]);

        $result = $this->service->getTotalRegistrations();

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(5, $result['trend']['current_month']);
        $this->assertEquals(0, $result['trend']['previous_month']);
        $this->assertEquals(0, $result['trend']['percentage_change']); // Avoid division by zero
    }

    #[Test]
    public function it_can_get_registrations_by_category()
    {
        Registration::factory()->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->create(['registration_category_snapshot' => 'undergrad_student']);
        Registration::factory()->create(['registration_category_snapshot' => 'grad_student']);
        Registration::factory()->create(['registration_category_snapshot' => 'professor']);
        // Create registration without category - factory should set default

        $result = $this->service->getRegistrationsByCategory();

        $this->assertArrayHasKey('undergrad_student', $result);
        $this->assertArrayHasKey('grad_student', $result);
        $this->assertArrayHasKey('professor', $result);

        $this->assertEquals(2, $result['undergrad_student']);
        $this->assertEquals(1, $result['grad_student']);
        $this->assertEquals(1, $result['professor']);
    }

    #[Test]
    public function it_can_get_pending_approvals()
    {
        // Create pending payment proofs
        Payment::factory()->count(3)->create(['status' => Payment::STATUS_PENDING_APPROVAL]);
        Payment::factory()->count(2)->create(['status' => Payment::STATUS_APPROVED]); // Should be excluded

        // Create pending enrollment proofs
        EnrollmentProof::factory()->count(2)->create(['status' => EnrollmentProof::STATUS_PENDING_APPROVAL]);
        EnrollmentProof::factory()->count(1)->create(['status' => EnrollmentProof::STATUS_APPROVED]); // Should be excluded

        $result = $this->service->getPendingApprovals();

        $this->assertArrayHasKey('payment_proofs', $result);
        $this->assertArrayHasKey('enrollment_proofs', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertEquals(3, $result['payment_proofs']);
        $this->assertEquals(2, $result['enrollment_proofs']);
        $this->assertEquals(5, $result['total']);
    }

    #[Test]
    public function it_can_get_revenue_metrics()
    {
        // Create confirmed revenue (approved payments)
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 1000.00]);
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 500.00]);

        // Create pending revenue
        Payment::factory()->create(['status' => Payment::STATUS_PENDING, 'amount' => 300.00]);
        Payment::factory()->create(['status' => Payment::STATUS_PENDING_APPROVAL, 'amount' => 200.00]);

        // Create rejected payments (should be excluded)
        Payment::factory()->create(['status' => Payment::STATUS_REJECTED, 'amount' => 100.00]);

        $result = $this->service->getRevenue();

        $this->assertArrayHasKey('confirmed', $result);
        $this->assertArrayHasKey('pending', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertEquals(1500.00, $result['confirmed']);
        $this->assertEquals(500.00, $result['pending']);
        $this->assertEquals(2000.00, $result['total']);
    }

    #[Test]
    public function it_can_get_transport_needs()
    {
        // Create registrations with various transport needs
        Registration::factory()->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => false,
        ]);
        Registration::factory()->create([
            'needs_transport_from_usp' => false,
            'needs_transport_from_gru' => true,
        ]);
        Registration::factory()->create([
            'needs_transport_from_usp' => true,
            'needs_transport_from_gru' => true,
        ]);
        Registration::factory()->create([
            'needs_transport_from_usp' => false,
            'needs_transport_from_gru' => false,
        ]);

        $result = $this->service->getTransportNeeds();

        $this->assertArrayHasKey('from_usp', $result);
        $this->assertArrayHasKey('from_gru', $result);
        $this->assertArrayHasKey('both', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertEquals(2, $result['from_usp']); // 2 registrations need USP transport
        $this->assertEquals(2, $result['from_gru']); // 2 registrations need GRU transport
        $this->assertEquals(1, $result['both']); // 1 registration needs both
        $this->assertEquals(3, $result['total']); // 2 + 2 - 1 (avoid double counting)
    }

    #[Test]
    public function it_can_get_all_metrics_at_once()
    {
        Registration::factory()->create(['registration_category_snapshot' => 'undergrad_student']);
        Payment::factory()->create(['status' => Payment::STATUS_APPROVED, 'amount' => 100.00]);

        $result = $this->service->getAllMetrics();

        $this->assertArrayHasKey('total_registrations', $result);
        $this->assertArrayHasKey('registrations_by_category', $result);
        $this->assertArrayHasKey('pending_approvals', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('transport_needs', $result);
    }

    #[Test]
    public function it_caches_metrics_data()
    {
        // Clear cache
        Cache::flush();

        Registration::factory()->create();

        // First call should hit the database
        $firstResult = $this->service->getTotalRegistrations();

        // Create new registration that shouldn't appear in cached result
        Registration::factory()->create();

        // Second call should return cached result
        $secondResult = $this->service->getTotalRegistrations();

        // Results should be the same (cached)
        $this->assertEquals($firstResult['total'], $secondResult['total']);

        // Clear cache and call again
        $this->service->clearCache();
        $thirdResult = $this->service->getTotalRegistrations();

        // Now result should include the new registration
        $this->assertGreaterThan($firstResult['total'], $thirdResult['total']);
    }

    #[Test]
    public function it_can_clear_all_cache()
    {
        // Populate cache
        $this->service->getTotalRegistrations();
        $this->service->getRegistrationsByCategory();
        $this->service->getPendingApprovals();
        $this->service->getRevenue();
        $this->service->getTransportNeeds();

        // Verify cache exists
        $this->assertTrue(Cache::has('dashboard.total_registrations'));
        $this->assertTrue(Cache::has('dashboard.registrations_by_category'));
        $this->assertTrue(Cache::has('dashboard.pending_approvals'));
        $this->assertTrue(Cache::has('dashboard.revenue'));
        $this->assertTrue(Cache::has('dashboard.transport_needs'));

        // Clear cache
        $this->service->clearCache();

        // Verify cache is cleared
        $this->assertFalse(Cache::has('dashboard.total_registrations'));
        $this->assertFalse(Cache::has('dashboard.registrations_by_category'));
        $this->assertFalse(Cache::has('dashboard.pending_approvals'));
        $this->assertFalse(Cache::has('dashboard.revenue'));
        $this->assertFalse(Cache::has('dashboard.transport_needs'));
    }

    #[Test]
    public function it_handles_empty_data_gracefully()
    {
        $totalRegistrations = $this->service->getTotalRegistrations();
        $registrationsByCategory = $this->service->getRegistrationsByCategory();
        $pendingApprovals = $this->service->getPendingApprovals();
        $revenue = $this->service->getRevenue();
        $transportNeeds = $this->service->getTransportNeeds();

        // All should return valid structure with zero values
        $this->assertEquals(0, $totalRegistrations['total']);
        $this->assertEquals([], $registrationsByCategory);
        $this->assertEquals(0, $pendingApprovals['total']);
        $this->assertEquals(0.0, $revenue['total']);
        $this->assertEquals(0, $transportNeeds['total']);
    }
}
