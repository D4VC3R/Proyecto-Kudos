<?php

namespace Tests\Feature\Admin;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Caracterización (F5-01): edición y moderación de ítems desde el panel (admin/items).
 * No se envía `images`: lo retira F5-06 (BE-R10).
 */
class AdminItemModerationTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    public function test_un_admin_edita_un_item_desde_el_panel(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());

        $this->putJson("/api/admin/items/{$item->id}", [
            'name' => 'Nombre corregido',
            'description' => 'Descripción corregida desde el panel de moderación.',
            'moderation_reason' => 'Errata',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre corregido');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Nombre corregido']);
    }

    public function test_un_admin_cambia_la_categoria_y_el_estado_desde_el_panel(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());
        $series = $this->createCategory('series', 'Series');

        $this->putJson("/api/admin/items/{$item->id}", [
            'category_id' => $series->id,
            'status' => 'inactive',
            'moderation_reason' => 'Mal clasificada',
        ])->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'category_id' => $series->id, 'status' => 'inactive']);
    }

    public function test_editar_desde_el_panel_con_datos_invalidos_da_422(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());

        $this->putJson("/api/admin/items/{$item->id}", ['description' => 'corta'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['description']]]);

        $this->putJson("/api/admin/items/{$item->id}", ['status' => 'borrado'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['status']]]);

        $fresh = $item->fresh();
        $this->assertSame($item->name, $fresh->name);
        $this->assertSame($item->description, $fresh->description);
        $this->assertSame('active', $fresh->status);
    }

    public function test_moderate_desactiva_un_item_con_motivo(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());

        $this->patchJson("/api/admin/items/{$item->id}/moderate", ['status' => 'inactive', 'reason' => 'Ficha duplicada'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'status' => 'inactive']);
    }

    public function test_moderate_sin_motivo_al_desactivar_da_422(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());

        $this->patchJson("/api/admin/items/{$item->id}/moderate", ['status' => 'inactive'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reason']]]);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'status' => 'active']);
    }

    public function test_moderate_reactiva_un_item_sin_motivo(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $this->patchJson("/api/admin/items/{$item->id}/moderate", ['status' => 'active'])
            ->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'status' => 'active']);
    }

    public function test_un_usuario_normal_recibe_403_al_editar_o_moderar_items(): void
    {
        $item = $this->createItem($this->createCategory());
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        $this->putJson("/api/admin/items/{$item->id}", ['name' => 'Intento de edición'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->patchJson("/api/admin/items/{$item->id}/moderate", ['status' => 'inactive', 'reason' => 'Intento'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $fresh = $item->fresh();
        $this->assertSame($item->name, $fresh->name);
        $this->assertSame('active', $fresh->status);
    }

    public function test_un_invitado_recibe_401_al_editar_o_moderar_items(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->putJson("/api/admin/items/{$item->id}", ['name' => 'Intento de edición'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->patchJson("/api/admin/items/{$item->id}/moderate", ['status' => 'inactive', 'reason' => 'Intento'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_editar_desde_el_panel_con_images_no_cambia_las_imagenes(): void
    {
        $this->actingAsAdmin();
        $item = $this->createItem($this->createCategory());
        $original = $item->images;

        $this->putJson("/api/admin/items/{$item->id}", [
            'name' => 'Nombre corregido',
            'images' => [['path' => 'otra/ruta.jpg', 'disk' => 'public']],
            'moderation_reason' => 'Errata',
        ])->assertOk();

        $this->assertEquals($original, $item->fresh()->images);
        $this->assertSame('Nombre corregido', $item->fresh()->name);
    }
}
