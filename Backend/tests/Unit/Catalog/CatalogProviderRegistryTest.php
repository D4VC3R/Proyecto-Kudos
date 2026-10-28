<?php

namespace Tests\Unit\Catalog;

use App\Catalog\CatalogProviderRegistry;
use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Data\CatalogItemData;
use App\Catalog\Providers\TmdbMovieProvider;
use App\Catalog\Providers\TmdbTvProvider;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use stdClass;
use Tests\TestCase;
use UnexpectedValueException;

class CatalogProviderRegistryTest extends TestCase
{
    private function fakeProvider(): CatalogProvider
    {
        return new class implements CatalogProvider
        {
            public function key(): string
            {
                return 'fake';
            }

            public function search(string $query): array
            {
                return [];
            }

            public function fetch(string $externalId): ?CatalogItemData
            {
                return null;
            }

            public function fetchNew(int $limit): iterable
            {
                return [];
            }
        };
    }

    public function test_devuelve_el_proveedor_configurado_para_la_categoria(): void
    {
        $fake = $this->fakeProvider();
        $this->app->instance('tests.catalog.fake-provider', $fake);
        config(['catalog.categories' => ['peliculas' => ['provider' => 'tests.catalog.fake-provider']]]);

        $registry = app(CatalogProviderRegistry::class);

        $this->assertSame($fake, $registry->forCategory('peliculas'));
        $this->assertTrue($registry->has('peliculas'));
    }

    public function test_lanza_una_excepcion_si_la_categoria_no_tiene_proveedor(): void
    {
        config(['catalog.categories' => ['peliculas' => ['provider' => 'tests.catalog.fake-provider']]]);

        $registry = app(CatalogProviderRegistry::class);

        $this->assertFalse($registry->has('libros'));
        $this->expectException(InvalidArgumentException::class);
        $registry->forCategory('libros');
    }

    public function test_lanza_una_excepcion_si_la_clase_no_implementa_el_contrato(): void
    {
        $this->app->instance('tests.catalog.not-a-provider', new stdClass);
        config(['catalog.categories' => ['peliculas' => ['provider' => 'tests.catalog.not-a-provider']]]);

        $this->expectException(UnexpectedValueException::class);
        app(CatalogProviderRegistry::class)->forCategory('peliculas');
    }

    public function test_lista_las_categorias_configuradas(): void
    {
        $this->assertSame(['peliculas', 'series'], app(CatalogProviderRegistry::class)->categorySlugs());
    }

    public function test_resuelve_los_proveedores_de_tmdb_de_peliculas_y_series(): void
    {
        Http::fake();

        $registry = app(CatalogProviderRegistry::class);

        $this->assertInstanceOf(TmdbMovieProvider::class, $registry->forCategory('peliculas'));
        $this->assertInstanceOf(TmdbTvProvider::class, $registry->forCategory('series'));
        Http::assertNothingSent();
    }

    public function test_traduce_el_proveedor_y_el_external_id_a_su_categoria(): void
    {
        Http::fake();
        config(['catalog.categories' => [
            'peliculas' => ['provider' => TmdbMovieProvider::class],
            'series' => ['provider' => TmdbTvProvider::class],
        ]]);

        $registry = app(CatalogProviderRegistry::class);

        $this->assertSame(['peliculas'], $registry->categoriesFor('tmdb', 'movie:603'));
        $this->assertSame(['series'], $registry->categoriesFor('tmdb', 'tv:1399'));
        $this->assertSame([], $registry->categoriesFor('tmdb', 'person:1'));
        $this->assertSame([], $registry->categoriesFor('igdb', '1'));
        Http::assertNothingSent();
    }

    public function test_un_proveedor_sin_recognizes_acepta_cualquier_external_id(): void
    {
        $this->app->instance('tests.catalog.fake-provider', $this->fakeProvider());
        config(['catalog.categories' => ['libros' => ['provider' => 'tests.catalog.fake-provider']]]);

        $this->assertSame(['libros'], app(CatalogProviderRegistry::class)->categoriesFor('fake', 'lo-que-sea'));
    }
}
