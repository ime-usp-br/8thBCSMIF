<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutoApprovedPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the payment upload form is not shown for auto-approved workshop payments for graduate students.
     * This test specifically addresses AC14 for Issue #80.
     */
    public function test_upload_form_is_not_shown_for_auto_approved_workshop_payments(): void
    {
        // 1. Arrange
        // Create a verified user and a registration for a graduate student
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'registration_category_snapshot' => 'grad_student',
        ]);

        // Create a workshop event
        $workshop = Event::factory()->workshop()->create();
        $registration->events()->attach($workshop->code, ['price_at_registration' => 0]);

        // Create an auto-approved payment for the workshop (amount 0, status 'approved')
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 0.00,
            'status' => 'approved',
            'payment_proof_path' => null, // No proof uploaded
        ]);

        // 2. Act & Assert
        $this->actingAs($user);

        // Render the Livewire component
        $component = Livewire::test('pages.my-registrations');

        // Assert that the component renders correctly
        $component->assertStatus(200);

        // Assert that the "Upload Payment Proof" form is NOT visible
        $component->assertDontSeeHtml(__('Upload Payment Proof'));

        // Assert that the informational message for auto-approved workshops IS visible
        $component->assertSee(__('This workshop is free for graduate students and has been automatically approved.'));
    }

    /**
     * Test that the payment upload form IS SHOWN for regular pending payments.
     * This is a sanity check to ensure we didn't break existing functionality.
     */
    public function test_upload_form_is_shown_for_regular_pendings(): void
    {
        // 1. Arrange
        // Create a verified user and a registration for a professor (who needs to pay)
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'registration_category_snapshot' => 'professor',
        ]);

        // Create a main conference event
        $conference = Event::factory()->mainConference()->create();
        $registration->events()->attach($conference->code, ['price_at_registration' => 1000]);

        // Create a standard pending payment
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 1000.00,
            'status' => 'pending',
            'payment_proof_path' => null,
        ]);

        // 2. Act & Assert
        $this->actingAs($user);

        // Render the Livewire component
        $component = Livewire::test('pages.my-registrations');

        // Assert that the component renders correctly
        $component->assertStatus(200);

        // Assert that the "Upload Payment Proof" form IS visible
        $component->assertSeeHtml(__('Upload Payment Proof'));

        // Assert that the auto-approved message is NOT visible
        $component->assertDontSee(__('This workshop is free for graduate students and has been automatically approved.'));
    }
}
