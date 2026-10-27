<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ItemSearchTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_la_busqueda_encuentra_por_nombre_parcial_sin_distinguir_mayusculas(): void
    {
        $category = $this->createCategory('videojuegos', 'Videojuegos');
        $this->createItem($category, ['name' => 'The Legend of Zelda']);
        $this->createItem($category, ['name' => 'Zelda: Tears of the Kingdom']);
        $this->createItem($category, ['name' => 'Elden Ring']);
        $this->createItem($category, ['name' => 'Zelda Inactiva', 'status' => Item::STATUS_INACTIVE]);

        $response = $this->getJson('/api/items?search=zELDa');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            ['The Legend of Zelda', 'Zelda: Tears of the Kingdom'],
            collect($response->json('data'))->pluck('name')->all()
        );

        $response = $this->getJson('/api/items?search=legend');

        $response->assertOk();
        $this->assertSame(['The Legend of Zelda'], collect($response->json('data'))->pluck('name')->all());
    }
}
