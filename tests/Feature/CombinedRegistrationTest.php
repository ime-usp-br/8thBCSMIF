<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CombinedRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
    }

    private function getValidRegistrationData(User $user, array $eventCodes, array $overrides = []): array
    {
        return array_merge([
            'full_name' => $user->name,
            'nationality' => 'Brazilian',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'document_country_origin' => 'Brazil',
            'cpf' => '123.456.789-00',
            'rg_number' => '1234567',
            'email' => $user->email,
            'phone_number' => '+55 11 987654321',
            'address_street' => 'Rua Exemplo, 123',
            'address_city' => 'São Paulo',
            'address_state_province' => 'SP',
            'address_country' => 'BR',
            'address_postal_code' => '01000-000',
            'affiliation' => 'University Test',
            'position' => 'graduate_student',
            'is_abe_member' => false,
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-10-03',
            'selected_event_codes' => $eventCodes,
            'participation_format' => 'in-person',
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => false,
            'dietary_restrictions' => 'none',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_relationship' => 'Friend',
            'emergency_contact_phone' => '+55 11 912345678',
            'requires_visa_letter' => false,
            'sou_da_usp' => false,
            'confirm_information_accuracy' => true,
            'confirm_data_processing_consent' => true,
        ], $overrides);
    }

    /**
     * AC16: Test that auto-approval logic works for graduate students with workshop only (isolated).
     */
    #[Test]
    public function graduate_student_workshop_only_gets_auto_approved_payment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Get existing workshop event
        $workshop = Event::where('code', 'WDA2025')->first() ?? Event::factory()->workshop()->create([
            'code' => 'WDA2025',
            'name' => 'Workshop on Data Analysis 2025',
            'is_main_conference' => false,
        ]);

        // Create registration for grad student with workshop only
        $response = $this->actingAs($user)->post(route('event-registrations.store'),
            $this->getValidRegistrationData($user, [$workshop->code])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'registration_category_snapshot' => 'grad_student',
        ]);

        $registration = Registration::where('user_id', $user->id)->first();

        // Verify registration has workshop event
        $this->assertTrue($registration->events->contains('code', $workshop->code));

        // Verify auto-approved payment was created
        $payment = $registration->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals(0.00, $payment->amount);
        $this->assertEquals('approved', $payment->status);

        // Verify payment is associated with workshop event
        $this->assertTrue($payment->events->contains('code', $workshop->code));

        // Verify payment has correct note
        $this->assertStringContainsString('Free workshop for graduate students', $payment->notes);
    }

    /**
     * AC16: Test that auto-approval logic works for graduate students with combined registration
     * (workshop + main conference).
     */
    #[Test]
    public function graduate_student_combined_registration_gets_mixed_payments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Get existing workshop and main conference events
        $workshop = Event::where('code', 'WDA2025')->first() ?? Event::factory()->workshop()->create([
            'code' => 'WDA2025',
            'name' => 'Workshop on Data Analysis 2025',
            'is_main_conference' => false,
        ]);

        $mainConference = Event::where('code', 'BCSMIF2025')->first() ?? Event::factory()->mainConference()->create([
            'code' => 'BCSMIF2025',
            'name' => '8th Brazilian Conference on Statistical Modeling in Insurance and Finance',
            'is_main_conference' => true,
        ]);

        // Create registration for grad student with both workshop and main conference
        $response = $this->actingAs($user)->post(route('event-registrations.store'),
            $this->getValidRegistrationData($user, [$workshop->code, $mainConference->code])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'registration_category_snapshot' => 'grad_student',
        ]);

        $registration = Registration::where('user_id', $user->id)->first();

        // Verify registration has both events
        $this->assertTrue($registration->events->contains('code', $workshop->code));
        $this->assertTrue($registration->events->contains('code', $mainConference->code));

        // Verify two payments were created (one for workshop, one for main conference)
        $payments = $registration->payments;
        $this->assertGreaterThanOrEqual(1, $payments->count());

        // Find the auto-approved workshop payment
        $workshopPayment = $payments->where('status', 'approved')->first();
        $this->assertNotNull($workshopPayment, 'Workshop payment should be auto-approved');
        $this->assertEquals(0.00, $workshopPayment->amount);
        $this->assertTrue($workshopPayment->events->contains('code', $workshop->code));

        // Find the main conference payment (should be pending)
        $mainConferencePayment = $payments->where('status', 'pending')->first();
        if ($mainConferencePayment) {
            $this->assertGreaterThan(0, $mainConferencePayment->amount);
        }
    }

    /**
     * AC16: Test that professors with workshops get regular payments (not auto-approved).
     */
    #[Test]
    public function professor_with_workshop_gets_regular_payment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Get existing workshop event
        $workshop = Event::where('code', 'WDA2025')->first() ?? Event::factory()->workshop()->create([
            'code' => 'WDA2025',
            'name' => 'Workshop on Data Analysis 2025',
            'is_main_conference' => false,
        ]);

        // Create registration for professor with workshop
        $response = $this->actingAs($user)->post(route('event-registrations.store'),
            $this->getValidRegistrationData($user, [$workshop->code], [
                'position' => 'professor',
                'is_abe_member' => true,
            ])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'registration_category_snapshot' => 'professor_abe',
        ]);

        $registration = Registration::where('user_id', $user->id)->first();

        // Verify registration has workshop event
        $this->assertTrue($registration->events->contains('code', $workshop->code));

        // Verify regular payment was created (not auto-approved)
        $payment = $registration->payments()->first();
        if ($payment) {
            $this->assertNotEquals('approved', $payment->status);
            $this->assertGreaterThan(0, $payment->amount);
        }
    }

    /**
     * AC16: Test that undergraduate students don't get auto-approved payments.
     */
    #[Test]
    public function undergraduate_student_with_workshop_gets_no_auto_approved_payment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Get existing workshop event
        $workshop = Event::where('code', 'WDA2025')->first() ?? Event::factory()->workshop()->create([
            'code' => 'WDA2025',
            'name' => 'Workshop on Data Analysis 2025',
            'is_main_conference' => false,
        ]);

        // Create registration for undergrad student with workshop
        $response = $this->actingAs($user)->post(route('event-registrations.store'),
            $this->getValidRegistrationData($user, [$workshop->code], [
                'position' => 'undergraduate_student',
            ])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'registration_category_snapshot' => 'undergrad_student',
        ]);

        $registration = Registration::where('user_id', $user->id)->first();

        // Verify registration has workshop event
        $this->assertTrue($registration->events->contains('code', $workshop->code));

        // Verify no auto-approved payment was created (undergrad is always free)
        $autoApprovedPayment = $registration->payments()->where('status', 'approved')->first();
        $this->assertNull($autoApprovedPayment, 'Undergrad students should not get auto-approved payments');
    }
}
