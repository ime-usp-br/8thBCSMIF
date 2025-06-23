<?php

namespace Tests\Browser;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

#[Group('dusk')]
#[Group('modification')]
#[Group('interface')]
class RegistrationModificationInterfaceTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class);
        $this->seed(FeesTableSeeder::class);
    }

    #[Test]
    public function modify_registration_page_loads_correctly(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Interface Test User',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Modify Registration'))
                ->assertSee(__('Current Registration'))
                ->assertSee(__('Add New Events'))
                ->assertSee(__('Back to My Registration'));
        });
    }

    #[Test]
    public function displays_current_registration_overview(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Current Overview Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $raaEvent = Event::where('code', 'RAA2025')->first();

        $registration->events()->attach([
            $bcsmifEvent->code => ['price_at_registration' => 600.00],
            $raaEvent->code => ['price_at_registration' => 250.00],
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Current Registration'))
                ->assertSee('BCSMIF2025')
                ->assertSee('RAA2025')
                ->assertSee('R$ 850,00'); // 600 + 250
        });
    }

    #[Test]
    public function shows_only_available_events_for_selection(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Available Events Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $raaEvent = Event::where('code', 'RAA2025')->first();

        // Only attach BCSMIF, so RAA should be available
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Add New Events'))
                // Should see RAA checkbox (available)
                ->assertSee('RAA2025')
                ->assertPresent('input[value="RAA2025"]')
                // Should not see BCSMIF checkbox (already selected)
                ->assertDontSee('input[value="BCSMIF2025"]');
        });
    }

    #[Test]
    public function displays_pending_payment_warning(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Pending Payment Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        // Add pending payment
        $registration->payments()->create([
            'amount' => 300.00,
            'status' => 'pending_approval',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Payment Under Review'))
                ->assertSee(__('You have payments that are currently under administrative review'));
        });
    }

    #[Test]
    public function financial_summary_appears_when_events_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Financial Summary Test',
            'email' => $user->email,
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Add New Events'))

                // Initially no financial summary
                ->assertDontSee(__('Financial Summary'))

                // Select RAA event
                ->check('input[value="RAA2025"]')
                ->waitForText(__('Financial Summary'))

                // Verify summary sections appear
                ->assertSee(__('Original Value'))
                ->assertSee(__('Already Paid'))
                ->assertSee(__('Cost of New Items'))
                ->assertSee(__('Total to Pay Now'))
                ->assertSee('R$ 600,00'); // Original value
        });
    }

    #[Test]
    public function confirm_changes_button_appears_when_events_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Confirm Button Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Add New Events'))

                // Initially no confirm button
                ->assertDontSee(__('Confirm Changes'))

                // Select RAA event
                ->check('input[value="RAA2025"]')
                ->waitForText(__('Confirm Changes'))
                ->assertSee(__('Confirm Changes'));
        });
    }

    #[Test]
    public function financial_summary_updates_when_multiple_events_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Multiple Events Test',
            'email' => $user->email,
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Add New Events'))

                // Select first additional event
                ->check('input[value="RAA2025"]')
                ->waitForText(__('Financial Summary'))
                ->pause(1000) // Wait for calculation

                // Check if we have other available events to select
                ->script('
                    const otherCheckboxes = Array.from(document.querySelectorAll("input[type=checkbox]"))
                        .filter(cb => cb.value !== "RAA2025" && cb.value !== "BCSMIF2025");
                    if (otherCheckboxes.length > 0) {
                        otherCheckboxes[0].click();
                    }
                ')
                ->pause(1000) // Wait for recalculation
                ->assertSee(__('Financial Summary'));
        });
    }

    #[Test]
    public function back_button_navigates_to_my_registrations(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Back Button Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Back to My Registration'))
                ->click('a[href="'.route('registrations.my').'"]')
                ->waitForText(__('My Registrations'))
                ->assertPathIs('/my-registration');
        });
    }

    #[Test]
    public function displays_no_events_message_when_all_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'All Events Test',
            'email' => $user->email,
        ]);

        // Attach all available events
        $allEvents = Event::all();
        $eventData = [];
        foreach ($allEvents as $event) {
            $eventData[$event->code] = ['price_at_registration' => 100.00];
        }
        $registration->events()->attach($eventData);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('All Events Selected'))
                ->assertSee(__('You are already registered for all available events'));
        });
    }

    #[Test]
    public function handles_unauthenticated_access(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/my-registration/modify')
                ->waitForLocation('/login/local')
                ->assertPathIs('/login/local');
        });
    }

    #[Test]
    public function handles_access_without_registration(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('No Registration Found'))
                ->assertSee(__('You need to register for an event first'))
                ->assertSee(__('Register for Event'));
        });
    }

    #[Test]
    public function event_selection_checkbox_functionality(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Checkbox Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Add New Events'))

                // Test checking and unchecking
                ->assertNotChecked('input[value="RAA2025"]')
                ->check('input[value="RAA2025"]')
                ->assertChecked('input[value="RAA2025"]')
                ->waitForText(__('Financial Summary'))

                ->uncheck('input[value="RAA2025"]')
                ->assertNotChecked('input[value="RAA2025"]')
                ->pause(500)
                ->assertDontSee(__('Confirm Changes'));
        });
    }

    #[Test]
    public function responsive_layout_works_on_mobile(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Mobile Test',
            'email' => $user->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->resize(375, 667) // iPhone SE viewport
                ->loginAs($user)
                ->visit('/my-registration/modify')
                ->waitForText(__('Modify Registration'))
                ->assertSee(__('Current Registration'))
                ->assertSee(__('Add New Events'))

                // Test mobile-specific layout elements
                ->check('input[value="RAA2025"]')
                ->waitForText(__('Financial Summary'))
                ->assertSee(__('Confirm Changes'));
        });
    }
}
