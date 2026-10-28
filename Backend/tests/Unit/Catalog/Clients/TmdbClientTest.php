<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\TmdbClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class TmdbClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    public function test_envia_el_user_agent_el_token_y_el_idioma(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response(['id' => 603])]);

        $response = app(TmdbClient::class)->get('movie/603', ['append_to_response' => 'external_ids,credits']);

        $this->assertTrue($response->ok());
        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://api.themoviedb.org/3/movie/603?')
                && $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
                && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
                && $query['language'] === 'es-ES'
                && $query['append_to_response'] === 'external_ids,credits';
        });
    }

    public function test_fuerza_el_idioma_es_es_aunque_se_pida_otro(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::response(['id' => 603])]);

        app(TmdbClient::class)->get('movie/603', ['language' => 'en-US']);

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['language'] === 'es-ES';
        });
    }

    public function test_reintenta_ante_un_429_hasta_obtener_respuesta(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::sequence()
            ->push(['status_code' => 25], 429)
            ->push(['status_code' => 25], 429)
            ->push(['id' => 603]),
        ]);

        $response = app(TmdbClient::class)->get('movie/603');

        $this->assertTrue($response->ok());
        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_reintenta_ante_un_error_del_servidor(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::sequence()
            ->push([], 503)
            ->push(['id' => 603]),
        ]);

        $response = app(TmdbClient::class)->get('movie/603');

        $this->assertTrue($response->ok());
        Http::assertSentCount(2);
    }

    public function test_no_reintenta_ante_un_404(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::response(['status_code' => 34], 404)]);

        $response = app(TmdbClient::class)->get('movie/0');

        $this->assertTrue($response->notFound());
        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_devuelve_la_ultima_respuesta_tras_agotar_los_reintentos(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::response(['status_code' => 25], 429)]);

        $response = app(TmdbClient::class)->get('movie/603');

        $this->assertSame(429, $response->status());
        Http::assertSentCount(3);
    }

    public function test_reintenta_ante_un_fallo_de_conexion(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::sequence()
            ->pushFailedConnection()
            ->push(['id' => 603]),
        ]);

        $response = app(TmdbClient::class)->get('movie/603');

        $this->assertTrue($response->ok());
        Sleep::assertSleptTimes(1);
    }

    public function test_falla_sin_token_y_no_hace_peticiones(): void
    {
        config(['catalog.providers.tmdb.token' => null]);
        Http::fake();

        $this->assertThrows(
            fn () => app(TmdbClient::class)->get('movie/603'),
            RuntimeException::class,
            'TMDB_API_TOKEN',
        );

        Http::assertNothingSent();
    }

    public function test_respeta_el_limite_de_peticiones_por_segundo(): void
    {
        config(['catalog.providers.tmdb.rate_per_second' => 2]);
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Http::fake(['api.themoviedb.org/*' => Http::response(['ok' => true])]);

        $client = app(TmdbClient::class);
        $client->get('movie/603');
        $client->get('movie/603');
        $client->get('movie/603');

        Http::assertSentCount(3);
        Sleep::assertSleptTimes(1); // la tercera espera a la ventana siguiente
    }
}
