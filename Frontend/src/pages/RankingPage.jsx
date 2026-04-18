import React, { useState } from 'react';
import { Loader2 } from 'lucide-react';
import { useUserRanking } from '../hooks/users/useUserQueries';
import { UserRankingHeader } from '../components/ranking/UserRankingHeader';
import { UserRankingMyPositionCard } from '../components/ranking/UserRankingMyPositionCard';
import { UserRankingTable } from '../components/ranking/UserRankingTable';

export const RankingPage = () => {
  const [page, setPage] = useState(1);
  const { data: rankingResponse, isLoading, isFetching } = useUserRanking(page);

  if (isLoading) {
    return (
      <div className="flex h-[70vh] items-center justify-center">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }

  const topPageData = rankingResponse?.data?.top_page || [];
  const meta = rankingResponse?.meta?.top_pagination;
  const myPosition = rankingResponse?.meta?.my_position;
  const ITEMS_PER_PAGE = 10;

  return (
    <div className="mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8">
      <UserRankingHeader />

      <UserRankingMyPositionCard position={myPosition} />

      <div className={`transition-opacity duration-300 ${isFetching ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
        <UserRankingTable
          items={topPageData}
          page={page}
          setPage={setPage}
          meta={meta}
          itemsPerPage={ITEMS_PER_PAGE}
        />
      </div>
    </div>
  );
};

export default RankingPage;

