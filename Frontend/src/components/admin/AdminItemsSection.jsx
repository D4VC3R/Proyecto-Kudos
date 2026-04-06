import { useAdminItemsContext } from '../../hooks/admin';
import { AdminItemsFilters } from './AdminItemsFilters';
import { AdminSectionHeader } from './shared/AdminSectionHeader';
import { AdminItemsTable } from './AdminItemsTable';
import { PaginationControls } from '../common/PaginationControls';

export const AdminItemsSection = () => {
  const {
    items,
    isLoadingItems,
    isItemsError,
    isFetchingItems,
    currentPage,
    lastPage,
    total,
    canGoPrev,
    canGoNext,
    goToFirstPage,
    goToPreviousPage,
    goToNextPage,
    goToLastPage,
  } = useAdminItemsContext();

  const showPagination = !isLoadingItems && !isItemsError && items.length > 0;

  return (
      <section className="space-y-4 rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
        <AdminSectionHeader
            subtitle="Activa o desactiva items de forma controlada desde el panel admin."
            title="Moderacion de items"
        />

        <AdminItemsFilters />

        <AdminItemsTable />

        {showPagination && (
            <PaginationControls
                canGoNext={canGoNext}
                canGoPrev={canGoPrev}
                currentPage={currentPage}
                entityLabel="items"
                isFetching={isFetchingItems}
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