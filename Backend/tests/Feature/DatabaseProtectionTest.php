<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_tests_usan_la_base_de_datos_testing(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('testing', config('database.connections.pgsql.database'));
    }

    public function test_la_proteccion_aborta_con_otra_base_de_datos(): void
    {
        // Solo cambia la configuración en memoria: no se conecta a kudos_db.
        config(['database.connections.pgsql.database' => 'kudos_db']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Los tests deben ejecutarse contra la base de datos 'testing', no 'kudos_db'.");

        $this->ensureTestingDatabase();
    }
}
