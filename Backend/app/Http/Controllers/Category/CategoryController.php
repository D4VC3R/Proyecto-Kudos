<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\DeleteCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Votes\GetNextCategoryItemRequest;
use App\Http\Resources\CategoryRankingResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemDetailResource;
use App\Models\Category;
use App\Services\NextCategoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controlador para gestionar categorías.
 * Proporciona endpoints para listar, crear, mostrar detalles, actualizar y eliminar categorías,
 * así como obtener el ranking de ítems por categoría y el siguiente ítem a votar.
 */
class CategoryController extends Controller
{

    public function __construct(
        protected NextCategoryItemService $nextCategoryItemService,
    ) {}

    public function index(): JsonResponse
    {
    // Usamos withItemCount() a nivel de BD para evitar el problema N+1 al iterar sobre las categorías.
        $categories = Category::withItemCount()->orderBy('name')->get();
        return $this->respondData(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());
        return $this->respondMutation('Categoría creada con éxito.', new CategoryResource($category), status: 201);
    }

    public function show(Category $category): JsonResponse
    {

        $category->loadCount('items');
        return $this->respondData(new CategoryWithItemsResource($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());
        return $this->respondMutation('Categoría actualizada correctamente.', new CategoryResource($category->fresh()));
    }

    public function destroy(DeleteCategoryRequest $request, Category $category): JsonResponse
    {
        if ($category->items()->exists()) {
            return $this->respondError(
                code: 'CONFLICT_CATEGORY_HAS_ITEMS',
                message: "No se puede eliminar la categoría porque tiene {$category->itemsCount} ítems asociados.",
                status: 409
            );
        }

        $categoryName = $category->name;
        $category->delete();

        return $this->respondMutation("Categoría '{$categoryName}' eliminada correctamente.");
    }

		// El ranking se obtiene a través de una consulta optimizada en el modelo Category, que calcula el puntaje total de cada ítem y los ordena.
    public function ranking(Request $request, Category $category): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $items = $category->getRankingPaginator($perPage);

        return $this->respondData(new CategoryRankingResource([
            'category' => $category,
            'ranking' => $items,
        ]), meta: [
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
        ]);
    }

		// El siguiente ítem se obtiene a través de un servicio que selecciona el ítem más adecuado para el usuario, teniendo en cuenta su historial de votos y el estado de los ítems en la categoría.
    public function nextItem(GetNextCategoryItemRequest $request, Category $category): JsonResponse|Response
    {
        $result = $this->nextCategoryItemService->getNextItem($request->user(), $category);

        if ($result === null) {
            return response()->noContent();
        }

        return $this->respondData(
            data: new ItemDetailResource($result['item']),
            meta: ['remaining' => $result['remaining']],
        );
    }
}
