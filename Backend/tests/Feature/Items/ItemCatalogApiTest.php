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
    use CreatesItems, RefreshDatabase;

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

    public function test_un_juego_de_igdb_atribuye_a_igdb_y_la_descripcion_a_steam(): void
    {
        $category = Category::factory()->create(['slug' => 'videojuegos', 'name' => 'Videojuegos']);
        $item = $this->createItem($category, [
            'name' => 'Hades',
            'description_source' => 'steam',
            'description_url' => 'https://store.steampowered.com/app/1145360/',
        ]);
        $item->sources()->create([
            'provider' => 'igdb',
            'external_id' => '113112',
            'url' => 'https://www.igdb.com/games/hades--1',
            'is_primary' => true,
        ]);
        $item->sources()->create([
            'provider' => 'steam',
            'external_id' => '1145360',
            'url' => 'https://store.steampowered.com/app/1145360/',
            'is_primary' => false,
        ]);

        $response = $this->getJson("/api/items/{$item->id}")->assertOk();

        $this->assertEquals(
            ['name' => 'IGDB', 'url' => 'https://www.igdb.com/games/hades--1'],
            $response->json('data.attribution.data_source')
        );
        $this->assertEquals(
            ['name' => 'Steam', 'url' => 'https://store.steampowered.com/app/1145360/', 'license' => null, 'license_url' => null],
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

    /**
     * Imagen de Commons tal como la guarda DownloadItemImagesJob (F4-02): con licencia, autor y página del archivo.
     */
    private function commonsImage(int $order, string $license, array $overrides = []): array
    {
        return array_merge([
            'disk' => 'public',
            'alt' => 'Madrid',
            'order' => $order,
            'role' => 'poster',
            'variants' => ['thumb' => "items/tests/madrid-{$order}-thumb.webp", 'banner' => "items/tests/madrid-{$order}-banner.webp"],
            'license' => $license,
            'author' => 'Ejemplo & otros',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Madrid_skyline.jpg',
        ], $overrides);
    }

    public function test_el_detalle_expone_la_licencia_el_autor_y_la_pagina_de_una_foto_de_commons(): void
    {
        $category = Category::factory()->create(['slug' => 'destinos', 'name' => 'Destinos']);
        $item = $this->createItem($category, ['images' => [$this->commonsImage(0, 'CC BY-SA 4.0')]]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0.license', 'CC BY-SA 4.0')
            ->assertJsonPath('data.images.0.license_url', 'https://creativecommons.org/licenses/by-sa/4.0/deed.es')
            ->assertJsonPath('data.images.0.author', 'Ejemplo & otros')
            ->assertJsonPath('data.images.0.source_url', 'https://commons.wikimedia.org/wiki/File:Madrid_skyline.jpg')
            ->assertJsonPath('data.images.0.variants.thumb', 'items/tests/madrid-0-thumb.webp')
            ->assertJsonPath('data.images.0.alt', 'Madrid')
            ->assertJsonPath('data.images.0.order', 0);
    }

    public function test_la_licencia_de_cada_foto_enlaza_a_su_texto_cuando_se_conoce(): void
    {
        $category = Category::factory()->create(['slug' => 'destinos', 'name' => 'Destinos']);
        $item = $this->createItem($category, ['images' => [
            $this->commonsImage(0, 'CC BY 2.0'),
            $this->commonsImage(1, 'CC BY-SA 3.0 de'),
            $this->commonsImage(2, 'CC0'),
            $this->commonsImage(3, 'CC0 1.0'),
            $this->commonsImage(4, 'Public domain'),
            $this->commonsImage(5, '© titular de derechos'),
        ]]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0.license_url', 'https://creativecommons.org/licenses/by/2.0/deed.es')
            ->assertJsonPath('data.images.1.license_url', 'https://creativecommons.org/licenses/by-sa/3.0/de/deed.es')
            ->assertJsonPath('data.images.2.license_url', 'https://creativecommons.org/publicdomain/zero/1.0/deed.es')
            ->assertJsonPath('data.images.3.license_url', 'https://creativecommons.org/publicdomain/zero/1.0/deed.es')
            ->assertJsonPath('data.images.4.license_url', null)
            ->assertJsonPath('data.images.5.license_url', null);
    }

    public function test_una_imagen_sin_datos_de_licencia_conserva_los_campos_y_devuelve_nulos(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['images' => [[
                'variants' => ['thumb', 'banner'], 'meta', 'alt', 'order', 'license', 'license_url', 'author', 'source_url',
            ]]]])
            ->assertJsonPath('data.images.0.license', null)
            ->assertJsonPath('data.images.0.license_url', null)
            ->assertJsonPath('data.images.0.author', null)
            ->assertJsonPath('data.images.0.source_url', null);
    }

    public function test_una_portada_de_igdb_no_tiene_autor_ni_enlace_de_licencia(): void
    {
        $category = Category::factory()->create(['slug' => 'videojuegos', 'name' => 'Videojuegos']);
        $item = $this->createItem($category, ['images' => [$this->commonsImage(0, '© titular de derechos', [
            'author' => null,
            'source_url' => 'https://www.igdb.com/games/hades--1',
        ])]]);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0.license', '© titular de derechos')
            ->assertJsonPath('data.images.0.license_url', null)
            ->assertJsonPath('data.images.0.author', null)
            ->assertJsonPath('data.images.0.source_url', 'https://www.igdb.com/games/hades--1');
    }

    public function test_una_descripcion_de_steam_en_ingles_enlaza_a_la_tienda_en_ingles(): void
    {
        $category = Category::factory()->create(['slug' => 'videojuegos', 'name' => 'Videojuegos']);
        $item = $this->createItem($category, [
            'description_source' => 'steam',
            'description_url' => 'https://store.steampowered.com/app/1145360/?l=english',
        ]);

        $response = $this->getJson("/api/items/{$item->id}")->assertOk();

        $this->assertEquals(
            ['name' => 'Steam', 'url' => 'https://store.steampowered.com/app/1145360/?l=english', 'license' => null, 'license_url' => null],
            $response->json('data.attribution.description_source')
        );
    }

    public function test_el_siguiente_item_para_votar_incluye_el_credito_de_la_foto(): void
    {
        $this->actingAsUser();
        $category = Category::factory()->create(['slug' => 'destinos', 'name' => 'Destinos']);
        $this->createItem($category, ['images' => [$this->commonsImage(0, 'CC BY-SA 4.0')]]);

        $this->getJson('/api/categories/destinos/next-item')
            ->assertOk()
            ->assertJsonPath('data.images.0.author', 'Ejemplo & otros')
            ->assertJsonPath('data.images.0.license_url', 'https://creativecommons.org/licenses/by-sa/4.0/deed.es');
    }
}
