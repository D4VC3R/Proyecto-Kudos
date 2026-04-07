import { useAdminProposalsContext } from '../../hooks/admin/useAdminContexts';
import { AdminProposalsFilters } from './AdminProposalsFilters';
import { AdminSectionHeader } from './shared/AdminSectionHeader';
import { AdminProposalsTable } from './AdminProposalsTable';
import { PaginationControls } from '../common/PaginationControls'; // Ajusta la ruta si es ../../common/PaginationControls

export const AdminProposalsSection = () => {
  const {
    proposals,
    isLoadingProposals,
    isProposalsError,
    isFetchingProposals,
    currentPage,
    lastPage,
    total,
    canGoPrev,
    canGoNext,
    goToFirstPage,
    goToPreviousPage,
    goToNextPage,
    goToLastPage,
  } = useAdminProposalsContext();

  const showPagination = !isLoadingProposals && !isProposalsError && proposals.length > 0;

  return (
      <section className="space-y-4 rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
        <AdminSectionHeader
            subtitle="Revisa propuestas y define su estado de moderacion."
            title="Moderacion de propuestas"
        />

        <AdminProposalsFilters />
        <AdminProposalsTable />

        {showPagination && (
            <PaginationControls
                canGoNext={canGoNext}
                canGoPrev={canGoPrev}
                currentPage={currentPage}
                entityLabel="propuestas"
                isFetching={isFetchingProposals}
                lastPage={lastPage}
                onFirst={goToFirstPage}
                onLast={goToLastPage}
                onNext={goToNextPage}
                onPrev={goToPreviousPage}
                total={total}
            />
        )}
      </section>
  );
};