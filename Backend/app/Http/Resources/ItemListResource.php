<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ItemListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Item $item */
        $item = $this->resource;
        $user = $this->resolveViewer($request);
        $isAdmin = $user instanceof User && $user->hasRole('admin');

	    return [
		    'id' => $item->id,
		    'name' => $item->name,
		    'description' => $item->description,
		    'images' => collect($item->images ?? [])
			    ->take(1)
			    ->map(fn ($img) => [
				    'variants' => [
					    'thumb' => $img['variants']['thumb'] ?? null,
				    ],
				    'meta' => $img['meta'] ?? null,
				    'alt' => $img['alt'] ?? null,
			    ])->toArray(),
		    'status' => $item->status,
		    'vote_avg' => (float) $item->vote_avg,
		    'vote_count' => (int) $item->vote_count,
            'user_vote' => $this->when(
                $user && $item->relationLoaded('userVote') && $item->userVote,
                fn () => [
                    'id' => $item->userVote->id,
                    'score' => $item->userVote->score,
                    'voted_at' => $item->userVote->created_at?->toIso8601String(),
                ]
            ),

            'can_vote' => $this->resolveCanVote($user, $item),
            'can_edit' => $this->when($user instanceof User, fn () => $isAdmin || $item->creator_id === $user->id),
            'can_delete' => $this->when($user instanceof User, fn () => $isAdmin),

            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $item->creator->id,
                'name' => $item->creator->name,
            ]),
        ];
    }

    protected function resolveCanVote(?User $user, Item $item): bool
    {
        if (!$user || $item->status !== Item::STATUS_ACTIVE) {
            return false;
        }

        if ($item->relationLoaded('userVote')) {
            return $item->userVote === null;
        }

        return false;
    }

    protected function resolveViewer(Request $request): ?User
    {
        $requestUser = $request->user();
        if ($requestUser instanceof User) {
            return $requestUser;
        }

        $sanctumUser = Auth::guard('sanctum')->user();

        return $sanctumUser instanceof User ? $sanctumUser : null;
    }
}