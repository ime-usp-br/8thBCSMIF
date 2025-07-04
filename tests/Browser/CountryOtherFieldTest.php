<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CountryOtherFieldTest extends DuskTestCase
{
    /**
     * Test that selecting "Other" in Country of residence shows conditional text field
     */
    public function test_country_of_residence_other_field_appears(): void
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
     * Test that selecting a regular country hides the conditional text field
     */
    public function test_country_of_residence_other_field_disappears(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitFor('#document_country_origin')
                ->select('#document_country_origin', 'OTHER')
                ->waitFor('[dusk="other-document-country-input"]')
                ->assertVisible('[dusk="other-document-country-input"]')
                ->select('#document_country_origin', 'BR')
                ->waitUntilMissing('[dusk="other-document-country-input"]')
                ->assertDontSee('[dusk="other-document-country-input"]');
        });
    }
}
