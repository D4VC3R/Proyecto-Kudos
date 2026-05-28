<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource mínimo para representar un ítem en rankings.
 * Solo expone la información estrictamente necesaria para la vista de pódium,
 * reduciendo drásticamente el peso del payload.
 */
class ItemMinimalResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		/** @var Item $item */
		$item = $this->resource;

		return [
			'id' => $item->id,
			'name' => $item->name,
			'vote_avg' => (float) $item->vote_avg,
			'vote_count' => (int)$item->vote_count,
		];
	}
}