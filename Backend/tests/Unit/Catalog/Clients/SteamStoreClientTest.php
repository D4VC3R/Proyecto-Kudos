<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\SteamStoreClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class SteamStoreClientTest extends TestCase
{
    use LoadsFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)']);
        // Tiempo congelado y sincronizado con las esperas: el límite de 1 petición cada 2 s avanza el reloj en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
    }

    public function test_pide_appdetails_en_espanol_con_cc_es(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::response($this->fixture('catalog/steam/appdetails-1145360-es.json'))]);

        app(SteamStoreClient::class)->appDetails('1145360');

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://store.steampowered.com/api/appdetails?')
                && $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
                && $query['appids'] === '1145360'
                && $query['l'] === 'spanish'
                && $query['cc'] === 'es';
        });
    }

    public function test_devuelve_la_descripcion_en_texto_plano_y_los_idiomas(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::response($this->fixture('catalog/steam/appdetails-1145360-es.json'))]);

        $details = app(SteamStoreClient::class)->appDetails('1145360');

        $this->assertSame(
            'Desafía al dios de los muertos mientras te abres paso a golpes para salir del inframundo en este "roguelike" de los creadores de Bastion, Transistor y Pyre.',
            $details['short_description'],
        );
        $this->assertStringContainsString('Español - España', (string) $details['supported_languages']);
    }

    public function test_success_false_cuenta_como_404(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::response($this->fixture('catalog/steam/appdetails-1145360-success-false.json'))]);

        $this->assertNull(app(SteamStoreClient::class)->appDetails('1145360'));
    }

    public function test_un_404_devuelve_null_sin_reintentar(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::response([], 404)]);

        $this->assertNull(app(SteamStoreClient::class)->appDetails('1145360'));
        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_reintenta_ante_un_429(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::sequence()
            ->push([], 429)
            ->push($this->fixture('catalog/steam/appdetails-1145360-es.json')),
        ]);

        $details = app(SteamStoreClient::class)->appDetails('1145360');

        $this->assertNotNull($details);
        Http::assertSentCount(2);
    }

    public function test_lanza_ante_un_fallo_de_conexion_persistente(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::sequence()
            ->pushFailedConnection()
            ->pushFailedConnection()
            ->pushFailedConnection(),
        ]);

        $this->expectException(ConnectionException::class);

        app(SteamStoreClient::class)->appDetails('1145360');
    }

    public function test_respeta_una_peticion_cada_dos_segundos(): void
    {
        Http::fake(['store.steampowered.com/api/appdetails*' => Http::response($this->fixture('catalog/steam/appdetails-1145360-es.json'))]);

        $client = app(SteamStoreClient::class);
        $client->appDetails('1145360');
        $client->appDetails('1145360');

        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    public function test_supports_spanish_detecta_el_espanol_en_supported_languages(): void
    {
        $es = $this->fixture('catalog/steam/appdetails-1145360-es.json')['1145360']['data']['supported_languages'];
        $en = $this->fixture('catalog/steam/appdetails-1145360-en.json')['1145360']['data']['supported_languages'];

        $this->assertTrue(SteamStoreClient::supportsSpanish($es));
        $this->assertFalse(SteamStoreClient::supportsSpanish($en));
        $this->assertFalse(SteamStoreClient::supportsSpanish(null));
        $this->assertTrue(SteamStoreClient::supportsSpanish('Espa&ntilde;ol - Espa&ntilde;a'));
    }

    public function test_rechaza_un_appid_no_numerico_sin_hacer_peticiones(): void
    {
        Http::fake();

        $this->assertThrows(
            fn () => app(SteamStoreClient::class)->appDetails('12ab'),
            InvalidArgumentException::class,
        );

        Http::assertNothingSent();
    }
}
