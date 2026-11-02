<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\IgdbClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class IgdbClientTest extends TestCase
{
    private const BODY = 'fields name; where id = 1; limit 1;';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.igdb.client_id' => 'id-de-prueba',
            'catalog.providers.igdb.client_secret' => 'secreto-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
        Cache::flush();
    }

    /** @return array<string, mixed> */
    private function token(string $token = 'token-igdb-de-prueba', int $expiresIn = 5000000): array
    {
        return ['access_token' => $token, 'expires_in' => $expiresIn, 'token_type' => 'bearer'];
    }

    private function twitchRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://id.twitch.tv/'))->count();
    }

    private function igdbRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://api.igdb.com/'))->count();
    }

    public function test_pide_el_token_de_twitch_y_consulta_igdb_con_client_id_y_bearer(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::response($this->token()),
            'api.igdb.com/v4/games' => Http::response([['id' => 1]]),
        ]);

        $response = app(IgdbClient::class)->query('games', self::BODY);

        $this->assertTrue($response->ok());
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://api.igdb.com/v4/games'
            && $request->hasHeader('Client-ID', 'id-de-prueba')
            && $request->hasHeader('Authorization', 'Bearer token-igdb-de-prueba')
            && $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
            && $request->body() === self::BODY
            && str_starts_with($request->header('Content-Type')[0], 'text/plain'));
    }

    public function test_las_credenciales_van_en_el_cuerpo_y_no_en_la_url(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::response($this->token()),
            'api.igdb.com/v4/games' => Http::response([]),
        ]);

        app(IgdbClient::class)->query('games', self::BODY);

        Http::assertSent(function (Request $request) {
            if (! str_starts_with($request->url(), 'https://id.twitch.tv/')) {
                return false;
            }

            parse_str($request->body(), $form);

            return $request->method() === 'POST'
                && ! str_contains($request->url(), '?')
                && $form === [
                    'client_id' => 'id-de-prueba',
                    'client_secret' => 'secreto-de-prueba',
                    'grant_type' => 'client_credentials',
                ];
        });
    }

    public function test_cachea_el_token_hasta_que_caduca(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::response($this->token(expiresIn: 3600)),
            'api.igdb.com/v4/games' => Http::response([]),
        ]);

        $client = app(IgdbClient::class);
        $client->query('games', self::BODY);
        $client->query('games', self::BODY);

        $this->assertSame(1, $this->twitchRequests());

        $this->travel(3600)->seconds();
        $client->query('games', self::BODY);

        $this->assertSame(2, $this->twitchRequests());
    }

    public function test_con_un_401_renueva_el_token_y_repite_una_vez(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::sequence()
                ->push($this->token('token-viejo'))
                ->push($this->token('token-nuevo')),
            'api.igdb.com/v4/games' => Http::sequence()
                ->push(['message' => 'Authorization Failure'], 401)
                ->push([['id' => 1]]),
        ]);

        $response = app(IgdbClient::class)->query('games', self::BODY);

        $this->assertSame(200, $response->status());
        $this->assertSame(2, $this->twitchRequests());
        $this->assertSame(2, $this->igdbRequests());
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.igdb.com/')
            && $request->hasHeader('Authorization', 'Bearer token-nuevo'));
    }

    public function test_falla_sin_credenciales_y_no_hace_peticiones(): void
    {
        config(['catalog.providers.igdb.client_id' => null]);
        Http::fake();

        $this->assertThrows(
            fn () => app(IgdbClient::class)->query('games', self::BODY),
            RuntimeException::class,
            'IGDB_CLIENT_ID',
        );

        Http::assertNothingSent();
    }

    public function test_reintenta_ante_un_429(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::response($this->token()),
            'api.igdb.com/v4/games' => Http::sequence()
                ->push(['message' => 'Too Many Requests'], 429)
                ->push(['message' => 'Too Many Requests'], 429)
                ->push([['id' => 1]]),
        ]);

        $response = app(IgdbClient::class)->query('games', self::BODY);

        $this->assertTrue($response->ok());
        $this->assertSame(3, $this->igdbRequests());
    }

    public function test_respeta_el_limite_de_peticiones_por_segundo(): void
    {
        config(['catalog.providers.igdb.rate_per_second' => 2]);
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Http::fake([
            'id.twitch.tv/oauth2/token*' => Http::response($this->token()),
            'api.igdb.com/v4/games' => Http::response([]),
        ]);

        $client = app(IgdbClient::class);
        $client->query('games', self::BODY);
        $client->query('games', self::BODY);
        $client->query('games', self::BODY);

        $this->assertSame(3, $this->igdbRequests());
        Sleep::assertSleptTimes(1); // la tercera espera a la ventana siguiente
    }
}
