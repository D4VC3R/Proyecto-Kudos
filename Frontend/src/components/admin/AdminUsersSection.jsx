import { Link } from 'react-router-dom';
import { useAdminUsersContext } from '../../hooks/admin';
import { AdminUsersFilters } from './AdminUsersFilters';
import { AdminUsersTable } from './AdminUsersTable';
import { AdminAsyncBadge } from './shared/AdminAsyncBadge';
import { AdminSectionHeader } from './shared/AdminSectionHeader';
import { PaginationControls } from '../common/PaginationControls';

export const AdminUsersSection = () => {
  const {
    users,
    isLoadingUsers,
    isUsersError,
    isFetchingUsers,
    currentPage,
    lastPage,
    total,
    canGoPrev,
    canGoNext,
    goToFirstPage,
    goToPreviousPage,
    goToNextPage,
    goToLastPage,
  } = useAdminUsersContext();

  const showPagination = !isLoadingUsers && !isUsersError && users.length > 0;

  return (
      <section className="space-y-4 rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
        <AdminSectionHeader
            subtitle="Filtra, ordena y aplica acciones de moderacion sin salir de la tabla."
            title="Listado de usuarios"
        />

        <div className="flex items-center justify-between gap-3">
          <Link
              className="inline-block rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-100 transition-colors hover:bg-slate-800"
              to="/admin"
          >
            Volver atras
          </Link>
          <AdminAsyncBadge visible={isFetchingUsers && !isLoadingUsers} />
        </div>

        <AdminUsersFilters />
        <AdminUsersTable />

        {showPagination && (
            <PaginationControls
                canGoNext={canGoNext}
                canGoPrev={canGoPrev}
                currentPage={currentPage}
                entityLabel="usuarios"
                isFetching={isFetchingUsers}
                lastPage={lastPage}
                onFirst={goToFirstPage}
                onLast={goToLastPage}
                onNext={goToNextPage}
                onPrev={goToPreviousPage}
                showUpdatingInSummary={false}
                total={total}
            />
        )}
      </section>
  );
};