<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\AdminDashboardController;
use App\Services\DashboardMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Mockery;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardMetricService $mockMetricsService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database is properly migrated
        $this->ensureDatabaseMigrated();

        // Create and bind mock service
        $this->mockMetricsService = Mockery::mock(DashboardMetricService::class);
        $this->app->instance(DashboardMetricService::class, $this->mockMetricsService);
    }

    public function test_index_returns_view_instance(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();

        $this->assertInstanceOf(View::class, $result);
    }

    public function test_index_returns_correct_view_name(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();

        $this->assertEquals('admin.dashboard', $result->getName());
    }

    public function test_index_passes_metrics_data_to_view(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();

        $data = $result->getData();

        $this->assertArrayHasKey('metrics', $data);
        $this->assertIsArray($data['metrics']);
    }

    public function test_metrics_data_structure_is_complete(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        // Assert all required metrics sections exist
        $this->assertArrayHasKey('total_registrations', $metrics);
        $this->assertArrayHasKey('registrations_by_category', $metrics);
        $this->assertArrayHasKey('pending_approvals', $metrics);
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('transport_needs', $metrics);
    }

    public function test_total_registrations_metric_structure(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        $this->assertArrayHasKey('count', $metrics['total_registrations']);
        $this->assertArrayHasKey('trend', $metrics['total_registrations']);
        $this->assertArrayHasKey('change_percent', $metrics['total_registrations']);

        $this->assertIsInt($metrics['total_registrations']['count']);
        $this->assertIsString($metrics['total_registrations']['trend']);
        $this->assertIsFloat($metrics['total_registrations']['change_percent']);
    }

    public function test_registrations_by_category_metric_structure(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        $categories = ['undergrad_student', 'grad_student', 'professor', 'professional'];

        foreach ($categories as $category) {
            $this->assertArrayHasKey($category, $metrics['registrations_by_category']);
            $this->assertIsInt($metrics['registrations_by_category'][$category]);
        }
    }

    public function test_pending_approvals_metric_structure(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        $this->assertArrayHasKey('payment_proofs', $metrics['pending_approvals']);
        $this->assertArrayHasKey('enrollment_proofs', $metrics['pending_approvals']);

        $this->assertIsInt($metrics['pending_approvals']['payment_proofs']);
        $this->assertIsInt($metrics['pending_approvals']['enrollment_proofs']);
    }

    public function test_revenue_metric_structure(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        $this->assertArrayHasKey('confirmed', $metrics['revenue']);
        $this->assertArrayHasKey('pending', $metrics['revenue']);
        $this->assertArrayHasKey('currency', $metrics['revenue']);

        $this->assertIsFloat($metrics['revenue']['confirmed']);
        $this->assertIsFloat($metrics['revenue']['pending']);
        $this->assertIsString($metrics['revenue']['currency']);
        $this->assertEquals('BRL', $metrics['revenue']['currency']);
    }

    public function test_transport_needs_metric_structure(): void
    {
        // Mock the service method
        $this->mockMetricsService->shouldReceive('getCriticalMetrics')
            ->once()
            ->andReturn([
                'total_registrations' => ['total' => 5, 'trend' => ['percentage_change' => 10, 'current_month' => 3, 'previous_month' => 2]],
                'pending_approvals' => ['payment_proofs' => 2, 'enrollment_proofs' => 1, 'total' => 3],
                'revenue' => ['confirmed' => 1500.0, 'pending' => 500.0, 'total' => 2000.0],
            ]);

        $this->mockMetricsService->shouldReceive('warmCache')->once();

        $controller = $this->app->make(AdminDashboardController::class);

        $result = $controller->index();
        $data = $result->getData();
        $metrics = $data['metrics'];

        $this->assertArrayHasKey('from_usp', $metrics['transport_needs']);
        $this->assertArrayHasKey('from_gru', $metrics['transport_needs']);

        $this->assertIsInt($metrics['transport_needs']['from_usp']);
        $this->assertIsInt($metrics['transport_needs']['from_gru']);
    }
}
