<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnrollmentProofMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_proofs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('enrollment_proofs'));
    }

    public function test_enrollment_proofs_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id',
            'registration_id',
            'file_path',
            'original_filename',
            'uploaded_at',
            'status',
            'approved_at',
            'approved_by',
            'rejection_reason',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('enrollment_proofs', $column),
                "Column '{$column}' does not exist in enrollment_proofs table"
            );
        }
    }

    public function test_enrollment_proofs_table_has_foreign_keys(): void
    {
        $this->assertTrue(Schema::hasColumn('enrollment_proofs', 'registration_id'));
        $this->assertTrue(Schema::hasColumn('enrollment_proofs', 'approved_by'));
    }
}
