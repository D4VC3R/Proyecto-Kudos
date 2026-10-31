<?php

namespace App\Console\Commands;

use App\Services\UsageMetricsService;
use Illuminate\Console\Command;

/**
 * Muestra en tablas las métricas de uso de producto/vision.md §4 (F2-11).
 * Solo lee de la base de datos: no escribe nada ni usa analítica externa.
 */
class UsageMetricsCommand extends Command
{
    public const MAX_DAYS = 365;

    protected $signature = 'kudos:metrics
        {--days=7 : Días naturales del periodo, contando hoy (1-365)}';

    protected $description = 'Muestra las métricas de uso (usuarios activos, votos por día, cobertura de valoraciones y listas)';

    public function handle(UsageMetricsService $metrics): int
    {
        $raw = (string) $this->option('days');
        if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > self::MAX_DAYS) {
            $this->error('La opción --days debe ser un número entero entre 1 y 365.');

            return self::FAILURE;
        }
        $days = (int) $raw;

        $summary = $metrics->summary($days);
        $coverage = $summary['rating_coverage'];
        $lists = $summary['lists'];

        $this->info(sprintf(
            'Métricas de uso: últimos %d días (del %s al %s)',
            $days,
            $summary['from']->toDateString(),
            $summary['until']->toDateString()
        ));

        $this->table(['Métrica', 'Valor'], [
            ['Usuarios activos (votan o comentan)', $summary['active_users']],
            ['Ítems visibles', $coverage['items']],
            [sprintf('Ítems con al menos %d valoraciones', $coverage['min_ratings']), $coverage['rated_items']],
            [sprintf('Porcentaje de ítems con al menos %d valoraciones', $coverage['min_ratings']), number_format($coverage['percentage'], 1, ',', '.').' %'],
            ['Listas de usuario creadas', $lists['user_created']],
            ['Listas editoriales creadas', $lists['editorial_created']],
            ['Listas con al menos un "me gusta"', $lists['liked']],
        ]);

        $this->newLine();
        $this->line('Votos por día (por fecha del voto y tipo actual):');

        $rows = [];
        $totals = ['vote' => 0, 'want' => 0, 'not_interested' => 0, 'total' => 0];
        foreach ($summary['votes_per_day'] as $d) {
            $rows[] = [$d['date'], $d['vote'], $d['want'], $d['not_interested'], $d['total']];
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $d[$key];
            }
        }
        $rows[] = ['Total', $totals['vote'], $totals['want'], $totals['not_interested'], $totals['total']];

        $this->table(['Día', 'vote', 'want', 'not_interested', 'Total'], $rows);

        return self::SUCCESS;
    }
}
