<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\ImportCatalogItemOnDemandAction;
use App\Catalog\CatalogSearch;
use App\Catalog\Data\OnDemandImportResult;
use App\Catalog\Exceptions\CatalogUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ImportCatalogItemRequest;
use App\Http\Requests\Catalog\SearchCatalogRequest;
use App\Http\Resources\CatalogSearchResultResource;
use App\Http\Resources\ItemDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Búsqueda e importación bajo demanda desde el catálogo externo (F1-07, pipeline-catalogo §7).
 */
class CatalogController extends Controller
{
	public function __construct(
		protected CatalogSearch $catalogSearch,
		protected ImportCatalogItemOnDemandAction $importAction,
	) {}

	public function search(SearchCatalogRequest $request): JsonResponse
	{
		$validated = $request->validated();

		try {
			$results = $this->catalogSearch->search($validated['category'], $validated['q']);
		} catch (CatalogUnavailableException) {
			return $this->catalogUnavailable();
		}

		return $this->respondList(
			data: CatalogSearchResultResource::collection($results),
			meta: ['category' => $validated['category'], 'query' => Str::squish($validated['q'])],
		);
	}

	public function import(ImportCatalogItemRequest $request): JsonResponse
	{
		$validated = $request->validated();

		try {
			$result = $this->importAction->execute($request->user(), $validated['category'], $validated['external_id']);
		} catch (CatalogUnavailableException) {
			return $this->catalogUnavailable();
		}

		return match ($result->outcome) {
			OnDemandImportResult::CREATED => $this->respondMutation(
				'Título añadido a Kudos.',
				new ItemDetailResource($result->item->fresh(['category', 'creator'])),
				status: 201,
			),
			OnDemandImportResult::EXISTING => $this->respondMutation(
				'Este título ya estaba en Kudos.',
				['item_id' => $result->item->id],
			),
			OnDemandImportResult::UNAVAILABLE => $this->respondError(
				'conflict',
				'Este título ya está en Kudos, pero ahora mismo no está disponible.',
				status: 409,
			),
			OnDemandImportResult::NOT_FOUND => $this->respondError(
				'not_found',
				'No hemos encontrado este título en el catálogo externo.',
				status: 404,
			),
		};
	}

	private function catalogUnavailable(): JsonResponse
	{
		return $this->respondError(
			'catalog_unavailable',
			'No hemos podido consultar el catálogo externo. Inténtalo de nuevo en unos minutos.',
			status: 503,
		);
	}
}
