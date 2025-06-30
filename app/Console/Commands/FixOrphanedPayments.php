<?php

namespace App\Console\Commands;

use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixOrphanedPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:fix-orphaned-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies and fixes registrations that are missing payment records when they should have them.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to fix orphaned payments...');

        $registrations = Registration::where('payment_status', 'pending_payment')
            ->whereDoesntHave('payments')
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No orphaned payments found.');

            return Command::SUCCESS;
        }

        $fixedCount = 0;
        foreach ($registrations as $registration) {
            DB::beginTransaction();
            try {
                // Recalculate the total fee for the registration
                // This assumes the events and their prices at registration are still available
                /** @var float $totalFee */
                $totalFee = (float) $registration->events->sum('pivot.price_at_registration'); // @phpstan-ignore-line

                if ($totalFee > 0) {
                    $registration->payments()->create([
                        'amount' => $totalFee,
                        'status' => 'pending',
                    ]);
                    Log::info('Orphaned payment fixed for registration.', ['registration_id' => $registration->id, 'amount' => $totalFee]);
                    $this->info("Fixed payment for registration ID: {$registration->id} with amount: {$totalFee}");
                    $fixedCount++;
                } else {
                    Log::warning('Registration marked as pending_payment but has zero total fee, skipping.', ['registration_id' => $registration->id]);
                    $this->warn("Registration ID: {$registration->id} has pending_payment status but zero total fee. Skipping.");
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to fix orphaned payment for registration.', ['registration_id' => $registration->id, 'error' => $e->getMessage()]);
                $this->error("Failed to fix payment for registration ID: {$registration->id}. Error: {$e->getMessage()}");
            }
        }

        $this->info("Finished. Fixed {$fixedCount} orphaned payments.");

        return Command::SUCCESS;
    }
}
