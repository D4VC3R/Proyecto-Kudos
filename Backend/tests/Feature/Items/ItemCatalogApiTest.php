<?php

namespace Tests\Feature\Items;

use App\Catalog\Providers\TmdbMovieProvider;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * F1-06: la API de ítems expone los campos del catálogo y la atribución de las fuentes.
 */
class ItemCatalogApiTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    private function movieCategory(): Category
    {
        return Category::factory()->create([
            'slug' => 'peliculas',
            'name' => 'Películas',
            'attribute_schema' => TmdbMovieProvider::ATTRIBUTE_SCHEMA,
        ]);
    }

    private function fightClub(Category $category, array $overrides = []): Item
    {
        return $this->createItem($category, array_merge([
            'name' => 'El club de la lucha',
            'original_title' => 'Fight Club',
            'released_at' => '1999-10-15',
            'attributes' => ['year' => 1999, 'genres' => ['Drama'], 'runtime' => 139, 'directors' => ['David Fincher']],
            'description_source' => 'tmdb',
            'description_url' => 'https://www.themoviedb.org/movie/550',
        ], $overrides));
    }

    public function test_el_detalle_expone_los_campos_del_catalogo(): void
    {
        $item = $this->fightClub($this->movieCategory());

        $response = $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'name', 'description', 'images', 'status', 'vote_avg', 'vote_count', 'can_vote',
                'category', 'creator',
                'original_title', 'released_at', 'attributes', 'slug',
                'attribution' => ['data_source', 'description_source'],
            ]])
            ->assertJsonPath('data.original_title', 'Fight Club')
            ->assertJsonPath('data.released_at', '1999-10-15')
            ->assertJsonPath('data.slug', $item->slug);

        $this->assertNotNull($item->slug);
        $this->assertEquals(
            ['year' => 1999, 'genres' => ['Drama'], 'runtime' => 139, 'directors' => ['David Fincher']],
            $response->json('data.attributes')
        );
    }

    public function test_el_detalle_de_un_item_de_tmdb_atribuye_a_tmdb(): void
    {
        $item = $this->fightClub($this->movieCategory());
        $item->sources()->create([
            'provider' => 'tmdb',
            'external_id' => 'movie:550',
            'url' => 'https://www.themoviedb.org/movie/550',
            'is_primary' => true,
        ]);
        $item->sources()->create([
            'provider' => 'wikidata',
            'external_id' => 'Q190050',
            'url' => 'https://www.wikidata.org/wiki/Q190050',
            'is_primary' => false,
        ]);

        $response = $this->getJson("/api/items/{$item->id}")->assertOk();

        $this->assertEquals(
            ['name' => 'TMDB', 'url' => 'https://www.themoviedb.org/movie/550'],
            $response->json('data.attribution.data_source')
        );
        $this->assertEquals(
            ['name' => 'TMDB', 'url' => 'https://www.themoviedb.org/movie/550', 'license' => null, 'license_url' => null],
            $response->json('data.attribution.description_source')
        );
    }

    public function test_una_descripcion_de_wikipedia_lleva_licencia_y_enlace(): void
    {
        $item = $this->fightClub($this->movieCategory(), [
            'description_source' => 'wikipedia_es',
            'description_url' => 'https://es.wikipedia.org/wiki/El_club_de_la_lucha',
        ]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.attribution.description_source.name', 'Wikipedia')
            ->assertJsonPath('data.attribution.description_source.url', 'https://es.wikipedia.org/wiki/El_club_de_la_lucha')
            ->assertJsonPath('data.attribution.description_source.license', 'CC BY-SA 4.0')
            ->assertJsonPath('data.attribution.description_source.license_url', 'https://creativecommons.org/licenses/by-sa/4.0/deed.es');
    }

    public function test_una_descripcion_de_wikidata_atribuye_a_wikidata(): void
    {
        $item = $this->fightClub($this->movieCategory(), [
            'description_source' => 'wikidata',
            'description_url' => 'https://www.wikidata.org/wiki/Q190050',
        ]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.attribution.description_source.name', 'Wikidata')
            ->assertJsonPath('data.attribution.description_source.url', 'https://www.wikidata.org/wiki/Q190050')
            ->assertJsonPath('data.attribution.description_source.license', 'CC0 1.0');
    }

    public function test_un_item_sin_fuentes_ni_descripcion_externa_no_tiene_atribucion(): void
    {
        $item = $this->createItem($this->createCategory());

        $response = $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.attribution.data_source', null)
            ->assertJsonPath('data.attribution.description_source', null)
            ->assertJsonPath('data.original_title', null)
            ->assertJsonPath('data.released_at', null)
            ->assertJsonPath('data.attributes', []);

        $this->assertStringContainsString('"attributes":{}', $response->getContent());
    }

    public function test_una_descripcion_manual_no_se_atribuye(): void
    {
        $item = $this->fightClub($this->movieCategory(), ['description_source' => 'manual', 'description_url' => null]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['attribution' => ['data_source', 'description_source']]])
            ->assertJsonPath('data.attribution.description_source', null);
    }

    public function test_sin_descripcion_no_hay_atribucion_de_descripcion(): void
    {
        $item = $this->fightClub($this->movieCategory(), [
            'description' => null,
            'description_source' => 'tmdb',
            'description_url' => 'https://www.themoviedb.org/movie/550',
        ]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['attribution' => ['data_source', 'description_source']]])
            ->assertJsonPath('data.attribution.description_source', null);
    }

    public function test_una_fuente_no_primaria_no_es_la_fuente_de_los_datos(): void
    {
        $item = $this->fightClub($this->movieCategory());
        $item->sources()->create([
            'provider' => 'wikidata',
            'external_id' => 'Q190050',
            'url' => 'https://www.wikidata.org/wiki/Q190050',
            'is_primary' => false,
        ]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['attribution' => ['data_source', 'description_source']]])
            ->assertJsonPath('data.attribution.data_source', null)
            ->assertJsonPath('data.attribution.description_source.name', 'TMDB');
    }

    public function test_el_listado_expone_original_title_released_at_y_atributos_de_tarjeta(): void
    {
        $this->fightClub($this->movieCategory());

        $response = $this->getJson('/api/items?category_slug=peliculas')
            ->assertOk()
            ->assertJsonPath('data.0.original_title', 'Fight Club')
            ->assertJsonPath('data.0.released_at', '1999-10-15');

        $attributes = $response->json('data.0.attributes');
        $this->assertEquals(['year' => 1999, 'genres' => ['Drama']], $attributes);
        $this->assertArrayNotHasKey('runtime', $attributes);
        $this->assertArrayNotHasKey('directors', $attributes);
    }

    public function test_el_listado_incluye_el_esquema_de_la_categoria_anidada(): void
    {
        $this->fightClub($this->movieCategory());

        $response = $this->getJson('/api/items?category_slug=peliculas')->assertOk();

        $this->assertEquals(TmdbMovieProvider::ATTRIBUTE_SCHEMA, $response->json('data.0.category.attribute_schema'));
    }

    public function test_sin_esquema_el_listado_no_expone_atributos(): void
    {
        $this->fightClub($this->createCategory());

        $response = $this->getJson('/api/items?category_slug=peliculas')
            ->assertOk()
            ->assertJsonPath('data.0.attributes', []);

        $this->assertStringContainsString('"attributes":{}', $response->getContent());
    }

    public function test_el_listado_conserva_los_campos_existentes(): void
    {
        $this->fightClub($this->movieCategory());

        $this->getJson('/api/items?category_slug=peliculas')
            ->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'name', 'description', 'images', 'status', 'vote_avg', 'vote_count', 'can_vote',
                'category' => ['id', 'name', 'description', 'slug', 'image', 'items_count', 'attribute_schema', 'icon', 'sort_order'],
                'creator', 'original_title', 'released_at', 'attributes',
            ]]]);
    }
}
