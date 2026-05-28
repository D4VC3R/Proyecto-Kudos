<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso base para representar información común de los usuarios en el panel de administración.
 * Proporciona métodos auxiliares para resolver el rol del usuario y su estado de baneo.
 */
abstract class AdminUserBaseResource extends JsonResource
{
    /**
     * Resuelve el rol del usuario basado en sus relaciones cargadas o verificando directamente.
     * Prioriza el rol 'admin' sobre 'user' si ambos están presentes.
     *
     * @param User $user El usuario para el cual se va a resolver el rol.
     * @return string El rol resuelto del usuario ('admin' o 'user').
     */
    protected function resolveUserRole(User $user): string
    {
        if ($user->relationLoaded('roles')) {
            $roleNames = $user->roles
                ->pluck('name')
                ->filter(fn ($name) => is_string($name) && $name !== '')
                ->values();

            if ($roleNames->contains('admin')) {
                return 'admin';
            }

            if ($roleNames->contains('user')) {
                return 'user';
            }
        }

        return $user->hasRole('admin') ? 'admin' : 'user';
    }

    /**
     * Resuelve el estado de baneo del usuario basado en sus atributos relacionados con el baneo.
     * Determina si el usuario está activo, temporalmente baneado, permanentemente baneado o si su baneo ha expirado.
     *
     * @param User $user El usuario para el cual se va a resolver el estado de baneo.
     * @return string El estado de baneo resuelto del usuario ('active', 'temporary', 'permanent' o 'expired').
     */
    protected function resolveBanState(User $user): string
    {
        if (!$user->is_banned) {
            return 'active';
        }

        if ($user->banned_until === null) {
            return 'permanent';
        }

        return $user->banned_until->isPast() ? 'expired' : 'temporary';
    }

    /**
     * Mapea el estado de baneo del usuario para mostrarlo en el frontend ya formateado.
     *
     * @param string $banState El estado de baneo resuelto del usuario ('active', 'temporary', 'permanent' o 'expired').
     * @return string La etiqueta legible correspondiente al estado de baneo.
     */
    protected function resolveBanStateLabel(string $banState): string
    {
        return match ($banState) {
            'active' => 'Activo',
            'temporary' => 'Baneado temporal',
            'permanent' => 'Baneado permanente',
            'expired' => 'Baneo expirado',
            default => 'Estado desconocido',
        };
    }
}

