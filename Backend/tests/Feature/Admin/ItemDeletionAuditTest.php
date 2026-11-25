<?php

namespace Tests\Feature\Admin;

use App\Services\ModerationAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Borrar un ítem desde el panel queda en el registro de moderación.
 */
class ItemDeletionAuditTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    public function test_borrar_un_item_queda_en_el_registro_de_moderacion(): void
    {
        $actor = $this->actingAsAdmin();
        $target = $this->createItem($this->createCategory());

        $this->mock(ModerationAuditLogger::class, function (MockInterface $mock) use ($target, $actor) {
            $mock->shouldReceive('logItemModeration')
                ->once()
                ->withArgs(fn ($item, $admin, $action) => $item->id === $target->id && $admin->id === $actor->id && $action === 'admin_delete_item');
        });

        $this->deleteJson("/api/items/{$target->id}")->assertOk();

        $this->assertSoftDeleted($target);
    }
}
