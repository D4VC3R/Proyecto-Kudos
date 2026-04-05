<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function getPublicKudosRanking(?User $authenticatedUser, int $topPage = 1): array
    {
        $PER_PAGE = 10;
        $safeTopPage = max(1, $topPage);
        $topPaginator = $this->userRepository->paginateRanking($PER_PAGE, $safeTopPage);

        $response = [
            'top_page' => $this->mapPaginatorData($topPaginator),
            'top_pagination' => $this->extractPagination($topPaginator),
            'top_links' => $this->extractLinks($topPaginator),
            'my_position' => null,
            'my_page_data' => null,
            'my_page_pagination' => null,
            'my_page_links' => null,
        ];

        if (!$authenticatedUser) {
            return $response;
        }

        $authenticatedUser->refresh();

        $myRank = $this->userRepository->getUserRank($authenticatedUser);
        $myPage = (int) ceil($myRank / $PER_PAGE);

        $myPagePaginator = $this->userRepository->paginateRanking($PER_PAGE, $myPage);

        $response['my_position'] = [
            'user_id' => $authenticatedUser->id,
            'rank' => $myRank,
            'page' => $myPage,
            'total_kudos' => $authenticatedUser->total_kudos,
        ];
        $response['my_page_data'] = $this->mapPaginatorData($myPagePaginator);
        $response['my_page_pagination'] = $this->extractPagination($myPagePaginator);
        $response['my_page_links'] = $this->extractLinks($myPagePaginator);

        return $response;
    }

    private function mapPaginatorData(LengthAwarePaginator $paginator): array
    {
        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();

        return collect($paginator->items())
            ->values()
            ->map(function (User $user, int $index) use ($offset): array {
                return [
                    'rank' => $offset + $index + 1,
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_kudos' => $user->total_kudos,
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    private function extractPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function extractLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
}
