<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Caracterización (F5-01): alta y borrado de ítems (POST/DELETE /api/items, solo admin).
 * No se envía `images` (BE-R10, F5-06).
 */
class ItemCrudTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private function validPayload(string $categoryId): array
    {
        return [
            'name' => 'Película nueva',
            'description' => 'Descripción lo bastante larga para pasar la validación.',
            'category_id' => $categoryId,
        ];
    }

    public function test_un_admin_crea_un_item(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();

        $this->postJson('/api/items', $this->validPayload($category->id))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Película nueva')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.slug', 'pelicula-nueva');

        $this->assertDatabaseHas('items', [
            'name' => 'Película nueva',
            'creator_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);
    }

    public function test_crear_un_item_con_datos_invalidos_da_422(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/items', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['name', 'description', 'category_id']]]);

        $this->postJson('/api/items', $this->validPayload((string) Str::uuid()))
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['category_id']]]);

        $this->assertSame(0, Item::count());
    }

    public function test_un_admin_borra_un_item(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());

        $this->deleteJson("/api/items/{$item->id}")->assertOk();

        $this->assertSoftDeleted($item);
        // Sin comprobar el código del error: hoy es route_not_found (BE-R1, F5-04).
        $this->getJson("/api/items/{$item->id}")->assertNotFound();
    }

    public function test_un_usuario_normal_recibe_403_al_crear_o_borrar_items(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        $this->postJson('/api/items', $this->validPayload($category->id))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->deleteJson("/api/items/{$item->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertNotSoftDeleted($item);
        $this->assertSame(1, Item::count());
    }

    public function test_un_invitado_recibe_401_al_crear_o_borrar_items(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category);

        $this->postJson('/api/items', $this->validPayload($category->id))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->deleteJson("/api/items/{$item->id}")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->assertNotSoftDeleted($item);
    }
}
