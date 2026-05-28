<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ListUserRankingRequest;
use App\Http\Resources\UserRankingDataResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserRankingController extends Controller
{
	public function index(ListUserRankingRequest $request): JsonResponse
	{
		$authenticatedUser = Auth::guard('sanctum')->user();
		if ($authenticatedUser && !$authenticatedUser instanceof User) {
			$authenticatedUser = null;
		}

		$page = max(1, (int) ($request->validated()['page'] ?? 1));
		$perPage = 10;

		// Obtener la página Top (Global)
		$topPaginator = User::getRankingPaginator($perPage, $page);
		$topPaginator->getCollection()->load('profile:id,user_id,avatar');

		// Preparar los datos del usuario autenticado (si existe)
		$myRank = null;
		$myPage = null;
		$myPagePaginator = null;

		if ($authenticatedUser) {
			$myRank = $authenticatedUser->getKudosRank();
			$myPage = (int) ceil($myRank / $perPage);
			$myPagePaginator = User::getRankingPaginator($perPage, $myPage);
		}

		return $this->respondList(
			data: new UserRankingDataResource([
				'top_paginator' => $topPaginator,
				'my_page_paginator' => $myPagePaginator,
			]),

			meta: [
				'top_pagination' => [
					'current_page' => $topPaginator->currentPage(),
					'last_page' => $topPaginator->lastPage(),
					'per_page' => $topPaginator->perPage(),
					'total' => $topPaginator->total(),
				],
				'my_position' => $authenticatedUser ? [
					'user_id' => $authenticatedUser->id,
					'rank' => $myRank,
					'page' => $myPage,
					'total_kudos' => $authenticatedUser->total_kudos,
				] : null,
				'my_page_pagination' => $myPagePaginator ? [
					'current_page' => $myPagePaginator->currentPage(),
					'last_page' => $myPagePaginator->lastPage(),
					'per_page' => $myPagePaginator->perPage(),
					'total' => $myPagePaginator->total(),
				] : null,
			],

			// Enlaces extraídos en línea
			links: [
				'top_page' => [
					'first' => $topPaginator->url(1),
					'last' => $topPaginator->url($topPaginator->lastPage()),
					'prev' => $topPaginator->previousPageUrl(),
					'next' => $topPaginator->nextPageUrl(),
				],
				'my_page' => $myPagePaginator ? [
					'first' => $myPagePaginator->url(1),
					'last' => $myPagePaginator->url($myPagePaginator->lastPage()),
					'prev' => $myPagePaginator->previousPageUrl(),
					'next' => $myPagePaginator->nextPageUrl(),
				] : null,
			],
		);
	}
}