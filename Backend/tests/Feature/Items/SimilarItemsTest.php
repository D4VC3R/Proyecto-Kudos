<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Ítems similares de la ficha (F2-08): GET /api/items/{id}/similar.
 */
class SimilarItemsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_ordena_por_generos_en_comun_direccion_y_decada(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, [
            'name' => 'Origen',
            'attributes' => ['genres' => ['Drama', 'Crimen', 'Suspense'], 'directors' => ['Christopher Nolan'], 'year' => 2008],
            'released_at' => '2008-07-18',
        ]);

        // 1 género + Nolan + misma década = 1 + 2 + 1 = 4
        $b = $this->createItem($peliculas, [
            'name' => 'B',
            'attributes' => ['genres' => ['Drama'], 'directors' => ['Christopher Nolan']],
            'released_at' => '2005-01-01',
        ]);
        // 3 géneros, otra década = 3
        $a = $this->createItem($peliculas, [
            'name' => 'A',
            'attributes' => ['genres' => ['Drama', 'Crimen', 'Suspense']],
            'released_at' => '1972-03-24',
        ]);
        // 2 géneros, otra década = 2
        $c = $this->createItem($peliculas, [
            'name' => 'C',
            'attributes' => ['genres' => ['Crimen', 'Suspense']],
            'released_at' => '1994-10-14',
        ]);
        // Sin géneros en común, sin released_at y year 2003 (misma década) = 1
        $d = $this->createItem($peliculas, [
            'name' => 'D',
            'attributes' => ['genres' => ['Comedia'], 'year' => 2003],
        ]);
        // Nada en común = 0
        $e = $this->createItem($peliculas, [
            'name' => 'E',
            'attributes' => ['genres' => ['Comedia']],
            'released_at' => '1950-01-01',
        ]);

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');

        $this->assertSame([$b->id, $a->id, $c->id, $d->id], $ids);
        $this->assertNotContains($e->id, $ids);
    }

    public function test_comparte_creacion_en_series_suma_dos_puntos(): void
    {
        $series = $this->createCategory('series', 'Series');
        $origin = $this->createItem($series, [
            'name' => 'Breaking Bad',
            'attributes' => ['genres' => ['Drama'], 'creators' => ['Vince Gilligan']],
        ]);
        $withGenre = $this->createItem($series, ['name' => 'Con género', 'attributes' => ['genres' => ['Drama']]]);
        $withCreator = $this->createItem($series, ['name' => 'Con creador', 'attributes' => ['creators' => ['Vince Gilligan', 'Otro']]]);

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');

        $this->assertSame([$withCreator->id, $withGenre->id], $ids);
    }

    public function test_desempata_por_bayes_score_y_despues_por_popularidad(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => ['Drama']]]);
        $z = $this->createItem($peliculas, ['name' => 'Z', 'attributes' => ['genres' => ['Drama']], 'bayes_score' => 7, 'popularity' => 50]);
        $y = $this->createItem($peliculas, ['name' => 'Y', 'attributes' => ['genres' => ['Drama']], 'bayes_score' => 7, 'popularity' => 100]);
        $x = $this->createItem($peliculas, ['name' => 'X', 'attributes' => ['genres' => ['Drama']], 'bayes_score' => 8, 'popularity' => 1]);

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');

        $this->assertSame([$x->id, $y->id, $z->id], $ids);
    }

    public function test_excluye_el_propio_item_otras_categorias_inactivos_de_baja_calidad_y_borrados(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $attributes = ['genres' => ['Drama', 'Crimen']];

        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => $attributes]);
        $otherCategory = $this->createItem($series, ['name' => 'De otra categoría', 'attributes' => $attributes]);
        $inactive = $this->createItem($peliculas, ['name' => 'Inactivo', 'attributes' => $attributes, 'status' => Item::STATUS_INACTIVE]);
        $poor = $this->createItem($peliculas, ['name' => 'Ficha pobre', 'attributes' => $attributes, 'images' => [], 'description' => 'Item importado.']);
        $deleted = $this->createItem($peliculas, ['name' => 'Borrado', 'attributes' => $attributes]);
        $eligible = $this->createItem($peliculas, ['name' => 'Elegible', 'attributes' => $attributes]);

        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $poor->fresh()->quality_score);
        $deleted->delete();

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');

        $this->assertSame([$eligible->id], $ids);
        foreach ([$origin, $otherCategory, $inactive, $poor, $deleted] as $excluded) {
            $this->assertNotContains($excluded->id, $ids);
        }
    }

    public function test_devuelve_como_mucho_doce_items(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => ['Drama']]]);

        for ($i = 1; $i <= 15; $i++) {
            $this->createItem($peliculas, ['name' => "Similar {$i}", 'attributes' => ['genres' => ['Drama']]]);
        }

        $this->assertCount(12, $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data'));
    }

    public function test_un_item_sin_generos_direccion_ni_ano_no_tiene_similares(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => []]);
        $this->createItem($peliculas, ['name' => 'Otro', 'attributes' => ['genres' => ['Drama'], 'year' => 2000]]);

        $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_generos_con_comillas_e_interrogaciones_no_rompen_la_consulta(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $genres = ['¿Misterio?', "Acción d'autor"];
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => $genres, 'directors' => ["Dario D'Argento?"]]]);
        $candidate = $this->createItem($peliculas, ['name' => 'Candidato', 'attributes' => ['genres' => $genres]]);

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');

        $this->assertSame([$candidate->id], $ids);
    }

    public function test_cachea_la_lista_durante_24_horas(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => ['Drama']]]);
        $first = $this->createItem($peliculas, ['name' => 'Primero', 'attributes' => ['genres' => ['Drama']]]);

        $this->assertSame([$first->id], $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id'));

        $late = $this->createItem($peliculas, ['name' => 'Tardío', 'attributes' => ['genres' => ['Drama']]]);

        // Sigue la lista cacheada
        $this->assertSame([$first->id], $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id'));

        $this->travel(25)->hours();

        $ids = $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->json('data.*.id');
        $this->assertEqualsCanonicalizing([$first->id, $late->id], $ids);
    }

    public function test_un_item_de_una_categoria_inactiva_no_tiene_similares(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => ['Drama']]]);
        $this->createItem($peliculas, ['name' => 'Otro', 'attributes' => ['genres' => ['Drama']]]);
        $peliculas->update(['is_active' => false]);

        $this->getJson("/api/items/{$origin->id}/similar")->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_un_item_inactivo_da_403_a_un_invitado_y_uno_inexistente_404(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $inactive = $this->createItem($peliculas, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);

        $this->getJson("/api/items/{$inactive->id}/similar")->assertForbidden();
        $this->getJson('/api/items/00000000-0000-0000-0000-000000000000/similar')->assertNotFound();
    }

    public function test_con_sesion_cada_item_lleva_user_vote(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origin = $this->createItem($peliculas, ['name' => 'Origen', 'attributes' => ['genres' => ['Drama']]]);
        $voted = $this->createItem($peliculas, ['name' => 'Votado', 'attributes' => ['genres' => ['Drama']], 'bayes_score' => 9]);
        $unvoted = $this->createItem($peliculas, ['name' => 'Sin votar', 'attributes' => ['genres' => ['Drama']], 'bayes_score' => 5]);
        Vote::factory()->byUser($user)->forItem($voted)->rated(8.0)->create();

        $response = $this->getJson("/api/items/{$origin->id}/similar")->assertOk();

        // Los similares no excluyen lo votado
        $this->assertSame([$voted->id, $unvoted->id], $response->json('data.*.id'));
        $response->assertJsonPath('data.0.user_vote.type', Vote::TYPE_VOTE)
            ->assertJsonPath('data.0.can_vote', false)
            ->assertJsonPath('data.1.can_vote', true)
            ->assertJsonMissingPath('data.1.user_vote');
    }
}
