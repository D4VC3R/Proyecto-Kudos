import { createContext, useMemo, useState } from 'react';
import { useAdminItemsQuery, useAdminModerateItemMutation } from '../hooks/admin/index.js';
import { getApiErrorCode, getApiValidationDetails } from '../lib/apiErrorMap';

export const AdminItemsContext = createContext(null);

export const AdminItemsProvider = ({ children }) => {
  const [page, setPage] = useState(1);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [perPage, setPerPage] = useState(20);
  const [sortBy, setSortBy] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');

  const itemsQuery = useAdminItemsQuery({
    page,
    search,
    status: statusFilter,
    sortBy,
    sortDirection,
    perPage,
  });

  const moderateItemMutation = useAdminModerateItemMutation();

  const items = itemsQuery.data?.items ?? [];
  const meta = itemsQuery.data?.meta ?? null;

  const currentPage = meta?.current_page ?? page;
  const lastPage = meta?.last_page ?? 1;
  const total = meta?.total ?? items.length;

  const canGoPrev = currentPage > 1;
  const canGoNext = currentPage < lastPage;

  const updateSearchInput = (value) => {
    setSearchInput(value);
  };

  const updateStatusFilter = (value) => {
    setStatusFilter(value);
    setPage(1);
  };

  const updatePerPage = (value) => {
    const safePerPage = Number(value) || 20;
    setPerPage(safePerPage);
    setPage(1);
  };

  const applyFilters = () => {
    setSearch(searchInput.trim());
    setPage(1);
  };

  const resetFilters = () => {
    setSearchInput('');
    setSearch('');
    setStatusFilter('');
    setPerPage(20);
    setSortBy('created_at');
    setSortDirection('desc');
    setPage(1);
  };

  const requestSort = (field) => {
    if (sortBy !== field) {
      setSortBy(field);
      setSortDirection('desc');
      setPage(1);
      return;
    }

    setSortDirection((previousDirection) => (previousDirection === 'desc' ? 'asc' : 'desc'));
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

  const moderateItemStatus = async ({ itemId, status, reason }) => {
    try {
      await moderateItemMutation.mutateAsync({ itemId, status, reason });
      return { ok: true, code: null, validation: {} };
    } catch (error) {
      return {
        ok: false,
        code: getApiErrorCode(error),
        validation: getApiValidationDetails(error),
      };
    }
  };

  const value = useMemo(
    () => ({
      items,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      search,
      statusFilter,
      perPage,
      sortBy,
      sortDirection,
      isLoadingItems: itemsQuery.isLoading,
      isItemsError: itemsQuery.isError,
      itemsError: itemsQuery.error,
      isFetchingItems: itemsQuery.isFetching,
      isMutatingItems: moderateItemMutation.isPending,
      updateSearchInput,
      updateStatusFilter,
      updatePerPage,
      requestSort,
      applyFilters,
      resetFilters,
      goToFirstPage,
      goToPreviousPage,
      goToNextPage,
      goToLastPage,
      moderateItemStatus,
    }),
    [
      items,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      search,
      statusFilter,
      perPage,
      sortBy,
      sortDirection,
      itemsQuery.isLoading,
      itemsQuery.isError,
      itemsQuery.error,
      itemsQuery.isFetching,
      moderateItemMutation.isPending,
    ],
  );

  return <AdminItemsContext.Provider value={value}>{children}</AdminItemsContext.Provider>;
};

