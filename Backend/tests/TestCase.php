<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
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

    /**
     * R4: ningún test hace peticiones HTTP reales. Toda petición que no case con un Http::fake()
     * lanza StrayRequestException. Va en setUp() porque cada test arranca una aplicación nueva;
     * parent::setUp() ya ha pasado por setUpTraits(), así que la protección de la base de datos va antes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}
