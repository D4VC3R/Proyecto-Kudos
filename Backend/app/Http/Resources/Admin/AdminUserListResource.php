<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Recurso para devolver la información básica del usuario al listarlos en el panel admin o tras realizar un baneo.
 * Extiende el recurso base AdminUserBaseResource para incluir información adicional específica del detalle del usuario.
 */
class AdminUserListResource extends AdminUserBaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $banState = $this->resolveBanState($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => $user->hasVerifiedEmail(),
            'role' => $this->resolveUserRole($user),
            'is_banned' => (bool) $user->is_banned,
            'ban_state' => $banState,
            'ban_state_label' => $this->resolveBanStateLabel($banState),
            'banned_until' => $user->banned_until?->toIso8601String(),
        ];
    }
}

