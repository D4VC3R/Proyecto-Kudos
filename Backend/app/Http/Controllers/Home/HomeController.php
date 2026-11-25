<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ItemListResource;
use App\Http\Resources\UserListResource;
use App\Services\HomeFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Portada (F2-09).
 */
class HomeController extends Controller
{
    /**
     * GET /api/home: público. Con sesión, "Para ti" sale de las recomendaciones (F2-08) y los ítems llevan
     * el voto del usuario; sin ella, "Para ti" son los populares de las categorías activas.
     */
    public function index(Request $request, HomeFeedService $feed): JsonResponse
    {
        $result = $feed->forViewer($request->user());

        return $this->respondData([
            'for_you' => [
                'strategy' => $result['for_you']['strategy'],
                'groups' => array_map(fn (array $group) => [
                    'because' => $group['because'] ? new ItemListResource($group['because']) : null,
                    'items' => ItemListResource::collection($group['items']),
                ], $result['for_you']['groups']),
            ],
            'new_releases' => ItemListResource::collection($result['new_releases']),
            'editorial_lists' => UserListResource::collection($result['editorial_lists']),
            'top_rated' => ItemListResource::collection($result['top_rated']),
            'categories' => CategoryResource::collection($result['categories']),
        ]);
    }
}
