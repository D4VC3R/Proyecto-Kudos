import { AsyncSection } from '../components/common/AsyncSection';
import { RankingHeader } from '../components/ranking/RankingHeader';
import { RankingMyPositionCard } from '../components/ranking/RankingMyPositionCard';
import { RankingPagination } from '../components/ranking/RankingPagination';
import { RankingEmptyStatus, RankingErrorStatus, RankingLoadingStatus } from '../components/ranking/RankingStatus';
import { RankingTable } from '../components/ranking/RankingTable';
import { RankingProvider } from '../context/rankingContext';
import { useRankingContext } from '../hooks/useRankingContext';

const RankingPageContent = () => {
  const {
    rows,
    myPosition,
    isLoading,
    isError,
    error,
    isFetching,
    currentPage,
    lastPage,
    totalUsers,
    canGoPrev,
    canGoNext,
    canJumpToMyPage,
    goToFirstPage,
    goToPreviousPage,
    goToNextPage,
    goToLastPage,
    goToMyPage,
  } = useRankingContext();

  return (
    <section className="space-y-4">
      <RankingHeader />

      <AsyncSection
        EmptyComponent={RankingEmptyStatus}
        ErrorComponent={RankingErrorStatus}
        LoadingComponent={RankingLoadingStatus}
        error={error}
        isEmpty={rows.length === 0}
        isError={isError}
        isLoading={isLoading}
      >
        <RankingMyPositionCard myPosition={myPosition} />
        <RankingTable myUserId={myPosition?.user_id} rows={rows} />
        <RankingPagination
          canGoNext={canGoNext}
          canGoPrev={canGoPrev}
          canJumpToMyPage={canJumpToMyPage}
          currentPage={currentPage}
          hasMyPosition={Boolean(myPosition)}
          isFetching={isFetching}
          lastPage={lastPage}
          onFirst={goToFirstPage}
          onLast={goToLastPage}
          onMyPage={goToMyPage}
          onNext={goToNextPage}
          onPrevious={goToPreviousPage}
          totalUsers={totalUsers}
        />
      </AsyncSection>
    </section>
  );
};

export const RankingPage = () => {
  return (
    <RankingProvider>
      <RankingPageContent />
    </RankingProvider>
  );
};
