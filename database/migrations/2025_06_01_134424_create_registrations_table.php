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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Foreign key to users table, identifying the registered user.');

            // Personal Information
            $table->string('full_name')->comment('Full name of the participant at the time of registration.');
            $table->string('nationality')->nullable()->comment('Participant nationality.');
            $table->date('date_of_birth')->nullable()->comment('Participant date of birth.');
            $table->string('gender', 30)->nullable()->comment('Participant gender. E.g., male, female, other, prefer_not_to_say.');

            // Identification Details
            $table->string('document_country_origin')->nullable()->comment('Country of origin of the main identification document.');
            $table->string('cpf')->nullable()->index()->comment('CPF document number (for Brazilians).');
            $table->string('rg_number')->nullable()->comment('RG document number (for Brazilians).');
            $table->string('passport_number')->nullable()->index()->comment('Passport number (for internationals).');
            $table->date('passport_expiry_date')->nullable()->comment('Passport expiry date (for internationals).');

            // Contact Information
            $table->string('email')->comment('Primary contact email for the registration.');
            $table->string('phone_number')->nullable()->comment('Participant phone number.');
            $table->string('address_street')->nullable()->comment('Street address.');
            $table->string('address_city')->nullable()->comment('City.');
            $table->string('address_state_province')->nullable()->comment('State or province.');
            $table->string('address_country')->nullable()->comment('Country of residence.');
            $table->string('address_postal_code')->nullable()->comment('Postal code.');

            // Professional Details
            $table->string('affiliation')->nullable()->comment('Participant affiliation (University/Organization).');
            $table->string('position', 50)->nullable()->comment('Participant position/category. E.g., undergrad_student, grad_student.');
            $table->boolean('is_abe_member')->nullable()->default(false)->comment('Indicates if the participant is an ABE member.');

            // Event Participation
            $table->date('arrival_date')->nullable()->comment('Planned arrival date for the event.');
            $table->date('departure_date')->nullable()->comment('Planned departure date from the event.');
            $table->string('participation_format', 20)->nullable()->comment('Format of participation. E.g., in-person, online.');
            $table->boolean('needs_transport_from_gru')->default(false)->comment('Indicates if participant needs transport from GRU airport.');
            $table->boolean('needs_transport_from_usp')->default(false)->comment('Indicates if participant needs transport from USP campus.');

            // Dietary Restrictions
            $table->string('dietary_restrictions', 30)->nullable()->comment('Main dietary restriction. E.g., none, vegetarian, vegan.');
            $table->text('other_dietary_restrictions')->nullable()->comment('Details for other dietary restrictions.');

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable()->comment('Name of the emergency contact.');
            $table->string('emergency_contact_relationship')->nullable()->comment('Relationship with the emergency contact.');
            $table->string('emergency_contact_phone')->nullable()->comment('Phone number of the emergency contact.');

            // Visa Support
            $table->boolean('requires_visa_letter')->default(false)->comment('Indicates if the participant requires a visa invitation letter.');

            // Registration/Payment Data
            $table->string('registration_category_snapshot', 50)->comment('Participant category used for fee calculation at the time of registration.');
            $table->decimal('calculated_fee', 8, 2)->comment('Total fee calculated for the registration.');
            $table->string('payment_status', 50)->default('pending_payment')->index()->comment('Payment status of the registration. E.g., pending_payment, paid_br.');
            $table->string('payment_proof_path')->nullable()->comment('Path to the uploaded payment proof file (for Brazilians).');
            $table->timestamp('payment_uploaded_at')->nullable()->comment('Timestamp when payment proof was uploaded.');
            $table->timestamp('invoice_sent_at')->nullable()->comment('Timestamp when invoice was sent (for internationals).');
            $table->text('notes')->nullable()->comment('Administrative notes about the registration.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
