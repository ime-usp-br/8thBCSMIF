<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRegistrationsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that my-registrations route exists and is protected by auth middleware.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_my_registrations_route_requires_authentication(): void
    {
        // Test that unauthenticated users are redirected to login
        $response = $this->get('/my-registrations');
        $response->assertRedirect(route('login.local'));
    }

    /**
     * Test that my-registrations route requires email verification.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_my_registrations_route_requires_email_verification(): void
    {
        // Create user without verified email
        $user = User::factory()->unverified()->create();

        // Test that unverified users are redirected to verification notice
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * Test that authenticated and verified users can access my-registrations page.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_authenticated_verified_users_can_access_my_registrations(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Test that verified users can access the page
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSeeVolt('pages.my-registrations');
    }

    /**
     * Test that my-registrations route has correct name.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_my_registrations_route_has_correct_name(): void
    {
        // Verify route name exists and points to correct URL
        $this->assertEquals(url('/my-registrations'), route('registrations.my'));
    }

    /**
     * Test that my-registrations page displays proper header and content.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_my_registrations_page_displays_correct_content(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Test page content
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('My Registrations'));
    }

    /**
     * Test that my-registrations page displays empty state when no registrations exist.
     * This test specifically addresses AC1 requirements for Issue #13.
     */
    public function test_my_registrations_page_displays_empty_state(): void
    {
        // Create verified user with no registrations
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Test empty state content
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('No registrations found'));
        $response->assertSee(__('You have not registered for any events yet.'));
        $response->assertSee(__('Register for Event'));
        $response->assertSee(route('register-event'));
    }

    /**
     * Test that my-registrations page displays user's registrations with proper information.
     * This test specifically addresses AC2 requirements for Issue #13.
     */
    public function test_my_registrations_page_displays_user_registrations(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events
        $event1 = Event::factory()->create(['name' => 'Conference Workshop']);
        $event2 = Event::factory()->create(['name' => 'Main Event']);

        // Create registration for the user
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'calculated_fee' => 150.00,
            'payment_status' => 'pending_payment',
        ]);

        // Attach events to registration
        $registration->events()->attach($event1->code, ['price_at_registration' => 75.00]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 75.00]);

        // Test that registration is displayed
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('Registration').' #'.$registration->id);
        $response->assertSee('Conference Workshop');
        $response->assertSee('Main Event');
        $response->assertSee('R$ 150,00');
        $response->assertSee(__('Pending payment'));
    }

    /**
     * Test that my-registrations page only displays current user's registrations.
     * This test specifically addresses AC2 requirements for Issue #13.
     */
    public function test_my_registrations_page_displays_only_current_user_registrations(): void
    {
        // Create two verified users
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        // Create event
        $event = Event::factory()->create(['name' => 'Test Event']);

        // Create registrations for both users
        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'calculated_fee' => 100.00,
        ]);
        $registration1->events()->attach($event->code, ['price_at_registration' => 100.00]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'calculated_fee' => 200.00,
        ]);
        $registration2->events()->attach($event->code, ['price_at_registration' => 200.00]);

        // Test that user1 only sees their own registration
        $response = $this->actingAs($user1)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('Registration').' #'.$registration1->id);
        $response->assertSee('R$ 100,00');
        $response->assertDontSee(__('Registration').' #'.$registration2->id);
        $response->assertDontSee('R$ 200,00');
    }

    /**
     * Test that registration list displays all key information as required by AC3.
     * This test specifically addresses AC3 requirements for Issue #13.
     */
    public function test_registration_list_displays_key_information(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create multiple events with descriptive names
        $event1 = Event::factory()->create(['name' => 'Workshop on Risk Analysis']);
        $event2 = Event::factory()->create(['name' => '8th BCSMIF Conference']);
        $event3 = Event::factory()->create(['name' => 'Dependence Analysis Workshop']);

        // Create registration with multiple events and different payment statuses
        $registration1 = Registration::factory()->create([
            'user_id' => $user->id,
            'calculated_fee' => 350.75,
            'payment_status' => 'pending_payment',
        ]);
        $registration1->events()->attach($event1->code, ['price_at_registration' => 100.25]);
        $registration1->events()->attach($event2->code, ['price_at_registration' => 200.50]);
        $registration1->events()->attach($event3->code, ['price_at_registration' => 50.00]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user->id,
            'calculated_fee' => 0.00,
            'payment_status' => 'approved',
        ]);
        $registration2->events()->attach($event1->code, ['price_at_registration' => 0.00]);

        $registration3 = Registration::factory()->create([
            'user_id' => $user->id,
            'calculated_fee' => 150.00,
            'payment_status' => 'pending_br_proof_approval',
        ]);
        $registration3->events()->attach($event2->code, ['price_at_registration' => 150.00]);

        // Test the page displays all key information for each registration
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();

        // AC3 Requirement 1: Registration ID for each registration
        $response->assertSee(__('Registration').' #'.$registration1->id);
        $response->assertSee(__('Registration').' #'.$registration2->id);
        $response->assertSee(__('Registration').' #'.$registration3->id);

        // AC3 Requirement 2: List of event names for each registration
        // Registration 1 should show all three events (check each one individually)
        $response->assertSee('Workshop on Risk Analysis');
        $response->assertSee('8th BCSMIF Conference');
        $response->assertSee('Dependence Analysis Workshop');

        // AC3 Requirement 3: Total fee formatted correctly
        $response->assertSee('R$ 350,75'); // Registration 1
        $response->assertSee('R$ 0,00');   // Registration 2 (free)
        $response->assertSee('R$ 150,00'); // Registration 3

        // AC3 Requirement 4: Payment status formatted with proper styling
        // Check for localized status text
        $response->assertSee(__('Pending payment'));           // pending_payment
        $response->assertSee(__('Approved'));                  // approved
        $response->assertSee(__('Pending br proof approval')); // pending_br_proof_approval

        // Verify status badges have correct CSS classes
        $content = $response->getContent();

        // Check for pending_payment styling (yellow)
        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $content);

        // Check for approved styling (green)
        $this->assertStringContainsString('bg-green-100 text-green-800', $content);

        // Check for pending_br_proof_approval styling (blue)
        $this->assertStringContainsString('bg-blue-100 text-blue-800', $content);
    }
}
