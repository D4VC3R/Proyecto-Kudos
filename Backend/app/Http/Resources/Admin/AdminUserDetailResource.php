<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserDetailResource extends AdminUserBaseResource
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
            'banned_at' => $user->banned_at?->toIso8601String(),
            'banned_until' => $user->banned_until?->toIso8601String(),
            'ban_reason' => $user->ban_reason,
            'total_kudos' => (int) $user->total_kudos,
            'creations_accepted' => (int) $user->creations_accepted,
            'proposals_count' => (int) $user->proposals_count,
            'votes_count' => (int) $user->votes_count,
            'comments_count' => (int) $user->comments_count,
            'items_count' => (int) $user->items_count,
            'reviewed_proposals_count' => (int) $user->reviewed_proposals_count,
            'sessions_count' => (int) $user->tokens()->count(),
            'profile' => [
                'city' => $user->profile?->city,
                'birthdate' => $user->profile?->birthdate?->format('Y-m-d'),
            ],
        ];
    }
}

