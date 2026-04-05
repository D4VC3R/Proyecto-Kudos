import { createContext, useEffect, useMemo, useState } from 'react';
import { useAdminUsersQuery, useAdminBanUserMutation, useAdminUnbanUserMutation } from '../hooks/admin/index.js';

export const AdminUsersContext = createContext(null);

export const AdminUsersProvider = ({ children }) => {
  const [page, setPage] = useState(1);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [banState, setBanState] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [perPage, setPerPage] = useState(20);
  const [sortBy, setSortBy] = useState('name');
  const [sortDirection, setSortDirection] = useState('desc');

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      setSearch(searchInput.trim());
      setPage(1);
    }, 300);

    return () => clearTimeout(timeoutId);
  }, [searchInput]);

  const usersQuery = useAdminUsersQuery({
    page,
    search,
    banState,
    role: roleFilter,
    perPage,
    sortBy,
    sortDirection,
  });

  const banMutation = useAdminBanUserMutation();
  const unbanMutation = useAdminUnbanUserMutation();

  const rawUsers = usersQuery.data?.data ?? [];
  const meta = usersQuery.data?.meta ?? null;
  const summary = meta?.summary ?? null;

  const currentPage = meta?.current_page ?? page;
  const lastPage = meta?.last_page ?? 1;
  const total = meta?.total ?? rawUsers.length;

  const canGoPrev = currentPage > 1;
  const canGoNext = currentPage < lastPage;

  const isMutating = banMutation.isPending || unbanMutation.isPending;

  const updateSearchInput = (value) => {
    setSearchInput(value);
  };

  const updateBanState = (value) => {
    setBanState(value);
    setPage(1);
  };

  const updateRoleFilter = (value) => {
    setRoleFilter(value);
    setPage(1);
  };

  const updatePerPage = (value) => {
    const safePerPage = Number(value) || 20;
    setPerPage(safePerPage);
    setPage(1);
  };

  const requestSort = (field) => {
    if (sortBy !== field) {
      setSortBy(field);
      setSortDirection('desc');
      setPage(1);
      return;
    }

    setSortDirection((prevDirection) => (prevDirection === 'desc' ? 'asc' : 'desc'));
    setPage(1);
  };

  const resetFilters = () => {
    setSearchInput('');
    setSearch('');
    setBanState('');
    setRoleFilter('');
    setPerPage(20);
    setSortBy('name');
    setSortDirection('desc');
    setPage(1);
  };

  const goToFirstPage = () => {
    setPage(1);
  };

  const goToPreviousPage = () => {
    setPage((prevPage) => Math.max(1, prevPage - 1));
  };

  const goToNextPage = () => {
    setPage((prevPage) => Math.min(lastPage, prevPage + 1));
  };

  const goToLastPage = () => {
    setPage(lastPage);
  };

  const banUser = async ({ userId, reason, isPermanent, days }) => {
    const payload = {
      reason,
      is_permanent: isPermanent,
    };

    if (!isPermanent) {
      payload.days = days;
    }

    await banMutation.mutateAsync({ userId, payload });
  };

  const unbanUser = async ({ userId }) => {
    await unbanMutation.mutateAsync({ userId });
  };

  const value = useMemo(
    () => ({
      users: rawUsers,
      summary,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      banState,
      roleFilter,
      perPage,
      sortBy,
      sortDirection,
      isLoadingUsers: usersQuery.isLoading,
      isUsersError: usersQuery.isError,
      usersError: usersQuery.error,
      isFetchingUsers: usersQuery.isFetching,
      isMutating,
      updateSearchInput,
      updateBanState,
      updateRoleFilter,
      updatePerPage,
      requestSort,
      resetFilters,
      goToFirstPage,
      goToPreviousPage,
      goToNextPage,
      goToLastPage,
      banUser,
      unbanUser,
    }),
    [
      rawUsers,
      summary,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      banState,
      roleFilter,
      perPage,
      sortBy,
      sortDirection,
      usersQuery.isLoading,
      usersQuery.isError,
      usersQuery.error,
      usersQuery.isFetching,
      isMutating,
    ],
  );

  return <AdminUsersContext.Provider value={value}>{children}</AdminUsersContext.Provider>;
};

