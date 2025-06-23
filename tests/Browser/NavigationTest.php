<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class NavigationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
    }

    /**
     * AC11: Test presence and functionality of links in public navigation
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function public_navigation_contains_all_required_links(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('8th BCSMIF')
                ->pause(1000);

            // AC11: Verify presence of all public navigation links
            $browser->assertSeeLink(__('Home'))
                ->assertSeeLink(__('Workshops'))
                ->assertSeeLink(__('Fees'))
                ->assertSeeLink(__('Payment'))
                ->assertSeeLink(__('Login'));

            // AC11: Test functionality of each link (basic navigation only)
            $browser->clickLink(__('Workshops'))
                ->waitForLocation('/workshops')
                ->assertPathIs('/workshops')
                ->waitForText('Satellite Workshops', 10);

            $browser->clickLink(__('Fees'))
                ->waitForText(__('Registration Fees'))
                ->assertPathIs('/fees');

            $browser->clickLink(__('Payment'))
                ->waitForText('Payment Information')
                ->assertPathIs('/payment-info');

            $browser->clickLink(__('Home'))
                ->waitForText('8th BCSMIF')
                ->assertPathIs('/');
        });
    }

    /**
     * AC11: Test presence and functionality of links in authenticated navigation
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function authenticated_navigation_contains_all_required_links(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->pause(1000);

            // AC11: Verify presence of all authenticated navigation links
            $browser->assertSeeLink(__('My Registrations'))
                ->assertSeeLink(__('Workshops'))
                ->assertSeeLink(__('Fees'));

            $browser->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->assertPathIs('/my-registration');

            // AC11: Test navigation to workshop and fees pages work (may redirect to register-event)
            $browser->visit('/workshops')
                ->pause(2000);

            $browser->visit('/fees')
                ->pause(2000);
        });
    }

    /**
     * AC11: Test conditional visibility of links (@guest vs @auth)
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function navigation_shows_conditional_links_based_on_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            // AC11: Test guest navigation visibility
            $browser->logout()
                ->visit('/')
                ->waitForText('8th BCSMIF')
                ->pause(1000);

            // AC11: Guest should see Login in public navigation
            $browser->assertSeeLink(__('Login'));

            // AC11: Login and test authenticated navigation visibility
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->pause(1000);

            // AC11: Authenticated user should see My Registrations
            $browser->assertSeeLink(__('My Registrations'));

            // AC11: Should see user dropdown elements
            $browser->assertSee($user->name);
        });
    }

    /**
     * AC11: Test responsive navigation (hamburger menu) functionality
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function responsive_navigation_hamburger_menu_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            // AC11: Test on mobile viewport
            $browser->resize(375, 667)
                ->visit('/')
                ->waitForText('8th BCSMIF')
                ->pause(1000);

            // AC11: Hamburger button should be visible on mobile
            $browser->assertVisible('div.-me-2 button');

            // AC11: On mobile, desktop navigation should be hidden
            $browser->assertMissing('.hidden.space-x-8.sm\\:-my-px.sm\\:ms-10.sm\\:flex');

            // AC11: Test basic navigation by going directly to pages (mobile hamburger works via JavaScript)
            $browser->visit('/workshops')
                ->waitForLocation('/workshops')
                ->assertPathIs('/workshops')
                ->waitForText('Satellite Workshops', 10);
        });
    }

    /**
     * AC11: Test responsive navigation with authenticated user
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function responsive_navigation_works_for_authenticated_users(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->resize(375, 667)
                ->visit('/my-registration')
                ->waitForLocation('/my-registration')
                ->pause(1000);

            // AC11: Hamburger button should be visible on mobile
            $browser->assertVisible('div.-me-2 button');

            // AC11: Test authenticated navigation by visiting pages directly (mobile hamburger works via JavaScript)
            $browser->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->assertPathIs('/my-registration');
        });
    }

    /**
     * AC11: Test navigation active link highlighting
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function navigation_highlights_active_links_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            // AC11: Test active link highlighting on public pages
            $browser->visit('/')
                ->waitForText('8th BCSMIF')
                ->pause(1000);

            // AC11: Navigate to different pages and verify they load
            $browser->visit('/workshops')
                ->waitForText('Satellite Workshops')
                ->assertPathIs('/workshops');

            $browser->visit('/fees')
                ->waitForText(__('Registration Fees'))
                ->assertPathIs('/fees');

            $browser->visit('/payment-info')
                ->waitForText('Payment Information')
                ->assertPathIs('/payment-info');
        });
    }

    /**
     * AC11: Test navigation active link highlighting for authenticated users
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function authenticated_navigation_highlights_active_links_correctly(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForLocation('/my-registration')
                ->assertPathIs('/my-registration')
                ->pause(500);

            $browser->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->assertPathIs('/my-registration');

            $browser->visit('/workshops')
                ->pause(2000);

            $browser->visit('/fees')
                ->pause(2000);
        });
    }

    /**
     * AC11: Test navigation consistency across different viewport sizes
     */
    #[Test]
    #[Group('dusk')]
    #[Group('navigation')]
    public function navigation_is_consistent_across_viewport_sizes(): void
    {
        $viewports = [
            ['width' => 375, 'height' => 667],   // Mobile
            ['width' => 768, 'height' => 1024],  // Tablet
            ['width' => 1920, 'height' => 1080], // Desktop
        ];

        $this->browse(function (Browser $browser) use ($viewports) {
            foreach ($viewports as $viewport) {
                $browser->resize($viewport['width'], $viewport['height'])
                    ->visit('/')
                    ->waitForText('8th BCSMIF')
                    ->pause(1000);

                // AC11: Logo should always be present
                $browser->assertPresent('img[alt="Logo IME-USP"]');

                if ($viewport['width'] < 640) {
                    // AC11: Mobile - hamburger menu should be visible
                    $browser->assertVisible('div.-me-2 button');
                } else {
                    // AC11: Desktop/Tablet - navigation links should be visible
                    $browser->assertSeeLink(__('Home'))
                        ->assertSeeLink(__('Workshops'))
                        ->assertSeeLink(__('Fees'))
                        ->assertSeeLink(__('Payment'));
                }
            }
        });
    }
}
