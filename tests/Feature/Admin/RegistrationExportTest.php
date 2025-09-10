<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(RoleSeeder::class);

        // Create admin user
        $this->adminUser = User::factory()->create();
        $adminRole = Role::findByName('admin');
        $this->adminUser->assignRole($adminRole);
    }

    public function test_admin_can_access_export_endpoint(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id', 'full_name', 'email'],
            'filters' => [],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_non_admin_cannot_access_export_endpoint(): void
    {
        $regularUser = User::factory()->create();

        $this->actingAs($regularUser);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id', 'full_name'],
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_export_endpoint(): void
    {
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id', 'full_name'],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_export_csv_validates_required_columns(): void
    {
        $this->actingAs($this->adminUser);

        // Test without columns
        $response = $this->post(route('admin.registrations.export-csv'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['columns']);

        // Test with empty columns array
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => [],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['columns']);
    }

    public function test_export_csv_validates_column_format(): void
    {
        $this->actingAs($this->adminUser);

        // Test with invalid column format
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => [123, null, ''], // Invalid formats
        ]);

        $response->assertStatus(422);
    }

    public function test_export_csv_with_basic_columns(): void
    {
        $this->actingAs($this->adminUser);

        // Create test registrations
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'status' => 'approved',
        ]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'pending',
        ]);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id', 'full_name', 'email', 'status'],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        // Check CSV headers
        $this->assertStringContainsString('ID', $csvContent);
        $this->assertStringContainsString('Full Name', $csvContent);
        $this->assertStringContainsString('Email', $csvContent);
        $this->assertStringContainsString('Status', $csvContent);

        // Check CSV data
        $this->assertStringContainsString('Alice Johnson', $csvContent);
        $this->assertStringContainsString('alice@example.com', $csvContent);
        $this->assertStringContainsString('Bob Smith', $csvContent);
        $this->assertStringContainsString('bob@example.com', $csvContent);
    }

    public function test_export_csv_applies_filters(): void
    {
        $this->actingAs($this->adminUser);

        // Create test registrations with different statuses
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'Approved User',
            'status' => 'approved',
        ]);

        Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Pending User',
            'status' => 'pending',
        ]);

        // Export only approved registrations
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name', 'status'],
            'filters' => [
                'filterPaymentStatus' => 'approved',
            ],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        // Should contain approved user but not pending user
        $this->assertStringContainsString('Approved User', $csvContent);
        $this->assertStringNotContainsString('Pending User', $csvContent);
    }

    public function test_export_csv_applies_search_filter(): void
    {
        $this->actingAs($this->adminUser);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        // Search for "john"
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name', 'email'],
            'filters' => [
                'search' => 'john',
            ],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('John Doe', $csvContent);
        $this->assertStringNotContainsString('Jane Smith', $csvContent);
    }

    public function test_export_csv_applies_date_filters(): void
    {
        $this->actingAs($this->adminUser);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create registration from yesterday
        Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'Old Registration',
            'created_at' => now()->subDay(),
        ]);

        // Create registration from today
        Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'New Registration',
            'created_at' => now(),
        ]);

        // Filter from today only
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name'],
            'filters' => [
                'filterDateFrom' => now()->format('Y-m-d'),
            ],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('New Registration', $csvContent);
        $this->assertStringNotContainsString('Old Registration', $csvContent);
    }

    public function test_export_csv_applies_event_filter(): void
    {
        $this->actingAs($this->adminUser);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $event1 = Event::factory()->create(['code' => 'CONF2024']);
        $event2 = Event::factory()->create(['code' => 'WORKSHOP']);

        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'Conference Attendee',
        ]);
        $registration1->events()->attach($event1);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Workshop Attendee',
        ]);
        $registration2->events()->attach($event2);

        // Filter by CONF2024 event only
        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name'],
            'filters' => [
                'filterEventCode' => 'CONF2024',
            ],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('Conference Attendee', $csvContent);
        $this->assertStringNotContainsString('Workshop Attendee', $csvContent);
    }

    public function test_export_csv_includes_events_column(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create();
        $event1 = Event::factory()->create(['code' => 'EVENT1']);
        $event2 = Event::factory()->create(['code' => 'EVENT2']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Multi Event User',
        ]);
        $registration->events()->attach([$event1->id, $event2->id]);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name', 'events'],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('Multi Event User', $csvContent);
        $this->assertStringContainsString('EVENT1, EVENT2', $csvContent);
    }

    public function test_export_csv_includes_payment_status(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Paid User',
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'approved',
        ]);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['full_name', 'payment_status'],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        $this->assertStringContainsString('Paid User', $csvContent);
        $this->assertStringContainsString('approved', $csvContent);
    }

    public function test_export_csv_filename_contains_timestamp(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id'],
        ]);

        $response->assertStatus(200);

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment; filename="registrations_export_', $contentDisposition);
        $this->assertStringContainsString(now()->format('Y-m-d'), $contentDisposition);
    }

    public function test_export_csv_with_all_available_columns(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create();
        Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Complete User',
            'email' => 'complete@example.com',
            'nationality' => 'Brazilian',
            'phone_number' => '+5511999999999',
            'affiliation' => 'University of São Paulo',
            'is_abe_member' => true,
        ]);

        // Test with many columns
        $allColumns = [
            'id', 'full_name', 'email', 'nationality', 'phone_number',
            'affiliation', 'is_abe_member', 'status', 'created_at',
        ];

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => $allColumns,
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        // Check that all requested columns are present in headers
        $this->assertStringContainsString('ID', $csvContent);
        $this->assertStringContainsString('Full Name', $csvContent);
        $this->assertStringContainsString('Email', $csvContent);
        $this->assertStringContainsString('Nationality', $csvContent);
        $this->assertStringContainsString('ABE Member', $csvContent);

        // Check data
        $this->assertStringContainsString('Complete User', $csvContent);
        $this->assertStringContainsString('complete@example.com', $csvContent);
        $this->assertStringContainsString('Brazilian', $csvContent);
        $this->assertStringContainsString('Yes', $csvContent); // is_abe_member = true
    }

    public function test_export_csv_handles_large_dataset(): void
    {
        $this->actingAs($this->adminUser);

        // Create many registrations
        $users = User::factory(50)->create();
        foreach ($users as $user) {
            Registration::factory()->create([
                'user_id' => $user->id,
                'full_name' => "User {$user->id}",
            ]);
        }

        $response = $this->post(route('admin.registrations.export-csv'), [
            'columns' => ['id', 'full_name'],
        ]);

        $response->assertStatus(200);
        $csvContent = $response->getContent();

        // Count lines (header + 50 data rows)
        $lines = explode("\n", trim($csvContent));
        $this->assertGreaterThanOrEqual(50, count($lines) - 1); // -1 for header
    }
}
