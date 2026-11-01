<?php

namespace App\Http\Resources;

use App\Models\ItemComment;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Perfil público de un usuario (F3-03, GET /api/users/{username}). Solo datos que el usuario ha decidido publicar:
 * nunca el correo, la ciudad, la fecha de nacimiento, los enlaces ni los intereses.
 */
class PublicProfileResource extends JsonResource
{
    public function __construct(
        User $resource,
        private readonly Collection $topByCategory,
        private readonly Collection $lists,
        private readonly Collection $reviews,
        private readonly bool $isOwner,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // el frontend lo usa para "Reportar" (reportable_id)
            'username' => $this->username,
            'name' => $this->name,
            'avatar' => $this->profile?->avatar,
            'biography' => $this->profile?->biography,
            'is_public' => (bool) ($this->profile?->is_public ?? false),
            'is_owner' => $this->isOwner,
            'member_since' => $this->created_at?->toIso8601String(),
            'top_by_category' => $this->topByCategory->map(fn (array $group) => [
                'category' => [
                    'id' => $group['category']->id,
                    'name' => $group['category']->name,
                    'slug' => $group['category']->slug,
                    'icon' => $group['category']->icon,
                ],
                'items' => $group['votes']->map(fn (Vote $vote) => [
                    'score' => $vote->score,
                    'item' => new ItemListResource($vote->item),
                ])->all(),
            ])->all(),
            'lists' => UserListResource::collection($this->lists),
            'reviews' => $this->reviews->map(fn (ItemComment $review) => [
                'id' => $review->id,
                'content' => $review->content,
                'score' => $review->vote?->type === Vote::TYPE_VOTE ? $review->vote->score : null,
                'created_at' => $review->created_at?->toIso8601String(),
                'item' => new ItemListResource($review->item),
            ])->all(),
        ];
    }
}
