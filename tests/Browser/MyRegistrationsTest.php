<?php

namespace Tests\Browser;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class MyRegistrationsTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
    }

    /**
     * AC9: Se o upload falhar devido à validação no backend (tipo/tamanho de arquivo),
     * a página exibe as mensagens de erro de validação ($errors) correspondentes.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function upload_proof_shows_validation_errors_for_invalid_files(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with pending payment status and Brazilian document
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment Proof Upload'));

            // AC9: Test with invalid file type (text file instead of image/PDF)
            $browser->attach('input[name="payment_proof"]', __DIR__.'/../fixtures/invalid_file.txt')
                ->press(__('Upload Payment Proof'))
                ->waitForText(__('validation.uploaded'))
                ->assertSee(__('validation.uploaded'));

            // Verify error styling is applied (red background for errors)
            $browser->assertVisible('.bg-red-100');
        });
    }

    /**
     * AC9: Test file size validation error display
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function upload_proof_shows_validation_errors_for_large_files(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with pending payment status and Brazilian document
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment Proof Upload'));

            // AC9: Test with file that's too large (over 10MB limit)
            // Note: Using a large file fixture for testing file size validation
            $browser->attach('input[name="payment_proof"]', __DIR__.'/../fixtures/large_file.jpg')
                ->press(__('Upload Payment Proof'))
                ->waitForText(__('validation.uploaded'))
                ->assertSee(__('validation.uploaded'));

            // AC9: Verify that validation errors are displayed in the error section
            $browser->assertVisible('.bg-red-100')
                ->assertVisible('.border-red-400')
                ->assertVisible('.text-red-700');
        });
    }

    /**
     * AC9: Test missing file validation error display
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function upload_proof_shows_validation_errors_for_missing_file(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with pending payment status and Brazilian document
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment Proof Upload'));

            // AC9: Test submitting form without selecting a file
            $browser->press(__('Upload Payment Proof'))
                ->waitForText(__('validation.required'))
                ->assertSee(__('validation.required'));

            // AC9: Verify that validation errors are displayed in the correct error styling
            $browser->assertVisible('.bg-red-100')
                ->assertVisible('.border-red-400')
                ->assertVisible('.text-red-700')
                ->assertVisible('ul.list-disc.list-inside');
        });
    }
}
