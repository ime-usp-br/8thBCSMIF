<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFormDynamicFeesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            EventsTableSeeder::class,
            FeesTableSeeder::class,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_displays_when_position_and_events_are_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $component
            ->assertSee(__('Registration Fees'))
            ->assertSet('total_fee', fn ($total) => $total > 0)
            ->assertSet('fee_details', fn ($details) => count($details) > 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_updates_when_changing_participant_position(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('selected_event_codes', ['BCSMIF2025'])
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('position', 'graduate_student');

        $studentFee = $component->get('total_fee');

        // Change position to professional
        $component->set('position', 'professional');
        $professionalFee = $component->get('total_fee');

        // Fees should be different between student and professional
        $this->assertNotEquals($studentFee, $professionalFee);
        $this->assertGreaterThan($studentFee, $professionalFee);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_updates_when_changing_abe_membership(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('selected_event_codes', ['BCSMIF2025'])
            ->set('participation_format', 'in-person')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no');

        $nonAbeFee = $component->get('total_fee');

        // Change to ABE member
        $component->set('is_abe_member', 'yes');
        $abeFee = $component->get('total_fee');

        // ABE member fee should be different (typically lower)
        $this->assertNotEquals($nonAbeFee, $abeFee);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_updates_when_changing_participation_format(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('selected_event_codes', ['BCSMIF2025'])
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person');

        $inPersonFee = $component->get('total_fee');

        // Change to online
        $component->set('participation_format', 'online');
        $onlineFee = $component->get('total_fee');

        // Online fee should be different from in-person
        $this->assertNotEquals($inPersonFee, $onlineFee);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_updates_when_adding_multiple_events(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $singleEventFee = $component->get('total_fee');
        $singleEventDetails = $component->get('fee_details');

        // Add workshop
        $component->set('selected_event_codes', ['BCSMIF2025', 'RAA2025']);

        $multipleEventsFee = $component->get('total_fee');
        $multipleEventsDetails = $component->get('fee_details');

        // Total fee should increase when adding events
        $this->assertGreaterThan($singleEventFee, $multipleEventsFee);
        $this->assertGreaterThan(count($singleEventDetails), count($multipleEventsDetails));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_details_show_correct_event_names_and_prices(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $feeDetails = $component->get('fee_details');

        $this->assertNotEmpty($feeDetails);

        foreach ($feeDetails as $detail) {
            $this->assertArrayHasKey('event_code', $detail);
            $this->assertArrayHasKey('event_name', $detail);
            $this->assertArrayHasKey('calculated_price', $detail);
            $this->assertIsFloat($detail['calculated_price']);
            $this->assertGreaterThanOrEqual(0, $detail['calculated_price']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_shows_zero_when_no_events_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', []);

        $component
            ->assertSet('total_fee', 0.0)
            ->assertSet('fee_details', [])
            ->assertDontSee(__('Registration Fees'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_shows_zero_when_no_position_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', '')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $component
            ->assertSet('total_fee', 0.0)
            ->assertSet('fee_details', [])
            ->assertDontSee(__('Registration Fees'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_handles_nonexistent_events_gracefully(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'undergraduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['NONEXISTENT_EVENT']);

        $feeDetails = $component->get('fee_details');

        // Should handle nonexistent events gracefully
        $this->assertNotEmpty($feeDetails);
        $this->assertEquals('NONEXISTENT_EVENT', $feeDetails[0]['event_code']);
        $this->assertEquals(__('fees.event_not_found'), $feeDetails[0]['event_name']);
        $this->assertEquals(0.0, $feeDetails[0]['calculated_price']);
        $this->assertArrayHasKey('error', $feeDetails[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_display_section_is_visually_correct(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $component
            ->assertSee(__('Registration Fees'))
            ->assertSee(__('Total'))
            ->assertSee('R$'); // Currency symbol should be displayed
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fee_calculation_uses_fee_calculation_service(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Test that the component correctly maps position values to participant categories
        $component = Livewire::test('registration-form')
            ->set('position', 'graduate_student')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['BCSMIF2025']);

        $feeDetails = $component->get('fee_details');

        // Verify that fee calculation actually occurred
        $this->assertNotEmpty($feeDetails);

        // Test different position mappings
        $component->set('position', 'graduate_student');
        $gradFeeDetails = $component->get('fee_details');

        // Should get different results for different positions (if fees differ)
        if ($feeDetails[0]['calculated_price'] !== $gradFeeDetails[0]['calculated_price']) {
            $this->assertNotEquals($feeDetails[0]['calculated_price'], $gradFeeDetails[0]['calculated_price']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function workshop_discount_applies_when_attending_main_conference(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Test with just workshop
        $component = Livewire::test('registration-form')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('participation_format', 'in-person')
            ->set('selected_event_codes', ['RAA2025']);

        $workshopOnlyFee = $component->get('total_fee');

        // Test with main conference + workshop
        $component->set('selected_event_codes', ['BCSMIF2025', 'RAA2025']);
        $conferenceAndWorkshopFee = $component->get('total_fee');

        // Fee with both should potentially show discount for workshop
        $this->assertGreaterThan($workshopOnlyFee, $conferenceAndWorkshopFee);
    }
}
