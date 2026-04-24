import React, { useState } from 'react';
import { useUserRanking } from '../hooks/users/useUserQueries';
import { UserRankingHeader } from '../components/ranking/UserRankingHeader';
import { UserRankingMyPositionCard } from '../components/ranking/UserRankingMyPositionCard';
import { UserRankingTable } from '../components/ranking/UserRankingTable';
import { Skeleton } from '../components/common/Skeleton';

export const RankingPage = () => {
  const [page, setPage] = useState(1);
  const { data: rankingResponse, isLoading, isFetching } = useUserRanking(page);

  const topPageData = rankingResponse?.data?.top_page || [];
  const meta = rankingResponse?.meta?.top_pagination;
  const myPosition = rankingResponse?.meta?.my_position;
  const ITEMS_PER_PAGE = 10;

  return (
    <div className={`mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8 transition-opacity duration-300 ${isFetching && !isLoading ? 'opacity-60 pointer-events-none' : 'opacity-100'}`}>
      <UserRankingHeader />

      {isLoading ? (
        <div className="flex flex-col gap-10 w-full">
          <Skeleton className="h-32 w-full rounded-3xl" />
          <Skeleton className="h-[500px] w-full rounded-3xl" />
        </div>
      ) : (
        <>
          <UserRankingMyPositionCard position={myPosition} />
          <UserRankingTable
            items={topPageData}
            page={page}
            setPage={setPage}
            meta={meta}
            itemsPerPage={ITEMS_PER_PAGE}
          />
        </>
      )}
    </div>
  );
};

export default RankingPage;



