import React, { useState } from 'react';
// Componentes
import UserRankingMyPositionCard from '../components/ui/ranking/UserRankingMyPositionCard';
import UserRankingTable from '../components/ui/ranking/UserRankingTable';
import Skeleton from '../components/ui/Skeleton.jsx';
import SectionHeader from "../components/ui/SectionHeader.jsx";
// Hooks
import { useUserRanking } from '../hooks/users/useUserQueries';

const RankingPage = () => {
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
          subtitle="Conéctate todos los días, mándanos propuestas y vota en tus categorías favoritas para escalar en el ranking Kudos"
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



