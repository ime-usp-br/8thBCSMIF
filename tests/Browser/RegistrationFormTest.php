<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class RegistrationFormTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
    }

    /**
     * AC1: Teste Dusk verifica a presença de todos os campos fixos do formulário de inscrição
     * (Nome, Email, etc.) e que o e-mail do usuário logado é pré-preenchido.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_all_required_fields_and_prefills_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC1: Verify all fixed fields are present
            // 1. Personal Information
            $browser->assertVisible('@full-name-input')
                ->assertVisible('@nationality-input')
                ->assertVisible('@date-of-birth-input')
                ->assertVisible('@gender-male')
                ->assertVisible('@gender-female')
                ->assertVisible('@gender-other')
                ->assertVisible('@gender-prefer-not-to-say');

            // 2. Identification Details
            $browser->assertVisible('@document-country-origin-select')
                ->assertVisible('@cpf-input') // Initially visible for BR default
                ->assertVisible('@rg-number-input'); // Initially visible for BR default

            // 3. Contact Information
            $browser->assertVisible('@email-input')
                ->assertVisible('@phone-number-input')
                ->assertVisible('@street-address-input')
                ->assertVisible('@city-input')
                ->assertVisible('@state-province-input')
                ->assertVisible('@country-select')
                ->assertVisible('@postal-code-input');

            // 4. Professional Details
            $browser->assertVisible('@affiliation-input')
                ->assertVisible('@position-undergraduate')
                ->assertVisible('@position-graduate')
                ->assertVisible('@position-postgraduate')
                ->assertVisible('@position-professor')
                ->assertVisible('@position-professional')
                ->assertVisible('@position-other')
                ->assertVisible('@is-abe-member-yes')
                ->assertVisible('@is-abe-member-no');

            // 5. Event Participation
            $browser->assertVisible('@arrival-date-input')
                ->assertVisible('@departure-date-input')
                ->assertVisible('@event-BCSMIF2025')
                ->assertVisible('@event-RAA2025')
                ->assertVisible('@event-WDA2025')
                ->assertVisible('@participation-format-in-person')
                ->assertVisible('@participation-format-online')
                ->assertVisible('@transport-gru')
                ->assertVisible('@transport-usp');

            // 6. Dietary Restrictions
            $browser->assertVisible('@dietary-restrictions-none')
                ->assertVisible('@dietary-restrictions-vegetarian')
                ->assertVisible('@dietary-restrictions-vegan')
                ->assertVisible('@dietary-restrictions-gluten-free')
                ->assertVisible('@dietary-restrictions-other');

            // 7. Emergency Contact
            $browser->assertVisible('@emergency-contact-name-input')
                ->assertVisible('@emergency-contact-relationship-input')
                ->assertVisible('@emergency-contact-phone-input');

            // 8. Declaration
            $browser->assertVisible('@confirm-information-checkbox')
                ->assertVisible('@consent-data-processing-checkbox');

            // Submit button
            $browser->assertVisible('@submit-registration-button');

            // AC1: Verify that the logged user's email is pre-filled
            $browser->assertValue('@email-input', $user->email);
        });
    }
}
