<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class CountryListTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
    }

    /**
     * Test that country dropdown contains the Europe/North America focused list
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-list')]
    public function test_country_dropdown_contains_europe_north_america_focused_list(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin');

            // Verify the new countries are present in document country dropdown
            $expectedCountries = [
                'Azerbaijan',
                'Belgium',
                'Brazil',
                'Canada',
                'Chile',
                'France',
                'Germany',
                'Greece',
                'Italy',
                'Portugal',
                'United Arab Emirates',
                'United Kingdom',
                'United States',
                'Other',
            ];

            foreach ($expectedCountries as $country) {
                if ($country === 'Other') {
                    $browser->assertSelectHasOption('#document_country_origin', 'OTHER');
                } else {
                    $browser->assertSelectHasOption('#document_country_origin', $country);
                }
            }

            // Verify the same countries are in address country dropdown
            $browser->waitFor('[dusk="country-select"]');

            foreach ($expectedCountries as $country) {
                if ($country === 'Other') {
                    $browser->assertSelectHasOption('[dusk="country-select"]', 'OTHER');
                } else {
                    $browser->assertSelectHasOption('[dusk="country-select"]', $country);
                }
            }
        });
    }

    /**
     * Test that old Latin American countries are no longer present (except Brazil and Chile)
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-list')]
    public function test_old_latin_american_countries_removed(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin');

            // Verify old countries are no longer present
            $removedCountries = [
                'AR', // Argentina
                'CO', // Colombia
                'MX', // Mexico
                'PE', // Peru
                'UY', // Uruguay
                'VE', // Venezuela
            ];

            foreach ($removedCountries as $countryCode) {
                $browser->assertSelectMissingOption('#document_country_origin', $countryCode);
                $browser->assertSelectMissingOption('[dusk="country-select"]', $countryCode);
            }
        });
    }

    /**
     * Test that countries display with bilingual format
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-list')]
    public function test_countries_display_bilingual_format(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin');

            // Check that some countries display in bilingual format
            // The exact text depends on current locale, but should include both Portuguese and English
            $browser->assertSeeIn('#document_country_origin', 'Brasil / Brazil')
                ->assertSeeIn('#document_country_origin', 'Estados Unidos / United States')
                ->assertSeeIn('#document_country_origin', 'Alemanha / Germany')
                ->assertSeeIn('#document_country_origin', 'França / France');

            // Same check for address country dropdown
            $browser->assertSeeIn('[dusk="country-select"]', 'Brasil / Brazil')
                ->assertSeeIn('[dusk="country-select"]', 'Estados Unidos / United States')
                ->assertSeeIn('[dusk="country-select"]', 'Alemanha / Germany')
                ->assertSeeIn('[dusk="country-select"]', 'França / France');
        });
    }

    /**
     * Test that countries are ordered alphabetically by English name
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-list')]
    public function test_countries_ordered_alphabetically_by_english_name(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin');

            // Get all option values and verify alphabetical order by English name
            $options = $browser->elements('#document_country_origin option');
            $countries = [];

            foreach ($options as $option) {
                $value = $option->getAttribute('value');
                if (! empty($value)) {
                    $countries[] = $value;
                }
            }

            // Remove the first empty option and OTHER (which should be last)
            $countries = array_filter($countries, fn ($country) => ! empty($country) && $country !== 'OTHER');

            // Verify alphabetical order
            $expectedOrder = [
                'Azerbaijan',
                'Belgium',
                'Brazil',
                'Canada',
                'Chile',
                'France',
                'Germany',
                'Greece',
                'Italy',
                'Portugal',
                'United Arab Emirates',
                'United Kingdom',
                'United States',
            ];

            $this->assertEquals($expectedOrder, array_values($countries));
        });
    }
}
