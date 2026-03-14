<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
        $this->info('No se detectaron inconsistencias entre total_kudos y el ledger.');
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
        $this->warn('Ejecuta el comando con --fix para reconciliar automaticamente los desajustes.');
        return;
    }

    DB::transaction(function () use ($mismatches) {
        foreach ($mismatches as $row) {
            DB::table('users')
                ->where('id', $row->id)
                ->update(['total_kudos' => (int) $row->ledger_total]);
        }
    });

    $this->info('Reconciliacion aplicada correctamente.');
})->purpose('Audita y opcionalmente reconcilia users.total_kudos con kudos_transactions');

