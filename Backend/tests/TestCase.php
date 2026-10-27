<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithUsers;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithUsers;

    /**
     * Protección: RefreshDatabase borra la base de datos. Nunca contra la de desarrollo o producción.
     *
     * Va en setUpTraits() y no en beforeRefreshingDatabase(): en PHP, el método vacío del trait
     * RefreshDatabase que usa cada test taparía el de esta clase y la comprobación no se ejecutaría.
     * setUpTraits() corre con la configuración ya cargada y antes de RefreshDatabase,
     * DatabaseMigrations y DatabaseTruncation.
     */
    protected function setUpTraits()
    {
        $this->ensureTestingDatabase();

        return parent::setUpTraits();
    }

    protected function ensureTestingDatabase(): void
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        if ($database !== 'testing') {
            throw new \RuntimeException("Los tests deben ejecutarse contra la base de datos 'testing', no '{$database}'.");
        }
    }
}
