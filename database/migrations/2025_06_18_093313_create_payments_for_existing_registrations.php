<?php

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Payment records for existing registrations that don't have payments yet
        DB::transaction(function () {
            $registrationsWithoutPayments = Registration::whereDoesntHave('user.payments')->with(['user', 'events'])->get();

            foreach ($registrationsWithoutPayments as $registration) {
                if (! $registration->user) {
                    continue;
                }

                // Determine payment status based on registration payment_status
                $paymentStatus = match ($registration->payment_status) {
                    'paid_br', 'paid_international', 'approved' => 'paid_br',
                    'free' => 'paid_br', // Free registrations are considered paid
                    default => 'pending_payment'
                };

                // Create payment record
                $payment = Payment::create([
                    'user_id' => $registration->user_id,
                    'payment_reference' => Payment::generatePaymentReference(),
                    'payment_method' => $registration->payment_status === 'paid_international' ? 'international_invoice' : 'bank_transfer',
                    'payment_status' => $paymentStatus,
                    'total_amount' => $registration->calculated_fee,
                    'payment_proof_path' => $registration->payment_proof_path,
                    'payment_uploaded_at' => $registration->payment_uploaded_at,
                    'invoice_sent_at' => $registration->invoice_sent_at,
                    'created_at' => $registration->created_at,
                    'updated_at' => $registration->updated_at,
                ]);

                // Associate events with the payment
                foreach ($registration->events as $event) {
                    $payment->events()->attach($event->code, [
                        'individual_price' => $event->pivot->price_at_registration,
                        'registration_id' => $registration->id,
                        'created_at' => $registration->created_at,
                        'updated_at' => $registration->updated_at,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all payments that were created from existing registrations
        // This is a bit drastic, but necessary for a clean rollback
        // In production, you might want to be more careful about this
        Payment::whereHas('events', function ($query) {
            $query->whereNotNull('registration_id');
        })->delete();
    }
};
