<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Queries\Users\ListPublicKudosRankingQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRankingController extends Controller
{
    public function __construct(protected ListPublicKudosRankingQuery $listPublicKudosRankingQuery)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = Auth::guard('sanctum')->user();

        if ($authenticatedUser && !$authenticatedUser instanceof User) {
            $authenticatedUser = null;
        }

        $result = $this->listPublicKudosRankingQuery->execute($authenticatedUser);

        return response()->json([
            'data' => [
                'top_page' => $result['top_page'],
                'my_page_data' => $result['my_page_data'],
            ],
            'meta' => [
                'top_pagination' => $result['top_pagination'],
                'my_position' => $result['my_position'],
                'my_page_pagination' => $result['my_page_pagination'],
            ],
            'links' => [
                'top_page' => $result['top_links'],
                'my_page' => $result['my_page_links'],
            ],
        ]);
    }
}

