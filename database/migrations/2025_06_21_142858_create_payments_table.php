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
            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade')
                ->comment('Foreign key to registrations table');
            $table->decimal('amount', 8, 2)->comment('Payment amount');
            $table->string('status')->index()->comment('Payment status: pending, paid, pending_approval, cancelled');
            $table->string('payment_proof_path')->nullable()->comment('Path to payment proof file');
            $table->timestamp('payment_date')->nullable()->comment('Date when payment was confirmed');
            $table->text('notes')->nullable()->comment('Administrative notes');
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
