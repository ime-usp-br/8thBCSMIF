<?php

namespace Tests\Unit\Services;

use App\Models\EnrollmentProof;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\RegistrationExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class RegistrationExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RegistrationExportService;
    }

    public function test_get_available_columns_returns_structured_array(): void
    {
        $columns = $this->service->getAvailableColumns();

        $this->assertIsArray($columns);
        $this->assertArrayHasKey('basic', $columns);
        $this->assertArrayHasKey('personal', $columns);
        $this->assertArrayHasKey('contact', $columns);
        $this->assertArrayHasKey('professional', $columns);
        $this->assertArrayHasKey('conference', $columns);
        $this->assertArrayHasKey('administrative', $columns);

        // Test basic information group
        $basicColumns = $columns['basic'];
        $this->assertArrayHasKey('id', $basicColumns);
        $this->assertArrayHasKey('full_name', $basicColumns);
        $this->assertArrayHasKey('email', $basicColumns);
        $this->assertArrayHasKey('status', $basicColumns);
        $this->assertArrayHasKey('created_at', $basicColumns);

        // Verify that all values are translated strings
        foreach ($columns as $group) {
            foreach ($group as $key => $value) {
                $this->assertIsString($value);
            }
        }
    }

    public function test_get_column_groups_returns_localized_labels(): void
    {
        $groups = $this->service->getColumnGroups();

        $this->assertIsArray($groups);
        $this->assertCount(6, $groups);

        $expectedGroups = ['basic', 'personal', 'contact', 'professional', 'conference', 'administrative'];
        foreach ($expectedGroups as $group) {
            $this->assertArrayHasKey($group, $groups);
            $this->assertIsString($groups[$group]);
        }
    }

    public function test_export_to_csv_with_valid_columns(): void
    {
        // Create test data
        $user = User::factory()->create();
        $event = Event::factory()->create(['code' => 'TEST01']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'approved',
        ]);
        $registration->events()->attach($event);

        // Create query builder
        $query = Registration::with(['events', 'payments', 'enrollmentProof']);

        // Test basic columns export
        $selectedColumns = ['id', 'full_name', 'email', 'status'];
        $response = $this->service->exportToCsv($query, $selectedColumns);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        // Check CSV content
        $csvContent = $response->getContent();
        $this->assertStringContainsString('ID', $csvContent);
        $this->assertStringContainsString('Full Name', $csvContent);
        $this->assertStringContainsString('Email', $csvContent);
        $this->assertStringContainsString('Status', $csvContent);
        $this->assertStringContainsString('John Doe', $csvContent);
        $this->assertStringContainsString('john@example.com', $csvContent);
    }

    public function test_export_to_csv_with_invalid_columns_returns_400(): void
    {
        $query = Registration::query();
        $invalidColumns = [];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('No valid columns selected for export.');

        $this->service->exportToCsv($query, $invalidColumns);
    }

    public function test_export_to_csv_includes_relationships(): void
    {
        // Create test data with relationships
        $user = User::factory()->create();
        $event1 = Event::factory()->create(['code' => 'EVENT1']);
        $event2 = Event::factory()->create(['code' => 'EVENT2']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Jane Smith',
        ]);

        $registration->events()->attach([$event1->id, $event2->id]);

        // Add payment
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'approved',
        ]);

        // Add enrollment proof
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'approved',
        ]);

        $query = Registration::with(['events', 'payments', 'enrollmentProof']);
        $selectedColumns = ['full_name', 'events', 'payment_status', 'enrollment_proof_status'];

        $response = $this->service->exportToCsv($query, $selectedColumns);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('Jane Smith', $csvContent);
        $this->assertStringContainsString('EVENT1, EVENT2', $csvContent);
        $this->assertStringContainsString('approved', $csvContent);
    }

    public function test_export_to_csv_handles_boolean_fields(): void
    {
        $registration = Registration::factory()->create([
            'is_abe_member' => true,
            'needs_transport_from_gru' => false,
            'needs_transport_from_usp' => true,
            'requires_visa_letter' => false,
        ]);

        $query = Registration::query();
        $selectedColumns = [
            'is_abe_member',
            'needs_transport_from_gru',
            'needs_transport_from_usp',
            'requires_visa_letter',
        ];

        $response = $this->service->exportToCsv($query, $selectedColumns);
        $csvContent = $response->getContent();

        // Check that boolean values are converted to Yes/No
        $lines = explode("\n", $csvContent);
        $dataLine = $lines[1]; // First data row after header

        $this->assertStringContainsString('Yes', $dataLine); // is_abe_member
        $this->assertStringContainsString('No', $dataLine);  // needs_transport_from_gru
        $this->assertStringContainsString('Yes', $dataLine); // needs_transport_from_usp
        $this->assertStringContainsString('No', $dataLine);  // requires_visa_letter
    }

    public function test_export_to_csv_handles_dates(): void
    {
        $registration = Registration::factory()->create([
            'date_of_birth' => '1990-05-15',
            'created_at' => '2024-01-15 14:30:00',
        ]);

        $query = Registration::query();
        $selectedColumns = ['date_of_birth', 'created_at'];

        $response = $this->service->exportToCsv($query, $selectedColumns);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('15/05/1990', $csvContent);
        $this->assertStringContainsString('15/01/2024', $csvContent);
    }

    public function test_export_to_csv_handles_empty_relationships(): void
    {
        $registration = Registration::factory()->create();

        $query = Registration::with(['events', 'payments', 'enrollmentProof']);
        $selectedColumns = ['full_name', 'events', 'payment_status', 'enrollment_proof_status'];

        $response = $this->service->exportToCsv($query, $selectedColumns);
        $csvContent = $response->getContent();

        // Should not throw errors and should contain appropriate empty values
        $this->assertStringContainsString('No payment', $csvContent);
        $this->assertStringContainsString('No proof', $csvContent);
    }

    public function test_export_to_csv_filename_format(): void
    {
        $query = Registration::query();
        $selectedColumns = ['id', 'full_name'];

        $response = $this->service->exportToCsv($query, $selectedColumns);

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment; filename="registrations_export_', $contentDisposition);
        $this->assertStringContainsString('.csv"', $contentDisposition);
    }

    public function test_export_to_csv_includes_utf8_bom(): void
    {
        $registration = Registration::factory()->create([
            'full_name' => 'João da Silva', // Test UTF-8 characters
        ]);

        $query = Registration::query();
        $selectedColumns = ['full_name'];

        $response = $this->service->exportToCsv($query, $selectedColumns);
        $csvContent = $response->getContent();

        // Check that BOM is present for UTF-8 support in Excel
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);
        $this->assertStringContainsString('João da Silva', $csvContent);
    }
}
