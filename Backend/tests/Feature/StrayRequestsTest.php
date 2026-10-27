<?php

namespace Tests\Feature;

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * R4: tests/TestCase.php bloquea las peticiones HTTP no simuladas en todas las clases de test.
 * Esta clase no llama a Http::preventStrayRequests() a propósito.
 */
class StrayRequestsTest extends TestCase
{
    public function test_una_peticion_http_no_simulada_falla_en_cualquier_clase_de_test(): void
    {
        // Va antes de la petición: sin la protección, el test falla aquí sin llegar a salir a la red.
        $this->assertTrue(Http::preventingStrayRequests());

        $this->expectException(StrayRequestException::class);
        $this->expectExceptionMessage('http://kudos.invalid/');

        Http::get('http://kudos.invalid/');
    }

    public function test_una_peticion_simulada_con_http_fake_sigue_funcionando(): void
    {
        Http::fake([
            'kudos.invalid/*' => Http::response(['ok' => true]),
        ]);

        $response = Http::get('http://kudos.invalid/ping');

        $this->assertTrue($response->ok());
        $this->assertTrue($response->json('ok'));
        Http::assertSentCount(1);
    }
}
