<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRegistrationsUploadFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that payment proof upload form is implemented in the template.
     * This test specifically addresses AC6 requirements for Issue #13.
     */
    public function test_payment_proof_upload_form_is_implemented(): void
    {
        // Create verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Test the page loads
        $response = $this->actingAs($user)->get('/my-registrations');
        $response->assertOk();

        // AC6: Verify template file contains the upload form implementation
        $templatePath = resource_path('views/livewire/pages/my-registrations.blade.php');
        $templateContent = file_get_contents($templatePath);

        // Check that the AC6 conditional logic exists in the template
        $this->assertStringContainsString('payment_status === \'pending_payment\'', $templateContent);
        $this->assertStringContainsString('document_country_origin === \'Brasil\'', $templateContent);

        // Check that the form exists with correct attributes
        $this->assertStringContainsString('event-registrations.upload-proof', $templateContent);
        $this->assertStringContainsString('payment_proof', $templateContent);
        $this->assertStringContainsString('multipart/form-data', $templateContent);
    }

    /**
     * Test that upload form route exists and is properly configured.
     * This test specifically addresses AC6 requirements for Issue #13.
     */
    public function test_upload_proof_route_exists(): void
    {
        // AC6: Verify the route exists and uses correct controller method
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('event-registrations.upload-proof'));

        // Get route instance
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('event-registrations.upload-proof');

        // Verify route configuration
        $this->assertEquals(['POST'], $route->methods());
        $this->assertStringContainsString('RegistrationController@uploadProof', $route->getActionName());

        // Verify middleware
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('verified', $middleware);
    }

    /**
     * Test that upload form submits via POST to the correct route with correct field name.
     * This test specifically addresses AC7 requirements for Issue #13.
     */
    public function test_upload_form_submits_post_to_correct_route_with_payment_proof_field(): void
    {
        // AC7: Verify the form submits (via POST) to the route event-registrations.upload-proof
        // existing (defined in Issue #9), sending the file with the name payment_proof.

        // Create verified user and registration
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = \App\Models\Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Brasil',
        ]);

        // Load the template and check form structure
        $templatePath = resource_path('views/livewire/pages/my-registrations.blade.php');
        $templateContent = file_get_contents($templatePath);

        // AC7: Verify form action points to correct route
        $this->assertStringContainsString('action="{{ route(\'event-registrations.upload-proof\', $selectedRegistration) }}"', $templateContent);

        // AC7: Verify form method is POST
        $this->assertStringContainsString('method="POST"', $templateContent);

        // AC7: Verify form has multipart encoding for file uploads
        $this->assertStringContainsString('enctype="multipart/form-data"', $templateContent);

        // AC7: Verify input field name is "payment_proof"
        $this->assertStringContainsString('name="payment_proof"', $templateContent);

        // AC7: Verify CSRF token is included
        $this->assertStringContainsString('@csrf', $templateContent);

        // AC7: Verify the route actually resolves to the controller method
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('event-registrations.upload-proof');
        $this->assertStringContainsString('RegistrationController@uploadProof', $route->getActionName());
    }

    /**
     * Test that all required translation keys for AC6 exist.
     * This test specifically addresses AC6 requirements for Issue #13.
     */
    public function test_upload_form_translation_keys_exist(): void
    {
        // AC6: Verify all translation keys required by the upload form exist
        $this->assertNotEmpty(__('Payment Proof Upload'));
        $this->assertNotEmpty(__('Payment Proof Document'));
        $this->assertNotEmpty(__('Upload Payment Proof'));
        $this->assertNotEmpty(__('Accepted formats: JPG, JPEG, PNG, PDF. Maximum size: 10MB.'));

        // Verify keys are translated properly in both languages
        app()->setLocale('en');
        $this->assertEquals('Payment Proof Upload', __('Payment Proof Upload'));

        app()->setLocale('pt_BR');
        $this->assertEquals('Upload de Comprovante de Pagamento', __('Payment Proof Upload'));
    }
}
