<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRankingController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = Auth::guard('sanctum')->user();
        if ($authenticatedUser && !$authenticatedUser instanceof User) {
            $authenticatedUser = null;
        }
        $result = $this->userService->getPublicKudosRanking($authenticatedUser);
        return $this->respondList(
            data: [
                'top_page' => $result['top_page'],
                'my_page_data' => $result['my_page_data'],
            ],
            meta: [
                'top_pagination' => $result['top_pagination'],
                'my_position' => $result['my_position'],
                'my_page_pagination' => $result['my_page_pagination'],
            ],
            links: [
                'top_page' => $result['top_links'],
                'my_page' => $result['my_page_links'],
            ],
        );
    }
}
