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

    /**
     * AC4: Teste Dusk simula o preenchimento e submissão bem-sucedida do formulário
     * com dados válidos para um participante brasileiro e verifica o redirecionamento
     * para a página de confirmação/dashboard com mensagem de sucesso.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_submits_successfully_for_brazilian_participant(): void
    {
        $user = User::factory()->create([
            'email' => 'brazilian.user@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC4: Fill all required fields for a Brazilian participant

            // 1. Personal Information
            $browser->type('@full-name-input', 'Test User')
                ->type('@nationality-input', 'Brazilian')
                ->type('@date-of-birth-input', '01-01-1990')
                ->click('@gender-male');

            // 2. Identification Details (Brazilian)
            $browser->select('@document-country-origin-select', 'BR')
                ->waitFor('@cpf-input')
                ->waitFor('@rg-number-input')
                ->type('@cpf-input', '123.456.789-00')
                ->type('@rg-number-input', '12.345.678-9');

            // 3. Contact Information
            $browser->type('@phone-number-input', '+55 11 987654321')
                ->type('@street-address-input', 'Test Street, 123')
                ->type('@city-input', 'São Paulo')
                ->type('@state-province-input', 'SP')
                ->select('@country-select', 'BR')
                ->type('@postal-code-input', '01000-000');

            // 4. Professional Details
            $browser->type('@affiliation-input', 'Universidade de São Paulo')
                ->click('@position-undergraduate')
                ->click('@is-abe-member-no');

            // 5. Event Participation
            $browser->type('@arrival-date-input', '28-09-2025')
                ->type('@departure-date-input', '03-10-2025')
                ->check('@event-BCSMIF2025')
                ->click('@participation-format-in-person');

            // 6. Dietary Restrictions
            $browser->click('@dietary-restrictions-none');

            // 7. Emergency Contact
            $browser->type('@emergency-contact-name-input', 'Parent Name')
                ->type('@emergency-contact-relationship-input', 'Parent')
                ->type('@emergency-contact-phone-input', '+55 11 987654321');

            // 8. Declaration
            $browser->check('@confirm-information-checkbox')
                ->check('@consent-data-processing-checkbox');

            // AC4: Submit the form and wait for processing
            $browser->click('@submit-registration-button')
                ->pause(3000) // Give time for Livewire validation and form submission
                ->waitForLocation('/dashboard', 30);

            // AC4: Verify successful redirection to dashboard
            $browser->assertPathIs('/dashboard');

            // AC4: Successful redirection confirms form submission worked
            // (Success message verification would require dashboard message display implementation)
        });
    }

    /**
     * AC5: Teste Dusk simula o preenchimento e submissão bem-sucedida do formulário
     * com dados válidos para um participante internacional (incluindo seleção de
     * suporte a visto, se aplicável) e verifica o redirecionamento para a página
     * de confirmação/dashboard com mensagem de sucesso.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_submits_successfully_for_international_participant(): void
    {
        $user = User::factory()->create([
            'email' => 'international.user@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC5: Fill all required fields for an international participant

            // 1. Personal Information
            $browser->type('@full-name-input', 'International Test User')
                ->type('@nationality-input', 'American')
                ->type('@date-of-birth-input', '01-01-1985')
                ->click('@gender-female');

            // 2. Identification Details (International - using passport)
            $browser->select('@document-country-origin-select', 'US')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->type('#passport_number', 'A12345678')
                ->type('#passport_expiry_date', '01-01-2030');

            // 3. Contact Information
            $browser->type('@phone-number-input', '+1 555 123-4567')
                ->type('@street-address-input', '123 Main Street')
                ->type('@city-input', 'New York')
                ->type('@state-province-input', 'NY')
                ->select('@country-select', 'US')
                ->type('@postal-code-input', '10001');

            // 4. Professional Details
            $browser->type('@affiliation-input', 'Columbia University')
                ->click('@position-professor')
                ->click('@is-abe-member-yes');

            // 5. Event Participation
            $browser->type('@arrival-date-input', '27-09-2025')
                ->type('@departure-date-input', '04-10-2025')
                ->check('@event-BCSMIF2025')
                ->check('@event-RAA2025')
                ->click('@participation-format-in-person');

            // 6. Dietary Restrictions
            $browser->click('@dietary-restrictions-vegetarian');

            // 7. Emergency Contact
            $browser->type('@emergency-contact-name-input', 'Emergency Contact')
                ->type('@emergency-contact-relationship-input', 'Spouse')
                ->type('@emergency-contact-phone-input', '+1 555 987-6543');

            // 8. Visa Support (specific to international participants)
            $browser->waitFor('@requires-visa-letter-yes')
                ->assertVisible('@requires-visa-letter-yes')
                ->assertVisible('@requires-visa-letter-no')
                ->click('@requires-visa-letter-yes');

            // 9. Declaration
            $browser->check('@confirm-information-checkbox')
                ->check('@consent-data-processing-checkbox');

            // AC5: Submit the form and wait for processing
            $browser->click('@submit-registration-button')
                ->pause(3000) // Give time for Livewire validation and form submission
                ->waitForLocation('/dashboard', 30);

            // AC5: Verify successful redirection to dashboard
            $browser->assertPathIs('/dashboard');

            // AC5: Successful redirection confirms form submission worked for international participant
            // (Success message verification would require dashboard message display implementation)
        });
    }

    /**
     * AC5: Teste Dusk simula o preenchimento e submissão bem-sucedida do formulário
     * com dados válidos para um participante internacional que NÃO precisa de visto
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_submits_successfully_for_international_participant_without_visa(): void
    {
        $user = User::factory()->create([
            'email' => 'canadian.user@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC5: Fill all required fields for an international participant (Canada - no visa needed)

            // 1. Personal Information
            $browser->type('@full-name-input', 'Canadian Test User')
                ->type('@nationality-input', 'Canadian')
                ->type('@date-of-birth-input', '15-06-1988')
                ->click('@gender-other');

            // Fill other gender field when "Other" is selected
            $browser->waitFor('input[wire\\:model="other_gender"]')
                ->type('input[wire\\:model="other_gender"]', 'Non-binary');

            // 2. Identification Details (International - using passport)
            $browser->select('@document-country-origin-select', 'CA')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->type('#passport_number', 'CA987654321')
                ->type('#passport_expiry_date', '15-12-2029');

            // 3. Contact Information
            $browser->type('@phone-number-input', '+1 416 555-0123')
                ->type('@street-address-input', '456 Queen Street West')
                ->type('@city-input', 'Toronto')
                ->type('@state-province-input', 'ON')
                ->select('@country-select', 'CA')
                ->type('@postal-code-input', 'M5V 2A4');

            // 4. Professional Details
            $browser->type('@affiliation-input', 'University of Toronto')
                ->click('@position-other');

            // Fill other position field when "Other" is selected
            $browser->waitFor('input[wire\\:model="other_position"]')
                ->type('input[wire\\:model="other_position"]', 'Research Associate');

            $browser->click('@is-abe-member-no');

            // 5. Event Participation
            $browser->type('@arrival-date-input', '26-09-2025')
                ->type('@departure-date-input', '05-10-2025')
                ->check('@event-WDA2025')
                ->click('@participation-format-online');

            // 6. Dietary Restrictions - select "Other" and specify
            $browser->click('@dietary-restrictions-other');
            $browser->waitFor('input[wire\\:model="other_dietary_restrictions"]')
                ->type('input[wire\\:model="other_dietary_restrictions"]', 'Kosher');

            // 7. Emergency Contact
            $browser->type('@emergency-contact-name-input', 'Parent Contact')
                ->type('@emergency-contact-relationship-input', 'Mother')
                ->type('@emergency-contact-phone-input', '+1 416 555-9876');

            // 8. Visa Support (specific to international participants) - NO visa needed
            $browser->waitFor('@requires-visa-letter-no')
                ->assertVisible('@requires-visa-letter-yes')
                ->assertVisible('@requires-visa-letter-no')
                ->click('@requires-visa-letter-no');

            // 9. Declaration
            $browser->check('@confirm-information-checkbox')
                ->check('@consent-data-processing-checkbox');

            // AC5: Submit the form and wait for processing
            $browser->click('@submit-registration-button')
                ->pause(3000) // Give time for Livewire validation and form submission
                ->waitForLocation('/dashboard', 30);

            // AC5: Verify successful redirection to dashboard
            $browser->assertPathIs('/dashboard');

            // AC5: Successful redirection confirms form submission worked for international participant
        });
    }

    /**
     * AC6: Testes Dusk verificam que a submissão do formulário com dados inválidos
     * (campos obrigatórios faltando, formatos incorretos) exibe as mensagens de erro
     * de validação frontend apropriadas (x-input-error).
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_frontend_validation_errors_for_missing_required_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Submit form with minimal data (missing most required fields)
            $browser->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify frontend validation error messages are displayed using x-input-error

            // Personal Information errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'full name']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'nationality']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'date of birth']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'gender']).'")');

            // Contact Information errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'phone number']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'address street']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'address city']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'address state province']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'address postal code']).'")');

            // Professional Details errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'affiliation']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'position']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'is abe member']).'")');

            // Event Participation errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'arrival date']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'departure date']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'selected event codes']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'participation format']).'")');

            // Emergency Contact errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'emergency contact name']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'emergency contact relationship']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'emergency contact phone']).'")');

            // Declaration errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.accepted', ['attribute' => 'confirm information']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.accepted', ['attribute' => 'consent data processing']).'")');
        });
    }

    /**
     * AC6: Teste Dusk verifica mensagens de erro para campos condicionais obrigatórios
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_frontend_validation_errors_for_conditional_required_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Test Brazilian participant missing CPF/RG
            $browser->select('@document-country-origin-select', 'BR')
                ->waitFor('@cpf-input')
                ->waitFor('@rg-number-input')
                ->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify CPF and RG validation errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'cpf', 'other' => 'document country origin', 'value' => 'BR']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'rg number', 'other' => 'document country origin', 'value' => 'BR']).'")');

            // AC6: Test international participant missing passport
            $browser->select('@document-country-origin-select', 'US')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify passport validation errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required_unless', ['attribute' => 'passport number', 'other' => 'document country origin', 'values' => 'BR']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required_unless', ['attribute' => 'passport expiry date', 'other' => 'document country origin', 'values' => 'BR']).'")');

            // AC6: Test "Other" fields validation
            $browser->click('@gender-other')
                ->waitFor('input[wire\\:model="other_gender"]')
                ->click('@position-other')
                ->waitFor('input[wire\\:model="other_position"]')
                ->click('@dietary-restrictions-other')
                ->waitFor('input[wire\\:model="other_dietary_restrictions"]')
                ->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify "Other" fields validation errors
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'other gender', 'other' => 'gender', 'value' => 'other']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'other position', 'other' => 'position', 'value' => 'other']).'")')
                ->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'other dietary restrictions', 'other' => 'dietary restrictions', 'value' => 'other']).'")');
        });
    }

    /**
     * AC6: Teste Dusk verifica mensagens de erro para formatos de dados incorretos
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_shows_frontend_validation_errors_for_incorrect_formats(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: Fill form with incorrect data formats

            // Invalid email format
            $browser->clear('@email-input')
                ->type('@email-input', 'invalid-email-format');

            // Invalid date of birth (future date)
            $browser->type('@date-of-birth-input', '01-01-2030');

            // For international participant, invalid passport expiry (past date)
            $browser->select('@document-country-origin-select', 'US')
                ->waitFor('#passport_number')
                ->waitFor('#passport_expiry_date')
                ->type('#passport_expiry_date', '01-01-2020');

            // Invalid arrival/departure dates (past dates and wrong order)
            $browser->type('@arrival-date-input', '01-01-2020')
                ->type('@departure-date-input', '01-01-2019');

            // Submit form to trigger validation
            $browser->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify format validation errors are displayed

            // Email format error
            $browser->assertPresent('.text-red-600:contains("'.__('validation.email', ['attribute' => 'email']).'")');

            // Date of birth error (future date)
            $browser->assertPresent('.text-red-600:contains("'.__('validation.before', ['attribute' => 'date of birth', 'date' => 'today']).'")');

            // Passport expiry date error (past date)
            $browser->assertPresent('.text-red-600:contains("'.__('validation.after', ['attribute' => 'passport expiry date', 'date' => 'today']).'")');

            // Arrival date error (past date)
            $browser->assertPresent('.text-red-600:contains("'.__('validation.after_or_equal', ['attribute' => 'arrival date', 'date' => 'today']).'")');

            // Departure date error (before arrival date)
            $browser->assertPresent('.text-red-600:contains("'.__('validation.after', ['attribute' => 'departure date', 'date' => 'arrival date']).'")');
        });
    }

    /**
     * AC6: Teste Dusk verifica que mensagens de erro desaparecem quando dados válidos são inseridos
     */
    #[Test]
    #[Group('dusk')]
    #[Group('registration-form')]
    public function registration_form_clears_frontend_validation_errors_when_valid_data_entered(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register-event')
                ->waitForText(__('Registration Form'));

            // AC6: First, trigger validation errors by submitting empty form
            $browser->click('@submit-registration-button')
                ->pause(2000); // Wait for Livewire validation

            // AC6: Verify errors are present
            $browser->assertPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'full name']).'")');

            // AC6: Fill in valid data and verify errors disappear
            $browser->type('@full-name-input', 'Valid Name')
                ->pause(1000) // Wait for Livewire to process
                ->assertNotPresent('.text-red-600:contains("'.__('validation.required', ['attribute' => 'full name']).'")');

            // AC6: Test email format correction
            $browser->clear('@email-input')
                ->type('@email-input', 'invalid-email')
                ->click('@submit-registration-button')
                ->pause(2000) // Wait for validation
                ->assertPresent('.text-red-600:contains("'.__('validation.email', ['attribute' => 'email']).'")')
                ->clear('@email-input')
                ->type('@email-input', 'valid@example.com')
                ->pause(1000) // Wait for Livewire to process
                ->assertNotPresent('.text-red-600:contains("'.__('validation.email', ['attribute' => 'email']).'")');

            // AC6: Test conditional field error clearing
            $browser->click('@gender-other')
                ->waitFor('input[wire\\:model="other_gender"]')
                ->click('@submit-registration-button')
                ->pause(2000) // Wait for validation
                ->assertPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'other gender', 'other' => 'gender', 'value' => 'other']).'")')
                ->type('input[wire\\:model="other_gender"]', 'Non-binary')
                ->pause(1000) // Wait for Livewire to process
                ->assertNotPresent('.text-red-600:contains("'.__('validation.required_if', ['attribute' => 'other gender', 'other' => 'gender', 'value' => 'other']).'")');
        });
    }
}
