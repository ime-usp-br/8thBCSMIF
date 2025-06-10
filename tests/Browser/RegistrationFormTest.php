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

    /**
     * AC2: Teste Dusk verifica a lógica de exibição condicional dos campos de Identificação
     * (CPF/RG para Brasil, Passaporte para outros) e se a validação frontend correspondente é acionada.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_conditional_identification_fields_for_brazilian_users(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC2: Initially should show BR fields (CPF/RG) since BR is default
            $browser->assertVisible('@cpf-input')
                ->assertVisible('@rg-number-input')
                ->assertMissing('input[name="passport_number"]')
                ->assertMissing('input[name="passport_expiry_date"]');

            // AC2: Change to international country and verify passport fields appear
            $browser->select('@document-country-origin-select', 'US')
                ->waitUntilMissing('@cpf-input')
                ->waitUntilMissing('@rg-number-input')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->assertVisible('#passport_number')
                ->assertVisible('#passport_expiry_date');

            // AC2: Change back to Brazil and verify CPF/RG fields reappear
            $browser->select('@document-country-origin-select', 'BR')
                ->waitFor('@cpf-input')
                ->waitFor('@rg-number-input')
                ->waitUntilMissing('#passport_number')
                ->waitUntilMissing('#passport_expiry_date')
                ->assertVisible('@cpf-input')
                ->assertVisible('@rg-number-input');
        });
    }

    /**
     * AC2: Teste Dusk verifica a validação frontend dos campos condicionais de identificação
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_validates_conditional_identification_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC2: Test that Brazilian fields are required when BR is selected
            $browser->select('@document-country-origin-select', 'BR')
                ->waitFor('@cpf-input')
                ->waitFor('@rg-number-input');

            // AC2: Verify CPF field has required attribute when Brazilian
            $browser->assertAttribute('@cpf-input', 'required', 'true')
                ->assertAttribute('@rg-number-input', 'required', 'true');

            // AC2: Test that passport fields are required when international country is selected
            $browser->select('@document-country-origin-select', 'US')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date');

            // AC2: Verify passport fields have required attribute when international
            $browser->assertAttribute('#passport_number', 'required', 'true')
                ->assertAttribute('#passport_expiry_date', 'required', 'true');

            // AC2: Switch back to Brazil and verify CPF/RG are required again
            $browser->select('@document-country-origin-select', 'BR')
                ->waitFor('@cpf-input')
                ->waitFor('@rg-number-input')
                ->assertAttribute('@cpf-input', 'required', 'true')
                ->assertAttribute('@rg-number-input', 'required', 'true');
        });
    }
}
