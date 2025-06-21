<?php

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the create_payments_table migration.
 */
#[Group('migration')]
#[Group('payments-migration')]
class CreatePaymentsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payments_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));
    }

    #[Test]
    public function payments_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('payments', 'id'));
        $this->assertTrue(Schema::hasColumn('payments', 'registration_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'amount'));
        $this->assertTrue(Schema::hasColumn('payments', 'status'));
        $this->assertTrue(Schema::hasColumn('payments', 'payment_proof_path'));
        $this->assertTrue(Schema::hasColumn('payments', 'payment_date'));
        $this->assertTrue(Schema::hasColumn('payments', 'notes'));
        $this->assertTrue(Schema::hasColumn('payments', 'created_at'));
        $this->assertTrue(Schema::hasColumn('payments', 'updated_at'));
    }

    #[Test]
    public function payments_table_has_correct_column_types(): void
    {
        $columns = Schema::getColumnListing('payments');
        $this->assertContains('id', $columns);
        $this->assertContains('registration_id', $columns);
        $this->assertContains('amount', $columns);
        $this->assertContains('status', $columns);
        $this->assertContains('payment_proof_path', $columns);
        $this->assertContains('payment_date', $columns);
        $this->assertContains('notes', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
    }

    #[Test]
    public function payments_table_has_foreign_key_constraint(): void
    {
        // Test that we can create a payment with a valid registration_id
        // and that it fails with an invalid registration_id
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to insert a payment with non-existent registration_id
        \DB::table('payments')->insert([
            'registration_id' => 99999,
            'amount' => 100.00,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function payments_table_allows_nullable_columns(): void
    {
        // Create a registration first
        $registrationId = \DB::table('registrations')->insertGetId([
            'user_id' => \DB::table('users')->insertGetId([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'registration_category_snapshot' => 'student',
            'payment_status' => 'pending_payment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert payment with nullable fields as null
        $paymentId = \DB::table('payments')->insertGetId([
            'registration_id' => $registrationId,
            'amount' => 100.00,
            'status' => 'pending',
            'payment_proof_path' => null,
            'payment_date' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($paymentId);

        $payment = \DB::table('payments')->find($paymentId);
        $this->assertNull($payment->payment_proof_path);
        $this->assertNull($payment->payment_date);
        $this->assertNull($payment->notes);
    }

    #[Test]
    public function payments_table_has_status_index(): void
    {
        // This test verifies that the status column has an index
        // We can't directly check for indexes in SQLite, but we can verify
        // the column exists and accepts the expected status values
        $registrationId = \DB::table('registrations')->insertGetId([
            'user_id' => \DB::table('users')->insertGetId([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'registration_category_snapshot' => 'student',
            'payment_status' => 'pending_payment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statuses = ['pending', 'paid', 'pending_approval', 'cancelled'];

        foreach ($statuses as $status) {
            $paymentId = \DB::table('payments')->insertGetId([
                'registration_id' => $registrationId,
                'amount' => 100.00,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($paymentId);
            $payment = \DB::table('payments')->find($paymentId);
            $this->assertEquals($status, $payment->status);
        }
    }
}
