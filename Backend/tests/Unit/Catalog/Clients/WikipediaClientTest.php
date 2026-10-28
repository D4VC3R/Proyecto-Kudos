<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\WikipediaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class WikipediaClientTest extends TestCase
{
    private const REST = 'es.wikipedia.org/api/rest_v1/page/summary/*';

    private const ACTION = 'es.wikipedia.org/w/api.php*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    /** @return array<string, mixed> */
    private function actionApiResponse(): array
    {
        return ['query' => ['pages' => [[
            'pageid' => 1,
            'title' => 'Matrix',
            'extract' => 'Matrix es una película de 1999.',
            'fullurl' => 'https://es.wikipedia.org/wiki/Matrix',
        ]]]];
    }

    /** @return array{title: string, extract: string, url: string} */
    private function expectedSummary(): array
    {
        return [
            'title' => 'Matrix',
            'extract' => 'Matrix es una película de 1999.',
            'url' => 'https://es.wikipedia.org/wiki/Matrix',
        ];
    }

    public function test_summary_usa_la_api_rest_y_envia_el_user_agent(): void
    {
        Http::fake([self::REST => Http::response([
            'title' => 'Matrix',
            'extract' => 'Matrix es una película de 1999.',
            'content_urls' => ['desktop' => ['page' => 'https://es.wikipedia.org/wiki/Matrix']],
        ])]);

        $summary = app(WikipediaClient::class)->summary('Matrix');

        $this->assertSame($this->expectedSummary(), $summary);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)'));
    }

    public function test_summary_codifica_el_titulo_en_la_url(): void
    {
        Http::fake([self::REST => Http::response(['title' => 'Matrix (película)', 'extract' => 'Texto.'])]);

        app(WikipediaClient::class)->summary('Matrix (película)');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://es.wikipedia.org/api/rest_v1/page/summary/Matrix_%28pel%C3%ADcula%29');
    }

    public function test_summary_recurre_a_la_action_api_si_rest_falla(): void
    {
        Http::fake([
            self::REST => Http::response([], 503),
            self::ACTION => Http::response($this->actionApiResponse()),
        ]);

        $summary = app(WikipediaClient::class)->summary('Matrix');

        $this->assertSame($this->expectedSummary(), $summary);
        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://es.wikipedia.org/w/api.php?')
                && $query['action'] === 'query'
                && $query['prop'] === 'extracts|info'
                && $query['exintro'] === '1'
                && $query['explaintext'] === '1'
                && $query['redirects'] === '1'
                && $query['titles'] === 'Matrix';
        });
        Http::assertSentCount(4); // 3 intentos de REST + 1 de la Action API
    }

    public function test_summary_recurre_a_la_action_api_si_rest_no_responde(): void
    {
        Http::fake([
            self::REST => Http::sequence()
                ->pushFailedConnection()
                ->pushFailedConnection()
                ->pushFailedConnection(),
            self::ACTION => Http::response($this->actionApiResponse()),
        ]);

        $summary = app(WikipediaClient::class)->summary('Matrix');

        $this->assertSame($this->expectedSummary(), $summary);
    }

    public function test_summary_devuelve_null_si_la_entrada_no_existe(): void
    {
        Http::fake([
            self::REST => Http::response([], 404),
            self::ACTION => Http::response(['query' => ['pages' => [['title' => 'No existe', 'missing' => true]]]]),
        ]);

        $this->assertNull(app(WikipediaClient::class)->summary('No existe'));
    }

    public function test_summary_devuelve_null_si_no_hay_extracto(): void
    {
        Http::fake([self::REST => Http::response(['title' => 'Matrix', 'extract' => ''])]);

        $this->assertNull(app(WikipediaClient::class)->summary('Matrix'));
        Http::assertSentCount(1);
    }
}
