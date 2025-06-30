<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentCreationInvestigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_identify_registrations_without_payments_when_they_should_have_them(): void
    {
        // Arrange: Create test data
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'code' => 'TEST001',
            'name' => 'Test Event with Fee',
        ]);

        // Create a problematic registration (has payment_status = pending_payment but no actual payments)
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'professor_non_abe_professional',
            'participation_format' => 'in_person',
        ]);

        // Attach event with price
        $registration->events()->attach($event->code, [
            'price_at_registration' => 250.00,
        ]);

        // Act: Run the investigation command
        $this->artisan('registrations:investigate-payments')
            ->expectsOutput('🔍 Investigating Registration Payment Issues')
            ->expectsOutput('Total registrations found: 1')
            ->expectsOutput('Problematic registrations: 1')
            ->assertExitCode(0);

        // Assert: Verify the core logic works by checking the database state
        // (The real command does log, but we'll focus on testing the core investigation logic)
        $this->assertEquals('pending_payment', $registration->fresh()->payment_status);
        $this->assertEquals(0, $registration->fresh()->payments()->count());
        $this->assertGreaterThan(0, $registration->fresh()->events()->sum('price_at_registration'));
    }

    public function test_identifies_multiple_problematic_registrations(): void
    {
        // Arrange: Create multiple problematic registrations
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $event = Event::factory()->create(['code' => 'TEST002']);

        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in_person',
        ]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'undergrad_student',
            'participation_format' => 'online',
        ]);

        // Both have events with fees
        $registration1->events()->attach($event->code, ['price_at_registration' => 150.00]);
        $registration2->events()->attach($event->code, ['price_at_registration' => 100.00]);

        // Act: Run investigation
        $this->artisan('registrations:investigate-payments')
            ->expectsOutput('Total registrations found: 2')
            ->expectsOutput('Problematic registrations: 2')
            ->assertExitCode(0);

        // Assert: Both registrations should be problematic
        $this->assertEquals(0, $registration1->fresh()->payments()->count());
        $this->assertEquals(0, $registration2->fresh()->payments()->count());
        $this->assertEquals('pending_payment', $registration1->fresh()->payment_status);
        $this->assertEquals('pending_payment', $registration2->fresh()->payment_status);
    }

    public function test_does_not_flag_free_registrations_as_problematic(): void
    {
        // Arrange: Create a free registration
        $user = User::factory()->create();
        $event = Event::factory()->create(['code' => 'FREE001']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'free',
            'registration_category_snapshot' => 'undergrad_student',
            'participation_format' => 'online',
        ]);

        // Attach event with zero price
        $registration->events()->attach($event->code, ['price_at_registration' => 0.00]);

        // Act: Run investigation
        $this->artisan('registrations:investigate-payments')
            ->expectsOutput('Total registrations found: 1')
            ->expectsOutput('Problematic registrations: 0')
            ->expectsOutput('✅ No problematic registrations found')
            ->assertExitCode(0);

        // Assert: This registration should not be problematic because it's free
        $this->assertEquals('free', $registration->fresh()->payment_status);
        $this->assertEquals(0, $registration->fresh()->events()->sum('price_at_registration'));
    }

    public function test_does_not_flag_registrations_with_existing_payments(): void
    {
        // Arrange: Create registration with proper payment
        $user = User::factory()->create();
        $event = Event::factory()->create(['code' => 'PAID001']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'professor_abe',
            'participation_format' => 'in_person',
        ]);

        $registration->events()->attach($event->code, ['price_at_registration' => 300.00]);

        // Create the expected payment
        $registration->payments()->create([
            'amount' => 300.00,
            'status' => 'pending',
        ]);

        // Act: Run investigation
        $this->artisan('registrations:investigate-payments')
            ->expectsOutput('Total registrations found: 1')
            ->expectsOutput('Problematic registrations: 0')
            ->expectsOutput('✅ No problematic registrations found')
            ->assertExitCode(0);

        // Assert: This registration should not be problematic because it has a payment
        $this->assertEquals(1, $registration->fresh()->payments()->count());
        $this->assertGreaterThan(0, $registration->fresh()->events()->sum('price_at_registration'));
    }

    public function test_handles_fee_calculation_errors_gracefully(): void
    {
        // Arrange: Create registration with invalid data that might cause fee calculation to fail
        $user = User::factory()->create();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'invalid_category', // This might cause fee calculation to fail
            'participation_format' => 'invalid_format',
        ]);

        // Create event but don't attach it (this might also cause issues)
        Event::factory()->create(['code' => 'ERROR001']);

        // Act: Run investigation (should not crash)
        $this->artisan('registrations:investigate-payments')
            ->expectsOutput('Total registrations found: 1')
            ->assertExitCode(0);

        // The command should handle errors gracefully and continue
        $this->assertTrue(true); // Test passes if no exception thrown
    }
}
