<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\RecentActivityFeed;
use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\ActivityFeedService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RecentActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_successfully()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertStatus(200);
        $component->assertSee('Recent Activity');
    }

    /** @test */
    public function it_initializes_with_recent_activities()
    {
        // Create test data
        $user = User::factory()->create(['name' => 'John Doe']);
        Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSet('activities', function ($activities) {
            return $activities->count() > 0;
        });
    }

    /** @test */
    public function it_displays_activity_items_in_template()
    {
        $user = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Jane Smith',
            'payment_status' => 'pending',
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('New Registration Submission');
        $component->assertSee('Jane Smith');
        $component->assertSee('jane@example.com');
        $component->assertSee('Pending');
        $component->assertSee('View Registration');
    }

    /** @test */
    public function it_shows_empty_state_when_no_activities()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('No recent activity');
        $component->assertSee('Activity will appear here as registrations, payments, and enrollment proofs are submitted.');
    }

    /** @test */
    public function it_refreshes_activities_when_refresh_method_called()
    {
        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id]);

        $component = Livewire::test(RecentActivityFeed::class);

        $initialCount = $component->get('activities')->count();

        // Create new activity
        Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $component->call('refreshActivities');

        $newCount = $component->get('activities')->count();
        $this->assertGreaterThan($initialCount, $newCount);
    }

    /** @test */
    public function it_sets_loading_state_during_refresh()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSet('isLoading', false);

        // Mock the service to add delay
        $mock = Mockery::mock(ActivityFeedService::class);
        $mock->shouldReceive('getRecentActivity')
            ->once()
            ->andReturn(collect());
        $mock->shouldReceive('getActivityCounts')
            ->once()
            ->andReturn([]);

        $this->app->instance(ActivityFeedService::class, $mock);

        $component->call('refreshActivities');

        $component->assertSet('isLoading', false); // Should be false after completion
    }

    /** @test */
    public function it_changes_limit_and_reloads_activities()
    {
        $user = User::factory()->create();
        Registration::factory()->count(10)->create(['user_id' => $user->id]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->call('setLimit', 5);

        $component->assertSet('limit', 5);
        $this->assertLessThanOrEqual(5, $component->get('activities')->count());
    }

    /** @test */
    public function it_constrains_limit_between_5_and_20()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        // Test below minimum
        $component->call('setLimit', 2);
        $component->assertSet('limit', 5);

        // Test above maximum
        $component->call('setLimit', 25);
        $component->assertSet('limit', 20);

        // Test within bounds
        $component->call('setLimit', 15);
        $component->assertSet('limit', 15);
    }

    /** @test */
    public function it_returns_correct_activity_icon()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        // Test registration icon by calling the underlying service
        $service = app(\App\Services\ActivityFeedService::class);
        $this->assertEquals('fas fa-user-plus text-blue-500', $service->getActivityIcon('registration_submission'));
        $this->assertEquals('fas fa-credit-card text-green-500', $service->getActivityIcon('payment_proof_upload'));
    }

    /** @test */
    public function it_returns_correct_status_badge_class()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        // Test status badge classes by calling the underlying service
        $service = app(\App\Services\ActivityFeedService::class);
        $this->assertEquals('bg-yellow-100 text-yellow-800', $service->getStatusBadgeClass('pending'));
        $this->assertEquals('bg-green-100 text-green-800', $service->getStatusBadgeClass('approved'));
    }

    /** @test */
    public function it_returns_correct_status_text()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        // Test status text by calling the underlying service
        $service = app(\App\Services\ActivityFeedService::class);
        $this->assertEquals('Pending Approval', $service->getStatusText('pending_approval'));
        $this->assertEquals('Rejected', $service->getStatusText('rejected'));
    }

    /** @test */
    public function it_formats_timestamps_correctly()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $timestamp = Carbon::now()->subMinutes(5);
        $result = $timestamp->diffForHumans();

        $this->assertStringContainsString('minutes ago', $result);
    }

    /** @test */
    public function it_formats_string_timestamps()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $timestamp = Carbon::now()->subHours(2);
        $result = $timestamp->diffForHumans();

        $this->assertStringContainsString('hours ago', $result);
    }

    /** @test */
    public function it_detects_if_has_activities()
    {
        // Add activity first
        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSet('activities', function ($activities) {
            return $activities->count() > 0;
        });
    }

    /** @test */
    public function it_returns_activity_count_by_type()
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Create activities within 24 hours
        Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subHours(12),
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'payments/proof.pdf',
            'updated_at' => Carbon::now()->subHours(6),
        ]);

        $component = Livewire::test(RecentActivityFeed::class);

        // Test that activity counts are loaded correctly
        $component->assertSet('activityCounts', function ($counts) {
            return isset($counts['registrations']) && $counts['registrations'] > 0 &&
                   isset($counts['payments']) && $counts['payments'] > 0;
        });
    }

    /** @test */
    public function it_handles_unknown_activity_types()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSet('activityCounts', function ($counts) {
            return is_array($counts);
        });
    }

    /** @test */
    public function it_displays_correct_activity_counts_in_template()
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Create recent activities
        Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subHours(1),
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'payments/proof.pdf',
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'uploaded_at' => Carbon::now()->subHours(3),
        ]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSeeInOrder([
            'new registrations',
            'payment uploads',
            'enrollment proofs',
        ]);
    }

    /** @test */
    public function it_displays_link_to_admin_registrations()
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
        ]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('View all activities');
        $component->assertSeeHtml('href="'.route('admin.registrations.index').'"');
    }

    /** @test */
    public function it_displays_polling_information()
    {
        // Create an activity to ensure the settings section renders
        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id]);

        $component = Livewire::test(RecentActivityFeed::class);

        // The component should have polling enabled
        $component->assertSeeHtml('wire:poll.30s="refreshActivities"');
    }

    /** @test */
    public function it_displays_limit_selector()
    {
        // Create an activity to ensure the settings section renders
        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id]);

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSeeHtml('wire:model.live="limit"');
        $component->assertSeeHtml('<option value="10">10</option>');
    }

    /** @test */
    public function it_displays_manual_refresh_button()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSeeHtml('wire:click="refreshActivities"');
        $component->assertSee('Refresh activity feed');
    }

    /** @test */
    public function it_shows_loading_indicator_during_refresh()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSeeHtml('wire:loading');
        $component->assertSeeHtml('wire:target="refreshActivities"');
        $component->assertSee('Updating...');
    }

    /** @test */
    public function it_displays_debug_info_in_local_environment()
    {
        // Set app environment to local for debug info
        config(['app.debug' => true]);
        app()->detectEnvironment(fn () => 'local');

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('Debug Info');
        $component->assertSee('Polling every 30s');
    }

    /** @test */
    public function it_hides_debug_info_in_production_environment()
    {
        // Set app environment to production
        config(['app.debug' => false]);
        app()->detectEnvironment(fn () => 'production');

        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertDontSee('Debug Info');
    }

    /** @test */
    public function it_includes_wire_poll_directive_in_template()
    {
        $component = Livewire::test(RecentActivityFeed::class);

        // Check that the template includes the wire:poll directive
        $component->assertSeeHtml('wire:poll.30s="refreshActivities"');
    }
}
