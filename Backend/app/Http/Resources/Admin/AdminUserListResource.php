<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;

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
            'role' => $this->resolveUserRole($user),
            'is_banned' => (bool) $user->is_banned,
            'ban_state' => $banState,
            'ban_state_label' => $this->resolveBanStateLabel($banState),
            'banned_until' => $user->banned_until?->toIso8601String(),
        ];
    }
}

