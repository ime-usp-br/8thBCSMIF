<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Fee;
use App\Services\FeeCalculationService;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase as LaravelTestCase; // Use Laravel's TestCase for better integration and mocking capabilities

/**
 * Unit tests for the FeeCalculationService.
 */
#[CoversClass(FeeCalculationService::class)]
class FeeCalculationServiceTest extends LaravelTestCase
{
    /**
     * Test that the FeeCalculationService class exists and can be instantiated.
     * This covers AC1 of Issue #6.
     * It also ensures that the constructor with its dependencies can be resolved.
     */
    #[Test]
    public function fee_calculation_service_class_exists_and_can_be_instantiated(): void
    {
        // Mock the dependencies (Event and Fee models) required by the constructor.
        // This is good practice for unit testing services, allowing isolation.
        $eventMock = Mockery::mock(Event::class);
        $feeMock = Mockery::mock(Fee::class);

        // Attempt to instantiate the service.
        $service = new FeeCalculationService($eventMock, $feeMock);

        // Assert that the instantiated object is an instance of FeeCalculationService.
        $this->assertInstanceOf(FeeCalculationService::class, $service);
    }

    /**
     * Clean up the testing environment before the next test.
     * This is important for Mockery to close any mocks.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}