<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalDetailResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		/** @var Proposal $proposal */
		$proposal = $this->resource;

		return [
			'id' => $proposal->id,
			'name' => $proposal->name,
			'description' => $proposal->description,
			// OPTIMIZACIÓN: Enviamos todas las imágenes y variantes limpiando campos irrelevantes
			'images' => collect($proposal->images ?? [])
				->map(fn ($img) => [
					'variants' => [
						'thumb' => $img['variants']['thumb'] ?? null,
						'banner' => $img['variants']['banner'] ?? null,
					],
					'alt' => $img['alt'] ?? null,
					'order' => $img['order'] ?? 0,
				])->toArray(),
			'status' => $proposal->status,
			'creator_id' => $proposal->creator_id,
			'category_id' => $proposal->category_id,
			'reviewed_by' => $proposal->reviewed_by,
			'reviewed_at' => $proposal->reviewed_at?->toIso8601String(),
			'admin_notes' => $proposal->admin_notes,
			'created_at' => $proposal->created_at?->toIso8601String(),
			'updated_at' => $proposal->updated_at?->toIso8601String(),
			'creator' => $this->when($proposal->relationLoaded('creator'), fn () => [
				'id' => $proposal->creator?->id,
				'name' => $proposal->creator?->name,
				'email' => $proposal->creator?->email,
			]),
			'category' => $this->when($proposal->relationLoaded('category'), fn () => [
				'id' => $proposal->category?->id,
				'name' => $proposal->category?->name,
				'slug' => $proposal->category?->slug,
			]),
			'reviewer' => $this->when($proposal->relationLoaded('reviewer'), fn () => [
				'id' => $proposal->reviewer?->id,
				'name' => $proposal->reviewer?->name,
			]),
		];
	}
}