<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Política de permisos para los reportes de contenido: solo el admin gestiona la cola.
 */
class ReportPolicy
{
    /**
     * Indica si se puede listar la cola de reportes.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede resolver un reporte.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Report  $report  Reporte a resolver.
     */
    public function resolve(User $user, Report $report): bool
    {
        return $user->hasRole('admin');
    }
}
