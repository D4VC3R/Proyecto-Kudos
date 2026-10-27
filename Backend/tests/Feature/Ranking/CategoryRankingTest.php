<?php

namespace Tests\Feature\Ranking;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class CategoryRankingTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

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
                    'ranking'  => [['id', 'name', 'vote_avg', 'vote_count']],
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
            'name'       => 'Inactivo',
            'status'     => Item::STATUS_INACTIVE,
            'vote_avg'   => 10,
            'vote_count' => 50,
        ]);
        $this->createItem($series, ['name' => 'De series', 'vote_avg' => 10, 'vote_count' => 50]);

        $response = $this->getJson('/api/categories/peliculas/ranking');

        $response->assertOk()->assertJsonPath('meta.total', 1);
        $ids = collect($response->json('data.ranking'))->pluck('id')->all();
        $this->assertSame([$a->id], $ids);
    }
}
