<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRankingDataResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'top_page' => $this->formatRankingData($this->resource['top_paginator']),
			'my_page_data' => isset($this->resource['my_page_paginator'])
				? $this->formatRankingData($this->resource['my_page_paginator'])
				: null,
		];
	}

	/**
	 * Lógica de presentación específica para el ranking, formateando los datos de cada usuario y calculando su posición en base a la paginación.
	 */
	private function formatRankingData(LengthAwarePaginator $paginator): array
	{
		$offset = ($paginator->currentPage() - 1) * $paginator->perPage();

		return collect($paginator->items())
			->values()
			->map(function (User $user, int $index) use ($offset) {
				return [
					'rank' => $offset + $index + 1,
					'id' => $user->id,
					'name' => $user->name,
					'total_kudos' => $user->total_kudos,
					'avatar' => $user->profile?->avatar,
					'created_at' => $user->created_at?->toIso8601String(),
				];
			})
			->all();
	}
}

