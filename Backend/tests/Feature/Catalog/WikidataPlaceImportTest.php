<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

/**
 * F4-02: importación de un destino de punta a punta (Wikidata + foto de Commons + descripción de Wikipedia/Wikidata).
 */
class WikidataPlaceImportTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)']);
        // Tiempo congelado y sincronizado con las esperas: el límite de Wikimedia avanza el reloj en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
        Bus::fake([DownloadItemImagesJob::class]);

        $this->createCategory('destinos', 'Destinos');
    }

    /**
     * @param  array<string, string>  $entities  QID => fixture de wbgetentities
     */
    private function fakeWikidata(array $entities): \Closure
    {
        return function (Request $request) use ($entities) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['props'] ?? null) === 'labels') {
                return Http::response($this->fixture('catalog/wikidata/labels-Q29.json'));
            }

            $fixture = $entities[$query['ids'] ?? ''] ?? null;

            return Http::response($fixture !== null
                ? $this->fixture("catalog/wikidata/{$fixture}.json")
                : ['entities' => [($query['ids'] ?? 'Q1') => ['id' => $query['ids'] ?? 'Q1', 'missing' => '']]]);
        };
    }

    private function sparqlFake(): \Closure
    {
        return function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);
            $query = (string) ($params['query'] ?? '');

            if (str_contains($query, 'wd:Q162620')) {
                return Http::response($this->fixture('catalog/wikidata/sparql-capitales-provincia.json'));
            }

            if (preg_match('/wdt:P17 wd:(Q\d+)/', $query, $m) === 1) {
                return Http::response($this->fixture("catalog/wikidata/sparql-ciudades-{$m[1]}.json"));
            }

            return Http::response($this->fixture('catalog/wikidata/sparql-paises.json'));
        };
    }

    /** @param  array<string, string>  $entities */
    private function fakeAll(array $entities = ['Q2807' => 'Q2807-madrid', 'Q29' => 'Q29-espana', 'Q5836' => 'Q2807-madrid'], string $commons = 'imageinfo-con-licencia'): void
    {
        Http::fake([
            'www.wikidata.org/w/api.php*' => $this->fakeWikidata($entities),
            'query.wikidata.org/sparql*' => $this->sparqlFake(),
            'commons.wikimedia.org/w/api.php*' => Http::response($this->fixture("catalog/commons/{$commons}.json")),
            'es.wikipedia.org/api/rest_v1/page/summary/*' => Http::response($this->fixture('catalog/wikipedia/summary-madrid.json')),
        ]);
    }

    private function import(string $qid = 'Q2807'): void
    {
        ImportCatalogItemJob::dispatchSync('destinos', $qid);
    }

    public function test_importa_una_ciudad_con_la_descripcion_de_wikipedia(): void
    {
        $this->fakeAll();

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('Madrid', $item->name);
        $this->assertSame('wikipedia_es', $item->description_source);
        $this->assertSame('https://es.wikipedia.org/wiki/Madrid', $item->description_url);
        $this->assertSame('Q2807', $item->wikidata_id);
        $this->assertSame('ciudad', $item->attributes['kind']);
        $this->assertSame('España', $item->attributes['country']);
        $this->assertSame(3332035, $item->attributes['population']);
        $this->assertEquals(['lat' => 40.416944, 'lng' => -3.703333], $item->attributes['coordinates']);
        $this->assertSame(Item::STATUS_ACTIVE, $item->status);

        $this->assertSame(1, $item->sources()->count());
        $source = $item->sources()->firstOrFail();
        $this->assertSame('wikidata', $source->provider);
        $this->assertSame('Q2807', $source->external_id);
        $this->assertTrue($source->is_primary);
        $this->assertSame('https://www.wikidata.org/wiki/Q2807', $source->url);

        Bus::assertDispatched(DownloadItemImagesJob::class, fn ($job) => $job->itemId === $item->id
            && count($job->images) === 1
            && $job->images[0]['license'] === 'CC BY-SA 4.0'
            && $job->images[0]['author'] === 'Ejemplo & otros');
    }

    public function test_reimportar_no_duplica_y_la_fuente_sigue_siendo_principal(): void
    {
        $this->fakeAll();

        $this->import();
        $this->import();

        $this->assertSame(1, Item::query()->count());
        $this->assertSame(1, ItemSource::query()->count());
        $this->assertTrue(ItemSource::query()->firstOrFail()->is_primary);
    }

    public function test_sin_eswiki_usa_la_descripcion_de_wikidata(): void
    {
        $this->fakeAll(['Q2807' => 'Q2807-madrid-sin-eswiki']);

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('wikidata', $item->description_source);
        $this->assertStringStartsWith('Capital de España.', (string) $item->description);
    }

    public function test_una_foto_sin_licencia_no_llega_al_job_de_imagenes(): void
    {
        $this->fakeAll(commons: 'imageinfo-sin-licencia');

        $this->import();

        $item = Item::query()->firstOrFail();
        Bus::assertDispatched(DownloadItemImagesJob::class, fn ($job) => $job->itemId === $item->id && $job->images === []);
    }

    public function test_si_la_entidad_ya_no_existe_se_desactiva(): void
    {
        $item = $this->createItem(Category::query()->where('slug', 'destinos')->firstOrFail());
        ItemSource::create([
            'item_id' => $item->id, 'provider' => 'wikidata', 'external_id' => 'Q2807',
            'url' => 'https://www.wikidata.org/wiki/Q2807', 'is_primary' => true,
        ]);
        Http::fake([
            'www.wikidata.org/w/api.php*' => Http::response(['entities' => ['Q2807' => ['id' => 'Q2807', 'missing' => '']]]),
        ]);

        $this->import();

        $this->assertSame(Item::STATUS_INACTIVE, $item->fresh()->status);
        $this->assertSame(1, Item::query()->count());
    }

    public function test_fetch_new_de_destinos_importa_paises_y_ciudades(): void
    {
        $this->fakeAll(['Q2807' => 'Q2807-madrid', 'Q29' => 'Q29-espana', 'Q5836' => 'Q2807-madrid-sin-foto']);

        $this->artisan('catalog:fetch-new', ['--category' => 'destinos', '--limit' => 3])->assertSuccessful();

        $this->assertSame(['Q29', 'Q2807', 'Q5836'], Item::query()->orderBy('id')->pluck('wikidata_id')->all());
        $this->assertSame('país', Item::query()->where('wikidata_id', 'Q29')->firstOrFail()->attributes['kind']);
        $this->assertSame(3, ItemSource::query()->where('provider', 'wikidata')->where('is_primary', true)->count());
        $this->assertSame(3, ItemSource::query()->count());
    }

    public function test_la_api_expone_las_coordenadas_y_atribuye_los_datos_a_wikidata(): void
    {
        $this->fakeAll();
        $this->import();
        $item = Item::query()->firstOrFail();

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.coordinates.lat', 40.416944)
            ->assertJsonPath('data.attribution.data_source.name', 'Wikidata');
    }
}
