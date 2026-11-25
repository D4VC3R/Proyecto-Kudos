<?php

namespace Tests\Feature\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Votos de un ítem borrado (soft delete): se pueden retirar y se recalculan sus medias.
 */
class DeletedItemVoteTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    public function test_borrar_el_voto_de_un_item_borrado_funciona_y_el_voto_desaparece(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());
        $vote = Vote::factory()->byUser($user)->forItem($item)->rated(8)->create();
        $item->delete();

        $this->deleteJson("/api/votes/{$vote->id}")->assertOk();

        $this->assertDatabaseMissing('votes', ['id' => $vote->id]);
        $this->assertSame(0, Item::withTrashed()->find($item->id)->vote_count);
    }
}
