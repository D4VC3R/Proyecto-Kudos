<?php

namespace Tests\Feature\Catalog;

use App\Catalog\CatalogImporter;
use App\Catalog\Data\CatalogItemData;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class CatalogImporterTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private const DESCRIPTION = 'Thomas Anderson lleva una doble vida: de día es programador y de noche, un hacker llamado Neo.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    private function importer(): CatalogImporter
    {
        return app(CatalogImporter::class);
    }

    /** @param array<string, mixed> $overrides */
    private function data(array $overrides = []): CatalogItemData
    {
        $defaults = [
            'provider' => 'tmdb',
            'externalId' => 'movie:603',
            'categorySlug' => 'peliculas',
            'name' => 'Matrix',
            'originalTitle' => 'The Matrix',
            'description' => self::DESCRIPTION,
            'descriptionSource' => 'tmdb',
            'descriptionUrl' => 'https://www.themoviedb.org/movie/603',
            'releasedAt' => '1999-03-31',
            'attributes' => ['year' => 1999, 'genres' => ['Acción'], 'runtime' => 136],
            'popularity' => 85.1,
            'wikidataId' => 'Q83495',
            'sourceUrl' => 'https://www.themoviedb.org/movie/603',
            'images' => [
                ['url' => 'https://image.tmdb.org/t/p/w780/a.jpg', 'role' => 'poster', 'license' => '© titular de derechos', 'author' => null, 'source_url' => 'https://www.themoviedb.org/movie/603'],
                ['url' => 'https://image.tmdb.org/t/p/w1280/b.jpg', 'role' => 'banner', 'license' => '© titular de derechos', 'author' => null, 'source_url' => 'https://www.themoviedb.org/movie/603'],
            ],
        ];

        return new CatalogItemData(...array_merge($defaults, $overrides));
    }

    public function test_crea_el_item_con_su_fuente_principal_y_la_de_wikidata(): void
    {
        $item = $this->importer()->import($this->data())->fresh();

        $this->assertSame('Matrix', $item->name);
        $this->assertSame('The Matrix', $item->original_title);
        $this->assertSame(self::DESCRIPTION, $item->description);
        $this->assertSame('tmdb', $item->description_source);
        $this->assertSame('Q83495', $item->wikidata_id);
        $this->assertNull($item->creator_id);
        $this->assertSame(Item::STATUS_ACTIVE, $item->status);
        $this->assertSame('matrix-1999', $item->slug);
        $this->assertNotNull($item->last_synced_at);
        $this->assertSame('1999-03-31', $item->released_at->format('Y-m-d'));

        $tmdb = $item->sources()->where('provider', 'tmdb')->first();
        $this->assertSame('movie:603', $tmdb->external_id);
        $this->assertTrue($tmdb->is_primary);
        $this->assertSame('https://www.themoviedb.org/movie/603', $tmdb->url);

        $wikidata = $item->sources()->where('provider', 'wikidata')->first();
        $this->assertSame('Q83495', $wikidata->external_id);
        $this->assertFalse($wikidata->is_primary);
        $this->assertSame('https://www.wikidata.org/wiki/Q83495', $wikidata->url);
    }

    public function test_importar_dos_veces_el_mismo_dto_no_duplica_nada(): void
    {
        $first = $this->importer()->import($this->data());
        $firstSync = $first->fresh()->last_synced_at;

        $this->travel(1)->days();
        $second = $this->importer()->import($this->data());

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->slug, $second->fresh()->slug);
        $this->assertSame(1, Item::query()->count());
        $this->assertSame(2, ItemSource::query()->count());
        $this->assertTrue($second->fresh()->last_synced_at->greaterThan($firstSync));
    }

    public function test_reimportar_actualiza_los_campos_y_conserva_el_slug(): void
    {
        $item = $this->importer()->import($this->data());

        $this->importer()->import($this->data(['name' => 'Matrix (remasterizada)']));

        $item = $item->fresh();
        $this->assertSame('Matrix (remasterizada)', $item->name);
        $this->assertSame('matrix-1999', $item->slug);
    }

    public function test_encuentra_el_item_por_wikidata_id_dentro_de_la_misma_categoria(): void
    {
        $existing = $this->createItem(Category::query()->where('slug', 'peliculas')->first(), [
            'name' => 'Matrix antigua',
            'wikidata_id' => 'Q83495',
        ]);

        $item = $this->importer()->import($this->data());

        $this->assertSame($existing->id, $item->id);
        $this->assertSame(1, Item::query()->count());
        $this->assertSame('Matrix', $item->fresh()->name);
        $this->assertSame(2, $item->sources()->count());
    }

    public function test_un_qid_de_otra_categoria_no_fusiona_los_items(): void
    {
        Log::spy();
        $other = $this->createItem(Category::query()->where('slug', 'series')->first(), [
            'name' => 'Otra cosa',
            'wikidata_id' => 'Q83495',
        ]);

        $item = $this->importer()->import($this->data());

        $this->assertNotSame($other->id, $item->id);
        $this->assertSame(2, Item::query()->count());
        $item = $item->fresh();
        $this->assertNull($item->wikidata_id);
        $this->assertSame(0, $item->sources()->where('provider', 'wikidata')->count());
        $this->assertSame('Otra cosa', $other->fresh()->name);
        $this->assertSame('Q83495', $other->fresh()->wikidata_id);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'QID ya pertenece'))
            ->once();
    }

    public function test_nunca_fusiona_por_nombre(): void
    {
        $this->createItem(Category::query()->where('slug', 'peliculas')->first(), ['name' => 'Matrix']);

        $this->importer()->import($this->data(['wikidataId' => null]));

        $this->assertSame(2, Item::query()->where('name', 'Matrix')->count());
    }

    public function test_sin_descripcion_el_quality_score_queda_por_debajo_del_umbral(): void
    {
        $item = $this->importer()->import($this->data(['description' => null, 'descriptionSource' => null, 'descriptionUrl' => null]))->fresh();

        $this->assertNull($item->description);
        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $item->quality_score);
    }

    public function test_no_borra_una_descripcion_de_otra_fuente_si_el_proveedor_no_trae_ninguna(): void
    {
        $this->importer()->import($this->data([
            'description' => 'Resumen de Wikipedia.',
            'descriptionSource' => 'wikipedia_es',
            'descriptionUrl' => 'https://es.wikipedia.org/wiki/Matrix',
        ]));

        $item = $this->importer()->import($this->data(['description' => null, 'descriptionSource' => null, 'descriptionUrl' => null]))->fresh();

        $this->assertSame('Resumen de Wikipedia.', $item->description);
        $this->assertSame('wikipedia_es', $item->description_source);
        $this->assertSame('https://es.wikipedia.org/wiki/Matrix', $item->description_url);
    }

    public function test_borra_la_sinopsis_del_proveedor_si_desaparece(): void
    {
        $this->importer()->import($this->data());

        $item = $this->importer()->import($this->data(['description' => null, 'descriptionSource' => null, 'descriptionUrl' => null]))->fresh();

        $this->assertNull($item->description);
        $this->assertNull($item->description_source);
        $this->assertNull($item->description_url);
    }

    public function test_no_descarga_imagenes(): void
    {
        Http::fake();

        $item = $this->importer()->import($this->data())->fresh();

        $this->assertSame([], $item->images);
        Http::assertNothingSent();
    }

    public function test_un_item_borrado_no_se_resucita(): void
    {
        $item = $this->importer()->import($this->data());
        $item->delete();

        $this->importer()->import($this->data(['name' => 'Otro nombre']));

        $trashed = Item::withTrashed()->find($item->id);
        $this->assertTrue($trashed->trashed());
        $this->assertSame('Matrix', $trashed->name);
        $this->assertSame(1, Item::withTrashed()->count());
    }

    public function test_deja_una_sola_fuente_principal(): void
    {
        $item = $this->importer()->import($this->data());
        ItemSource::query()->where('item_id', $item->id)->where('provider', 'tmdb')->update(['is_primary' => false]);
        ItemSource::query()->create([
            'item_id' => $item->id, 'provider' => 'rawg', 'external_id' => '99', 'url' => null, 'is_primary' => true,
        ]);

        $this->importer()->import($this->data());

        $primary = $item->sources()->where('is_primary', true)->get();
        $this->assertCount(1, $primary);
        $this->assertSame('tmdb', $primary->first()->provider);
    }

    public function test_reimportar_no_reactiva_un_item_desactivado(): void
    {
        $item = $this->importer()->import($this->data());
        $item->update(['status' => Item::STATUS_INACTIVE]);

        $this->importer()->import($this->data(['name' => 'Matrix 2']));

        $item = $item->fresh();
        $this->assertSame('Matrix 2', $item->name);
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
    }

    public function test_un_item_nuevo_se_crea_activo(): void
    {
        $item = $this->importer()->import($this->data())->fresh();

        $this->assertSame(Item::STATUS_ACTIVE, $item->status);
    }

    public function test_deactivate_desactiva_el_item_sin_borrarlo(): void
    {
        $item = $this->importer()->import($this->data());

        $result = $this->importer()->deactivate('tmdb', 'movie:603');

        $this->assertSame($item->id, $result->id);
        $item = Item::withTrashed()->find($item->id);
        $this->assertFalse($item->trashed());
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
    }

    public function test_deactivate_sin_item_devuelve_null(): void
    {
        $this->assertNull($this->importer()->deactivate('tmdb', 'movie:999'));
        $this->assertSame(0, Item::query()->count());
    }

    public function test_falla_si_la_categoria_no_existe(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->importer()->import($this->data(['categorySlug' => 'no-existe']));
    }

    /** Juego de IGDB con la página de Steam como fuente secundaria (F4-01). */
    private function gameData(array $overrides = []): CatalogItemData
    {
        return $this->data(array_merge([
            'provider' => 'igdb',
            'externalId' => '113112',
            'categorySlug' => 'videojuegos',
            'name' => 'Hades',
            'originalTitle' => null,
            'description' => 'Desafía al dios de los muertos.',
            'descriptionSource' => 'steam',
            'descriptionUrl' => 'https://store.steampowered.com/app/1145360/',
            'releasedAt' => '2020-09-17',
            'attributes' => ['year' => 2020, 'genres' => ['Rol']],
            'wikidataId' => null,
            'sourceUrl' => 'https://www.igdb.com/games/hades--1',
            'images' => [],
            'secondarySources' => [['provider' => 'steam', 'external_id' => '1145360', 'url' => 'https://store.steampowered.com/app/1145360/']],
        ], $overrides));
    }

    public function test_crea_las_fuentes_secundarias_sin_marcarlas_como_principales(): void
    {
        $this->createCategory('videojuegos', 'Videojuegos');

        $item = $this->importer()->import($this->gameData());

        $igdb = $item->sources()->where('provider', 'igdb')->first();
        $steam = $item->sources()->where('provider', 'steam')->first();
        $this->assertTrue($igdb->is_primary);
        $this->assertSame('1145360', $steam->external_id);
        $this->assertFalse($steam->is_primary);
        $this->assertSame('https://store.steampowered.com/app/1145360/', $steam->url);
        $this->assertSame(2, $item->sources()->count());
    }

    public function test_una_fuente_secundaria_de_otro_item_no_se_mueve(): void
    {
        $this->createCategory('videojuegos', 'Videojuegos');
        Log::spy();
        $first = $this->importer()->import($this->gameData());

        $second = $this->importer()->import($this->gameData(['externalId' => '999', 'name' => 'Hades II', 'sourceUrl' => 'https://www.igdb.com/games/hades-ii']));

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->id, ItemSource::query()->where('provider', 'steam')->where('external_id', '1145360')->value('item_id'));
        $this->assertSame(0, $second->sources()->where('provider', 'steam')->count());
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'fuente secundaria'))
            ->once();
    }

    public function test_sustituye_la_fuente_secundaria_si_cambia_su_id(): void
    {
        $this->createCategory('videojuegos', 'Videojuegos');
        $item = $this->importer()->import($this->gameData());

        $this->importer()->import($this->gameData(['secondarySources' => [
            ['provider' => 'steam', 'external_id' => '2222', 'url' => 'https://store.steampowered.com/app/2222/'],
        ]]));

        $steam = $item->sources()->where('provider', 'steam')->get();
        $this->assertCount(1, $steam);
        $this->assertSame('2222', $steam[0]->external_id);
        $this->assertSame(1, Item::query()->count());
    }

    public function test_conserva_la_descripcion_de_steam_si_steam_no_responde(): void
    {
        $this->createCategory('videojuegos', 'Videojuegos');
        $item = $this->importer()->import($this->gameData());

        $this->importer()->import($this->gameData([
            'description' => null,
            'descriptionSource' => null,
            'descriptionUrl' => null,
        ]));

        $item = $item->fresh();
        $this->assertSame('Desafía al dios de los muertos.', $item->description);
        $this->assertSame('steam', $item->description_source);
        $this->assertSame('https://store.steampowered.com/app/1145360/', $item->description_url);
    }

    /** Destino de Wikidata (F4-02): el proveedor es Wikidata y su QID es el external_id. */
    private function placeData(array $overrides = []): CatalogItemData
    {
        return $this->data(array_merge([
            'provider' => 'wikidata',
            'externalId' => 'Q2807',
            'categorySlug' => 'destinos',
            'name' => 'Madrid',
            'originalTitle' => null,
            'description' => null,
            'descriptionSource' => null,
            'descriptionUrl' => null,
            'releasedAt' => null,
            'attributes' => ['kind' => 'ciudad', 'country' => 'España'],
            'popularity' => 3332035.0,
            'wikidataId' => 'Q2807',
            'sourceUrl' => 'https://www.wikidata.org/wiki/Q2807',
            'images' => [],
            'secondarySources' => [],
        ], $overrides));
    }

    public function test_un_proveedor_wikidata_deja_su_fuente_como_unica_y_principal(): void
    {
        $this->createCategory('destinos', 'Destinos');

        $this->importer()->import($this->placeData());
        $item = $this->importer()->import($this->placeData())->fresh();

        $this->assertSame('Q2807', $item->wikidata_id);
        $this->assertCount(1, $item->sources);

        $source = $item->sources->first();
        $this->assertSame('wikidata', $source->provider);
        $this->assertSame('Q2807', $source->external_id);
        $this->assertTrue($source->is_primary);
    }

    public function test_un_proveedor_wikidata_no_pierde_su_fuente_si_el_qid_es_de_otro_item(): void
    {
        $this->createCategory('destinos', 'Destinos');
        $this->createItem(Category::query()->where('slug', 'peliculas')->first(), ['wikidata_id' => 'Q2807']);

        $item = $this->importer()->import($this->placeData())->fresh();

        $this->assertNull($item->wikidata_id);
        $this->assertCount(1, $item->sources);

        $source = $item->sources->first();
        $this->assertSame('wikidata', $source->provider);
        $this->assertSame('Q2807', $source->external_id);
        $this->assertTrue($source->is_primary);
    }
}
