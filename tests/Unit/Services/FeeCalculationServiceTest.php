<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\FeeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(FeeCalculationService::class)]
#[Group('service')]
#[Group('fee-calculation')]
class FeeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeeCalculationService $service;

    private string $mainConferenceCode;

    private string $workshopCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(FeeCalculationService::class);

        // Seed basic events and fees
        $this->mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $this->workshopCode = 'RAA2025'; // Example workshop code

        Event::factory()->create([
            'code' => $this->mainConferenceCode,
            'name' => 'Main Conference Event',
            'is_main_conference' => true,
            'registration_deadline_early' => Carbon::parse('2025-08-15'),
        ]);
        Event::factory()->create([
            'code' => $this->workshopCode,
            'name' => 'Workshop Event',
            'is_main_conference' => false,
            'registration_deadline_early' => Carbon::parse('2025-08-15'),
        ]);
        Event::factory()->create([
            'code' => 'OTHERCONF',
            'name' => 'Other Conference',
            'is_main_conference' => true, // Example of another main event, if structure allows
            'registration_deadline_early' => Carbon::parse('2025-07-01'),
        ]);
    }

    #[Test]
    public function fee_calculation_service_class_exists_and_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FeeCalculationService::class, $this->service);
    }

    #[Test]
    public function calculate_fees_method_exists_accepts_parameters_and_returns_expected_structure(): void
    {
        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);
        $this->assertIsArray($result['details']);
        $this->assertIsFloat($result['total_fee']);
    }

    #[Test]
    public function calculate_fees_method_works_with_default_participation_type(): void
    {
        // This test relies on 'in-person' being the default, if not, it might need adjustment
        // For AC3, this is mostly a placeholder to ensure the method structure holds
        $defaultParticipationType = config('fee_calculation.default_participation_type', 'in-person');

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => $defaultParticipationType,
            'period' => 'early',
            'price' => 100.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01') // Early period
        );

        $this->assertEquals(100.00, $result['total_fee']);
        $this->assertEquals($this->mainConferenceCode, $result['details'][0]['event_code']);
        $this->assertEquals(100.00, $result['details'][0]['calculated_price']);
    }

    #[Test]
    public function it_correctly_fetches_event_data(): void
    {
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 150.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'), // Early bird
            'in-person'
        );

        $this->assertCount(1, $result['details']);
        $this->assertEquals('Main Conference Event', $result['details'][0]['event_name']);
        $this->assertEquals(150.00, $result['details'][0]['calculated_price']);
    }

    #[Test]
    public function it_handles_event_not_found(): void
    {
        $result = $this->service->calculateFees(
            'grad_student',
            ['NONEXISTENT_EVENT'],
            Carbon::parse('2025-08-01'),
            'in-person'
        );

        $this->assertCount(1, $result['details']);
        $this->assertEquals('NONEXISTENT_EVENT', $result['details'][0]['event_code']);
        $this->assertEquals(__('fees.event_not_found'), $result['details'][0]['error']);
        $this->assertEquals(0.00, $result['details'][0]['calculated_price']);
        $this->assertEquals(0.00, $result['total_fee']);
    }

    #[Test]
    public function it_correctly_fetches_fee_for_main_event_early_period(): void
    {
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 1200.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-10'), // Early: Before 2025-08-15
            'in-person'
        );
        $this->assertEquals(1200.00, $result['details'][0]['calculated_price']);
        $this->assertEquals(1200.00, $result['total_fee']);
    }

    #[Test]
    public function it_correctly_determines_early_period_on_exact_deadline_date(): void
    {
        // Main conference event (BCSMIF2025) has registration_deadline_early = 2025-08-15 from setUp
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early', // This fee is for 'early' period
            'price' => 1250.00, // Different price to distinguish
            'is_discount_for_main_event_participant' => false,
        ]);
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'late', // This fee is for 'late' period
            'price' => 1450.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        // Registration date IS EXACTLY ON the early deadline
        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-15'), // Exactly on early deadline (2025-08-15)
            'in-person'
        );
        $this->assertCount(1, $result['details']);
        $this->assertEquals(1250.00, $result['details'][0]['calculated_price']); // Should pick the 'early' fee
        $this->assertEquals(1250.00, $result['total_fee']);
        $this->assertArrayNotHasKey('error', $result['details'][0]);
    }

    #[Test]
    public function it_determines_late_period_if_event_has_no_early_deadline(): void
    {
        $eventNoEarlyDeadlineCode = 'EVENT_NO_EARLY';
        Event::factory()->create([
            'code' => $eventNoEarlyDeadlineCode,
            'name' => 'Event Without Early Deadline',
            'registration_deadline_early' => null, // Explicitly null
        ]);

        Fee::factory()->create([
            'event_code' => $eventNoEarlyDeadlineCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'late', // Only late fee defined for this test
            'price' => 1500.00,
            'is_discount_for_main_event_participant' => false,
        ]);
        Fee::factory()->create([ // ensure early fee is not picked even if it exists by mistake
            'event_code' => $eventNoEarlyDeadlineCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00, // much cheaper
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$eventNoEarlyDeadlineCode],
            Carbon::parse('2025-07-01'), // Any date, as there's no early deadline, period should be 'late'
            'in-person'
        );

        $this->assertCount(1, $result['details']);
        $this->assertEquals(1500.00, $result['details'][0]['calculated_price']); // Should pick 'late' fee
        $this->assertArrayNotHasKey('error', $result['details'][0]);
        $this->assertEquals($eventNoEarlyDeadlineCode, $result['details'][0]['event_code']);
    }

    #[Test]
    public function it_correctly_fetches_fee_for_main_event_late_period(): void
    {
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'late',
            'price' => 1400.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-20'), // Late: After 2025-08-15
            'in-person'
        );
        $this->assertEquals(1400.00, $result['details'][0]['calculated_price']);
        $this->assertEquals(1400.00, $result['total_fee']);
    }

    #[Test]
    public function it_handles_fee_not_found_for_event_combination(): void
    {
        // Event exists, but no matching fee for this category/type/period
        $result = $this->service->calculateFees(
            'non_existent_category',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person'
        );

        $this->assertCount(1, $result['details']);
        $this->assertEquals($this->mainConferenceCode, $result['details'][0]['event_code']);
        $this->assertEquals(__('fees.fee_config_not_found'), $result['details'][0]['error']);
        $this->assertEquals(0.00, $result['details'][0]['calculated_price']);
        $this->assertEquals(0.00, $result['total_fee']);
    }

    #[Test]
    public function it_fetches_workshop_fee_with_discount_when_attending_main_conference(): void
    {
        Fee::factory()->create([ // Discounted fee
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00,
            'is_discount_for_main_event_participant' => true,
        ]);
        Fee::factory()->create([ // Non-discounted fee (should not be picked)
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 250.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode, $this->workshopCode], // Attending main and workshop
            Carbon::parse('2025-08-01'), // Early
            'in-person'
        );

        $workshopDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);
        $this->assertNotNull($workshopDetail);
        $this->assertEquals(100.00, $workshopDetail['calculated_price']);
        $this->assertArrayNotHasKey('error', $workshopDetail);
    }

    #[Test]
    public function it_fetches_workshop_normal_fee_when_discounted_not_available_but_attending_main(): void
    {
        // ONLY Non-discounted fee exists for workshop
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 250.00,
            'is_discount_for_main_event_participant' => false,
        ]);
        // Main conference fee (to make total calculable)
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 1200.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode, $this->workshopCode], // Attending main and workshop
            Carbon::parse('2025-08-01'), // Early
            'in-person'
        );

        $workshopDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);
        $this->assertNotNull($workshopDetail);
        $this->assertEquals(250.00, $workshopDetail['calculated_price']);
        $this->assertArrayNotHasKey('error', $workshopDetail);
        $this->assertEquals(1200.00 + 250.00, $result['total_fee']);
    }

    #[Test]
    public function it_fetches_workshop_normal_fee_when_not_attending_main_conference(): void
    {
        Fee::factory()->create([ // Discounted fee (should not be picked)
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00,
            'is_discount_for_main_event_participant' => true,
        ]);
        Fee::factory()->create([ // Non-discounted fee
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 250.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->workshopCode], // Attending only workshop
            Carbon::parse('2025-08-01'), // Early
            'in-person'
        );

        $workshopDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);
        $this->assertNotNull($workshopDetail);
        $this->assertEquals(250.00, $workshopDetail['calculated_price']);
        $this->assertArrayNotHasKey('error', $workshopDetail);
        $this->assertEquals(250.00, $result['total_fee']);
    }

    #[Test]
    public function it_handles_fee_not_found_for_workshop_when_attending_main(): void
    {
        // No fees defined for workshop at all
        Fee::factory()->create([ // Main conference fee
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 1200.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode, $this->workshopCode],
            Carbon::parse('2025-08-01'),
            'in-person'
        );

        $workshopDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);
        $this->assertNotNull($workshopDetail);
        $this->assertEquals(0.00, $workshopDetail['calculated_price']);
        $this->assertEquals(__('fees.fee_config_not_found'), $workshopDetail['error']);
        $this->assertEquals(1200.00, $result['total_fee']); // Only main conference fee
    }

    #[Test]
    public function it_correctly_calculates_total_for_multiple_events_and_types(): void
    {
        // Main conference fee
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);
        // Workshop fee (discounted)
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 0.00, // grad_student workshop is free if discounted
            'is_discount_for_main_event_participant' => true,
        ]);
        // Workshop fee (normal, if not discounted)
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 0.00, // grad_student workshop is free even if not discounted
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode, $this->workshopCode],
            Carbon::parse('2025-08-01'),
            'in-person'
        );

        $this->assertCount(2, $result['details']);
        $mainEventDetail = collect($result['details'])->firstWhere('event_code', $this->mainConferenceCode);
        $workshopEventDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);

        $this->assertEquals(600.00, $mainEventDetail['calculated_price']);
        $this->assertEquals(0.00, $workshopEventDetail['calculated_price']); // Should pick discounted
        $this->assertEquals(600.00 + 0.00, $result['total_fee']);
    }

    #[Test]
    public function it_correctly_uses_participation_type_online(): void
    {
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'online', // Online fee
            'period' => 'early',
            'price' => 200.00,
            'is_discount_for_main_event_participant' => false,
        ]);
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person', // In-person fee (should not be picked)
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'online' // Requesting online fee
        );
        $this->assertEquals(200.00, $result['details'][0]['calculated_price']);
        $this->assertEquals(200.00, $result['total_fee']);
    }

    public static function providesEventAndFeeDataScenarios(): array
    {
        $mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $workshopCode = 'RAA2025'; // Must match setUp

        return [
            'main_event_early' => [
                'category' => 'grad_student',
                'events' => [$mainConferenceCode],
                'date' => '2025-08-01',
                'type' => 'in-person',
                'fees_to_create' => [
                    ['event_code' => $mainConferenceCode, 'participant_category' => 'grad_student', 'type' => 'in-person', 'period' => 'early', 'price' => 600.00, 'is_discount_for_main_event_participant' => false],
                ],
                'expected_total' => 600.00,
                'expected_details_count' => 1,
                'expected_event_prices' => [$mainConferenceCode => 600.00],
            ],
            'workshop_only_late_normal_price' => [
                'category' => 'professor_abe',
                'events' => [$workshopCode],
                'date' => '2025-08-20', // Late
                'type' => 'online',
                'fees_to_create' => [
                    ['event_code' => $workshopCode, 'participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'late', 'price' => 150.00, 'is_discount_for_main_event_participant' => false],
                    ['event_code' => $workshopCode, 'participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'late', 'price' => 100.00, 'is_discount_for_main_event_participant' => true], // Discounted, should be ignored
                ],
                'expected_total' => 150.00,
                'expected_details_count' => 1,
                'expected_event_prices' => [$workshopCode => 150.00],
            ],
            'main_and_workshop_with_discount' => [
                'category' => 'professor_non_abe_professional',
                'events' => [$mainConferenceCode, $workshopCode],
                'date' => '2025-08-01', // Early
                'type' => 'in-person',
                'fees_to_create' => [
                    ['event_code' => $mainConferenceCode, 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 1600.00, 'is_discount_for_main_event_participant' => false],
                    ['event_code' => $workshopCode, 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 500.00, 'is_discount_for_main_event_participant' => true], // Discounted
                    ['event_code' => $workshopCode, 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 700.00, 'is_discount_for_main_event_participant' => false], // Normal
                ],
                'expected_total' => 1600.00 + 500.00,
                'expected_details_count' => 2,
                'expected_event_prices' => [$mainConferenceCode => 1600.00, $workshopCode => 500.00],
            ],
            'main_and_workshop_discount_not_found_uses_normal_workshop_price' => [
                'category' => 'professor_non_abe_professional',
                'events' => [$mainConferenceCode, $workshopCode],
                'date' => '2025-08-01', // Early
                'type' => 'in-person',
                'fees_to_create' => [
                    ['event_code' => $mainConferenceCode, 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 1600.00, 'is_discount_for_main_event_participant' => false],
                    // No discounted fee for workshop
                    ['event_code' => $workshopCode, 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 700.00, 'is_discount_for_main_event_participant' => false], // Normal workshop
                ],
                'expected_total' => 1600.00 + 700.00,
                'expected_details_count' => 2,
                'expected_event_prices' => [$mainConferenceCode => 1600.00, $workshopCode => 700.00],
            ],
            'event_not_found_mixed_with_found_event' => [
                'category' => 'grad_student',
                'events' => [$mainConferenceCode, 'EVENT_DOES_NOT_EXIST'],
                'date' => '2025-08-01',
                'type' => 'online',
                'fees_to_create' => [
                    ['event_code' => $mainConferenceCode, 'participant_category' => 'grad_student', 'type' => 'online', 'period' => 'early', 'price' => 200.00, 'is_discount_for_main_event_participant' => false],
                ],
                'expected_total' => 200.00,
                'expected_details_count' => 2,
                'expected_event_prices' => [$mainConferenceCode => 200.00, 'EVENT_DOES_NOT_EXIST' => 0.00],
                'expected_error_for_event' => ['EVENT_DOES_NOT_EXIST' => __('fees.event_not_found')],
            ],
            'fee_not_found_mixed_with_found_fee' => [
                'category' => 'undergrad_student',
                'events' => [$mainConferenceCode, $workshopCode], // Main event has fee, workshop does not for this category
                'date' => '2025-08-01',
                'type' => 'in-person',
                'fees_to_create' => [
                    ['event_code' => $mainConferenceCode, 'participant_category' => 'undergrad_student', 'type' => 'in-person', 'period' => 'early', 'price' => 0.00, 'is_discount_for_main_event_participant' => false],
                    // No fee for workshop for undergrad
                ],
                'expected_total' => 0.00,
                'expected_details_count' => 2,
                'expected_event_prices' => [$mainConferenceCode => 0.00, $workshopCode => 0.00],
                'expected_error_for_event' => [$workshopCode => __('fees.fee_config_not_found')],
            ],
        ];
    }

    #[Test]
    public function it_accepts_optional_registration_parameter(): void
    {
        // AC1: Test that the method accepts an optional Registration parameter
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);
        // Should have modification-specific keys when registration is provided
        $this->assertArrayHasKey('new_total_fee', $result);
        $this->assertArrayHasKey('total_paid', $result);
        $this->assertArrayHasKey('amount_due', $result);
    }

    #[Test]
    public function it_calculates_total_paid_from_paid_payments(): void
    {
        // AC2: Test calculation of total_paid from payments with status 'paid'
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Create payments with different statuses
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 300.00,
            'status' => 'paid',
        ]);
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'paid',
        ]);
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'status' => 'pending', // Should not be included
        ]);

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        $this->assertEquals(500.00, $result['total_paid']); // Only paid payments: 300 + 200
        $this->assertEquals(600.00, $result['new_total_fee']);
        $this->assertEquals(100.00, $result['amount_due']); // 600 - 500
    }

    #[Test]
    public function it_recalculates_total_for_new_event_selection_with_discounts(): void
    {
        // AC3: Test recalculation with discounts applied
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 250.00,
            'status' => 'paid',
        ]);

        // Setup fees for main conference and workshop
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 1200.00,
            'is_discount_for_main_event_participant' => false,
        ]);
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00, // Discounted price
            'is_discount_for_main_event_participant' => true,
        ]);
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 250.00, // Normal price
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode, $this->workshopCode], // Adding both events
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        $this->assertEquals(1300.00, $result['new_total_fee']); // 1200 + 100 (discounted workshop)
        $this->assertEquals(250.00, $result['total_paid']);
        $this->assertEquals(1050.00, $result['amount_due']); // 1300 - 250
    }

    #[Test]
    public function it_returns_detailed_response_structure_for_modifications(): void
    {
        // AC4: Test detailed response structure
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 400.00,
            'status' => 'paid',
        ]);

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        // Check all required fields are present
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);
        $this->assertArrayHasKey('new_total_fee', $result);
        $this->assertArrayHasKey('total_paid', $result);
        $this->assertArrayHasKey('amount_due', $result);

        // Check values
        $this->assertEquals(600.00, $result['total_fee']);
        $this->assertEquals(600.00, $result['new_total_fee']);
        $this->assertEquals(400.00, $result['total_paid']);
        $this->assertEquals(200.00, $result['amount_due']);
    }

    #[Test]
    public function it_applies_retroactive_discount_when_main_conference_added(): void
    {
        // AC5: Test retroactive discount logic
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 250.00, // Original workshop price
            'status' => 'paid',
        ]);

        // Setup workshop fees
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00, // Discounted price for main conference participants
            'is_discount_for_main_event_participant' => true,
        ]);
        Fee::factory()->create([
            'event_code' => $this->workshopCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 250.00, // Normal price
            'is_discount_for_main_event_participant' => false,
        ]);

        // Main conference fee
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'professor_abe',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 1200.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        // Test scenario: User originally registered for workshop only, now adding main conference
        $result = $this->service->calculateFees(
            'professor_abe',
            [$this->mainConferenceCode, $this->workshopCode], // Now including main conference
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        // Workshop should now be calculated at discounted price due to main conference attendance
        $workshopDetail = collect($result['details'])->firstWhere('event_code', $this->workshopCode);
        $this->assertEquals(100.00, $workshopDetail['calculated_price']); // Discounted price

        $this->assertEquals(1300.00, $result['new_total_fee']); // 1200 + 100 (discounted workshop)
        $this->assertEquals(250.00, $result['total_paid']);
        $this->assertEquals(1050.00, $result['amount_due']); // 1300 - 250
    }

    #[Test]
    public function it_continues_working_for_new_registrations_when_registration_is_null(): void
    {
        // AC6: Test backward compatibility
        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            null // Explicitly null registration
        );

        // Should work exactly as before
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('total_fee', $result);
        $this->assertEquals(600.00, $result['total_fee']);

        // Should NOT have modification-specific keys
        $this->assertArrayNotHasKey('new_total_fee', $result);
        $this->assertArrayNotHasKey('total_paid', $result);
        $this->assertArrayNotHasKey('amount_due', $result);
    }

    #[Test]
    public function it_handles_zero_payments_correctly(): void
    {
        // Test edge case: registration with no payments
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        // No payments created

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        $this->assertEquals(0.00, $result['total_paid']);
        $this->assertEquals(600.00, $result['new_total_fee']);
        $this->assertEquals(600.00, $result['amount_due']); // Full amount due
    }

    #[Test]
    public function it_handles_negative_amount_due_when_overpaid(): void
    {
        // Test edge case: registration with overpayment
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 800.00, // More than required
            'status' => 'paid',
        ]);

        Fee::factory()->create([
            'event_code' => $this->mainConferenceCode,
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 600.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->calculateFees(
            'grad_student',
            [$this->mainConferenceCode],
            Carbon::parse('2025-08-01'),
            'in-person',
            $registration
        );

        $this->assertEquals(800.00, $result['total_paid']);
        $this->assertEquals(600.00, $result['new_total_fee']);
        $this->assertEquals(-200.00, $result['amount_due']); // Negative = overpaid
    }
}
