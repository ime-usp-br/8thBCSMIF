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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Short code for the event, e.g., BCSMIF2025. Used as a logical key.');
            $table->string('name')->comment('Full name of the event or workshop.');
            $table->text('description')->nullable()->comment('Detailed description of the event.');
            $table->date('start_date')->comment('Event start date.');
            $table->date('end_date')->comment('Event end date.');
            $table->string('location')->comment('Location where the event will take place.');
            $table->date('registration_deadline_early')->nullable()->comment('Deadline for early bird registration discount.');
            $table->date('registration_deadline_late')->nullable()->comment('Final deadline for registration.');
            $table->boolean('is_main_conference')->default(false)->index()->comment('Indicates if this is the main conference event.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
