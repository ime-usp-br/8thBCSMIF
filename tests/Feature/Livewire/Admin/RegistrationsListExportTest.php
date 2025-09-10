<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\RegistrationsList;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationsListExportTest extends TestCase
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

    public function test_export_modal_initial_state(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Modal should be closed initially
        $component->assertSet('showExportModal', false);

        // Should have available columns and groups loaded
        $component->assertSet('availableColumns', function ($value) {
            return is_array($value) && count($value) > 0;
        });

        $component->assertSet('columnGroups', function ($value) {
            return is_array($value) && count($value) === 6;
        });

        // Should have default selected columns
        $component->assertSet('selectedColumns', ['id', 'full_name', 'email', 'status', 'created_at']);
    }

    public function test_open_export_modal(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        $component->assertSet('showExportModal', true);
    }

    public function test_close_export_modal(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Open modal first
        $component->call('openExportModal');
        $component->assertSet('showExportModal', true);

        // Close modal
        $component->call('closeExportModal');
        $component->assertSet('showExportModal', false);
    }

    public function test_select_all_columns(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('selectAllColumns');

        // Should select all available columns
        $selectedColumns = $component->get('selectedColumns');
        $this->assertGreaterThan(20, count($selectedColumns)); // Should have many columns
    }

    public function test_deselect_all_columns(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // First select all
        $component->call('selectAllColumns');
        $this->assertNotEmpty($component->get('selectedColumns'));

        // Then deselect all
        $component->call('deselectAllColumns');
        $component->assertSet('selectedColumns', []);
    }

    public function test_toggle_group_columns(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Initially should have some basic columns selected
        $initialSelected = $component->get('selectedColumns');

        // Toggle basic group (should add more columns)
        $component->call('toggleGroupColumns', 'basic');

        // Check if basic group columns are all selected
        $this->assertTrue($component->call('isGroupFullySelected', 'basic'));

        // Toggle again should deselect the group
        $component->call('toggleGroupColumns', 'basic');

        // Should have fewer selected columns now
        $finalSelected = $component->get('selectedColumns');
        $this->assertNotContains('id', $finalSelected);
    }

    public function test_is_group_fully_selected(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Initially basic group should not be fully selected
        $this->assertFalse($component->call('isGroupFullySelected', 'basic'));

        // Select all basic columns
        $component->set('selectedColumns', ['id', 'full_name', 'email', 'status', 'created_at']);

        // Now basic group should be fully selected
        $this->assertTrue($component->call('isGroupFullySelected', 'basic'));
    }

    public function test_is_group_partially_selected(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Select only some basic columns
        $component->set('selectedColumns', ['id', 'full_name']);

        // Basic group should be partially selected
        $this->assertTrue($component->call('isGroupPartiallySelected', 'basic'));

        // Select all basic columns
        $component->set('selectedColumns', ['id', 'full_name', 'email', 'status', 'created_at']);

        // Should no longer be partially selected
        $this->assertFalse($component->call('isGroupPartiallySelected', 'basic'));
    }

    public function test_export_csv_validation_no_columns(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Set no selected columns
        $component->set('selectedColumns', []);

        // Try to export
        $component->call('exportCsv');

        // Should have validation error
        $component->assertHasErrors(['selectedColumns']);
    }

    public function test_export_csv_with_valid_columns(): void
    {
        $this->actingAs($this->adminUser);

        // Create test registration
        $user = User::factory()->create();
        Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
        ]);

        $component = Livewire::test(RegistrationsList::class);

        // Set valid columns
        $component->set('selectedColumns', ['id', 'full_name', 'email']);

        // Export should succeed
        $component->call('exportCsv');

        // Should dispatch export event
        $component->assertDispatched('export-csv');

        // Should close modal
        $component->assertSet('showExportModal', false);

        // Should show success message
        $component->assertSessionHas('success');
    }

    public function test_export_csv_includes_current_filters(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Set some filters
        $component->set('search', 'test search');
        $component->set('filterEventCode', 'EVENT1');
        $component->set('filterPaymentStatus', 'approved');

        // Set valid columns
        $component->set('selectedColumns', ['id', 'full_name']);

        // Export
        $component->call('exportCsv');

        // Should dispatch event with filters
        $component->assertDispatched('export-csv', function ($data) {
            return $data[0]['filters']['search'] === 'test search' &&
                   $data[0]['filters']['filterEventCode'] === 'EVENT1' &&
                   $data[0]['filters']['filterPaymentStatus'] === 'approved';
        });
    }

    public function test_export_selected_opens_modal(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Call exportSelected (the method triggered by the Export button)
        $component->call('exportSelected');

        // Should open the export modal
        $component->assertSet('showExportModal', true);
    }

    public function test_modal_renders_column_groups(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        // Should render the modal with column groups
        $component->assertSee('Export Registrations');
        $component->assertSee('Basic Information');
        $component->assertSee('Personal Details');
        $component->assertSee('Contact Information');
        $component->assertSee('Professional Information');
        $component->assertSee('Conference Details');
        $component->assertSee('Administrative');
    }

    public function test_modal_renders_select_all_buttons(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        // Should render select all/deselect all buttons
        $component->assertSee('Select All');
        $component->assertSee('Deselect All');
    }

    public function test_modal_renders_column_checkboxes(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        // Should render specific column labels
        $component->assertSee('Full Name');
        $component->assertSee('Email');
        $component->assertSee('Nationality');
        $component->assertSee('Phone Number');
    }

    public function test_export_button_text_and_loading(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        // Should show export button text
        $component->assertSee('Export CSV');

        // During loading should show different text
        $component->assertSee('Exporting...');
    }

    public function test_modal_follows_approval_queue_pattern(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        $component->call('openExportModal');

        // Should have similar structure to approval queue modal
        $component->assertSee('Cancel'); // Cancel button like approval queue
        $component->assertSeeHtml('x-show="open"'); // Alpine.js pattern
        $component->assertSeeHtml('wire:click="closeExportModal"'); // Close functionality
    }

    public function test_component_handles_export_errors_gracefully(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RegistrationsList::class);

        // Set invalid state that might cause errors
        $component->set('selectedColumns', ['invalid_column']);

        // Try to export - should handle errors gracefully
        $component->call('exportCsv');

        // Should show error message or validation error
        $component->assertHasErrors();
    }

    public function test_export_functionality_preserves_pagination(): void
    {
        $this->actingAs($this->adminUser);

        // Create many registrations to test pagination
        $users = User::factory(20)->create();
        foreach ($users as $user) {
            Registration::factory()->create(['user_id' => $user->id]);
        }

        $component = Livewire::test(RegistrationsList::class);

        // Go to page 2
        $component->call('gotoPage', 2);

        // Export should still work and include all filtered results, not just current page
        $component->set('selectedColumns', ['id', 'full_name']);
        $component->call('exportCsv');

        $component->assertDispatched('export-csv');
    }
}
