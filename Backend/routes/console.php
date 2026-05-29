<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Comando de Artisan para auditar la consistencia entre el campo users.total_kudos y el total calculado a partir de kudos_transactions.
 * Permite detectar desajustes y opcionalmente reconciliar los datos con la opción --fix.
 */
Artisan::command('kudos:audit-consistency {--fix : Reconciliar users.total_kudos con el total del ledger}', function () {
    $rows = DB::table('users')
        ->leftJoin('kudos_transactions as kt', 'users.id', '=', 'kt.user_id')
        ->selectRaw('users.id, users.email, users.total_kudos, COALESCE(SUM(kt.kudos_amount), 0) as ledger_total')
        ->groupBy('users.id', 'users.email', 'users.total_kudos')
        ->orderBy('users.email')
        ->get();

    $mismatches = $rows->filter(fn ($row) => (int) $row->total_kudos !== (int) $row->ledger_total)->values();

    $this->info('Usuarios auditados: ' . $rows->count());
    $this->line('Desajustes detectados: ' . $mismatches->count());

    if ($mismatches->isEmpty()) {
        $this->info('¡Todo en orden! No se detectaron inconsistencias.');
        return;
    }

    $tableRows = $mismatches->map(fn ($row) => [
        $row->email,
        (int) $row->total_kudos,
        (int) $row->ledger_total,
        (int) $row->ledger_total - (int) $row->total_kudos,
    ])->all();

    $this->table(['email', 'cached_total', 'ledger_total', 'delta'], $tableRows);

    if (!$this->option('fix')) {
        $this->warn('Ejecuta el comando con --fix para ajustar los Kudos del usuario.');
        return;
    }

    DB::transaction(function () use ($mismatches) {
        foreach ($mismatches as $row) {
            DB::table('users')
                ->where('id', $row->id)
                ->update(['total_kudos' => (int) $row->ledger_total]);
        }
    });

    $this->info('Kudos restaurados correctamente.');
})->purpose('Audita y reconcilia users.total_kudos con kudos_transactions');

