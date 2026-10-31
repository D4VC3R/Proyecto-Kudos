<?php

namespace Tests\Feature\Home;

use App\Models\Category;
use App\Models\Item;
use App\Models\ListItem;
use App\Models\UserList;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Portada (F2-09): GET /api/home, con y sin sesión.
 */
class HomeTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function editorialList(array $attrs = []): UserList
    {
        return UserList::factory()->editorial()->create($attrs);
    }

    private function addEntry(UserList $list, Item $item, int $position, ?string $note = null): ListItem
    {
        $entry = ListItem::factory()->create([
            'list_id' => $list->id,
            'item_id' => $item->id,
            'position' => $position,
            'note' => $note,
        ]);
        $list->update(['items_count' => $list->listItems()->count()]);

        return $entry;
    }

    public function test_sin_sesion_devuelve_todas_las_secciones(): void
    {
        $this->createItem($this->createCategory());

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'for_you' => ['strategy', 'groups'],
                'new_releases',
                'editorial_lists',
                'top_rated',
                'categories',
            ]]);
    }

    public function test_sin_sesion_for_you_son_populares_de_las_categorias_activas(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $inactive = Category::factory()->create(['slug' => 'ocultas', 'name' => 'Ocultas', 'is_active' => false]);

        $p1 = $this->createItem($peliculas, ['name' => 'P1', 'popularity' => 90]);
        $p2 = $this->createItem($peliculas, ['name' => 'P2', 'popularity' => 50]);
        $s1 = $this->createItem($series, ['name' => 'S1', 'popularity' => 80]);
        $s2 = $this->createItem($series, ['name' => 'S2', 'popularity' => 40]);
        $hidden = $this->createItem($inactive, ['name' => 'Oculto', 'popularity' => 99]);

        $response = $this->getJson('/api/home')->assertOk()->assertJsonPath('data.for_you.strategy', 'popular');

        $groups = $response->json('data.for_you.groups');
        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]['because']);
        // Reparto por turnos entre categorías: 1.º de cada una, después el 2.º
        $this->assertSame([$p1->id, $s1->id, $p2->id, $s2->id], array_column($groups[0]['items'], 'id'));
        $this->assertNotContains($hidden->id, array_column($groups[0]['items'], 'id'));
    }

    public function test_sin_sesion_los_items_no_llevan_user_vote_y_no_se_pueden_votar(): void
    {
        $this->createItem($this->createCategory(), ['vote_count' => 5, 'vote_avg' => 8]);

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonMissingPath('data.top_rated.0.user_vote')
            ->assertJsonPath('data.top_rated.0.can_vote', false);
    }

    public function test_con_sesion_for_you_usa_las_recomendaciones_por_contenido(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $newest = $this->createItem($peliculas, ['name' => 'Reciente', 'attributes' => ['genres' => ['Terror']]]);
        $middle = $this->createItem($peliculas, ['name' => 'Media', 'attributes' => ['genres' => ['Comedia']]]);
        $oldest = $this->createItem($peliculas, ['name' => 'Antigua', 'attributes' => ['genres' => ['Drama']]]);
        foreach ([[$oldest, 10], [$middle, 5], [$newest, 1]] as [$item, $daysAgo]) {
            $vote = Vote::factory()->byUser($user)->forItem($item)->rated(9.0)->create();
            DB::table('votes')->where('id', $vote->id)->update(['updated_at' => now()->subDays($daysAgo)]);
        }
        $similar = $this->createItem($peliculas, ['name' => 'Similar', 'attributes' => ['genres' => ['Terror']]]);

        $response = $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.for_you.strategy', 'content')
            ->assertJsonPath('data.for_you.groups.0.because.id', $newest->id);

        $this->assertContains($similar->id, $response->json('data.for_you.groups.0.items.*.id'));
    }

    public function test_con_sesion_y_pocas_valoraciones_for_you_son_populares_sin_votados(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $voted = $this->createItem($peliculas, ['name' => 'Votada', 'popularity' => 99]);
        $free = $this->createItem($peliculas, ['name' => 'Libre', 'popularity' => 10]);
        Vote::factory()->byUser($user)->forItem($voted)->rated(9.0)->create();

        $response = $this->getJson('/api/home')->assertOk()->assertJsonPath('data.for_you.strategy', 'popular');

        $ids = $response->json('data.for_you.groups.0.items.*.id');
        $this->assertSame([$free->id], $ids);
        $this->assertNotContains($voted->id, $ids);
    }

    public function test_con_sesion_las_secciones_cacheadas_llevan_el_voto_del_usuario(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($peliculas, ['vote_count' => 5, 'vote_avg' => 8]);

        // Sin sesión se llena la caché de IDs
        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.top_rated.0.id', $item->id);

        $user = $this->actingAsUser();
        Vote::factory()->byUser($user)->forItem($item)->rated(9.0)->create();

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.top_rated.0.id', $item->id)
            ->assertJsonPath('data.top_rated.0.user_vote.score', 9)
            ->assertJsonPath('data.top_rated.0.can_vote', false);
    }

    public function test_new_releases_filtra_por_fecha_categoria_activa_y_calidad(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $inactive = Category::factory()->create(['slug' => 'ocultas', 'name' => 'Ocultas', 'is_active' => false]);

        $in59 = $this->createItem($peliculas, ['name' => 'Hace 59', 'released_at' => today()->subDays(59), 'popularity' => 10]);
        $today = $this->createItem($peliculas, ['name' => 'Hoy', 'released_at' => today(), 'popularity' => 30]);
        $in29 = $this->createItem($peliculas, ['name' => 'En 29', 'released_at' => today()->addDays(29), 'popularity' => 20]);

        $this->createItem($peliculas, ['name' => 'Hace 61', 'released_at' => today()->subDays(61), 'popularity' => 99]);
        $this->createItem($peliculas, ['name' => 'En 31', 'released_at' => today()->addDays(31), 'popularity' => 99]);
        $this->createItem($peliculas, ['name' => 'Sin fecha', 'popularity' => 99]);
        $this->createItem($inactive, ['name' => 'Categoría inactiva', 'released_at' => today(), 'popularity' => 99]);
        $this->createItem($peliculas, ['name' => 'Ficha pobre', 'released_at' => today(), 'popularity' => 99, 'images' => [], 'description' => 'Item importado.']);
        $this->createItem($peliculas, ['name' => 'Inactivo', 'released_at' => today(), 'popularity' => 99, 'status' => Item::STATUS_INACTIVE]);

        $response = $this->getJson('/api/home')->assertOk();

        // Orden por popularity desc
        $this->assertSame([$today->id, $in29->id, $in59->id], $response->json('data.new_releases.*.id'));
    }

    public function test_top_rated_exige_tres_votos_y_ordena_por_bayes_score(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $a = $this->createItem($peliculas, ['name' => 'A', 'vote_count' => 3, 'vote_avg' => 8.0, 'bayes_score' => 8.0]);
        $b = $this->createItem($peliculas, ['name' => 'B', 'vote_count' => 10, 'vote_avg' => 8.4, 'bayes_score' => 8.4]);
        $this->createItem($peliculas, ['name' => 'C', 'vote_count' => 2, 'vote_avg' => 9.9, 'bayes_score' => 9.9]);

        $response = $this->getJson('/api/home')->assertOk();

        $this->assertSame([$b->id, $a->id], $response->json('data.top_rated.*.id'));
    }

    public function test_editorial_lists_devuelve_hasta_cuatro_con_sus_seis_primeros_items(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $items = collect(range(1, 8))->map(fn (int $i) => $this->createItem($peliculas, ['name' => "Peli {$i}"]));

        // 5 listas editoriales públicas con ítems y distinto updated_at
        $lists = collect(range(1, 5))->map(function (int $i) use ($items) {
            $list = $this->editorialList(['title' => "Lista {$i}"]);
            $this->addEntry($list, $items[0], 1);
            DB::table('lists')->where('id', $list->id)->update(['updated_at' => now()->subDays(10 - $i)]);

            return $list;
        });

        // La lista más reciente trae 8 ítems; solo salen 6, por posición, con su nota
        $big = $lists[4];
        foreach ($items->skip(1)->values() as $index => $item) {
            $this->addEntry($big, $item, $index + 2, $index === 0 ? 'Una nota' : null);
        }
        DB::table('lists')->where('id', $big->id)->update(['updated_at' => now()]);

        // No salen: pública no editorial, editorial privada y editorial vacía
        $userList = UserList::factory()->create(['title' => 'De usuario', 'visibility' => UserList::VISIBILITY_PUBLIC]);
        $this->addEntry($userList, $items[0], 1);
        $private = $this->editorialList(['title' => 'Privada', 'visibility' => UserList::VISIBILITY_PRIVATE]);
        $this->addEntry($private, $items[0], 1);
        $this->editorialList(['title' => 'Vacía']);

        $response = $this->getJson('/api/home')->assertOk();

        $titles = $response->json('data.editorial_lists.*.title');
        $this->assertSame(['Lista 5', 'Lista 4', 'Lista 3', 'Lista 2'], $titles);

        $first = $response->json('data.editorial_lists.0');
        $this->assertCount(6, $first['items']);
        $this->assertSame([1, 2, 3, 4, 5, 6], array_column($first['items'], 'position'));
        $this->assertSame($items->take(6)->pluck('id')->all(), array_map(fn ($e) => $e['item']['id'], $first['items']));
        $this->assertSame('Una nota', $first['items'][1]['note']);
        $this->assertSame(8, $first['items_count']);
    }

    public function test_editorial_lists_ocultan_items_inactivos_y_omiten_listas_sin_items_visibles(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $inactiveCategory = Category::factory()->create(['slug' => 'ocultas', 'name' => 'Ocultas', 'is_active' => false]);

        $ok = $this->createItem($peliculas, ['name' => 'Visible']);
        $inactive = $this->createItem($peliculas, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);
        $deleted = $this->createItem($peliculas, ['name' => 'Borrado']);
        $hiddenCategory = $this->createItem($inactiveCategory, ['name' => 'De categoría oculta']);
        $deleted->delete();

        $mixed = $this->editorialList(['title' => 'Mixta']);
        $this->addEntry($mixed, $inactive, 1);
        $this->addEntry($mixed, $ok, 2);
        $this->addEntry($mixed, $deleted, 3);

        $onlyHidden = $this->editorialList(['title' => 'Solo ocultos']);
        $this->addEntry($onlyHidden, $inactive, 1);
        $this->addEntry($onlyHidden, $hiddenCategory, 2);

        $response = $this->getJson('/api/home')->assertOk();

        $this->assertSame(['Mixta'], $response->json('data.editorial_lists.*.title'));
        $this->assertSame([$ok->id], array_map(fn ($e) => $e['item']['id'], $response->json('data.editorial_lists.0.items')));
    }

    public function test_los_items_de_una_lista_solo_salen_en_las_filas_cacheadas_de_esa_lista(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $items = collect(range(1, 8))->map(fn (int $i) => $this->createItem($peliculas, ['name' => "Peli {$i}"]));

        $big = $this->editorialList(['title' => 'Grande']);
        foreach ($items as $index => $item) {
            $this->addEntry($big, $item, $index + 1);
        }
        DB::table('lists')->where('id', $big->id)->update(['updated_at' => now()->subDay()]);

        // Otra lista que repite el ítem 8, que en la grande queda fuera de los 6 primeros
        $small = $this->editorialList(['title' => 'Pequeña']);
        $this->addEntry($small, $items[7], 1);

        $response = $this->getJson('/api/home')->assertOk();

        $this->assertSame(['Pequeña', 'Grande'], $response->json('data.editorial_lists.*.title'));
        $this->assertCount(6, $response->json('data.editorial_lists.1.items'));
    }

    public function test_categories_devuelve_solo_las_activas_en_orden(): void
    {
        Category::factory()->create(['slug' => 'zeta', 'name' => 'Zeta', 'sort_order' => 1]);
        Category::factory()->create(['slug' => 'beta', 'name' => 'Beta', 'sort_order' => 2]);
        Category::factory()->create(['slug' => 'alfa', 'name' => 'Alfa', 'sort_order' => 2]);
        Category::factory()->create(['slug' => 'oculta', 'name' => 'Oculta', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->getJson('/api/home')->assertOk();

        $this->assertSame(['zeta', 'alfa', 'beta'], $response->json('data.categories.*.slug'));
    }

    public function test_la_parte_anonima_se_cachea_diez_minutos(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $first = $this->createItem($peliculas, ['name' => 'Primero', 'vote_count' => 5, 'vote_avg' => 8, 'bayes_score' => 8.0]);

        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.top_rated.0.id', $first->id);

        $second = $this->createItem($peliculas, ['name' => 'Segundo', 'vote_count' => 5, 'vote_avg' => 9, 'bayes_score' => 9.0]);

        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.top_rated.0.id', $first->id);

        $this->travel(11)->minutes();

        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.top_rated.0.id', $second->id);
    }

    public function test_editar_una_lista_editorial_borra_la_cache_de_la_portada(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($peliculas, ['name' => 'Peli']);
        $list = $this->editorialList(['title' => 'Mi lista']);
        $this->addEntry($list, $this->createItem($peliculas, ['name' => 'Otra']), 1);

        // Llena la caché sin sesión
        $this->getJson('/api/home')->assertOk()->assertJsonCount(1, 'data.editorial_lists.0.items');

        $this->actingAsAdmin();
        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $item->id])->assertCreated();

        $this->getJson('/api/home')->assertOk()->assertJsonCount(2, 'data.editorial_lists.0.items');
    }
}
