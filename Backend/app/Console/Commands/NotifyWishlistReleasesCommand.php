<?php

namespace App\Console\Commands;

use App\Jobs\NotifyWishlistReleasesJob;
use App\Models\Item;
use App\Models\Vote;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Aviso diario de estreno de la wishlist (F3-06). Busca los ítems visibles con released_at posterior a la
 * última ejecución (marca en la caché) y hasta hoy que alguien tenga en 'want', y encola un
 * NotifyWishlistReleasesJob por bloque en la cola 'default' (el scheduler tiene 128M: solo encola).
 * Sin marca, revisa los últimos kudos.wishlist.release_lookback_days días. El job no repite avisos.
 */
class NotifyWishlistReleasesCommand extends Command
{
    // Fecha (Y-m-d) del último día revisado.
    public const CACHE_KEY = 'wishlist:notify-releases:last-run';

    protected $signature = 'wishlist:notify-releases
        {--chunk=200 : Número de ítems por job}';

    protected $description = 'Avisa a los usuarios de los estrenos de los ítems de su wishlist';

    public function handle(): int
    {
        $until = Carbon::today();
        $from = $this->windowStart($until);

        if ($from->greaterThanOrEqualTo($until)) {
            $this->info('Hoy ya se han revisado los estrenos de la wishlist.');

            return self::SUCCESS;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $jobs = 0;
        $items = 0;

        Item::query()
            ->select('id')
            ->active()
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->where('released_at', '>', $from->toDateString())
            ->where('released_at', '<=', $until->toDateString())
            ->whereHas('votes', fn ($q) => $q->where('type', Vote::TYPE_WANT))
            ->chunkById($chunkSize, function ($chunk) use (&$jobs, &$items) {
                NotifyWishlistReleasesJob::dispatch($chunk->pluck('id')->all());
                $jobs++;
                $items += $chunk->count();
            });

        Cache::forever(self::CACHE_KEY, $until->toDateString());

        $this->info("Estrenos del {$from->copy()->addDay()->toDateString()} al {$until->toDateString()}: encolados {$jobs} jobs con {$items} ítems.");

        return self::SUCCESS;
    }

    /**
     * Último día ya revisado (exclusivo): la marca de la caché o, si no hay, hoy menos el margen configurado.
     */
    private function windowStart(Carbon $until): Carbon
    {
        $last = Cache::get(self::CACHE_KEY);

        if (is_string($last) && $last !== '') {
            return Carbon::parse($last)->startOfDay();
        }

        return $until->copy()->subDays(max(0, (int) config('kudos.wishlist.release_lookback_days', 7)));
    }
}
