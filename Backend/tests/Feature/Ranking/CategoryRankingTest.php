<?php

namespace Tests\Feature\Ranking;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class CategoryRankingTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_el_ranking_ordena_por_media_y_desempata_por_numero_de_votos(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');

        // Datos elegidos para que el orden no cambie con el ranking bayesiano de la F0-04 (m = 5, C = 5 de fallback):
        // A y B tienen la misma media, pero A tiene más votos; C tiene menos media; D no tiene votos.
        $d = $this->createItem($peliculas, ['name' => 'D', 'vote_avg' => 0, 'vote_count' => 0]);
        $c = $this->createItem($peliculas, ['name' => 'C', 'vote_avg' => 7, 'vote_count' => 10]);
        $b = $this->createItem($peliculas, ['name' => 'B', 'vote_avg' => 9, 'vote_count' => 4]);
        $a = $this->createItem($peliculas, ['name' => 'A', 'vote_avg' => 9, 'vote_count' => 10]);

        $response = $this->getJson('/api/categories/peliculas/ranking');

        $response->assertOk()
            ->assertJsonPath('data.category.slug', 'peliculas')
            ->assertJsonStructure([
                'data' => [
                    'category' => ['id', 'name', 'slug'],
                    'ranking' => [['id', 'name', 'vote_avg', 'vote_count']],
                ],
                'meta' => ['total', 'current_page', 'last_page', 'per_page'],
            ]);

        $ids = collect($response->json('data.ranking'))->pluck('id')->all();
        $this->assertSame([$a->id, $b->id, $c->id, $d->id], $ids);
    }

    public function test_el_ranking_excluye_los_items_inactivos(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $a = $this->createItem($peliculas, ['name' => 'A', 'vote_avg' => 9, 'vote_count' => 10]);
        $this->createItem($peliculas, [
            'name' => 'Inactivo',
            'status' => Item::STATUS_INACTIVE,
            'vote_avg' => 10,
            'vote_count' => 50,
        ]);
        $this->createItem($series, ['name' => 'De series', 'vote_avg' => 10, 'vote_count' => 50]);

        $response = $this->getJson('/api/categories/peliculas/ranking');

        $response->assertOk()->assertJsonPath('meta.total', 1);
        $ids = collect($response->json('data.ranking'))->pluck('id')->all();
        $this->assertSame([$a->id], $ids);
    }

    public function test_el_ranking_rechaza_un_per_page_fuera_de_rango(): void
    {
        $this->createCategory('peliculas', 'Películas');

        foreach (['-1', '0', '101', 'abc'] as $perPage) {
            $this->getJson("/api/categories/peliculas/ranking?per_page={$perPage}")
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'validation_error')
                ->assertJsonStructure(['error' => ['details' => ['per_page']]]);
        }
    }

    public function test_el_ranking_acepta_hasta_100_por_pagina(): void
    {
        $this->createCategory('peliculas', 'Películas');

        $this->getJson('/api/categories/peliculas/ranking?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_el_ranking_usa_10_por_pagina_por_defecto(): void
    {
        $this->createCategory('peliculas', 'Películas');

        $this->getJson('/api/categories/peliculas/ranking')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_el_ranking_incluye_el_recuento_de_items_activos_de_la_categoria(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItem($peliculas);
        $this->createItem($peliculas);
        $this->createItem($peliculas, ['status' => Item::STATUS_INACTIVE]);

        $this->getJson('/api/categories/peliculas/ranking')
            ->assertOk()
            ->assertJsonPath('data.category.items_count', 2);
    }
}
