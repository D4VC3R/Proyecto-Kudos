<?php

namespace App\Services;

use App\Models\Item;
use App\Models\UserList;
use App\Models\Vote;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Métricas de uso de Kudos (F2-11, producto/vision.md §4) para el comando `kudos:metrics`.
 * Solo lecturas sobre la base de datos; sin analítica externa.
 */
class UsageMetricsService
{
    /**
     * Periodo de N días naturales que termina ahora: desde las 00:00 de hace N-1 días hasta $now.
     * Con N = 7, hoy y los 6 días anteriores.
     */
    public function periodStart(int $days, CarbonInterface $now): CarbonImmutable
    {
        return CarbonImmutable::instance($now)->subDays($days - 1)->startOfDay();
    }

    /**
     * Todas las métricas del periodo.
     *
     * @return array{from: CarbonImmutable, until: CarbonImmutable, days: int, active_users: int,
     *     votes_per_day: list<array{date: string, vote: int, want: int, not_interested: int, total: int}>,
     *     rating_coverage: array{items: int, rated_items: int, min_ratings: int, percentage: float},
     *     lists: array{user_created: int, editorial_created: int, liked: int}}
     */
    public function summary(int $days, ?CarbonInterface $now = null): array
    {
        $until = CarbonImmutable::instance($now ?? now());
        $from = $this->periodStart($days, $until);

        return [
            'from' => $from,
            'until' => $until,
            'days' => $days,
            'active_users' => $this->activeUsers($from, $until),
            'votes_per_day' => $this->votesPerDay($from, $until),
            'rating_coverage' => $this->ratingCoverage(),
            'lists' => $this->listCounts($from, $until),
        ];
    }

    /**
     * Usuarios distintos con al menos un voto o un comentario en el periodo.
     * Los votos usan updated_at porque una transición (want -> valoración, cambio de nota) actualiza la fila
     * y también es actividad (al crear, updated_at = created_at). Los comentarios usan created_at (ocultar un
     * comentario cambia updated_at pero no es actividad del usuario) y se cuentan aunque luego se hayan borrado
     * u ocultado (DB::table, sin el scope de borrado suave). El union ya elimina duplicados.
     */
    public function activeUsers(CarbonInterface $from, CarbonInterface $until): int
    {
        $voters = DB::table('votes')->select('user_id')->whereBetween('updated_at', [$from, $until]);
        $commenters = DB::table('item_comments')->select('user_id')->whereBetween('created_at', [$from, $until]);

        return DB::query()->fromSub($voters->union($commenters), 'active_users')->count();
    }

    /**
     * Una fila por día natural del periodo, en orden ascendente, incluidos los días sin votos (a 0).
     * Se agrupa por created_at (fecha del primer voto sobre ese ítem) y el tipo es el actual de la fila;
     * los votos borrados no cuentan (no hay histórico).
     *
     * @return list<array{date: string, vote: int, want: int, not_interested: int, total: int}>
     */
    public function votesPerDay(CarbonInterface $from, CarbonInterface $until): array
    {
        $counts = DB::table('votes')
            ->selectRaw('DATE(created_at) AS day, type, COUNT(*) AS total')
            ->whereBetween('created_at', [$from, $until])
            ->groupByRaw('DATE(created_at), type')
            ->get();

        $days = [];
        $period = CarbonPeriod::create(
            CarbonImmutable::instance($from)->startOfDay(),
            '1 day',
            CarbonImmutable::instance($until)->startOfDay()
        );
        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            $days[$key] = ['date' => $key, 'vote' => 0, 'want' => 0, 'not_interested' => 0, 'total' => 0];
        }

        foreach ($counts as $row) {
            $key = substr((string) $row->day, 0, 10);
            if (! isset($days[$key])) {
                continue;
            }

            if ($row->type === Vote::TYPE_VOTE) {
                $column = 'vote';
            } elseif ($row->type === Vote::TYPE_WANT) {
                $column = 'want';
            } elseif (in_array($row->type, Vote::NOT_INTERESTED_TYPES, true)) {
                $column = 'not_interested';
            } else {
                continue;
            }

            $days[$key][$column] += (int) $row->total;
            $days[$key]['total'] += (int) $row->total;
        }

        return array_values($days);
    }

    /**
     * Ítems visibles (activos, sin borrar y de categoría activa) y cuántos tienen al menos
     * kudos.metrics.min_ratings votos de Vote::RATED_TYPES. Foto actual, no depende del periodo.
     * Se cuenta desde votes y no desde items.vote_count (caché que se recalcula de noche).
     *
     * @return array{items: int, rated_items: int, min_ratings: int, percentage: float}
     */
    public function ratingCoverage(): array
    {
        $minRatings = (int) config('kudos.metrics.min_ratings', 5);
        $items = Item::query()->active()->whereHas('category', fn ($q) => $q->where('is_active', true));

        $total = (clone $items)->count();
        $rated = (clone $items)->whereIn('id', Vote::query()
            ->select('item_id')
            ->whereIn('type', Vote::RATED_TYPES)
            ->groupBy('item_id')
            ->havingRaw('COUNT(*) >= ?', [$minRatings]))
            ->count();

        return [
            'items' => $total,
            'rated_items' => $rated,
            'min_ratings' => $minRatings,
            'percentage' => $total > 0 ? round($rated * 100 / $total, 1) : 0.0,
        ];
    }

    /**
     * "Creadas" cuenta también las borradas después (mide la actividad del periodo); "con al menos un me gusta"
     * es una foto actual de las listas no borradas y usa likes_count, que mantiene la F3-01 con los "me gusta".
     *
     * @return array{user_created: int, editorial_created: int, liked: int}
     */
    public function listCounts(CarbonInterface $from, CarbonInterface $until): array
    {
        return [
            'user_created' => UserList::withTrashed()->where('is_editorial', false)->whereBetween('created_at', [$from, $until])->count(),
            'editorial_created' => UserList::withTrashed()->where('is_editorial', true)->whereBetween('created_at', [$from, $until])->count(),
            'liked' => UserList::query()->where('likes_count', '>', 0)->count(),
        ];
    }
}
