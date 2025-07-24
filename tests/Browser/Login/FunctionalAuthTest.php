<?php

namespace Tests\Browser\Login;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;
use Tests\Fakes\FakeSenhaunicaSocialiteProvider;

/**
 * Functional Dusk tests for authentication flows
 *
 * These tests focus on what works reliably in the browser environment:
 * - Page navigation
 * - Element visibility
 * - Basic form structure
 * - Database interactions via factories
 */
#[Group('browser')]
#[Group('functional')]
class FunctionalAuthTest extends DuskTestCase
{
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
    public function senhaunica_button_has_correct_attributes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login/local')
                ->assertPresent('[dusk="senhaunica-login-button"]')
                ->assertAttribute('[dusk="senhaunica-login-button"]', 'href', route('login'));
        });
    }

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
    public function external_user_can_be_created_via_factory(): void
    {
        // This tests the underlying authentication system without browser interaction
        $user = $this->createTestUser([
            'name' => 'Test External User',
        ]);

        $user->assignRole('external_user');

        $this->assertNotNull($user);
        $this->assertStringContainsString('@example.com', $user->email);
        $this->assertNull($user->codpes);
        $this->assertTrue($user->hasRole('external_user'));
        $this->assertFalse($user->hasRole('usp_user'));
    }

    #[Test]
    public function usp_user_can_be_created_via_factory(): void
    {
        // This tests the underlying authentication system without browser interaction
        $user = $this->createTestUser([
            'name' => 'Test USP User',
            'codpes' => '1234567',
        ]);

        $user->removeRole('external_user');
        $user->assignRole('usp_user');

        $this->assertNotNull($user);
        $this->assertStringContainsString('@example.com', $user->email);
        $this->assertEquals('1234567', $user->codpes);
        $this->assertTrue($user->hasRole('usp_user'));
        $this->assertFalse($user->hasRole('external_user'));
        $this->assertTrue($user->hasVerifiedEmail());
    }

    #[Test]
    public function fake_senhaunica_provider_works(): void
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
    public function user_factory_creates_valid_authenticated_user(): void
    {
        // Create and authenticate a user via factory - this tests the underlying auth system
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

    #[Test]
    public function registration_form_structure_is_correct(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');

            // Test form attributes
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
}
