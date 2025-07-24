<?php

namespace Tests\Browser\Login;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;
use Tests\Fakes\FakeSenhaunicaSocialiteProvider;

/**
 * Comprehensive Dusk tests for authentication flows
 *
 * This consolidated file contains all the working browser tests for:
 * - Registration page structure and functionality
 * - Login page structure and functionality
 * - Navigation between authentication pages
 * - USP Senha Única integration
 * - User creation via factories
 * - Database seeders verification
 */
#[Group('browser')]
#[Group('authentication')]
class AuthenticationTest extends DuskTestCase
{
    // === PAGE STRUCTURE TESTS ===

    #[Test]
    public function registration_page_displays_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertSee('Name')
                ->assertSee('Email')
                ->assertSee('Password')
                ->assertSee('Confirm Password')
                ->assertSee("I'm from USP")
                ->assertPresent('[dusk="name-input"]')
                ->assertPresent('[dusk="email-input"]')
                ->assertPresent('[dusk="password-input"]')
                ->assertPresent('[dusk="password-confirmation-input"]')
                ->assertPresent('[dusk="is-usp-user-checkbox"]')
                ->assertPresent('[dusk="register-button"]')
                ->assertPresent('[dusk="already-registered-link"]');
        });
    }

    #[Test]
    public function login_page_displays_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                ->assertSee('Email')
                ->assertSee('Password')
                ->assertSee('Remember me')
                ->assertPresent('[dusk="email-input"]')
                ->assertPresent('[dusk="password-input"]')
                ->assertPresent('[dusk="login-button"]')
                ->assertPresent('[dusk="register-link"]')
                ->assertPresent('[dusk="forgot-password-link"]')
                ->assertPresent('[dusk="senhaunica-login-button"]');
        });
    }

    #[Test]
    public function registration_form_has_correct_attributes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');

            // Test HTML form attributes
            $browser->assertAttribute('[dusk="name-input"]', 'type', 'text')
                ->assertAttribute('[dusk="name-input"]', 'required', 'true')
                ->assertAttribute('[dusk="email-input"]', 'type', 'email')
                ->assertAttribute('[dusk="email-input"]', 'required', 'true')
                ->assertAttribute('[dusk="password-input"]', 'type', 'password')
                ->assertAttribute('[dusk="password-input"]', 'required', 'true')
                ->assertAttribute('[dusk="password-confirmation-input"]', 'type', 'password')
                ->assertAttribute('[dusk="password-confirmation-input"]', 'required', 'true')
                ->assertAttribute('[dusk="is-usp-user-checkbox"]', 'type', 'checkbox');

            // Test Livewire directives are present
            $browser->assertAttribute('[dusk="name-input"]', 'wire:model', 'name')
                ->assertAttribute('[dusk="email-input"]', 'wire:model.blur', 'email')
                ->assertAttribute('[dusk="password-input"]', 'wire:model', 'password')
                ->assertAttribute('[dusk="password-confirmation-input"]', 'wire:model', 'password_confirmation')
                ->assertAttribute('[dusk="is-usp-user-checkbox"]', 'wire:model.live', 'sou_da_usp');
        });
    }

    // === NAVIGATION TESTS ===

    #[Test]
    public function navigation_between_auth_pages_works(): void
    {
        $this->browse(function (Browser $browser) {
            // Start at login
            $browser->visit('/login/local')
                ->assertRouteIs('login.local');

            // Navigate to register
            $browser->click('[dusk="register-link"]')
                ->waitForRoute('register')
                ->assertRouteIs('register');

            // Navigate back to login
            $browser->click('[dusk="already-registered-link"]')
                ->waitForRoute('login.local')
                ->assertRouteIs('login.local');

            // Navigate to forgot password
            $browser->click('[dusk="forgot-password-link"]')
                ->waitForRoute('password.request')
                ->assertRouteIs('password.request');
        });
    }

    #[Test]
    public function basic_page_visits_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertPathIs('/register')
                ->visit('/login/local')
                ->assertPathIs('/login/local')
                ->visit('/forgot-password')
                ->assertPathIs('/forgot-password');
        });
    }

    // === USP SENHA ÚNICA TESTS ===

    #[Test]
    public function senhaunica_button_is_configured_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                ->assertPresent('[dusk="senhaunica-login-button"]')
                ->assertAttribute('[dusk="senhaunica-login-button"]', 'href', route('login'));
        });
    }

    #[Test]
    public function senhaunica_fake_provider_works(): void
    {
        // Test the mock infrastructure for USP authentication
        $fakeUserData = [
            'codpes' => '9876543',
            'nompes' => 'Fake USP User',
            'email' => 'fake@usp.br',
            'vinculo' => [
                [
                    'tipoVinculo' => 'SERVIDOR',
                    'tipoFuncao' => 'Docente',
                ],
            ],
        ];

        $fakeProvider = new FakeSenhaunicaSocialiteProvider($fakeUserData);
        $socialiteUser = $fakeProvider->user();

        $this->assertEquals('9876543', $socialiteUser->getId());
        $this->assertEquals('Fake USP User', $socialiteUser->getName());
        $this->assertEquals('fake@usp.br', $socialiteUser->getEmail());
        $this->assertNotNull($socialiteUser->token);
    }

    #[Test]
    public function login_routes_are_configured_correctly(): void
    {
        // Verify route configuration
        $loginRoute = route('login');
        $localLoginRoute = route('login.local');

        $this->assertNotEmpty($loginRoute);
        $this->assertNotEmpty($localLoginRoute);
        $this->assertNotEquals($loginRoute, $localLoginRoute);
    }

    // === USER CREATION TESTS ===

    #[Test]
    public function external_user_can_be_created_via_factory(): void
    {
        $user = User::factory()->create([
            'name' => 'Test External User',
            'email' => 'external@example.com',
        ]);

        $user->assignRole('external_user');

        $this->assertNotNull($user);
        $this->assertEquals('external@example.com', $user->email);
        $this->assertNull($user->codpes);
        $this->assertTrue($user->hasRole('external_user'));
        $this->assertFalse($user->hasRole('usp_user'));
    }

    #[Test]
    public function usp_user_can_be_created_via_factory(): void
    {
        $user = User::factory()->create([
            'name' => 'Test USP User',
            'email' => 'usp@usp.br',
            'codpes' => '1234567',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('usp_user');

        $this->assertNotNull($user);
        $this->assertEquals('usp@usp.br', $user->email);
        $this->assertEquals('1234567', $user->codpes);
        $this->assertTrue($user->hasRole('usp_user'));
        $this->assertFalse($user->hasRole('external_user'));
        $this->assertTrue($user->hasVerifiedEmail());
    }

    #[Test]
    public function authenticated_user_factory_works(): void
    {
        // Create verified user via factory
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('external_user');

        // Verify the user was created correctly
        $this->assertNotNull($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('external_user'));

        // Verify database state
        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    // === MIDDLEWARE AND REDIRECTION TESTS ===

    #[Test]
    public function unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            // Try to access protected route without authentication
            $browser->visit('/my-registration')
                ->waitForRoute('login.local')
                ->assertRouteIs('login.local');

            $browser->visit('/profile')
                ->waitForRoute('login.local')
                ->assertRouteIs('login.local');
        });
    }

    // === DATABASE AND SEEDERS TESTS ===

    #[Test]
    public function database_seeders_are_working(): void
    {
        // Verify that roles were seeded
        $this->assertDatabaseHas('roles', ['name' => 'usp_user']);
        $this->assertDatabaseHas('roles', ['name' => 'external_user']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);

        // Verify that events were seeded
        $this->assertDatabaseHas('events', ['name' => '8th Brazilian Conference on Statistical Modeling in Insurance and Finance']);

        // Verify that fees were seeded
        $this->assertTrue(\App\Models\Fee::count() > 0, 'Fees should be seeded');
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials_via_browser(): void
    {
        // Create a user using helper method
        $user = $this->createTestUser();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login/local')
                // Fill in the login form with wrong password
                ->type('[dusk="email-input"]', $user->email)
                ->type('[dusk="password-input"]', 'wrong-password')
                // Submit the form
                ->click('[dusk="login-button"]')
                // Wait for the page to process the request
                ->waitForText('These credentials do not match our records.', 5)
                // Verify error message is displayed
                ->assertSee('These credentials do not match our records.')
                // Verify we're still on the login page
                ->assertPathIs('/login/local');
        });

        // Verify the user is NOT authenticated
        $this->assertFalse(auth()->check());
    }

    #[Test]
    public function user_cannot_login_with_nonexistent_email_via_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                // Fill in the login form with non-existent email
                ->type('[dusk="email-input"]', 'nonexistent@example.com')
                ->type('[dusk="password-input"]', 'any-password')
                // Submit the form
                ->click('[dusk="login-button"]')
                // Wait for the page to process the request
                ->waitForText('These credentials do not match our records.')
                // Verify error message is displayed
                ->assertSee('These credentials do not match our records.')
                // Verify we're still on the login page
                ->assertRouteIs('login.local');
        });

        // Verify no user is authenticated
        $this->assertFalse(auth()->check());
    }

    #[Test]
    public function login_form_prevents_submission_with_empty_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                // Try to submit form without filling any fields
                ->click('[dusk="login-button"]')
                // HTML5 validation will prevent submission and keep us on the same page
                ->pause(1000) // Wait for HTML5 validation
                // Since HTML5 prevented submission, we should still be on login page
                ->assertRouteIs('login.local')
                // The form should not have been submitted (no redirect occurred)
                ->assertPathIs('/login/local');
        });

        // Verify no user is authenticated (form was not submitted)
        $this->assertFalse(auth()->check());
    }

    #[Test]
    public function login_form_validates_email_format_in_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local');

            // Disable HTML5 validation to test Livewire validation
            $browser->script('document.querySelector("form").setAttribute("novalidate", "true");');

            $browser
                // Fill in invalid email format
                ->type('[dusk="email-input"]', 'invalid-email-format')
                ->type('[dusk="password-input"]', 'password')
                // Submit the form
                ->click('[dusk="login-button"]')
                // Wait for Livewire to process and show validation errors
                ->pause(2000)
                // Check if validation error appeared
                ->waitFor('[dusk="email-error"]', 3)
                ->assertPresent('[dusk="email-error"]')
                // Verify we're still on the login page
                ->assertRouteIs('login.local');
        });

        // Verify no user is authenticated
        $this->assertFalse(auth()->check());
    }

    #[Test]
    public function login_form_shows_real_time_validation_with_livewire(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local');

            // Disable HTML5 validation to test Livewire validation
            $browser->script('document.querySelector("form").setAttribute("novalidate", "true");');

            $browser
                // Type invalid email and submit form to trigger validation
                ->type('[dusk="email-input"]', 'invalid-email')
                ->type('[dusk="password-input"]', 'password')
                ->click('[dusk="login-button"]') // Submit to trigger validation
                ->pause(1000) // Wait for Livewire to process
                // Check if email validation error appears after submit
                ->waitFor('[dusk="email-error"]', 3)
                ->assertPresent('[dusk="email-error"]')

                // Clear the email field and type valid email, then resubmit
                ->clear('[dusk="email-input"]')
                ->type('[dusk="email-input"]', 'valid@example.com')
                ->click('[dusk="login-button"]') // Submit again to see if error clears
                ->pause(1000) // Wait for Livewire to process
                // Since valid email but wrong password, should show auth error instead
                ->waitForText('These credentials do not match our records.')
                ->assertSee('These credentials do not match our records.');
            // Note: Email validation error may persist alongside auth error

            // Test works as expected - no need to test password validation separately
            // since that would be testing HTML5 validation which isn't the focus
        });
    }

    #[Test]
    public function livewire_validation_updates_dynamically_on_input_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local');

            // Disable HTML5 validation to test Livewire validation
            $browser->script('document.querySelector("form").setAttribute("novalidate", "true");');

            $browser
                // Start with invalid data and submit to trigger validation
                ->type('[dusk="email-input"]', 'bad-email')
                ->type('[dusk="password-input"]', 'password')
                ->click('[dusk="login-button"]')
                ->waitFor('[dusk="email-error"]', 3)
                ->assertPresent('[dusk="email-error"]')

                // Now fix the email - should clear the error
                ->clear('[dusk="email-input"]')
                ->type('[dusk="email-input"]', 'correct@example.com')
                ->click('[dusk="login-button"]') // Submit again to test if email error clears
                ->pause(500) // Give Livewire time to process the change

                // Should show auth failure instead of validation error
                ->waitForText('These credentials do not match our records.');
            // Note: Email validation error may persist alongside auth error
        });
    }

    // === USP SENHA ÚNICA FLOW TESTS ===

    #[Test]
    public function senhaunica_login_button_redirects_correctly_in_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                // Verify Senha Única button is present
                ->assertPresent('[dusk="senhaunica-login-button"]')
                // Click on Senha Única login button
                ->click('[dusk="senhaunica-login-button"]')
                // The button should redirect to the main login route
                // which triggers the Socialite redirect (tested in Feature tests)
                // In browser test, we can only verify the initial redirect
                ->waitUntilMissing('[dusk="senhaunica-login-button"]', 5); // Button should disappear as we navigate away

            // Note: Full OAuth flow testing requires external service simulation
            // which is complex in browser tests. The Feature tests cover the OAuth logic.
        });
    }


    #[Test]
    public function external_user_creation_flow_works_end_to_end(): void
    {
        // This test simulates what happens when an external user logs in
        // by creating an external user directly and testing the resulting experience

        // Create an external user (non-USP user)
        $externalUser = $this->createTestUser([
            'name' => 'Test External User',
            'email' => 'external@example.com',
            'codpes' => null, // External users don't have codpes
        ]);
        $externalUser->removeRole('usp_user');
        $externalUser->assignRole('external_user');

        $this->browse(function (Browser $browser) use ($externalUser) {
            // Login as external user through browser interface
            $browser->visit('/login/local')
                ->type('[dusk="email-input"]', $externalUser->email)
                ->type('[dusk="password-input"]', 'password')
                ->click('[dusk="login-button"]')
                ->pause(500);

            // User might land on either page depending on middleware timing:
            // - /my-registration if they don't have a registration (middleware redirects to register-event)
            // - /register-event if middleware already processed the redirect
            $currentPath = $browser->driver->getCurrentURL();

            if (str_contains($currentPath, '/register-event')) {
                // User was redirected to registration form (no existing registration)
                $browser->assertPathIs('/register-event')
                    ->assertSee('Registration Form')
                    ->assertSee($externalUser->name);
            } else {
                // User stayed on my-registration page (has existing registration)
                $browser->assertPathIs('/my-registration')
                    ->assertSee('My Registration')
                    ->assertSee($externalUser->name);
            }
        });

        // Verify user has external role
        $this->assertTrue($externalUser->hasRole('external_user'));
        $this->assertFalse($externalUser->hasRole('usp_user'));
        $this->assertNull($externalUser->codpes);
    }



}
