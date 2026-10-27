<?php

namespace Tests\Feature\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class DiscoverableItemsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category, ['name' => 'Ficha completa']);
        $this->createItem($category, ['name' => 'Ficha pobre', 'images' => [], 'description' => 'Item importado.']);
    }

    private function names($response): array
    {
        return collect($response->json('data'))->pluck('name')->all();
    }

    public function test_una_ficha_pobre_no_aparece_en_la_cola_de_votacion(): void
    {
        $this->actingAsUser();
        Cache::flush();

        $response = $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ficha completa')
            ->assertJsonPath('meta.remaining', 0);

        // La cola no consume el ítem hasta que se vota: tras votar la buena, no queda nada (la pobre no está en la cola).
        $this->postJson('/api/votes', ['item_id' => $response->json('data.id'), 'type' => 'vote', 'score' => 7])
            ->assertCreated();

        $this->getJson('/api/categories/peliculas/next-item')->assertNoContent();
    }

    public function test_una_ficha_pobre_no_aparece_con_discoverable(): void
    {
        $response = $this->getJson('/api/items?category_slug=peliculas&sort_by=vote_avg&sort_order=desc&per_page=20&discoverable=1');

        $response->assertOk();
        $this->assertSame(['Ficha completa'], $this->names($response));
    }

    public function test_una_ficha_pobre_si_aparece_en_explorar_y_en_la_busqueda(): void
    {
        $response = $this->getJson('/api/items?category_slug=peliculas&sort_by=vote_avg&sort_order=desc&per_page=20');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['Ficha completa', 'Ficha pobre'], $this->names($response));

        $search = $this->getJson('/api/items?search=pobre');

        $search->assertOk();
        $this->assertSame(['Ficha pobre'], $this->names($search));
    }

    public function test_discoverable_debe_ser_booleano(): void
    {
        $this->getJson('/api/items?discoverable=quizas')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['discoverable']]]);
    }

    public function test_el_umbral_de_descubrimiento_es_configurable(): void
    {
        config(['catalog.min_quality_for_discovery' => 90]);

        $response = $this->getJson('/api/items?discoverable=1');

        $response->assertOk();
        $this->assertSame([], $this->names($response));
    }
}
