<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingItemsRequest;
use App\Http\Resources\ItemListResource;
use App\Services\OnboardingItemsService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints del onboarding de un usuario nuevo (F2-07).
 */
class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingItemsService $onboardingItemsService) {}

    /**
     * GET /api/onboarding/items?categories=a,b: hasta catalog.onboarding_items ítems populares repartidos entre esas categorías (F2-07).
     */
    public function items(OnboardingItemsRequest $request): JsonResponse
    {
        $items = $this->onboardingItemsService->popularItems($request->user(), $request->validated('categories'));

        return $this->respondData(ItemListResource::collection($items));
    }
}
