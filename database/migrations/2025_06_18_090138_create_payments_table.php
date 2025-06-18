<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Foreign key to users table, identifying the user making the payment.');

            $table->string('payment_reference')->unique()
                ->comment('Unique reference for this payment (e.g., PAY-20250618-ABC123).');

            $table->string('payment_method', 50)
                ->comment('Payment method used (bank_transfer, international_invoice, etc.).');

            $table->string('payment_status', 50)->default('pending_payment')->index()
                ->comment('Payment status (pending_payment, paid_br, paid_international, etc.).');

            $table->decimal('total_amount', 8, 2)
                ->comment('Total amount for this payment.');

            $table->string('payment_proof_path')->nullable()
                ->comment('Path to uploaded payment proof file.');

            $table->timestamp('payment_uploaded_at')->nullable()
                ->comment('Timestamp when payment proof was uploaded.');

            $table->timestamp('invoice_sent_at')->nullable()
                ->comment('Timestamp when invoice was sent (for internationals).');

            $table->text('notes')->nullable()
                ->comment('Administrative notes about this payment.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
