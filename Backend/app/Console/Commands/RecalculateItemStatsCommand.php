<?php

namespace App\Console\Commands;

use App\Jobs\RecalculateItemStatsJob;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Console\Command;

/**
 * Encola el recálculo de las estadísticas de votos de los ítems por bloques.
 * Solo encola: el trabajo lo hace el worker en la cola 'catalog' (el scheduler tiene 128M, ver docs/infra/produccion.md §2).
 */
class RecalculateItemStatsCommand extends Command
{
    protected $signature = 'items:recalculate-stats
        {--category= : Slug de la categoría (por defecto, todas)}
        {--chunk=200 : Número de ítems por job}';

    protected $description = 'Encola el recálculo de vote_count y vote_avg de los ítems en la cola catalog';

    public function handle(): int
    {
        $category = null;
        $slug = $this->option('category');
        if ($slug !== null && $slug !== '') {
            $category = Category::query()->where('slug', $slug)->first();
            if (! $category) {
                $this->error("No existe la categoría '{$slug}'.");

                return self::FAILURE;
            }
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $jobs = 0;
        $items = 0;

        Item::query()
            ->select('id')
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->chunkById($chunkSize, function ($chunk) use (&$jobs, &$items) {
                RecalculateItemStatsJob::dispatch($chunk->pluck('id')->all());
                $jobs++;
                $items += $chunk->count();
            });

        $this->info("Encolados {$jobs} jobs con {$items} ítems en la cola catalog.");

        return self::SUCCESS;
    }
}
