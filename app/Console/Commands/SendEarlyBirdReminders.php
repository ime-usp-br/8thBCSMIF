<?php

namespace App\Console\Commands;

use App\Mail\EarlyBirdReminderNotification;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendEarlyBirdReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-early-bird-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send early bird reminder emails to participants with pending payments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        // Find events with early bird deadlines approaching (within next 1 day)
        $upcomingEarlyBirdEvents = Event::where('registration_deadline_early', '>', $today)
            ->where('registration_deadline_early', '<=', $today->copy()->addDays(1))
            ->get();

        if ($upcomingEarlyBirdEvents->isEmpty()) {
            $this->info(__('No events with approaching early bird deadlines found.'));

            return;
        }

        $totalReminders = 0;

        foreach ($upcomingEarlyBirdEvents as $event) {
            $this->info(__('Processing event: :event', ['event' => $event->name]));

            // Find registrations with pending payments for this event
            // that were created before the early bird deadline
            $eligibleRegistrations = Registration::whereHas('events', function ($query) use ($event) {
                $query->where('events.code', $event->code);
            })
                ->whereHas('payments', function ($query) {
                    $query->where('status', 'pending');
                })
                ->where('created_at', '<=', $event->registration_deadline_early)
                ->with(['user', 'events', 'payments'])
                ->get();

            foreach ($eligibleRegistrations as $registration) {
                try {
                    Mail::to($registration->email)
                        ->queue(new EarlyBirdReminderNotification($registration, $event));

                    $totalReminders++;
                    $this->info(__('Reminder sent to: :email', ['email' => $registration->email]));
                } catch (\Exception $e) {
                    $this->error(__('Failed to send reminder to :email: :error', [
                        'email' => $registration->email,
                        'error' => $e->getMessage(),
                    ]));
                }
            }
        }

        $this->info(__('Early bird reminders sent: :count', ['count' => $totalReminders]));
    }
}
