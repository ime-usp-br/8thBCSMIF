<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\FeeCalculationService;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(FeeCalculationService::class)]
#[Group('unit')]
#[Group('registration-modification')]
#[Group('fee-calculation')]
class RegistrationModificationCalculationTest extends TestCase
{
    use RefreshDatabase;

    private FeeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class);
        $this->seed(FeesTableSeeder::class);

        $this->service = $this->app->make(FeeCalculationService::class);
    }

    #[Test]
    public function calculates_fees_for_new_events_only(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(2)->get();
        $newEventCode = $events->first()->code;

        // Calculate fees for new event only
        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$newEventCode],
            now(),
            $registration->participation_format
        );

        $this->assertArrayHasKey('total_fee', $feeData);
        $this->assertArrayHasKey('details', $feeData);
        $this->assertGreaterThan(0, $feeData['total_fee']);
    }

    #[Test]
    public function calculates_fees_for_existing_registration_with_new_events(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(2)->get();
        $existingEventCode = $events->first()->code;
        $newEventCode = $events->last()->code;

        // Add existing event to registration
        $registration->events()->attach($existingEventCode, ['price_at_registration' => 100.00]);

        // Add existing payment
        $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'paid',
        ]);

        // Calculate fees for all events (existing + new)
        $allEventCodes = [$existingEventCode, $newEventCode];
        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            $allEventCodes,
            now(),
            $registration->participation_format,
            $registration
        );

        $this->assertArrayHasKey('total_fee', $feeData);
        $this->assertArrayHasKey('total_paid', $feeData);
        $this->assertArrayHasKey('amount_due', $feeData);
        $this->assertEquals(50.00, $feeData['total_paid']);
    }

    #[Test]
    public function calculates_amount_due_correctly_with_existing_payments(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 200.00]);

        // Add multiple payments
        $registration->payments()->create(['amount' => 100.00, 'status' => 'paid']);
        $registration->payments()->create(['amount' => 50.00, 'status' => 'paid']);

        $newEvent = Event::skip(1)->first();
        $allEventCodes = [$event->code, $newEvent->code];

        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            $allEventCodes,
            now(),
            $registration->participation_format,
            $registration
        );

        $this->assertEquals(150.00, $feeData['total_paid']);
        $this->assertGreaterThanOrEqual(0, $feeData['amount_due']);
    }

    #[Test]
    public function handles_zero_amount_due_when_fully_paid(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($event->code, ['price_at_registration' => 600.00]);

        // Add payment that covers the fee
        $registration->payments()->create(['amount' => 600.00, 'status' => 'paid']);

        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$event->code],
            now(),
            $registration->participation_format,
            $registration
        );

        $this->assertEquals(600.00, $feeData['total_paid']);
        $this->assertEquals(0.0, $feeData['amount_due']);
    }

    #[Test]
    public function excludes_pending_payments_from_total_paid(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        // Add paid and pending payments
        $registration->payments()->create(['amount' => 50.00, 'status' => 'paid']);
        $registration->payments()->create(['amount' => 30.00, 'status' => 'pending']);
        $registration->payments()->create(['amount' => 20.00, 'status' => 'pending_approval']);

        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$event->code],
            now(),
            $registration->participation_format,
            $registration
        );

        // Only paid amount should be counted
        $this->assertEquals(50.00, $feeData['total_paid']);
    }

    #[Test]
    public function calculates_new_items_cost_separately(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'professor_abe',
            'participation_format' => 'in-person',
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $raaEvent = Event::where('code', 'RAA2025')->first();

        // Add existing event (BCSMIF)
        $registration->events()->attach($bcsmifEvent->code, ['price_at_registration' => 1200.00]);

        // Calculate fee for new event only (RAA workshop)
        $newEventFee = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$raaEvent->code],
            now(),
            $registration->participation_format
        );

        // Calculate total fee for all events
        $totalFee = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$bcsmifEvent->code, $raaEvent->code],
            now(),
            $registration->participation_format,
            $registration
        );

        // RAA workshop has fees for professor_abe in-person (250.00 normal price)
        $this->assertGreaterThan(0, $newEventFee['total_fee']);
        $this->assertGreaterThan($newEventFee['total_fee'], $totalFee['total_fee']);
    }

    #[Test]
    public function handles_different_participation_formats(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'online',
        ]);

        $event = Event::first();

        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$event->code],
            now(),
            $registration->participation_format
        );

        $this->assertArrayHasKey('total_fee', $feeData);
        $this->assertGreaterThanOrEqual(0, $feeData['total_fee']);
    }

    #[Test]
    public function calculates_current_fee_structure_for_existing_events(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->first();
        $originalPrice = 150.00; // Different from current fee structure (600.00)
        $registration->events()->attach($event->code, ['price_at_registration' => $originalPrice]);

        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            [$event->code],
            now(),
            $registration->participation_format,
            $registration
        );

        // The service calculates current fee structure, not preserved original prices
        $eventDetails = collect($feeData['details'])->firstWhere('event_code', $event->code);
        $this->assertNotNull($eventDetails);
        $this->assertEquals(600.00, $eventDetails['calculated_price']); // Current fee for grad_student in-person
    }

    #[Test]
    public function calculates_fees_with_mixed_new_and_existing_events(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(3)->get();
        $existingEvent1 = $events->get(0);
        $existingEvent2 = $events->get(1);
        $newEvent = $events->get(2);

        // Add existing events with specific prices
        $registration->events()->attach([
            $existingEvent1->code => ['price_at_registration' => 100.00],
            $existingEvent2->code => ['price_at_registration' => 75.00],
        ]);

        $registration->payments()->create(['amount' => 50.00, 'status' => 'paid']);

        // Calculate fees for all events (existing + new)
        $allEventCodes = [$existingEvent1->code, $existingEvent2->code, $newEvent->code];
        $feeData = $this->service->calculateFees(
            $registration->registration_category_snapshot,
            $allEventCodes,
            now(),
            $registration->participation_format,
            $registration
        );

        $this->assertEquals(50.00, $feeData['total_paid']);
        $this->assertGreaterThan(175.00, $feeData['total_fee']); // 100 + 75 + new event fee
        $this->assertGreaterThan(0, $feeData['amount_due']);
    }
}
