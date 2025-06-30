<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationConditionalVisibilityAC6Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with a registration to avoid middleware redirects
     */
    private function createUserWithRegistration(): User
    {
        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Create a user without a registration for testing "Sign Up" visibility
     */
    private function createUserWithoutRegistration(): User
    {
        return User::factory()->create();
    }

    /**
     * Test that Login links appear only for guests (@guest).
     * This test specifically addresses AC6 requirement for guest visibility.
     */
    public function test_login_links_appear_only_for_guests(): void
    {
        // Test as guest - should see login links
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee(__('Login'));
        $response->assertDontSee(__('Sign Up'));

        // Test as authenticated user - should NOT see login links in public nav
        $user = $this->createUserWithRegistration();
        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
        $response->assertDontSee(__('Login'));
    }

    /**
     * Test that Dashboard, My Registrations, and Logout appear only for authenticated users (@auth).
     * This test specifically addresses AC6 requirement for authenticated user visibility.
     */
    public function test_authenticated_links_appear_only_for_auth_users(): void
    {
        // Test as guest - should NOT see authenticated links on public pages
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee(__('Dashboard'));

        // Test as authenticated user - should see authenticated links
        $user = $this->createUserWithRegistration();
        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Log Out'));
    }

    /**
     * Test conditional visibility in public navigation component for guests.
     * This test verifies AC6 guest section (@guest) functionality.
     */
    public function test_public_navigation_guest_section(): void
    {
        // Test public pages as guest
        $publicRoutes = ['/', '/workshops', '/fees', '/payment-info'];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $response->assertOk();

            // Should see guest-only links
            $response->assertSee(__('Login'));

            // Should NOT see authenticated-only links
            $response->assertDontSee(__('Log Out'));
            $response->assertDontSee(__('Sign Up'));
        }
    }

    /**
     * Test conditional visibility in public navigation component for authenticated users.
     * This test verifies AC6 auth section (@auth) functionality.
     */
    public function test_public_navigation_auth_section(): void
    {
        $user = $this->createUserWithoutRegistration();

        // Test public pages as authenticated user - should redirect to register-event
        $publicRoutes = ['/', '/workshops', '/fees', '/payment-info'];

        foreach ($publicRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertRedirect(route('register-event'));
        }

        // Test the register-event page itself where user can see Sign Up functionality
        $response = $this->actingAs($user)->get('/register-event');
        $response->assertOk();
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Log Out'));
        
        // Should NOT see guest-only links
        $response->assertDontSee(__('Login'));
    }

    /**
     * Test that authenticated navigation includes "My Registrations" for logged-in users.
     * This test verifies AC6 requirement for "Minhas Inscrições" visibility.
     */
    public function test_my_registrations_appears_for_authenticated_users(): void
    {
        $user = $this->createUserWithRegistration();

        // Test dashboard page (uses authenticated navigation)
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect('/my-registration');

        // Test the actual my-registration page
        $response = $this->actingAs($user)->get('/my-registration');
        $response->assertOk();
        $response->assertSee(__('My Registrations'));
    }

    /**
     * Test that "Sign Up" (Inscrever-se) link appears for authenticated users in appropriate contexts.
     * This test verifies AC6 requirement for "Inscrever-se" visibility for @auth.
     */
    public function test_sign_up_appears_correctly_for_auth_users(): void
    {
        // Test 1: Users without registrations should see Sign Up on register-event page
        $userWithoutRegistration = $this->createUserWithoutRegistration();
        
        // Public pages should redirect users without registrations
        $response = $this->actingAs($userWithoutRegistration)->get('/');
        $response->assertRedirect(route('register-event'));

        // The register-event page should show navigation with Sign Up link for users without registrations
        $response = $this->actingAs($userWithoutRegistration)->get('/register-event');
        $response->assertOk();

        // Test 2: Users WITH registrations should NOT see Sign Up in public navigation
        $userWithRegistration = $this->createUserWithRegistration();
        $response = $this->actingAs($userWithRegistration)->get('/');
        $response->assertOk();
        // Users with existing registrations should NOT see Sign Up (they already registered)
        $response->assertDontSee(__('Sign Up'));
    }

    /**
     * Test responsive navigation conditional visibility for guests.
     * This test verifies AC6 requirements in mobile/responsive view for guests.
     */
    public function test_responsive_navigation_guest_visibility(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Verify responsive section has proper @guest directives
        $this->assertStringContainsString('Responsive Authentication Links', $componentContent);
        $this->assertStringContainsString('@guest', $componentContent);
        $this->assertMatchesRegularExpression('/\@guest.*?Login.*?\@endguest/s', $componentContent);
    }

    /**
     * Test responsive navigation conditional visibility for authenticated users.
     * This test verifies AC6 requirements in mobile/responsive view for authenticated users.
     */
    public function test_responsive_navigation_auth_visibility(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Verify responsive section has proper @auth directives
        $this->assertStringContainsString('@auth', $componentContent);
        $this->assertMatchesRegularExpression('/\@auth.*?Dashboard.*?\@endauth/s', $componentContent);
        $this->assertMatchesRegularExpression('/\@auth.*?Log Out.*?\@endauth/s', $componentContent);
    }

    /**
     * Test that navigation component structure includes both @guest and @auth sections.
     * This test verifies AC6 overall conditional structure requirements.
     */
    public function test_navigation_has_complete_conditional_structure(): void
    {
        $componentPath = resource_path('views/components/layout/public-navigation.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Count @guest/@endguest pairs (should be 2: desktop + responsive)
        $guestDirectives = substr_count($componentContent, '@guest');
        $endGuestDirectives = substr_count($componentContent, '@endguest');
        $this->assertEquals($guestDirectives, $endGuestDirectives, 'All @guest directives should have matching @endguest');
        $this->assertGreaterThanOrEqual(2, $guestDirectives, 'Should have at least 2 @guest sections (desktop + responsive)');

        // Count @auth/@endauth pairs (should be 2: desktop + responsive)
        $authDirectives = substr_count($componentContent, '@auth');
        $endAuthDirectives = substr_count($componentContent, '@endauth');
        $this->assertEquals($authDirectives, $endAuthDirectives, 'All @auth directives should have matching @endauth');
        $this->assertGreaterThanOrEqual(2, $authDirectives, 'Should have at least 2 @auth sections (desktop + responsive)');
    }
}
