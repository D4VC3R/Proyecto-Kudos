import React, { useState } from 'react';
import clsx from 'clsx';
import PageHeader from '../components/ui/PageHeader.jsx';
import UserRankingTable from '../components/ui/ranking/UserRankingTable.jsx';
import RankingSkeleton from '../components/ui/skeletons/RankingSkeleton.jsx';
import { useUserRanking } from '../hooks/users/useUserQueries';

const RankingPage = () => {
  const [page, setPage] = useState(1);
  const { data: rankingResponse, isLoading, isFetching } = useUserRanking(page);

  const topPage = rankingResponse?.data?.top_page ?? [];
  const meta = rankingResponse?.meta?.top_pagination;
  const myPosition = rankingResponse?.meta?.my_position ?? null;

  return (
    <div className="flex w-full flex-col gap-8">
      <PageHeader
        title="Ranking"
        highlight="global"
        subtitle="Conéctate todos los días, mándanos propuestas y vota en tus categorías favoritas para subir en el ranking."
      />
      <div
        className={clsx('transition-opacity duration-300', isFetching && !isLoading && 'pointer-events-none opacity-60')}
        aria-busy={isFetching || undefined}
      >
        {isLoading ? (
          <RankingSkeleton />
        ) : (
          <UserRankingTable items={topPage} myPosition={myPosition} meta={meta} onPageChange={setPage} />
        )}
      </div>
    </div>
  );
};

export default RankingPage;
