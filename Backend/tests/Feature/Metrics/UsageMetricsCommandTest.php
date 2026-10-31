<?php

namespace Tests\Feature\Metrics;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use App\Services\UsageMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class UsageMetricsCommandTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private const MSG_DAYS = 'La opción --days debe ser un número entero entre 1 y 365.';

    protected function setUp(): void
    {
        parent::setUp();

        // Con --days=7 el periodo va de 2026-03-04 00:00:00 a 2026-03-10 12:00:00.
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
    }

    /**
     * Crea un voto con fechas explícitas (la factory crea sin protección y respeta las marcas de tiempo).
     */
    private function vote(User $user, Item $item, string $createdAt, string $type = Vote::TYPE_VOTE, ?string $updatedAt = null): Vote
    {
        return Vote::factory()->byUser($user)->forItem($item)->create([
            'type' => $type,
            'score' => $type === Vote::TYPE_VOTE ? 8 : null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt ?? $createdAt,
        ]);
    }

    private function comment(User $user, Item $item, string $createdAt): ItemComment
    {
        return ItemComment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_cuenta_los_usuarios_activos_por_votos_y_comentarios_del_periodo(): void
    {
        $service = app(UsageMetricsService::class);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        [$u1, $u2, $u3, $u4, $u5, $u6, $u7, $u8] = User::factory()->count(8)->create()->all();

        $this->vote($u1, $item, '2026-03-05 10:00:00');
        $this->comment($u2, $item, '2026-03-09 10:00:00');
        $this->vote($u3, $item, '2026-03-06 10:00:00');
        $this->comment($u3, $item, '2026-03-07 10:00:00');
        $this->vote($u4, $item, '2026-02-01 10:00:00');
        $this->vote($u5, $item, '2026-02-01 10:00:00', Vote::TYPE_VOTE, '2026-03-08 10:00:00');
        $this->comment($u6, $item, '2026-03-06 10:00:00')->delete();
        $this->comment($u7, $item, '2026-03-01 10:00:00');
        $this->vote($u8, $item, '2026-03-03 23:59:59');

        $this->assertSame(5, $service->activeUsers($service->periodStart(7, now()), now()));
    }

    public function test_reparte_los_votos_por_dia_y_por_tipo(): void
    {
        $service = app(UsageMetricsService::class);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        [$u1, $u2, $u3, $u4, $u5, $u6] = User::factory()->count(6)->create()->all();

        $this->vote($u1, $item, '2026-03-04 00:00:00');
        $this->vote($u2, $item, '2026-03-04 00:00:00');
        $this->vote($u3, $item, '2026-03-04 00:00:00', Vote::TYPE_WANT);
        $this->vote($u4, $item, '2026-03-10 10:00:00', Vote::TYPE_NOT_INTERESTED);
        $this->vote($u5, $item, '2026-03-10 10:00:00', Vote::TYPE_SKIP);
        $this->vote($u6, $item, '2026-03-03 23:59:59');

        $rows = $service->votesPerDay($service->periodStart(7, now()), now());

        $this->assertCount(7, $rows);
        $this->assertSame(
            ['date' => '2026-03-04', 'vote' => 2, 'want' => 1, 'not_interested' => 0, 'total' => 3],
            $rows[0]
        );
        $this->assertSame(
            ['date' => '2026-03-05', 'vote' => 0, 'want' => 0, 'not_interested' => 0, 'total' => 0],
            $rows[1]
        );
        $this->assertSame(
            ['date' => '2026-03-10', 'vote' => 0, 'want' => 0, 'not_interested' => 2, 'total' => 2],
            $rows[6]
        );
    }

    public function test_calcula_el_porcentaje_de_items_con_al_menos_cinco_valoraciones(): void
    {
        $service = app(UsageMetricsService::class);
        $category = $this->createCategory();
        $users = User::factory()->count(6)->create();

        [$a, $b] = [$this->createItem($category), $this->createItem($category)];
        $this->createItem($category);
        $this->createItem($category);
        $this->createItem($category);

        $inactive = $this->createItem($category, ['status' => 'inactive']);
        $deleted = $this->createItem($category);
        $deleted->delete();
        $inactiveCategory = $this->createCategory('politicos', 'Políticos');
        $inactiveCategory->update(['is_active' => false]);
        $ofInactiveCategory = $this->createItem($inactiveCategory);

        $at = '2026-03-09 10:00:00';
        foreach ($users->take(5) as $user) {
            $this->vote($user, $a, $at);
            $this->vote($user, $inactive, $at);
            $this->vote($user, $ofInactiveCategory, $at);
        }
        foreach ($users->take(4) as $user) {
            $this->vote($user, $b, $at);
        }
        $this->vote($users[4], $b, $at, Vote::TYPE_WANT);
        $this->vote($users[5], $b, $at, Vote::TYPE_NOT_INTERESTED);

        $this->assertSame(
            ['items' => 5, 'rated_items' => 1, 'min_ratings' => 5, 'percentage' => 20.0],
            $service->ratingCoverage()
        );

        config(['kudos.metrics.min_ratings' => 4]);

        $this->assertSame(2, $service->ratingCoverage()['rated_items']);
    }

    public function test_cuenta_las_listas_creadas_y_las_que_tienen_me_gusta(): void
    {
        $service = app(UsageMetricsService::class);

        UserList::factory()->create(['created_at' => '2026-03-05 10:00:00', 'updated_at' => '2026-03-05 10:00:00']);
        UserList::factory()->create(['created_at' => '2026-03-06 10:00:00', 'updated_at' => '2026-03-06 10:00:00'])->delete();
        UserList::factory()->create(['created_at' => '2026-02-20 10:00:00', 'updated_at' => '2026-02-20 10:00:00', 'likes_count' => 2]);
        UserList::factory()->editorial()->create(['created_at' => '2026-03-09 10:00:00', 'updated_at' => '2026-03-09 10:00:00']);
        UserList::factory()->create(['created_at' => '2026-02-01 10:00:00', 'updated_at' => '2026-02-01 10:00:00', 'likes_count' => 3])->delete();

        $this->assertSame(
            ['user_created' => 2, 'editorial_created' => 1, 'liked' => 1],
            $service->listCounts($service->periodStart(7, now()), now())
        );
    }

    public function test_el_comando_muestra_las_metricas_en_tablas(): void
    {
        $category = $this->createCategory();
        $rated = $this->createItem($category);
        $wanted = $this->createItem($category);
        $users = User::factory()->count(6)->create();

        foreach ($users->take(5) as $user) {
            $this->vote($user, $rated, '2026-03-10 09:00:00');
        }
        $this->vote($users[5], $wanted, '2026-03-09 09:00:00', Vote::TYPE_WANT);
        UserList::factory()->editorial()->create(['created_at' => '2026-03-10 09:00:00', 'updated_at' => '2026-03-10 09:00:00']);

        $this->artisan('kudos:metrics', ['--days' => 2])
            ->expectsOutputToContain('Métricas de uso: últimos 2 días (del 2026-03-09 al 2026-03-10)')
            ->expectsTable(['Métrica', 'Valor'], [
                ['Usuarios activos (votan o comentan)', 6],
                ['Ítems visibles', 2],
                ['Ítems con al menos 5 valoraciones', 1],
                ['Porcentaje de ítems con al menos 5 valoraciones', '50,0 %'],
                ['Listas de usuario creadas', 0],
                ['Listas editoriales creadas', 1],
                ['Listas con al menos un "me gusta"', 0],
            ])
            ->expectsTable(['Día', 'vote', 'want', 'not_interested', 'Total'], [
                ['2026-03-09', 0, 1, 0, 1],
                ['2026-03-10', 5, 0, 0, 5],
                ['Total', 5, 1, 0, 6],
            ])
            ->assertSuccessful();
    }

    public function test_el_comando_usa_siete_dias_por_defecto(): void
    {
        $this->artisan('kudos:metrics')
            ->expectsOutputToContain('últimos 7 días (del 2026-03-04 al 2026-03-10)')
            ->assertSuccessful();
    }

    public function test_el_comando_funciona_sin_datos(): void
    {
        $this->artisan('kudos:metrics')
            ->expectsTable(['Métrica', 'Valor'], [
                ['Usuarios activos (votan o comentan)', 0],
                ['Ítems visibles', 0],
                ['Ítems con al menos 5 valoraciones', 0],
                ['Porcentaje de ítems con al menos 5 valoraciones', '0,0 %'],
                ['Listas de usuario creadas', 0],
                ['Listas editoriales creadas', 0],
                ['Listas con al menos un "me gusta"', 0],
            ])
            ->assertSuccessful();
    }

    public function test_el_comando_rechaza_dias_no_validos(): void
    {
        foreach (['0', '366', 'abc', '-3', '1.5'] as $value) {
            $this->artisan('kudos:metrics', ['--days' => $value])
                ->expectsOutputToContain(self::MSG_DAYS)
                ->assertFailed();
        }
    }
}
