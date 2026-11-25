<?php

namespace Tests\Feature\Performance;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * El listado de comentarios no lanza una consulta por autor: el número de consultas no depende de cuántos haya.
 */
class CommentQueryCountTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    /**
     * Número de consultas de una petición, con la caché vacía para que todas las mediciones sigan el mismo camino.
     */
    private function countQueries(callable $request): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * Crea hilos con un comentario y una respuesta, cada uno de un usuario distinto.
     */
    private function createThreads(Item $item, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $parent = ItemComment::factory()->create([
                'item_id' => $item->id,
                'user_id' => User::factory()->create()->id,
            ]);

            ItemComment::factory()->create([
                'item_id' => $item->id,
                'user_id' => User::factory()->create()->id,
                'parent_id' => $parent->id,
            ]);
        }
    }

    public function test_el_listado_de_comentarios_hace_las_mismas_consultas_con_pocos_y_con_muchos(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->createThreads($item, 2);
        $few = $this->countQueries(function () use ($item) {
            $this->getJson("/api/items/{$item->id}/comments?per_page=50")
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        $this->createThreads($item, 6);
        $many = $this->countQueries(function () use ($item) {
            $this->getJson("/api/items/{$item->id}/comments?per_page=50")
                ->assertOk()
                ->assertJsonCount(8, 'data');
        });

        $this->assertSame($few, $many);
    }

    public function test_los_comentarios_y_sus_respuestas_incluyen_el_avatar_del_autor(): void
    {
        $item = $this->createItem($this->createCategory());

        $author = User::factory()->create();
        $author->profile->update(['avatar' => 'avatars/autor.webp']);
        $replier = User::factory()->create();
        $replier->profile->update(['avatar' => 'avatars/respuesta.webp']);

        $parent = ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $author->id]);
        ItemComment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $replier->id,
            'parent_id' => $parent->id,
        ]);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.user.avatar', 'avatars/autor.webp')
            ->assertJsonPath('data.0.replies.0.user.avatar', 'avatars/respuesta.webp');
    }
}
