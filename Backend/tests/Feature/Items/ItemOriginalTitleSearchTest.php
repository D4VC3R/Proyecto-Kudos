<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * La búsqueda de ítems también encuentra por original_title (F1-01):
 * /api/items, /api/admin/items y /api/votes/my-votes.
 */
class ItemOriginalTitleSearchTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_la_busqueda_publica_encuentra_un_item_por_su_original_title(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItem($peliculas, ['name' => 'El caballero oscuro', 'original_title' => 'The Dark Knight']);
        $this->createItem($peliculas, ['name' => 'Matrix']);

        $response = $this->getJson('/api/items?search=dark');

        $response->assertOk();
        $this->assertSame(['El caballero oscuro'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_la_busqueda_por_original_title_respeta_el_filtro_de_categoria(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $this->createItem($peliculas, ['name' => 'El caballero oscuro', 'original_title' => 'The Dark Knight']);
        $this->createItem($series, ['name' => 'Serie oscura', 'original_title' => 'Dark']);

        $response = $this->getJson('/api/items?search=dark&category_slug=peliculas');

        $response->assertOk();
        $this->assertSame(['El caballero oscuro'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_la_busqueda_por_original_title_no_devuelve_items_inactivos(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItem($peliculas, ['name' => 'El caballero oscuro', 'original_title' => 'The Dark Knight']);
        $this->createItem($peliculas, [
            'name' => 'Inactiva',
            'original_title' => 'Dark Inactive',
            'status' => Item::STATUS_INACTIVE,
        ]);

        $response = $this->getJson('/api/items?search=dark');

        $response->assertOk();
        $this->assertSame(['El caballero oscuro'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_la_busqueda_del_admin_encuentra_por_original_title(): void
    {
        $this->actingAsAdmin();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItem($peliculas, ['name' => 'El caballero oscuro', 'original_title' => 'The Dark Knight']);
        $this->createItem($peliculas, ['name' => 'Matrix']);

        $response = $this->getJson('/api/admin/items?search=dark');

        $response->assertOk();
        $this->assertSame(['El caballero oscuro'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_mis_votos_se_filtran_por_original_title(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $oscuro = $this->createItem($peliculas, ['name' => 'El caballero oscuro', 'original_title' => 'The Dark Knight']);
        $matrix = $this->createItem($peliculas, ['name' => 'Matrix']);

        foreach ([$oscuro, $matrix] as $item) {
            Vote::query()->create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'type' => Vote::TYPE_VOTE,
                'score' => 8,
            ]);
        }

        $response = $this->getJson('/api/votes/my-votes?search=dark');

        $response->assertOk();
        $this->assertSame(['El caballero oscuro'], collect($response->json('data'))->pluck('item.name')->all());
    }
}
