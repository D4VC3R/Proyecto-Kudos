<?php

namespace Tests\Feature\Recommendations;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Recomendaciones "Porque te gustó X" (F2-08): GET /api/recommendations.
 */
class RecommendationsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * Crea una valoración con la nota indicada, hecha hace $daysAgo días (votes.updated_at).
     */
    private function rate(User $user, Item $item, float $score, int $daysAgo = 0): Vote
    {
        $vote = Vote::factory()->byUser($user)->forItem($item)->rated($score)->create();
        DB::table('votes')->where('id', $vote->id)->update(['updated_at' => now()->subDays($daysAgo)]);

        return $vote;
    }

    /**
     * Crea un ítem de películas con esos géneros.
     */
    private function movie($category, string $name, array $genres, array $attrs = []): Item
    {
        return $this->createItem($category, array_merge(['name' => $name, 'attributes' => ['genres' => $genres]], $attrs));
    }

    public function test_sin_sesion_da_401(): void
    {
        $this->getJson('/api/recommendations')->assertUnauthorized();
    }

    public function test_agrupa_similares_no_votados_por_cada_valoracion_alta(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $oldest = $this->movie($peliculas, 'Antigua', ['Drama']);
        $middle = $this->movie($peliculas, 'Media', ['Comedia']);
        $newest = $this->movie($peliculas, 'Reciente', ['Terror']);
        $this->rate($user, $oldest, 8, 10);
        $this->rate($user, $middle, 9, 5);
        $this->rate($user, $newest, 10, 1);

        $dramaOk = $this->movie($peliculas, 'Drama ok', ['Drama']);
        $dramaWanted = $this->movie($peliculas, 'Drama quiero', ['Drama']);
        $comedyOk = $this->movie($peliculas, 'Comedia ok', ['Comedia']);
        $comedyIgnored = $this->movie($peliculas, 'Comedia no interesa', ['Comedia']);
        $horrorOk = $this->movie($peliculas, 'Terror ok', ['Terror']);

        Vote::factory()->byUser($user)->forItem($dramaWanted)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        Vote::factory()->byUser($user)->forItem($comedyIgnored)->create(['type' => Vote::TYPE_NOT_INTERESTED, 'score' => null]);

        $response = $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'content');

        $this->assertSame([$newest->id, $middle->id, $oldest->id], $response->json('data.*.because.id'));
        $response->assertJsonPath('data.0.because.user_vote.score', 10)
            ->assertJsonPath('data.0.because.user_vote.type', Vote::TYPE_VOTE);
        $this->assertSame([$horrorOk->id], $response->json('data.0.items.*.id'));
        $this->assertSame([$comedyOk->id], $response->json('data.1.items.*.id'));
        $this->assertSame([$dramaOk->id], $response->json('data.2.items.*.id'));

        $all = collect($response->json('data'))->pluck('items')->flatten(1)->pluck('id')->all();
        $this->assertNotContains($dramaWanted->id, $all);
        $this->assertNotContains($comedyIgnored->id, $all);
    }

    public function test_usa_como_mucho_las_cinco_ultimas_valoraciones_altas(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $origins = [];

        for ($i = 1; $i <= 6; $i++) {
            $origin = $this->movie($peliculas, "Origen {$i}", ["Género {$i}"]);
            $this->movie($peliculas, "Similar {$i}", ["Género {$i}"]);
            $this->rate($user, $origin, 9, $i * 2);
            $origins[] = $origin;
        }

        $response = $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'content');

        $becauseIds = $response->json('data.*.because.id');
        $this->assertCount(5, $becauseIds);
        $this->assertNotContains($origins[5]->id, $becauseIds);
        $this->assertSame(collect($origins)->take(5)->pluck('id')->all(), $becauseIds);
    }

    public function test_ignora_notas_por_debajo_de_8_y_votos_sin_nota(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        foreach ([8.0, 9.0, 7.5] as $i => $score) {
            $origin = $this->movie($peliculas, "Origen {$i}", ['Drama']);
            $this->rate($user, $origin, $score, $i);
        }
        $wanted = $this->movie($peliculas, 'Quiero', ['Drama']);
        $ignored = $this->movie($peliculas, 'No me interesa', ['Drama']);
        Vote::factory()->byUser($user)->forItem($wanted)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        Vote::factory()->byUser($user)->forItem($ignored)->create(['type' => Vote::TYPE_NOT_INTERESTED, 'score' => null]);

        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'popular');
    }

    public function test_no_repite_un_item_en_dos_filas(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $recent = $this->movie($peliculas, 'Reciente', ['Drama', 'Comedia']);
        $older = $this->movie($peliculas, 'Antigua', ['Drama', 'Comedia']);
        $third = $this->movie($peliculas, 'Tercera', ['Terror']);
        $this->rate($user, $recent, 9, 1);
        $this->rate($user, $older, 9, 2);
        $this->rate($user, $third, 9, 3);

        $common = $this->movie($peliculas, 'Común', ['Drama']);
        $onlyOlder = $this->movie($peliculas, 'Solo comedia', ['Comedia']);
        $this->movie($peliculas, 'Terror ok', ['Terror']);

        $response = $this->getJson('/api/recommendations')->assertOk();

        $rowOf = fn (string $id) => collect($response->json('data'))
            ->filter(fn ($group) => in_array($id, array_column($group['items'], 'id'), true))
            ->keys()->all();

        // Sale solo en la fila del origen más reciente
        $this->assertSame([0], $rowOf($common->id));
        $this->assertSame([0], $rowOf($onlyOlder->id));
    }

    public function test_omite_las_filas_sin_items(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $withoutSimilar = $this->movie($peliculas, 'Sin similares', ['Género único']);
        $b = $this->movie($peliculas, 'B', ['Drama']);
        $c = $this->movie($peliculas, 'C', ['Drama']);
        $this->rate($user, $withoutSimilar, 10, 1);
        $this->rate($user, $b, 9, 2);
        $this->rate($user, $c, 9, 3);
        $candidate = $this->movie($peliculas, 'Candidato', ['Drama']);

        $response = $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'content');

        $this->assertSame([$b->id], $response->json('data.*.because.id'));
        $this->assertSame([$candidate->id], $response->json('data.0.items.*.id'));
    }

    public function test_ignora_valoraciones_de_items_inactivos_o_de_categorias_inactivas(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $a = $this->movie($peliculas, 'A', ['Drama']);
        $b = $this->movie($peliculas, 'B', ['Drama']);
        $inactive = $this->movie($peliculas, 'Inactivo', ['Drama'], ['status' => Item::STATUS_INACTIVE]);
        $this->rate($user, $a, 9, 1);
        $this->rate($user, $b, 9, 2);
        $this->rate($user, $inactive, 9, 3);

        // Con el inactivo solo quedan 2 valoraciones válidas
        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'popular');

        // Una valoración de una categoría inactiva tampoco cuenta
        $inSeries = $this->createItem($series, ['name' => 'Serie', 'attributes' => ['genres' => ['Drama']]]);
        $this->rate($user, $inSeries, 9, 4);
        $series->update(['is_active' => false]);

        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'popular');
    }

    public function test_con_menos_de_tres_valoraciones_altas_devuelve_populares_de_sus_intereses(): void
    {
        $user = $this->actingAsUser();
        $user->profile->update(['interests' => ['series']]);
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $this->createItem($peliculas, ['name' => 'Película popular', 'popularity' => 999]);
        $low = $this->createItem($series, ['name' => 'Serie baja', 'popularity' => 10]);
        $high = $this->createItem($series, ['name' => 'Serie alta', 'popularity' => 90]);
        $mid = $this->createItem($series, ['name' => 'Serie media', 'popularity' => 50]);
        $voted = $this->createItem($series, ['name' => 'Serie votada', 'popularity' => 500]);
        $this->rate($user, $voted, 9);

        $response = $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'popular')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.because', null);

        $this->assertSame([$high->id, $mid->id, $low->id], $response->json('data.0.items.*.id'));
    }

    public function test_sin_intereses_devuelve_populares_de_las_categorias_activas(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $inactive = $this->createCategory('libros', 'Libros');
        $inactive->update(['is_active' => false]);

        $movie = $this->createItem($peliculas, ['name' => 'Película', 'popularity' => 10]);
        $show = $this->createItem($series, ['name' => 'Serie', 'popularity' => 20]);
        $book = $this->createItem($inactive, ['name' => 'Libro', 'popularity' => 999]);

        $response = $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'popular');

        $ids = $response->json('data.0.items.*.id');
        $this->assertEqualsCanonicalizing([$movie->id, $show->id], $ids);
        $this->assertNotContains($book->id, $ids);
    }

    public function test_si_ninguna_fila_tiene_items_devuelve_populares(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        foreach (['Uno', 'Dos', 'Tres'] as $i => $genre) {
            $origin = $this->movie($peliculas, "Origen {$genre}", [$genre]);
            $this->rate($user, $origin, 9, $i);
        }
        $popular = $this->createItem($peliculas, ['name' => 'Popular', 'popularity' => 50]);

        $response = $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.strategy', 'popular');

        $this->assertSame([$popular->id], $response->json('data.0.items.*.id'));
        $response->assertJsonPath('data.0.because', null);
    }
}
