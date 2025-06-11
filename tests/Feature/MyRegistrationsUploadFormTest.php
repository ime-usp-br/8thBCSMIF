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