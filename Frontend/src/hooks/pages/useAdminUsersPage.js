import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useAdminUsers } from './../admin/useAdminQueries';
import { useAdminUserActions } from './../admin/useAdminActions';

export const useAdminUsersPage = () => {
  const { page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange } =
    useFilters({ initialFilters: { isBanned: '', role: '', isVerified: '' } });

  // Transformación de los filtros para la API
  const queryFilters = useMemo(() => {
    const params = { page, search: debouncedSearch, per_page: 24 };
    if (filters.isBanned !== '') {
      params.is_banned = filters.isBanned === '1' ? 1 : 0;
    }
    if (filters.role !== '') {
      params.role = filters.role;
    }
    if (filters.isVerified !== '') {
      params.is_verified = filters.isVerified === '1' ? 1 : 0;
    }
    return params;
  }, [page, debouncedSearch, filters.isBanned, filters.role, filters.isVerified]);

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