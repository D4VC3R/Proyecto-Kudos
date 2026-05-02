import React, { useState } from 'react';
import { useUserRanking } from '../hooks/users/useUserQueries';
import { UserRankingMyPositionCard } from '../components/ranking/UserRankingMyPositionCard';
import { UserRankingTable } from '../components/ranking/UserRankingTable';
import { Skeleton } from '../components/common/Skeleton';
import {SectionHeader} from "../components/common/SectionHeader.jsx";

export const RankingPage = () => {
  const [page, setPage] = useState(1);
  const { data: rankingResponse, isLoading, isFetching } = useUserRanking(page);

  const topPageData = rankingResponse?.data?.top_page || [];
  const meta = rankingResponse?.meta?.top_pagination;
  const myPosition = rankingResponse?.meta?.my_position;
  const ITEMS_PER_PAGE = 10;

  return (
    <div className={`flex w-full flex-col transition-opacity duration-300 ${isFetching && !isLoading ? 'opacity-60 pointer-events-none' : 'opacity-100'}`}>

      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title="Ranking"
          highlight="Global"
          subtitle="Descubre a los mejores valoradores de la plataforma. Acumula Kudos para escalar en la tabla."
        />
      </div>

      <div className="mx-auto flex w-full max-w-4xl flex-col gap-10">
        {isLoading ? (
          <>
            <Skeleton className="h-32 w-full rounded-3xl" />
            <Skeleton className="h-[500px] w-full rounded-3xl" />
          </>
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
    </div>
  );
};

export default RankingPage;



