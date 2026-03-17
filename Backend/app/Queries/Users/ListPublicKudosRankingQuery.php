<?php

namespace App\Queries\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListPublicKudosRankingQuery
{
    private const PER_PAGE = 10;

    /**
     * @return array{top_page: array<int,array<string,mixed>>, top_pagination: array<string,mixed>, top_links: array<string,mixed>, my_position: ?array<string,mixed>, my_page_data: ?array<int,array<string,mixed>>, my_page_pagination: ?array<string,mixed>, my_page_links: ?array<string,mixed>}
     */
    public function execute(?User $authenticatedUser): array
    {
        $topPaginator = $this->baseQuery()->paginate(self::PER_PAGE, ['*'], 'page', 1);

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

        $myRank = $this->resolveRank($authenticatedUser);
        $myPage = (int) ceil($myRank / self::PER_PAGE);

        $myPagePaginator = $this->baseQuery()->paginate(self::PER_PAGE, ['*'], 'page', $myPage);

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

    private function baseQuery(): Builder
    {
        return User::query()
            ->select(['id', 'name', 'total_kudos', 'created_at'])
            ->orderByDesc('total_kudos')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    private function resolveRank(User $user): int
    {
        $usersAhead = User::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('total_kudos', '>', $user->total_kudos)
                    ->orWhere(function (Builder $tieBreaker) use ($user) {
                        $tieBreaker->where('total_kudos', $user->total_kudos)
                            ->where(function (Builder $sameKudos) use ($user) {
                                $sameKudos->where('created_at', '<', $user->created_at)
                                    ->orWhere(function (Builder $sameTimestamp) use ($user) {
                                        $sameTimestamp->where('created_at', $user->created_at)
                                            ->where('id', '<', $user->id);
                                    });
                            });
                    });
            })
            ->count();

        return $usersAhead + 1;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
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

    /**
     * @return array<string,mixed>
     */
    private function extractPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
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

