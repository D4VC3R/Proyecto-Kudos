import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useAdminUsers } from './../admin/useAdminQueries';
import { useAdminUserActions } from './../admin/useAdminActions';

export const useAdminUsersPage = () => {
  const { page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange } =
    useFilters({ initialFilters: { isBanned: '' } });

  // Transformación de los filtros para la API
  const queryFilters = useMemo(() => {
    const params = { page, search: debouncedSearch, per_page: 24 };
    if (filters.isBanned !== '') {
      params.is_banned = filters.isBanned === '1' ? 1 : 0;
    }
    return params;
  }, [page, debouncedSearch, filters.isBanned]);

  const { data: usersResponse, isLoading, isError } = useAdminUsers(queryFilters);
  const modalActions = useAdminUserActions();

  return {
    state: {
      page,
      searchInput,
      filters,
      usersResponse,
      isLoading,
      isError,
      ...modalActions,
    },
    actions: {
      setPage,
      handleSearchChange,
      handleFilterChange,
      ...modalActions,
    }
  };
};