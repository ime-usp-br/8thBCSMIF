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
        Schema::create('event_registration', function (Blueprint $table) {
            $table->unsignedBigInteger('registration_id')->comment('Foreign key to registrations table.');
            $table->string('event_code')->comment('Foreign key to events table (event code).');
            $table->decimal('price_at_registration', 8, 2)->comment('Price of the event at the time of this specific registration.');
            $table->timestamps();

            $table->foreign('registration_id')
                ->references('id')
                ->on('registrations')
                ->onDelete('cascade');

            $table->foreign('event_code')
                ->references('code')
                ->on('events')
                ->onDelete('cascade');

            $table->primary(['registration_id', 'event_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registration');
    }
};
