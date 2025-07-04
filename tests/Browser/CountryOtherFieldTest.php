<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class CountryOtherFieldTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
    }

    /**
     * Test that selecting "Other" in Document Country of Origin shows conditional text field
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_document_country_origin_other_field_appears(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin')
                ->assertPresent('#document_country_origin')
                ->select('#document_country_origin', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                ->assertVisible('[dusk="other-document-country-input"]')
                ->type('[dusk="other-document-country-input"]', 'Germany')
                ->assertValue('[dusk="other-document-country-input"]', 'Germany');
        });
    }

    /**
     * Test that selecting a regular country hides the Document Country of Origin conditional text field
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_document_country_origin_other_field_disappears(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin')
                ->select('#document_country_origin', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                ->assertVisible('[dusk="other-document-country-input"]')
                ->select('#document_country_origin', 'Brazil')
                ->waitUntilMissing('[dusk="other-document-country-input"]')
                ->assertDontSee('[dusk="other-document-country-input"]');
        });
    }

    /**
     * AC6: Test that selecting "Other" in Address Country shows conditional text field
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_address_country_other_field_appears(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('[dusk="country-select"]')
                ->assertPresent('[dusk="country-select"]')
                ->select('[dusk="country-select"]', 'OTHER')
                ->waitFor('[dusk="other-address-country-input"]')
                ->assertVisible('[dusk="other-address-country-input"]')
                ->type('[dusk="other-address-country-input"]', 'Switzerland')
                ->assertValue('[dusk="other-address-country-input"]', 'Switzerland');
        });
    }

    /**
     * AC6: Test that selecting a regular country hides the Address Country conditional text field
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_address_country_other_field_disappears(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('[dusk="country-select"]')
                ->select('[dusk="country-select"]', 'OTHER')
                ->waitFor('[dusk="other-address-country-input"]')
                ->assertVisible('[dusk="other-address-country-input"]')
                ->select('[dusk="country-select"]', 'United States')
                ->waitUntilMissing('[dusk="other-address-country-input"]')
                ->assertDontSee('[dusk="other-address-country-input"]');
        });
    }

    /**
     * AC6: Test successful form submission with "Other" countries for both fields
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_successful_form_submission_with_other_countries(): void
    {
        $user = User::factory()->create([
            'email' => 'international.other@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Fill all required fields for an international participant with "Other" countries

            // 1. Personal Information
            $browser->type('@full-name-input', 'Test User Other Countries')
                ->type('@nationality-input', 'Swiss')
                ->type('@date-of-birth-input', '01/31/1985')
                ->click('@gender-male');

            // 2. Identification Details (International - using "OTHER" country)
            $browser->select('@document-country-origin-select', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                ->type('[dusk="other-document-country-input"]', 'Switzerland')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->type('#passport_number', 'CH123456789')
                ->type('#passport_expiry_date', '01/31/2030');

            // 3. Contact Information (using "OTHER" country for address)
            $browser->type('@phone-number-input', '+41 44 123 4567')
                ->type('@street-address-input', 'Bahnhofstrasse 123')
                ->type('@city-input', 'Zurich')
                ->type('@state-province-input', 'ZH')
                ->select('[dusk="country-select"]', 'OTHER')
                ->waitFor('[dusk="other-address-country-input"]')
                ->type('[dusk="other-address-country-input"]', 'Switzerland')
                ->type('@postal-code-input', '8001');

            // 4. Professional Details
            $browser->type('@affiliation-input', 'ETH Zurich')
                ->click('@position-professor')
                ->click('@is-abe-member-no');

            // 5. Event Participation
            $browser->type('@arrival-date-input', '09/27/2025')
                ->type('@departure-date-input', '10/04/2025')
                ->check('@event-BCSMIF2025')
                ->click('@participation-format-in-person');

            // 6. Dietary Restrictions
            $browser->click('@dietary-restrictions-none')
                ->pause(300); // Allow Livewire to process

            // 7. Emergency Contact
            $browser->type('@emergency-contact-name-input', 'Emergency Contact Swiss')
                ->type('@emergency-contact-relationship-input', 'Spouse')
                ->type('@emergency-contact-phone-input', '+41 44 987 6543');

            // 8. Visa Support (specific to international participants)
            $browser->waitFor('@requires-visa-letter-yes')
                ->assertVisible('@requires-visa-letter-yes')
                ->assertVisible('@requires-visa-letter-no')
                ->click('@requires-visa-letter-no');

            // 9. Declaration
            $browser->check('@confirm-information-checkbox')
                ->check('@consent-data-processing-checkbox');

            // AC6: Submit the form and wait for processing
            $browser->click('@submit-registration-button')
                ->pause(3000) // Give time for Livewire validation and form submission
                ->waitForLocation('/my-registration', 30);

            // AC6: Verify successful redirection to my registrations
            $browser->assertPathIs('/my-registration');
        });
    }

    /**
     * AC6: Test validation fails when "Other" is selected but text field is left empty
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_validation_fails_when_other_country_fields_empty(): void
    {
        $user = User::factory()->create([
            'email' => 'validation.test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Fill most required fields but leave "Other" country fields empty

            // 1. Personal Information
            $browser->type('@full-name-input', 'Validation Test User')
                ->type('@nationality-input', 'Other Country')
                ->type('@date-of-birth-input', '01/31/1990')
                ->click('@gender-female');

            // 2. Identification Details - select OTHER but don't fill the text field
            $browser->select('@document-country-origin-select', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                // Don't type anything in the other-document-country-input field
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->type('#passport_number', 'XX123456789')
                ->type('#passport_expiry_date', '01/31/2030');

            // 3. Contact Information - select OTHER but don't fill the text field
            $browser->type('@phone-number-input', '+1 555 123-4567')
                ->type('@street-address-input', 'Test Street')
                ->type('@city-input', 'Test City')
                ->type('@state-province-input', 'Test State')
                ->select('[dusk="country-select"]', 'OTHER')
                ->waitFor('[dusk="other-address-country-input"]')
                // Don't type anything in the other-address-country-input field
                ->type('@postal-code-input', '12345');

            // 4. Professional Details
            $browser->type('@affiliation-input', 'Test University')
                ->click('@position-undergraduate')
                ->click('@is-abe-member-no');

            // 5. Event Participation
            $browser->type('@arrival-date-input', '09/28/2025')
                ->type('@departure-date-input', '10/03/2025')
                ->check('@event-BCSMIF2025')
                ->click('@participation-format-in-person');

            // 6. Dietary Restrictions
            $browser->click('@dietary-restrictions-none')
                ->pause(300); // Allow Livewire to process

            // 7. Emergency Contact
            $browser->type('@emergency-contact-name-input', 'Emergency Name')
                ->type('@emergency-contact-relationship-input', 'Parent')
                ->type('@emergency-contact-phone-input', '+1 555 987-6543');

            // 8. Visa Support
            $browser->waitFor('@requires-visa-letter-no')
                ->click('@requires-visa-letter-no');

            // 9. Declaration
            $browser->check('@confirm-information-checkbox')
                ->check('@consent-data-processing-checkbox');

            // AC6: Submit form without filling "Other" country text fields
            $browser->click('@submit-registration-button')
                ->pause(3000); // Wait for Livewire validation

            // AC6: Verify that validation fails and form stays on same page
            $browser->assertPathIs('/register-event')
                ->assertPresent('.text-red-600'); // Should show validation errors for empty "Other" fields
        });
    }

    /**
     * AC6: Test that "Other" fields show required attribute when "Other" is selected
     */
    #[Test]
    #[Group('dusk')]
    #[Group('country-other-field')]
    public function test_other_country_fields_become_required_when_selected(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Test Document Country of Origin "Other" field
            $browser->select('@document-country-origin-select', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                ->assertAttribute('[dusk="other-document-country-input"]', 'required', 'true')
                ->assertAttribute('[dusk="other-document-country-input"]', 'placeholder', __('Please specify the country'));

            // AC6: Test Address Country "Other" field
            $browser->select('[dusk="country-select"]', 'OTHER')
                ->waitFor('[dusk="other-address-country-input"]')
                ->assertAttribute('[dusk="other-address-country-input"]', 'required', 'true')
                ->assertAttribute('[dusk="other-address-country-input"]', 'placeholder', __('Please specify the country'));

            // AC6: Verify both fields can be filled with valid values
            $browser->type('[dusk="other-document-country-input"]', 'Netherlands')
                ->type('[dusk="other-address-country-input"]', 'Belgium');

            // AC6: Verify values are properly set
            $browser->assertValue('[dusk="other-document-country-input"]', 'Netherlands')
                ->assertValue('[dusk="other-address-country-input"]', 'Belgium');
        });
    }
}
