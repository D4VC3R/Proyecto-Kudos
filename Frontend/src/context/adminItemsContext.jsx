import { createContext, useMemo, useState } from 'react';
import { useAdminItemsQuery, useAdminModerateItemMutation } from '../hooks/admin/domains/itemsAdminHooks';
import { useTableState } from '../hooks/useTableState';
import { getApiErrorCode, getApiValidationDetails } from '../lib/apiErrorMap';

export const AdminItemsContext = createContext(null);

export const AdminItemsProvider = ({ children }) => {
    const tableState = useTableState(20, 'created_at');
    const [statusFilter, setStatusFilter] = useState('');

    const itemsQuery = useAdminItemsQuery({
        page: tableState.page,
        perPage: tableState.perPage,
        search: tableState.search,
        sortBy: tableState.sortBy,
        sortDirection: tableState.sortDirection,
        status: statusFilter,
    });

    const moderateItemMutation = useAdminModerateItemMutation();

    const items = itemsQuery.data?.data ?? [];
    const meta = itemsQuery.data?.meta ?? null;

    const currentPage = tableState.page;
    const lastPage = meta?.last_page ?? 1;
    const total = meta?.total ?? items.length;
    const canGoPrev = currentPage > 1;
    const canGoNext = currentPage < lastPage;

    const updateStatusFilter = (value) => {
        setStatusFilter(value);
        tableState.goToPage(1);
    };

    const resetFilters = () => {
        tableState.resetTableState();
        setStatusFilter('');
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
            items, total, currentPage, lastPage, canGoPrev, canGoNext,
            searchInput: tableState.searchInput,
            search: tableState.search,
            statusFilter,
            perPage: tableState.perPage,
            sortBy: tableState.sortBy,
            sortDirection: tableState.sortDirection,
            isLoadingItems: itemsQuery.isLoading,
            isItemsError: itemsQuery.isError,
            itemsError: itemsQuery.error,
            isFetchingItems: itemsQuery.isFetching,
            isMutatingItems: moderateItemMutation.isPending,
            updateSearchInput: tableState.updateSearchInput,
            updateStatusFilter,
            updatePerPage: tableState.updatePerPage,
            requestSort: tableState.requestSort,
            applyFilters: tableState.applySearch,
            resetFilters,
            goToFirstPage: () => tableState.goToPage(1),
            goToPreviousPage: () => tableState.goToPage(Math.max(1, currentPage - 1)),
            goToNextPage: () => tableState.goToPage(Math.min(lastPage, currentPage + 1)),
            goToLastPage: () => tableState.goToPage(lastPage),
            moderateItemStatus,
        }),
        [
            items, total, currentPage, lastPage, canGoPrev, canGoNext,
            tableState, statusFilter, itemsQuery.isLoading, itemsQuery.isError,
            itemsQuery.error, itemsQuery.isFetching, moderateItemMutation.isPending,
        ],
    );

    return <AdminItemsContext.Provider value={value}>{children}</AdminItemsContext.Provider>;
};