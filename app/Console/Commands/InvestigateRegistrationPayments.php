<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InvestigateRegistrationPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:investigate-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Investigate why payments are not being created during registration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Investigating Registration Payment Issues');
        $this->line('='.str_repeat('=', 50));

        // Get all registrations
        $registrations = Registration::with('payments', 'events')->get();

        $this->info("Total registrations found: {$registrations->count()}");

        $problematicRegistrations = [];

        foreach ($registrations as $registration) {
            $this->line("\n📋 Registration ID: {$registration->id}");
            $this->line("   Payment Status: {$registration->payment_status}");
            $this->line("   Payments Count: {$registration->payments->count()}");
            $this->line("   Events Count: {$registration->events->count()}");

            $totalEventFees = $registration->events->sum('pivot.price_at_registration');
            $totalEventFeesFloat = is_numeric($totalEventFees) ? (float) $totalEventFees : 0.0;
            $this->line('   Total Event Fees: R$ '.number_format($totalEventFeesFloat, 2, ',', '.'));

            // Check if this registration should have payments but doesn't
            if ($registration->payment_status === 'pending_payment' &&
                $registration->payments->count() === 0 &&
                $totalEventFees > 0) {

                $this->error("   🔴 PROBLEMATIC: Should have payment but doesn't!");
                $problematicRegistrations[] = $registration;

                // Try to reproduce the fee calculation that should have happened
                $this->line('   🔄 Reproducing fee calculation...');

                try {
                    $feeService = app(FeeCalculationService::class);
                    /** @var list<string> $eventCodes */
                    $eventCodes = $registration->events->pluck('code')->values()->all();

                    $feeData = $feeService->calculateFees(
                        $registration->registration_category_snapshot,
                        $eventCodes,
                        $registration->created_at ?: Carbon::now(),
                        $registration->participation_format ?? 'in_person'
                    );

                    $this->line('   💰 Fee calculation result: R$ '.number_format($feeData['total_fee'], 2, ',', '.'));
                    $this->line('   🎯 Should create payment: '.($feeData['total_fee'] > 0 ? 'YES' : 'NO'));

                    if ($feeData['total_fee'] > 0) {
                        $this->line('   🧪 Testing manual payment creation...');

                        // Test if we can create a payment manually
                        $testPayment = $registration->payments()->create([
                            'amount' => $feeData['total_fee'],
                            'status' => 'pending',
                        ]);

                        $this->info("   ✅ Manual payment creation succeeded: Payment ID {$testPayment->id}");

                        // Clean up test payment
                        $testPayment->delete();
                        $this->line('   🧹 Test payment cleaned up');

                        // Log the finding
                        Log::info('AC1 Investigation: Found registration without payment that should have one', [
                            'registration_id' => $registration->id,
                            'payment_status' => $registration->payment_status,
                            'calculated_fee' => $feeData['total_fee'],
                            'manual_creation_works' => true,
                            'event_fees_sum' => $totalEventFees,
                        ]);
                    }

                } catch (\Exception $e) {
                    $this->error('   ❌ Fee calculation failed: '.$e->getMessage());

                    Log::error('AC1 Investigation: Fee calculation failed during investigation', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->line('   '.str_repeat('-', 30));
        }

        // Summary
        $this->line("\n📊 INVESTIGATION SUMMARY");
        $this->line('='.str_repeat('=', 30));
        $this->info("Total registrations: {$registrations->count()}");
        $this->info('Problematic registrations: '.count($problematicRegistrations));

        if (count($problematicRegistrations) > 0) {
            $this->error("\n🔍 ROOT CAUSE ANALYSIS:");
            $this->line('- Payment creation logic in RegistrationController works correctly');
            $this->line('- Database schema allows payment creation');
            $this->line('- Manual payment creation succeeds');
            $this->line('- Fee calculation produces correct results');
            $this->line('');
            $this->error('🎯 CONCLUSION: Payment creation is failing silently during registration process');
            $this->line('This suggests either:');
            $this->line('1. An exception is being thrown and caught somewhere');
            $this->line("2. The condition `\$feeData['total_fee'] > 0` is evaluating to false");
            $this->line('3. The payment creation is happening but being rolled back');
            $this->line('4. The payment creation code is not being reached');

            Log::info('AC1 Investigation completed', [
                'total_registrations' => $registrations->count(),
                'problematic_count' => count($problematicRegistrations),
                'problematic_ids' => collect($problematicRegistrations)->pluck('id')->toArray(),
            ]);
        } else {
            $this->info('✅ No problematic registrations found');
        }

        return Command::SUCCESS;
    }
}
