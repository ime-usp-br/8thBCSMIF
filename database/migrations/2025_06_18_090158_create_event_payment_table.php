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
        Schema::create('event_payment', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->comment('Foreign key to payments table.');
            $table->string('event_code')->comment('Foreign key to events table (event code).');
            $table->decimal('individual_price', 8, 2)->comment('Individual price for this event in this payment.');
            $table->foreignId('registration_id')->nullable()
                ->constrained('registrations')
                ->comment('Foreign key to registrations table (which registration this payment covers).');
            $table->timestamps();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade');

            $table->foreign('event_code')
                ->references('code')
                ->on('events')
                ->onDelete('cascade');

            $table->primary(['payment_id', 'event_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_payment');
    }
};
