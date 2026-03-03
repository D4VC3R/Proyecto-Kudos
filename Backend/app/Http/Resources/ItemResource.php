<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
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
			'image' => $this->image ?? null,
			'state' => $this->state,

			// Estadísticas de votación
			'vote_avg' => (float)$this->vote_avg,
			'vote_count' => (int)$this->vote_count,

			// ✅ Voto del usuario (ya cargado eficientemente)
			'user_vote' => $this->when($user && $this->relationLoaded('userVote') && $this->userVote, function () {
				return [
					'id' => $this->userVote->id,
					'score' => $this->userVote->score,
					'voted_at' => $this->userVote->created_at->toIso8601String(),
				];
			}),

			// ✅ Permisos (ya calculados en el servicio)
			'can_vote' => $this->when($user, fn() => $this->can_vote ?? false),
			'can_edit' => $this->when($user, fn() => $this->can_edit ?? false),
			'can_delete' => $this->when($user, fn() => $this->can_delete ?? false),

			// Relaciones
			'category' => new CategoryResource($this->whenLoaded('category')),
			'creator' => $this->when($this->relationLoaded('creator'), [
				'id' => $this->creator->id,
				'name' => $this->creator->name,
			]),
			// 'tags' => TagResource::collection($this->whenLoaded('tags')),
			'locked_by_admin' => $this->when(
				$user && ($this->is_admin_user ?? false) && $this->relationLoaded('lockedByAdmin'),
				$this->lockedByAdmin ? [
					'id' => $this->lockedByAdmin->id,
					'name' => $this->lockedByAdmin->name,
				] : null
			),
            ];
	}
	private function calculateUserContext($user): void
	{
		// Cargar voto del usuario si no está cargado
		if (!$this->relationLoaded('userVote')) {
			$this->load(['votes' => function ($query) use ($user) {
				$query->where('user_id', $user->id);
			}]);
			$this->setRelation('userVote', $this->votes->first());
		}

		$isAdmin = $user->hasRole('admin');
		$hasVoted = $this->userVote !== null;

		// Calcular permisos
		$this->setAttribute('can_vote', $this->state === Item::STATE_ACCEPTED && !$hasVoted);
		$this->setAttribute('can_edit', $isAdmin || ($this->creator_id === $user->id && $this->state === Item::STATE_PENDING));
		$this->setAttribute('can_delete', $isAdmin || ($this->creator_id === $user->id && $this->state === Item::STATE_PENDING));
		$this->setAttribute('is_creator', $this->creator_id === $user->id);

	}
}
