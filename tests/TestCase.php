<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

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
    }
}
