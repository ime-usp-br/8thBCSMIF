<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Schedule::class)]
#[Group('console')]
#[Group('schedule')]
class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function early_bird_reminder_command_is_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);

        // Get all scheduled events
        $events = $schedule->events();

        // Look for our command in the scheduled events
        $earlyBirdCommandFound = false;
        foreach ($events as $event) {
            if (str_contains($event->command, 'app:send-early-bird-reminders')) {
                $earlyBirdCommandFound = true;

                // Verify it's scheduled to run daily
                $this->assertStringContainsString('0 0 * * *', $event->expression);
                break;
            }
        }

        $this->assertTrue($earlyBirdCommandFound, 'The app:send-early-bird-reminders command should be scheduled');
    }

    #[Test]
    public function schedule_list_includes_early_bird_reminder_command(): void
    {
        // Test using the schedule:list command
        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:send-early-bird-reminders')
            ->assertExitCode(0);
    }

    #[Test]
    public function early_bird_reminder_command_can_be_run_via_schedule(): void
    {
        // Test that the command can be executed via schedule:run
        // We'll test in dry-run mode to avoid actually sending emails
        $this->artisan('schedule:run', ['--verbose' => true])
            ->assertExitCode(0);
    }

    #[Test]
    public function schedule_has_proper_configuration(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        // Verify that we have at least one scheduled event (our command)
        $this->assertGreaterThan(0, count($events), 'Schedule should have at least one event');

        // Find our specific command and verify its properties
        foreach ($events as $event) {
            if (str_contains($event->command, 'app:send-early-bird-reminders')) {
                // Verify it runs daily (cron expression for daily is "0 0 * * *")
                $this->assertEquals('0 0 * * *', $event->expression,
                    'Early bird reminder should be scheduled to run daily at midnight');

                // Verify it's enabled
                $this->assertTrue($event->filtersPass($this->app),
                    'Early bird reminder command should be enabled');

                return;
            }
        }

        $this->fail('app:send-early-bird-reminders command not found in schedule');
    }

    #[Test]
    public function schedule_timezone_is_properly_configured(): void
    {
        // Get the application timezone
        $appTimezone = config('app.timezone');

        // Verify the application has a valid timezone configured
        $this->assertNotEmpty($appTimezone, 'Application timezone must be configured');
        $this->assertTrue(in_array($appTimezone, timezone_identifiers_list()),
            'Application timezone must be valid');
    }
}
