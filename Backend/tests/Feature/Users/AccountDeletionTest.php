<?php

namespace Tests\Feature\Users;

use App\Actions\LikeListAction;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\KudosTransaction;
use App\Models\ListItem;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\ListLikedNotification;
use App\Services\ItemStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Borrado de la propia cuenta (F3-08): DELETE /api/profile.
 */
class AccountDeletionTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function verifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function rate(User $user, Item $item, float $score): Vote
    {
        $vote = Vote::factory()->forItem($item)->byUser($user)->rated($score)->create();
        DB::transaction(fn () => app(ItemStatsService::class)->recalculate($item->id));

        return $vote;
    }

    private function comment(User $user, Item $item, array $attrs = []): ItemComment
    {
        return ItemComment::factory()->create(array_merge(['item_id' => $item->id, 'user_id' => $user->id], $attrs));
    }

    private function publicList(User $owner, string $title = 'Mi lista'): UserList
    {
        return UserList::factory()->create([
            'user_id' => $owner->id,
            'title' => $title,
            'visibility' => UserList::VISIBILITY_PUBLIC,
        ]);
    }

    private function destroyAs(User $user, string $password = 'password')
    {
        $this->actingAs($user, 'sanctum');

        return $this->deleteJson('/api/profile', ['password' => $password]);
    }

    private function assertNoRowsFor(string $userId): void
    {
        // Cualquier tabla con columna user_id (también las que se creen en el futuro).
        $tables = DB::table('information_schema.columns')
            ->where('table_schema', 'public')->where('column_name', 'user_id')->pluck('table_name');
        foreach ($tables as $table) {
            $this->assertSame(0, DB::table($table)->where('user_id', $userId)->count(), "Quedan filas de {$userId} en {$table}");
        }
        $this->assertSame(0, DB::table('users')->where('id', $userId)->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $userId)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $userId)->count());
        $this->assertSame(0, DB::table('model_has_roles')->where('model_uuid', $userId)->count());
        $this->assertSame(0, DB::table('proposals')->where('creator_id', $userId)->count());
    }

    public function test_borrar_la_cuenta_no_deja_filas_del_usuario(): void
    {
        $this->seedRolesAndPermissions();
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $a->assignRole('user');
        $b = $this->verifiedUser();
        $itemOne = $this->createItem($category);
        $itemTwo = $this->createItem($category);
        $created = $this->createItem($category, ['creator_id' => $a->id]);

        $vote = $this->rate($a, $itemOne, 8);
        Vote::factory()->forItem($itemTwo)->byUser($a)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        $this->comment($a, $itemOne);
        $list = $this->publicList($a);
        ListItem::factory()->create(['list_id' => $list->id, 'item_id' => $itemOne->id]);
        app(LikeListAction::class)->execute($this->publicList($b, 'Lista de B'), $a);
        KudosTransaction::create([
            'user_id' => $a->id, 'kudos_amount' => 2, 'reason' => 'vote_first_time_item',
            'action_key' => 'test:'.Str::uuid(), 'reference_type' => Vote::class, 'reference_id' => $vote->id,
        ]);
        Proposal::factory()->create(['creator_id' => $a->id, 'category_id' => $category->id]);
        $a->createToken('web');
        $a->notify(new ListLikedNotification($list, $b));

        $this->destroyAs($a)
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertNoRowsFor($a->id);
        $this->assertNull(Item::query()->find($created->id)->creator_id);
        $this->assertDatabaseCount('profiles', 1);
    }

    public function test_los_comentarios_con_respuestas_ajenas_se_anonimizan(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $item = $this->createItem($category);

        $vote = $this->rate($a, $item, 7);
        $review = $this->comment($a, $item, ['vote_id' => $vote->id]);
        $replyOfB = $this->comment($b, $item, ['parent_id' => $review->id]);
        $replyOfA = $this->comment($a, $item, ['parent_id' => $review->id]);
        $updatedAt = $review->fresh()->updated_at;

        $this->destroyAs($a)->assertOk();

        $anonymized = ItemComment::withTrashed()->find($review->id);
        $this->assertNotNull($anonymized);
        $this->assertNull($anonymized->user_id);
        $this->assertSame('[comentario eliminado]', $anonymized->content);
        $this->assertNull($anonymized->vote_id);
        $this->assertEquals($updatedAt, $anonymized->updated_at);
        $this->assertSame($review->id, ItemComment::find($replyOfB->id)->parent_id);
        $this->assertNull(ItemComment::withTrashed()->find($replyOfA->id));

        $this->app['auth']->forgetGuards();
        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.user.id', null)
            ->assertJsonPath('data.0.content', '[comentario eliminado]')
            ->assertJsonPath('data.0.is_review', false)
            ->assertJsonPath('data.0.replies.0.id', $replyOfB->id);

        $this->assertNoRowsFor($a->id);
    }

    public function test_los_comentarios_sin_respuestas_ajenas_se_borran_del_todo(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $item = $this->createItem($category);

        $this->comment($a, $item);
        $softDeleted = $this->comment($a, $item);
        $softDeleted->delete();
        $commentOfB = $this->comment($b, $item);
        $this->comment($a, $item, ['parent_id' => $commentOfB->id]);

        $this->destroyAs($a)->assertOk();

        $this->assertSame(0, ItemComment::withTrashed()->where('user_id', $a->id)->count());
        $this->assertNotNull(ItemComment::find($commentOfB->id));
    }

    public function test_las_medias_de_los_items_afectados_se_recalculan(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $x = $this->createItem($category);
        $y = $this->createItem($category);
        $z = $this->createItem($category);
        $w = $this->createItem($category);

        $this->rate($b, $x, 8);
        $this->rate($a, $x, 4);
        $this->rate($a, $y, 6);
        Vote::factory()->forItem($z)->byUser($a)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        $this->rate($a, $w, 5);
        $w->delete();
        $zBefore = $z->fresh()->only(['vote_count', 'vote_avg', 'bayes_score']);

        $this->destroyAs($a)->assertOk();

        $stats = app(ItemStatsService::class);
        $x = $x->fresh();
        $this->assertSame(1, (int) $x->vote_count);
        $this->assertEquals(8.0, (float) $x->vote_avg);
        $this->assertEqualsWithDelta(
            $stats->bayesScore(1, 8.0, $stats->categoryMean($x->category_id)),
            (float) $x->bayes_score,
            0.0001,
        );
        $y = $y->fresh();
        $this->assertSame(0, (int) $y->vote_count);
        $this->assertEquals(0.0, (float) $y->vote_avg);
        $this->assertEquals(0.0, (float) $y->bayes_score);
        $this->assertEquals($zBefore, $z->fresh()->only(['vote_count', 'vote_avg', 'bayes_score']));
        $this->assertSame(0, Vote::query()->where('user_id', $a->id)->count());
    }

    public function test_los_me_gusta_dados_se_quitan_y_se_recalcula_likes_count(): void
    {
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $c = $this->verifiedUser();
        $list = $this->publicList($b);
        app(LikeListAction::class)->execute($list, $a);
        app(LikeListAction::class)->execute($list, $c);
        $before = $list->fresh();
        $this->assertSame(2, $before->likes_count);

        $this->destroyAs($a)->assertOk();

        $after = $list->fresh();
        $this->assertSame(1, $after->likes_count);
        $this->assertEquals($before->updated_at, $after->updated_at);
        $this->assertDatabaseHas('list_likes', ['list_id' => $list->id, 'user_id' => $c->id]);
        $this->assertDatabaseMissing('list_likes', ['user_id' => $a->id]);
    }

    public function test_las_notificaciones_que_nombran_al_usuario_se_borran(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $c = $this->verifiedUser();
        $item = $this->createItem($category);
        $listOfB = $this->publicList($b);
        $commentOfB = $this->comment($b, $item);
        $replyOfA = $this->comment($a, $item, ['parent_id' => $commentOfB->id]);

        $b->notify(new ListLikedNotification($listOfB, $a));
        $b->notify(new CommentRepliedNotification($replyOfA));
        $b->notify(new ListLikedNotification($listOfB, $c));
        $this->assertSame(3, $b->notifications()->count());

        $this->destroyAs($a)->assertOk();

        $remaining = $b->notifications()->get();
        $this->assertCount(1, $remaining);
        $this->assertSame($c->id, $remaining->first()->data['liker_id']);
    }

    public function test_las_listas_propias_se_borran_del_todo(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $item = $this->createItem($category);
        $live = $this->publicList($a, 'Viva');
        $trashed = $this->publicList($a, 'Borrada');
        ListItem::factory()->create(['list_id' => $live->id, 'item_id' => $item->id]);
        ListItem::factory()->create(['list_id' => $trashed->id, 'item_id' => $item->id]);
        app(LikeListAction::class)->execute($live, $b);
        $trashed->delete();

        $this->destroyAs($a)->assertOk();

        $ids = [$live->id, $trashed->id];
        $this->assertSame(0, DB::table('lists')->whereIn('id', $ids)->count());
        $this->assertSame(0, DB::table('list_items')->whereIn('list_id', $ids)->count());
        $this->assertSame(0, DB::table('list_likes')->whereIn('list_id', $ids)->count());
    }

    public function test_los_reportes_enviados_se_conservan_sin_denunciante(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $item = $this->createItem($category);
        $commentOfB = $this->comment($b, $item);
        $report = Report::factory()->create([
            'reporter_id' => $a->id,
            'reportable_type' => ItemComment::class,
            'reportable_id' => $commentOfB->id,
            'details' => 'Es spam',
        ]);

        $this->destroyAs($a)->assertOk();

        $report = Report::find($report->id);
        $this->assertNotNull($report);
        $this->assertNull($report->reporter_id);
        $this->assertSame('Es spam', $report->details);
    }

    public function test_borrar_la_cuenta_revoca_los_tokens(): void
    {
        $a = $this->verifiedUser();
        $token = $a->createToken('web')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/profile', ['password' => 'password'])
            ->assertOk();

        $this->assertSame(0, DB::table('personal_access_tokens')->count());

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }

    public function test_borrar_la_cuenta_borra_el_avatar_del_disco(): void
    {
        Storage::fake('public');
        $a = $this->verifiedUser();
        $b = $this->verifiedUser();
        $pathA = "avatars/{$a->id}/avatar-x.webp";
        $pathB = "avatars/{$b->id}/avatar-y.webp";
        Storage::disk('public')->put($pathA, 'x');
        Storage::disk('public')->put($pathB, 'y');
        $a->profile->update(['avatar' => $pathA]);

        $this->destroyAs($a)->assertOk();

        Storage::disk('public')->assertMissing($pathA);
        Storage::disk('public')->assertExists($pathB);
    }

    public function test_con_una_contrasena_incorrecta_no_se_borra_nada(): void
    {
        $category = $this->createCategory();
        $a = $this->verifiedUser();
        $item = $this->createItem($category);
        $this->rate($a, $item, 8);
        $comment = $this->comment($a, $item);

        $this->destroyAs($a, 'otra-clave')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', 'La contraseña no es correcta.')
            ->assertJsonPath('error.details.password.0', 'La contraseña no es correcta.');

        $this->assertDatabaseHas('users', ['id' => $a->id]);
        $this->assertDatabaseHas('votes', ['user_id' => $a->id]);
        $this->assertNotNull(ItemComment::find($comment->id));
    }

    public function test_sin_contrasena_da_422(): void
    {
        $a = $this->verifiedUser();
        $this->actingAs($a, 'sanctum');

        $this->deleteJson('/api/profile')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.password.0', 'Escribe tu contraseña para confirmar el borrado.');

        $this->assertDatabaseHas('users', ['id' => $a->id]);
    }

    public function test_sin_sesion_da_401(): void
    {
        $this->deleteJson('/api/profile', ['password' => 'x'])->assertUnauthorized();
    }

    public function test_un_admin_no_puede_borrar_su_cuenta(): void
    {
        $admin = $this->actingAsAdmin();

        $this->deleteJson('/api/profile', ['password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_item_comments_user_id_admite_null_y_conserva_la_fk(): void
    {
        $nullable = DB::table('information_schema.columns')
            ->where('table_schema', 'public')->where('table_name', 'item_comments')->where('column_name', 'user_id')
            ->value('is_nullable');
        $this->assertSame('YES', $nullable);

        $deleteType = DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conname = 'item_comments_user_id_foreign'");
        $this->assertSame('c', $deleteType->confdeltype);
    }
}
