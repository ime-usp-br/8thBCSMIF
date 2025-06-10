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

    /**
     * AC3: Teste Dusk verifica a lógica de exibição condicional do campo "Outro" para Gênero,
     * Cargo/Posição e Restrições Alimentares, e se a validação frontend correspondente é acionada.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_other_gender_field_conditionally(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC3: Initially, other gender field should not be visible
            $browser->assertMissing('input[wire\\:model="other_gender"]');

            // AC3: Select 'Other' gender option
            $browser->click('@gender-other')
                ->waitFor('input[wire\\:model="other_gender"]')
                ->assertVisible('input[wire\\:model="other_gender"]')
                ->assertAttribute('input[wire\\:model="other_gender"]', 'required', 'true')
                ->assertAttribute('input[wire\\:model="other_gender"]', 'placeholder', __('Please specify'));

            // AC3: Select different gender option and verify other field disappears
            $browser->click('@gender-male')
                ->waitUntilMissing('input[wire\\:model="other_gender"]')
                ->assertMissing('input[wire\\:model="other_gender"]');

            // AC3: Select 'Other' again to verify it reappears
            $browser->click('@gender-other')
                ->waitFor('input[wire\\:model="other_gender"]')
                ->assertVisible('input[wire\\:model="other_gender"]');
        });
    }

    /**
     * AC3: Teste Dusk verifica a lógica de exibição condicional do campo "Outro" para Posição
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_other_position_field_conditionally(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC3: Initially, other position field should not be visible
            $browser->assertMissing('input[wire\\:model="other_position"]');

            // AC3: Select 'Other' position option
            $browser->click('@position-other')
                ->waitFor('input[wire\\:model="other_position"]')
                ->assertVisible('input[wire\\:model="other_position"]')
                ->assertAttribute('input[wire\\:model="other_position"]', 'required', 'true')
                ->assertAttribute('input[wire\\:model="other_position"]', 'placeholder', __('Please specify'));

            // AC3: Select different position option and verify other field disappears
            $browser->click('@position-professor')
                ->waitUntilMissing('input[wire\\:model="other_position"]')
                ->assertMissing('input[wire\\:model="other_position"]');

            // AC3: Select 'Other' again to verify it reappears
            $browser->click('@position-other')
                ->waitFor('input[wire\\:model="other_position"]')
                ->assertVisible('input[wire\\:model="other_position"]');
        });
    }

    /**
     * AC3: Teste Dusk verifica a lógica de exibição condicional do campo "Outro" para Restrições Alimentares
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_other_dietary_restrictions_field_conditionally(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC3: Initially, other dietary restrictions field should not be visible
            $browser->assertMissing('input[wire\\:model="other_dietary_restrictions"]');

            // AC3: Select 'Other' dietary restrictions option
            $browser->click('@dietary-restrictions-other')
                ->waitFor('input[wire\\:model="other_dietary_restrictions"]')
                ->assertVisible('input[wire\\:model="other_dietary_restrictions"]')
                ->assertAttribute('input[wire\\:model="other_dietary_restrictions"]', 'required', 'true')
                ->assertAttribute('input[wire\\:model="other_dietary_restrictions"]', 'placeholder', __('Please specify'));

            // AC3: Select different dietary restrictions option and verify other field disappears
            $browser->click('@dietary-restrictions-vegetarian')
                ->waitUntilMissing('input[wire\\:model="other_dietary_restrictions"]')
                ->assertMissing('input[wire\\:model="other_dietary_restrictions"]');

            // AC3: Select 'Other' again to verify it reappears
            $browser->click('@dietary-restrictions-other')
                ->waitFor('input[wire\\:model="other_dietary_restrictions"]')
                ->assertVisible('input[wire\\:model="other_dietary_restrictions"]');
        });
    }

    /**
     * AC3: Teste Dusk verifica validação frontend dos campos "Outro" quando são obrigatórios
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_validates_other_fields_when_required(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC3: Test all three "Other" fields become required when selected

            // Gender "Other" field
            $browser->click('@gender-other')
                ->waitFor('input[wire\\:model="other_gender"]')
                ->assertAttribute('input[wire\\:model="other_gender"]', 'required', 'true');

            // Position "Other" field
            $browser->click('@position-other')
                ->waitFor('input[wire\\:model="other_position"]')
                ->assertAttribute('input[wire\\:model="other_position"]', 'required', 'true');

            // Dietary Restrictions "Other" field
            $browser->click('@dietary-restrictions-other')
                ->waitFor('input[wire\\:model="other_dietary_restrictions"]')
                ->assertAttribute('input[wire\\:model="other_dietary_restrictions"]', 'required', 'true');

            // AC3: Test that the "Other" fields can be filled with valid values
            $browser->type('input[wire\\:model="other_gender"]', 'Non-binary')
                ->type('input[wire\\:model="other_position"]', 'Data Scientist')
                ->type('input[wire\\:model="other_dietary_restrictions"]', 'Lactose intolerant');

            // AC3: Verify values are properly set
            $browser->assertValue('input[wire\\:model="other_gender"]', 'Non-binary')
                ->assertValue('input[wire\\:model="other_position"]', 'Data Scientist')
                ->assertValue('input[wire\\:model="other_dietary_restrictions"]', 'Lactose intolerant');
        });
    }
}
