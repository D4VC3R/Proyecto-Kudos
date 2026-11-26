<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

/**
 * Importación del mismo título por dos procesos a la vez: no hay 500 ni ítems duplicados.
 */
class CatalogImportConcurrencyTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

    private Category $peliculas;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake();
        Cache::flush();
        Bus::fake([DownloadItemImagesJob::class]);

        $this->peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    /**
     * Simula un worker que importa el mismo título y confirma su fila mientras dura nuestra transacción:
     * justo antes de insertar la fuente tmdb/movie:603 crea esa misma fuente apuntando a $owner.
     *
     * En el test todo va en la misma conexión, así que el rollback de nuestra transacción también deshace
     * la fila del "otro proceso", que en la realidad ya estaría confirmada: por eso se vuelve a crear tras
     * el primer rollback.
     */
    private function simulateConcurrentImport(Item $owner): void
    {
        $inserted = false;
        $restored = false;

        $createOwnerSource = fn () => ItemSource::query()->create([
            'item_id' => $owner->id,
            'provider' => 'tmdb',
            'external_id' => 'movie:603',
            'url' => 'https://www.themoviedb.org/movie/603',
            'is_primary' => true,
        ]);

        ItemSource::creating(function (ItemSource $source) use (&$inserted, $createOwnerSource): void {
            if ($inserted || $source->provider !== 'tmdb' || $source->external_id !== 'movie:603') {
                return;
            }

            $inserted = true; // antes de crear la del otro proceso, para no repetirlo con ella
            $createOwnerSource();
        });

        Event::listen(TransactionRolledBack::class, function () use (&$inserted, &$restored, $owner, $createOwnerSource): void {
            if (! $inserted || $restored) {
                return;
            }

            $restored = true;

            if (! ItemSource::query()->where('item_id', $owner->id)->where('provider', 'tmdb')->exists()) {
                $createOwnerSource();
            }
        });
    }

    private function fakeMovie(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);
    }

    public function test_una_fuente_creada_a_mitad_de_la_importacion_bajo_demanda_no_da_500_ni_duplica(): void
    {
        $owner = $this->createItem($this->peliculas);
        $this->simulateConcurrentImport($owner);
        $this->fakeMovie();
        $this->actingAsUser();

        $this->postJson('/api/catalog/import', ['category' => 'peliculas', 'external_id' => 'movie:603'])
            ->assertOk()
            ->assertJsonPath('data.item_id', $owner->id);

        $this->assertSame(1, Item::query()->count());
        $this->assertSame($owner->id, ItemSource::query()->where('provider', 'tmdb')->where('external_id', 'movie:603')->value('item_id'));
        $this->assertSame('Q83495', $owner->fresh()->wikidata_id);
    }

    public function test_el_job_tampoco_duplica_si_otro_proceso_crea_la_fuente_a_la_vez(): void
    {
        $owner = $this->createItem($this->peliculas);
        $this->simulateConcurrentImport($owner);
        $this->fakeMovie();

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $this->assertSame(1, Item::query()->count());
        $this->assertSame($owner->id, ItemSource::query()->where('provider', 'tmdb')->where('external_id', 'movie:603')->value('item_id'));
    }
}
