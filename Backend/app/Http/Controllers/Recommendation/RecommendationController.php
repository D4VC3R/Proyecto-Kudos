<?php

namespace App\Http\Controllers\Recommendation;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemListResource;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recomendaciones del usuario (F2-08).
 */
class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendationService) {}

    /**
     * GET /api/recommendations (F2-08): filas "Porque te gustó X" a partir de las últimas valoraciones altas
     * o, con pocas, una fila de populares de sus intereses (because = null). meta.strategy: content | popular.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->recommendationService->forUser($request->user());

        $data = array_map(fn (array $group) => [
            'because' => $group['because'] ? new ItemListResource($group['because']) : null,
            'items' => ItemListResource::collection($group['items']),
        ], $result['groups']);

        return $this->respondData($data, ['strategy' => $result['strategy']]);
    }
}
