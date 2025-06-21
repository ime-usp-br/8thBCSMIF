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
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'calculated_fee',
                'payment_proof_path',
                'payment_uploaded_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->decimal('calculated_fee', 8, 2)->comment('Total fee calculated for the registration.');
            $table->string('payment_proof_path')->nullable()->comment('Path to the uploaded payment proof file (for Brazilians).');
            $table->timestamp('payment_uploaded_at')->nullable()->comment('Timestamp when payment proof was uploaded.');
        });
    }
};
