import { useFilters } from '../common/useFilters';
import { useAdminReports } from '../admin/useAdminQueries';
import { useAdminReportActions } from '../admin/useAdminActions';

/**
 * Maneja los filtros, la paginación y las acciones de la cola de reportes (F3-04).
 *
 * @returns Un objeto con `state` (filtros, respuesta de la API y estado del modal) y `actions`.
 * */
export const useAdminReportsPage = () => {
  const { page, setPage, filters, handleFilterChange } =
    useFilters({ initialFilters: { status: 'pending', reportable_type: '' } });

  const { data: reportsResponse, isLoading, isError } = useAdminReports({
    page,
    status: filters.status,
    reportable_type: filters.reportable_type,
    per_page: 10,
  });

  const modalActions = useAdminReportActions();

  return {
    state: {
      page,
      filters,
      reportsResponse,
      isLoading,
      isError,
      ...modalActions,
    },
    actions: {
      setPage,
      handleFilterChange,
      ...modalActions,
    },
  };
};
