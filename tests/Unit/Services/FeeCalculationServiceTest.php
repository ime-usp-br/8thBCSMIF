<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Fee;
use App\Services\FeeCalculationService;
use Carbon\Carbon;
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
    private Event $eventMock;

    private Fee $feeMock;

    private FeeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventMock = Mockery::mock(Event::class);
        $this->feeMock = Mockery::mock(Fee::class);
        $this->service = new FeeCalculationService($this->eventMock, $this->feeMock);
    }

    /**
     * Test that the FeeCalculationService class exists and can be instantiated.
     * This covers AC1 of Issue #6.
     * It also ensures that the constructor with its dependencies can be resolved.
     */
    #[Test]
    public function fee_calculation_service_class_exists_and_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FeeCalculationService::class, $this->service);
    }

    /**
     * Test that the calculateFees method exists, accepts the correct parameters,
     * and returns the expected basic structure.
     * This covers AC2 of Issue #6.
     */
    #[Test]
    public function calculate_fees_method_exists_accepts_parameters_and_returns_expected_structure(): void
    {
        $participantCategory = 'grad_student';
        $eventCodes = ['BCSMIF2025', 'RAA2025'];
        $registrationDate = Carbon::now();
        $isMainConferenceParticipant = true;

        // Call the method
        $result = $this->service->calculateFees(
            $participantCategory,
            $eventCodes,
            $registrationDate,
            $isMainConferenceParticipant
        );

        // Assert that the result is an array
        $this->assertIsArray($result);

        // Assert that the array has the 'details' and 'total_fee' keys
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);

        // Assert the types of the values for these keys
        $this->assertIsArray($result['details']);
        $this->assertIsFloat($result['total_fee']);

        // For AC2, we just check the basic structure.
        // The actual content of 'details' and the value of 'total_fee'
        // will be tested in subsequent ACs.
        $this->assertEquals([], $result['details']);
        $this->assertEquals(0.00, $result['total_fee']);
    }

    /**
     * Test calculateFees with default for isMainConferenceParticipant.
     */
    #[Test]
    public function calculate_fees_method_works_with_default_is_main_conference_participant(): void
    {
        $participantCategory = 'professor_abe';
        $eventCodes = ['WDA2025'];
        $registrationDate = Carbon::parse('2025-07-01');

        // Call the method without the last optional parameter
        $result = $this->service->calculateFees(
            $participantCategory,
            $eventCodes,
            $registrationDate
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);
        $this->assertIsArray($result['details']);
        $this->assertIsFloat($result['total_fee']);
        $this->assertEquals([], $result['details']);
        $this->assertEquals(0.00, $result['total_fee']);
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
