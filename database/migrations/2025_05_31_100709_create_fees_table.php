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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 30)->comment('Foreign key to events table, identifying the event.');
            $table->string('participant_category', 50)->comment('Category of the participant, e.g., undergrad_student, grad_student.');
            $table->string('type', 20)->default('in-person')->comment('Type of participation, e.g., in-person, online.');
            $table->string('period', 10)->default('early')->comment('Registration period, e.g., early, late.');
            $table->decimal('price', 8, 2)->comment('Price for this fee combination.');
            $table->boolean('is_discount_for_main_event_participant')->default(false)->comment('Indicates if this fee is a discounted price for main event participants (for workshops).');
            $table->timestamps();

            $table->foreign('event_code')
                  ->references('code')
                  ->on('events')
                  ->onDelete('cascade');

            $table->unique([
                'event_code',
                'participant_category',
                'type',
                'period',
                'is_discount_for_main_event_participant',
            ], 'fees_unique_combination_index');
            
            $table->index('participant_category');
            $table->index('type');
            $table->index('period');
            $table->index('is_discount_for_main_event_participant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
