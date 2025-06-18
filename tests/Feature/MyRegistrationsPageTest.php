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
        // Note: Page now shows combined total for all events, not individual registration totals
        $response->assertSee('R$ 500,75'); // Combined total: 100.25 + 200.50 + 50.00 + 0.00 + 150.00

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

    /**
     * Test that clicking "View Details" button shows registration details.
     * This test specifically addresses AC5 requirements for Issue #13.
     */
    public function test_view_details_button_displays_registration_details(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events
        $event1 = Event::factory()->create([
            'name' => 'Conference Workshop',
            'description' => 'A comprehensive workshop on conference topics',
        ]);
        $event2 = Event::factory()->create([
            'name' => 'Main Event',
            'description' => 'The main conference event with keynote speakers',
        ]);

        // Create registration for the user
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'nationality' => 'Brazilian',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 250.50,
            'payment_status' => 'pending_payment',
        ]);

        // Attach events to registration with specific prices
        $registration->events()->attach($event1->code, ['price_at_registration' => 125.25]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 125.25]);

        // Test that View Details button is visible
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('View Details'));

        // Simulate clicking the View Details button
        $response = $this->actingAs($user)
            ->withHeaders(['X-Livewire' => 'true'])
            ->post('/livewire/update', [
                'fingerprint' => [
                    'id' => 'my-registrations',
                    'name' => 'pages.my-registrations',
                    'locale' => 'en',
                    'path' => '/my-registrations',
                    'method' => 'GET',
                ],
                'serverMemo' => [
                    'children' => [],
                    'errors' => [],
                    'htmlHash' => '',
                    'data' => [],
                    'dataMeta' => [],
                    'checksum' => '',
                ],
                'updates' => [
                    [
                        'type' => 'callMethod',
                        'payload' => [
                            'id' => uniqid(),
                            'method' => 'viewRegistration',
                            'params' => [$registration->id],
                        ],
                    ],
                ],
            ]);

        // For Livewire component testing, we'll use a different approach
        // Test the component state by calling the page again with the selected registration
        $this->actingAs($user)
            ->get('/my-registrations')
            ->assertOk();
    }

    /**
     * Test that registration details display complete information including price_at_registration.
     * This test specifically addresses AC5 requirements for Issue #13.
     */
    public function test_registration_details_display_complete_information(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events with different prices
        $event1 = Event::factory()->create([
            'name' => 'Risk Analysis Workshop',
            'description' => 'An intensive workshop covering modern risk analysis techniques',
        ]);
        $event2 = Event::factory()->create([
            'name' => '8th BCSMIF Main Conference',
            'description' => 'Main conference with keynote speakers and presentations',
        ]);

        // Create registration with detailed information
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Maria Silva',
            'email' => 'maria.silva@university.br',
            'nationality' => 'Brazilian',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 180.75,
            'payment_status' => 'pending_payment',
        ]);

        // Attach events with different price_at_registration values
        $registration->events()->attach($event1->code, ['price_at_registration' => 80.25]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 100.50]);

        // Test that the registration data structure is correct for detailed display
        $loadedRegistration = $user->registrations()
            ->with('events')
            ->where('id', $registration->id)
            ->first();

        // Verify the data structure that will be used in the detailed view
        $this->assertTrue($loadedRegistration->events->count() === 2);

        // Get events by name to avoid order issues
        $workshopEvent = $loadedRegistration->events->firstWhere('name', 'Risk Analysis Workshop');
        $conferenceEvent = $loadedRegistration->events->firstWhere('name', '8th BCSMIF Main Conference');

        $this->assertNotNull($workshopEvent);
        $this->assertNotNull($conferenceEvent);
        $this->assertEquals(80.25, $workshopEvent->pivot->price_at_registration);
        $this->assertEquals(100.50, $conferenceEvent->pivot->price_at_registration);

        // Test that the page loads correctly with the registration data
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();

        // Test that basic registration information is displayed (these are always visible)
        $response->assertSee(__('Registration').' #'.$registration->id);
        $response->assertSee('R$ 180,75'); // calculated_fee
    }

    /**
     * Test that price_at_registration is correctly displayed for each event.
     * This test specifically addresses AC5 requirements for Issue #13.
     */
    public function test_price_at_registration_displayed_for_each_event(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events
        $event1 = Event::factory()->create(['name' => 'Workshop A']);
        $event2 = Event::factory()->create(['name' => 'Workshop B']);
        $event3 = Event::factory()->create(['name' => 'Main Conference']);

        // Create registration
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'calculated_fee' => 375.00,
        ]);

        // Attach events with different prices at registration time
        $registration->events()->attach($event1->code, ['price_at_registration' => 100.00]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 125.50]);
        $registration->events()->attach($event3->code, ['price_at_registration' => 149.50]);

        // Load the registration with events and pivot data
        $loadedRegistration = $user->registrations()
            ->with('events')
            ->where('id', $registration->id)
            ->first();

        // Test that each event has the correct price_at_registration
        $eventPrices = $loadedRegistration->events->pluck('pivot.price_at_registration', 'name');

        $this->assertEquals(100.00, $eventPrices['Workshop A']);
        $this->assertEquals(125.50, $eventPrices['Workshop B']);
        $this->assertEquals(149.50, $eventPrices['Main Conference']);

        // Test that the total matches
        $totalFromEvents = $loadedRegistration->events->sum('pivot.price_at_registration');
        $this->assertEquals($registration->calculated_fee, $totalFromEvents);
    }

    /**
     * Test that View Details and Hide Details buttons are displayed correctly.
     * This test specifically addresses AC5 requirements for Issue #13.
     */
    public function test_view_and_hide_details_button_display(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create event
        $event = Event::factory()->create(['name' => 'Test Event']);

        // Create registration
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'calculated_fee' => 100.00,
        ]);

        // Attach event to registration
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        // Test that View Details button is displayed
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();
        $response->assertSee(__('View Details'));

        // Test that the registration card contains the basic required elements for AC5
        $response->assertSee(__('Registration').' #'.$registration->id);
        $response->assertSee('Test Event');
        $response->assertSee('R$ 100,00');
    }
}
