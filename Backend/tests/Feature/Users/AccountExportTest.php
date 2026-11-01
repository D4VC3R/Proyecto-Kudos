<?php

namespace Tests\Feature\Users;

use App\Actions\LikeListAction;
use App\Models\ItemComment;
use App\Models\KudosTransaction;
use App\Models\ListItem;
use App\Models\Proposal;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Exportación de los datos del usuario (F3-08): GET /api/profile/export.
 */
class AccountExportTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_la_exportacion_devuelve_los_datos_del_usuario(): void
    {
        $category = $this->createCategory();
        $a = $this->actingAsUser();
        $b = User::factory()->create(['email_verified_at' => now()]);
        $a->profile->update(['biography' => 'Cinéfila', 'city' => 'Madrid', 'interests' => ['peliculas']]);
        $itemOne = $this->createItem($category);
        $itemTwo = $this->createItem($category);

        $vote = Vote::factory()->forItem($itemOne)->byUser($a)->rated(8)->create();
        Vote::factory()->forItem($itemTwo)->byUser($a)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        ItemComment::factory()->create(['item_id' => $itemOne->id, 'user_id' => $a->id]);
        ItemComment::factory()->create(['item_id' => $itemTwo->id, 'user_id' => $a->id])->delete();
        $list = UserList::factory()->create(['user_id' => $a->id, 'title' => 'Favoritas']);
        ListItem::factory()->create(['list_id' => $list->id, 'item_id' => $itemOne->id]);
        $listOfB = UserList::factory()->create(['user_id' => $b->id, 'visibility' => UserList::VISIBILITY_PUBLIC]);
        app(LikeListAction::class)->execute($listOfB, $a);
        Proposal::factory()->create(['creator_id' => $a->id, 'category_id' => $category->id, 'name' => 'Mi propuesta']);
        KudosTransaction::create([
            'user_id' => $a->id, 'kudos_amount' => 2, 'reason' => 'vote_first_time_item',
            'action_key' => 'test:'.Str::uuid(), 'reference_type' => Vote::class, 'reference_id' => $vote->id,
        ]);

        $response = $this->getJson('/api/profile/export')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'exported_at', 'account', 'profile', 'votes', 'comments', 'lists', 'liked_lists', 'proposals', 'kudos_transactions',
            ]])
            ->assertJsonPath('data.account.email', $a->email)
            ->assertJsonPath('data.profile.biography', 'Cinéfila')
            ->assertJsonPath('data.profile.interests', ['peliculas'])
            ->assertJsonPath('data.lists.0.items.0.item_id', $itemOne->id)
            ->assertJsonPath('data.liked_lists.0.list_id', $listOfB->id)
            ->assertJsonPath('data.liked_lists.0.title', $listOfB->title)
            ->assertJsonPath('data.proposals.0.name', 'Mi propuesta')
            ->assertJsonPath('data.kudos_transactions.0.kudos_amount', 2);

        $this->assertCount(2, $response->json('data.votes'));
        $comments = $response->json('data.comments');
        $this->assertCount(2, $comments);
        $this->assertSame(1, collect($comments)->whereNotNull('deleted_at')->count());
    }

    public function test_la_exportacion_oculta_las_listas_ajenas_que_ya_no_son_visibles(): void
    {
        $a = $this->actingAsUser();
        $b = User::factory()->create(['email_verified_at' => now()]);
        $private = UserList::factory()->create(['user_id' => $b->id, 'title' => 'Lista que pasa a privada', 'visibility' => UserList::VISIBILITY_PUBLIC]);
        $trashed = UserList::factory()->create(['user_id' => $b->id, 'title' => 'Lista que se borra', 'visibility' => UserList::VISIBILITY_PUBLIC]);
        app(LikeListAction::class)->execute($private, $a);
        app(LikeListAction::class)->execute($trashed, $a);
        $private->update(['visibility' => UserList::VISIBILITY_PRIVATE]);
        $trashed->delete();

        $response = $this->getJson('/api/profile/export')->assertOk();

        $liked = $response->json('data.liked_lists');
        $this->assertCount(2, $liked);
        $this->assertEqualsCanonicalizing([$private->id, $trashed->id], collect($liked)->pluck('list_id')->all());
        foreach ($liked as $entry) {
            $this->assertNull($entry['title']);
            $this->assertNull($entry['slug']);
        }
        foreach (['Lista que pasa a privada', 'Lista que se borra', $private->slug, $trashed->slug] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    public function test_la_exportacion_no_incluye_datos_de_otros_usuarios(): void
    {
        $category = $this->createCategory();
        $a = $this->actingAsUser();
        $b = User::factory()->create(['email_verified_at' => now()]);
        $mine = $this->createItem($category);
        $theirs = $this->createItem($category);

        Vote::factory()->forItem($mine)->byUser($a)->rated(6)->create();
        Vote::factory()->forItem($theirs)->byUser($b)->rated(9)->create();
        $commentOfB = ItemComment::factory()->create(['item_id' => $theirs->id, 'user_id' => $b->id]);
        UserList::factory()->create(['user_id' => $b->id]);

        $response = $this->getJson('/api/profile/export')->assertOk();

        $this->assertSame([$mine->id], collect($response->json('data.votes'))->pluck('item_id')->all());
        $this->assertSame([], $response->json('data.comments'));
        $this->assertSame([], $response->json('data.lists'));
        $this->assertStringNotContainsString($commentOfB->id, $response->getContent());
        $this->assertStringNotContainsString($b->email, $response->getContent());
    }

    public function test_la_exportacion_no_incluye_la_contrasena(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/profile/export')
            ->assertOk()
            ->assertJsonMissingPath('data.account.password')
            ->assertJsonMissingPath('data.account.remember_token');
    }

    public function test_la_exportacion_incluye_items_borrados(): void
    {
        $category = $this->createCategory();
        $a = $this->actingAsUser();
        $item = $this->createItem($category, ['name' => 'Película retirada']);
        Vote::factory()->forItem($item)->byUser($a)->rated(7)->create();
        $item->delete();

        $this->getJson('/api/profile/export')
            ->assertOk()
            ->assertJsonPath('data.votes.0.item_name', 'Película retirada')
            ->assertJsonPath('data.votes.0.category', 'peliculas');
    }

    public function test_la_exportacion_exige_sesion(): void
    {
        $this->getJson('/api/profile/export')->assertUnauthorized();
    }
}
