<?php

namespace Tests\Feature;

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
}
