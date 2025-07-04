<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFormConditionalLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test events
        Event::factory()->create(['code' => '8BCSMIF', 'name' => '8th BCSMIF']);
        Event::factory()->create(['code' => 'RISK', 'name' => 'Risk Analysis Workshop']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cpf_and_rg_fields_are_shown_for_brazilian_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'BR');

        $component->assertSee(__('CPF'))
            ->assertSee(__('RG (ID) Number'))
            ->assertDontSee(__('Passport Number'))
            ->assertDontSee(__('Passport Expiry Date'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passport_fields_are_shown_for_international_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'US');

        $component->assertSee(__('Passport Number'))
            ->assertSee(__('Passport Expiry Date'))
            ->assertDontSee(__('CPF'))
            ->assertDontSee(__('RG (ID) Number'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_gender_field_appears_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('gender', 'other');

        $component->assertSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_gender_field_hidden_when_other_not_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('gender', 'male');

        $component->assertDontSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_position_field_appears_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'other');

        $component->assertSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_position_field_hidden_when_other_not_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('position', 'professor');

        $component->assertDontSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_dietary_restriction_field_appears_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('dietary_restrictions', 'other');

        $component->assertSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_dietary_restriction_field_hidden_when_other_not_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('dietary_restrictions', 'vegetarian');

        $component->assertDontSee(__('Please specify'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function visa_support_section_appears_for_international_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'US');

        $component->assertSee(__('8. Visa Support'))
            ->assertSee(__('Do you require an invitation letter to get a Brazilian visa?'))
            ->assertSee(__('(For international participants only)'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function visa_support_section_hidden_for_brazilian_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'BR');

        $component->assertDontSee(__('8. Visa Support'))
            ->assertDontSee(__('Do you require an invitation letter to get a Brazilian visa?'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function document_fields_switch_dynamically_when_country_changes(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Start with Brazil
        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'BR');

        $component->assertSee(__('CPF'))
            ->assertSee(__('RG (ID) Number'))
            ->assertDontSee(__('Passport Number'));

        // Switch to international
        $component->set('document_country_origin', 'US');

        $component->assertSee(__('Passport Number'))
            ->assertSee(__('Passport Expiry Date'))
            ->assertDontSee(__('CPF'))
            ->assertDontSee(__('RG (ID) Number'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function conditional_validation_works_for_brazilian_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'BR')
            ->set('full_name', 'Test User')
            ->set('nationality', 'Brazilian')
            ->set('date_of_birth', '1990-01-01')
            ->set('gender', 'male')
            ->set('email', 'test@example.com')
            ->set('phone_number', '+55 11 987654321')
            ->set('address_street', 'Test Street')
            ->set('address_city', 'São Paulo')
            ->set('address_state_province', 'SP')
            ->set('address_country', 'BR')
            ->set('address_postal_code', '01234-567')
            ->set('affiliation', 'Test University')
            ->set('position', 'professor')
            ->set('is_abe_member', 'yes')
            ->set('arrival_date', '2025-09-28')
            ->set('departure_date', '2025-10-03')
            ->set('selected_event_codes', ['8BCSMIF'])
            ->set('participation_format', 'in-person')
            ->set('dietary_restrictions', 'none')
            ->set('emergency_contact_name', 'Emergency Contact')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+55 11 123456789')
            ->set('confirm_information', true)
            ->set('consent_data_processing', true)
            ->call('submit');

        $component->assertHasNoErrors(['passport_number', 'passport_expiry_date', 'requires_visa_letter']);
        $component->assertHasErrors(['cpf', 'rg_number']); // Required for Brazilians
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function conditional_validation_works_for_international_participants(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'US')
            ->set('full_name', 'Test User')
            ->set('nationality', 'American')
            ->set('date_of_birth', '1990-01-01')
            ->set('gender', 'male')
            ->set('email', 'test@example.com')
            ->set('phone_number', '+1 555 123456')
            ->set('address_street', 'Test Street')
            ->set('address_city', 'New York')
            ->set('address_state_province', 'NY')
            ->set('address_country', 'US')
            ->set('address_postal_code', '10001')
            ->set('affiliation', 'Test University')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('arrival_date', '2025-09-28')
            ->set('departure_date', '2025-10-03')
            ->set('selected_event_codes', ['8BCSMIF'])
            ->set('participation_format', 'in-person')
            ->set('dietary_restrictions', 'none')
            ->set('emergency_contact_name', 'Emergency Contact')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+1 555 987654')
            ->set('confirm_information', true)
            ->set('consent_data_processing', true)
            ->call('submit');

        $component->assertHasNoErrors(['cpf', 'rg_number']);
        $component->assertHasErrors(['passport_number', 'passport_expiry_date', 'requires_visa_letter']); // Required for internationals
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_document_country_field_appears_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'OTHER');

        $component->assertSee(__('Please specify the country'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_document_country_field_hidden_when_other_not_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'BR');

        $component->assertDontSee(__('Please specify the country'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_address_country_field_appears_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('address_country', 'OTHER');

        $component->assertSee(__('Please specify the country'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_address_country_field_hidden_when_other_not_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('address_country', 'BR');

        $component->assertDontSee(__('Please specify the country'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_document_country_field_is_required_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'OTHER')
            ->set('full_name', 'Test User')
            ->set('nationality', 'International')
            ->set('date_of_birth', '1990-01-01')
            ->set('gender', 'male')
            ->set('email', 'test@example.com')
            ->set('phone_number', '+1 555 123456')
            ->set('address_street', 'Test Street')
            ->set('address_city', 'Test City')
            ->set('address_state_province', 'Test State')
            ->set('address_country', 'US')
            ->set('address_postal_code', '10001')
            ->set('affiliation', 'Test University')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('arrival_date', '2025-09-28')
            ->set('departure_date', '2025-10-03')
            ->set('selected_event_codes', ['8BCSMIF'])
            ->set('participation_format', 'in-person')
            ->set('dietary_restrictions', 'none')
            ->set('emergency_contact_name', 'Emergency Contact')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+1 555 987654')
            ->set('passport_number', 'ABC123456')
            ->set('passport_expiry_date', '2030-12-31')
            ->set('requires_visa_letter', 'yes')
            ->set('confirm_information', true)
            ->set('consent_data_processing', true)
            ->call('validateAndSubmit');

        $component->assertHasErrors(['other_document_country_origin']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_address_country_field_is_required_when_other_is_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'US')
            ->set('address_country', 'OTHER')
            ->set('full_name', 'Test User')
            ->set('nationality', 'American')
            ->set('date_of_birth', '1990-01-01')
            ->set('gender', 'male')
            ->set('email', 'test@example.com')
            ->set('phone_number', '+1 555 123456')
            ->set('address_street', 'Test Street')
            ->set('address_city', 'Test City')
            ->set('address_state_province', 'Test State')
            ->set('address_postal_code', '10001')
            ->set('affiliation', 'Test University')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('arrival_date', '2025-09-28')
            ->set('departure_date', '2025-10-03')
            ->set('selected_event_codes', ['8BCSMIF'])
            ->set('participation_format', 'in-person')
            ->set('dietary_restrictions', 'none')
            ->set('emergency_contact_name', 'Emergency Contact')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+1 555 987654')
            ->set('passport_number', 'ABC123456')
            ->set('passport_expiry_date', '2030-12-31')
            ->set('requires_visa_letter', 'yes')
            ->set('confirm_information', true)
            ->set('consent_data_processing', true)
            ->call('validateAndSubmit');

        $component->assertHasErrors(['other_address_country']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_country_fields_validation_passes_when_values_provided(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test('registration-form')
            ->set('document_country_origin', 'OTHER')
            ->set('other_document_country_origin', 'Custom Country')
            ->set('address_country', 'OTHER')
            ->set('other_address_country', 'Custom Address Country')
            ->set('full_name', 'Test User')
            ->set('nationality', 'International')
            ->set('date_of_birth', '1990-01-01')
            ->set('gender', 'male')
            ->set('email', 'test@example.com')
            ->set('phone_number', '+1 555 123456')
            ->set('address_street', 'Test Street')
            ->set('address_city', 'Test City')
            ->set('address_state_province', 'Test State')
            ->set('address_postal_code', '10001')
            ->set('affiliation', 'Test University')
            ->set('position', 'professor')
            ->set('is_abe_member', 'no')
            ->set('arrival_date', '2025-09-28')
            ->set('departure_date', '2025-10-03')
            ->set('selected_event_codes', ['8BCSMIF'])
            ->set('participation_format', 'in-person')
            ->set('dietary_restrictions', 'none')
            ->set('emergency_contact_name', 'Emergency Contact')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+1 555 987654')
            ->set('passport_number', 'ABC123456')
            ->set('passport_expiry_date', '2030-12-31')
            ->set('requires_visa_letter', 'yes')
            ->set('confirm_information', true)
            ->set('consent_data_processing', true)
            ->call('validateAndSubmit');

        $component->assertHasNoErrors(['other_document_country_origin', 'other_address_country']);
    }
}
