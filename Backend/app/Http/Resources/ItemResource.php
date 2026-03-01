<?php

namespace App\Http\Resources;

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

		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'image' => $this->image ?? null,
			'state' => $this->state,

			// Estadísticas de votación
			'vote_avg' => (float) $this->vote_avg,
			'vote_count' => (int) $this->vote_count,


			'user_vote' => $this->when($user, function () use ($user) {
				$vote = $this->votes->firstWhere('user_id', $user->id);
				return $vote ? [
					'id' => $vote->id,
					'score' => $vote->score,
					'voted_at' => $vote->created_at->toIso8601String(),
				] : null;
			}),

			// 🔒 Indicador si el usuario puede votar
			'can_vote' => $this->when($user, function () use ($user) {
				return $this->state === 'accepted' &&
					!$this->votes->contains('user_id', $user->id);
			}),

			// ✏️ Indicador si el usuario puede editar
			'can_edit' => $this->when($user, function () use ($user) {
				return $user->can('update', $this->resource);
			}),

			// 🗑️ Indicador si el usuario puede eliminar
			'can_delete' => $this->when($user, function () use ($user) {
				return $user->can('delete', $this->resource);
			}),

			// Relaciones
			'category' => new CategoryResource($this->whenLoaded('category')),
			'creator' => $this->when($this->relationLoaded('creator'), [
				'id' => $this->creator->id,
				'name' => $this->creator->name,
			]),
			'tags' => TagResource::collection($this->whenLoaded('tags')),

			// Información administrativa (solo para admins o creador)
			'locked_at' => $this->when(
				$user && ($user->role === 'admin' || $user->id === $this->creator_id),
				$this->locked_at?->toIso8601String()
			),
			'locked_by_admin' => $this->when(
				$user && $user->role === 'admin',
				$this->lockedByAdmin ? [
					'id' => $this->lockedByAdmin->id,
					'name' => $this->lockedByAdmin->name,
				] : null
			),

			// Timestamps
			'created_at' => $this->created_at->toIso8601String(),
			'updated_at' => $this->updated_at->toIso8601String(),
		];
	}
}
