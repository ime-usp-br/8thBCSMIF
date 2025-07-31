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
        Schema::create('enrollment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade')
                ->comment('Foreign key to registrations table');
            $table->string('file_path')->nullable()->comment('Path to the uploaded enrollment proof file');
            $table->string('original_filename')->nullable()->comment('Original filename of the uploaded file');
            $table->timestamp('uploaded_at')->nullable()->comment('Timestamp when the file was uploaded');
            $table->string('status')->index()->comment('Status of the enrollment proof: pending, pending_approval, approved, rejected');
            $table->timestamp('approved_at')->nullable()->comment('Timestamp when the proof was approved');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ID of the admin user who approved the proof');
            $table->text('rejection_reason')->nullable()->comment('Reason for rejection if the proof was rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_proofs');
    }
};
