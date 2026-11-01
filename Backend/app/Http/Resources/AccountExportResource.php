<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Exportación de los datos de un usuario (F3-08, RGPD: acceso y portabilidad).
 * Recibe el usuario con las relaciones ya cargadas (ProfileController::export) y no hace consultas.
 * Nunca incluye la contraseña ni el token de sesión.
 *
 * @mixin User
 */
class AccountExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $this->id,
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'email_verified_at' => $this->email_verified_at?->toIso8601String(),
                'privacy_accepted_at' => $this->privacy_accepted_at?->toIso8601String(),
                'total_kudos' => (int) $this->total_kudos,
                'created_at' => $this->created_at?->toIso8601String(),
            ],
            'profile' => $this->profile === null ? null : [
                'avatar' => $this->profile->avatar,
                'biography' => $this->profile->biography,
                'city' => $this->profile->city,
                'birthdate' => $this->profile->birthdate?->format('Y-m-d'),
                'social_links' => $this->profile->social_links,
                'interests' => $this->profile->interests ?? [],
                'is_public' => (bool) $this->profile->is_public,
            ],
            'votes' => $this->votes->map(fn ($vote) => [
                'item_id' => $vote->item_id,
                'item_name' => $vote->item?->name,
                'category' => $vote->item?->category?->slug,
                'type' => $vote->type,
                'score' => $vote->score,
                'created_at' => $vote->created_at?->toIso8601String(),
                'updated_at' => $vote->updated_at?->toIso8601String(),
            ])->values()->all(),
            'comments' => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'item_id' => $comment->item_id,
                'item_name' => $comment->item?->name,
                'parent_id' => $comment->parent_id,
                'is_review' => $comment->vote_id !== null,
                'content' => $comment->content,
                'is_hidden' => (bool) $comment->is_hidden,
                'hidden_reason' => $comment->hidden_reason,
                'created_at' => $comment->created_at?->toIso8601String(),
                'updated_at' => $comment->updated_at?->toIso8601String(),
                'deleted_at' => $comment->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'lists' => $this->lists->map(fn ($list) => [
                'id' => $list->id,
                'title' => $list->title,
                'slug' => $list->slug,
                'description' => $list->description,
                'visibility' => $list->visibility,
                'is_ranked' => (bool) $list->is_ranked,
                'likes_count' => (int) $list->likes_count,
                'created_at' => $list->created_at?->toIso8601String(),
                'updated_at' => $list->updated_at?->toIso8601String(),
                'deleted_at' => $list->deleted_at?->toIso8601String(),
                'items' => $list->listItems->map(fn ($entry) => [
                    'item_id' => $entry->item_id,
                    'item_name' => $entry->item?->name,
                    'position' => $entry->position,
                    'note' => $entry->note,
                ])->values()->all(),
            ])->values()->all(),
            // De una lista ajena que ya no es visible (privada o borrada) solo se exporta el "me gusta", no su contenido.
            'liked_lists' => $this->likedLists->map(function ($list) {
                $visible = $list->user_id === $this->id
                    || ($list->visibility !== UserList::VISIBILITY_PRIVATE && ! $list->trashed());

                return [
                    'list_id' => $list->id,
                    'title' => $visible ? $list->title : null,
                    'slug' => $visible ? $list->slug : null,
                    'liked_at' => $list->pivot->created_at ? Carbon::parse($list->pivot->created_at)->toIso8601String() : null,
                ];
            })->values()->all(),
            'proposals' => $this->proposals->map(fn ($proposal) => [
                'id' => $proposal->id,
                'name' => $proposal->name,
                'description' => $proposal->description,
                'category' => $proposal->category?->slug,
                'status' => $proposal->status,
                'created_at' => $proposal->created_at?->toIso8601String(),
                'deleted_at' => $proposal->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'kudos_transactions' => $this->kudosTransactions->map(fn ($transaction) => [
                'kudos_amount' => $transaction->kudos_amount,
                'reason' => $transaction->reason,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
