<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Configura o ambiente de teste antes de cada teste na classe.
     *
     * Este método é chamado automaticamente pelo PHPUnit.
     * Aqui, desabilitamos o Vite para garantir que os testes PHPUnit/Feature
     * não dependam de assets compilados ou do servidor de desenvolvimento Vite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Ensure no active transactions from previous tests
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            // Ignore rollback errors
        }

        // Ensure Spatie permission cache is cleared for each test
        if (class_exists('\Spatie\Permission\PermissionRegistrar')) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    /**
     * Setup basic roles needed for testing.
     * Call this method in tests that need role-based functionality.
     */
    protected function setupBasicRoles(): void
    {
        // Create basic roles if they don't exist
        if (! Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }
        if (! Role::where('name', 'user')->exists()) {
            Role::create(['name' => 'user']);
        }
        if (! Role::where('name', 'coordinator')->exists()) {
            Role::create(['name' => 'coordinator']);
        }
    }

    /**
     * Ensure test database is properly migrated.
     * Call this method in tests that need a fully migrated database.
     */
    protected function ensureDatabaseMigrated(): void
    {
        // Check if migrations table exists, if not run migrations
        if (! DB::getSchemaBuilder()->hasTable('migrations')) {
            Artisan::call('migrate:fresh');
        }

        // Ensure critical tables exist
        if (! DB::getSchemaBuilder()->hasTable('registrations') ||
            ! DB::getSchemaBuilder()->hasColumn('registrations', 'status')) {
            Artisan::call('migrate:fresh');
        }
    }
}
