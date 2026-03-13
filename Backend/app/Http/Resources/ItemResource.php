<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        if ($user && !isset($this->can_vote)) {
            $this->calculateUserContext($user);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => $this->images ?? [],
            'status' => $this->status,

            'vote_avg' => (float) $this->vote_avg,
            'vote_count' => (int) $this->vote_count,

            'user_vote' => $this->when(
                $user && $this->relationLoaded('userVote') && $this->userVote,
                fn () => [
                    'id' => $this->userVote->id,
                    'score' => $this->userVote->score,
                    'voted_at' => $this->userVote->created_at->toIso8601String(),
                ]
            ),

            'can_vote' => $this->when($user, fn () => $this->can_vote ?? false),
            'can_edit' => $this->when($user, fn () => $this->can_edit ?? false),
            'can_delete' => $this->when($user, fn () => $this->can_delete ?? false),

            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => $this->when($this->relationLoaded('creator'), [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
        ];
    }

    private function calculateUserContext($user): void
    {
        if (!$this->relationLoaded('userVote')) {
            $this->load(['votes' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);
            $this->setRelation('userVote', $this->votes->first());
        }

        $isAdmin = $user->hasRole('admin');
        $hasVoted = $this->userVote !== null;

        $this->setAttribute('can_vote', $this->status === Item::STATUS_ACTIVE && !$hasVoted);
        $this->setAttribute('can_edit', $isAdmin);
        $this->setAttribute('can_delete', $isAdmin);
        $this->setAttribute('is_creator', $this->creator_id === $user->id);
    }
}
