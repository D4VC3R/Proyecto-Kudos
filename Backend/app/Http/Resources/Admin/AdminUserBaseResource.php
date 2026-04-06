<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class AdminUserBaseResource extends JsonResource
{
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

