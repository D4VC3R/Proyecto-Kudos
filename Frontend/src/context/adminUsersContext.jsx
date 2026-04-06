import { createContext, useMemo, useState } from 'react';
import { useAdminUsersQuery, useAdminBanUserMutation, useAdminUnbanUserMutation } from '../hooks/admin/domains/usersAdminHooks';
import { useTableState } from '../hooks/useTableState';

export const AdminUsersContext = createContext(null);

export const AdminUsersProvider = ({ children }) => {
    const tableState = useTableState(20, 'name');
    const [banState, setBanState] = useState('');
    const [roleFilter, setRoleFilter] = useState('');

    const usersQuery = useAdminUsersQuery({
        page: tableState.page,
        perPage: tableState.perPage,
        search: tableState.search,
        sortBy: tableState.sortBy,
        sortDirection: tableState.sortDirection,
        banState,
        role: roleFilter,
    });

    const banMutation = useAdminBanUserMutation();
    const unbanMutation = useAdminUnbanUserMutation();

    const users = usersQuery.data?.data ?? [];
    const meta = usersQuery.data?.meta ?? null;
    const summary = usersQuery.data?.summary ?? null;

    const currentPage = tableState.page;
    const lastPage = meta?.last_page ?? 1;
    const total = meta?.total ?? users.length;
    const canGoPrev = currentPage > 1;
    const canGoNext = currentPage < lastPage;

    const isMutating = banMutation.isPending || unbanMutation.isPending;

    const updateBanState = (value) => {
        setBanState(value);
        tableState.goToPage(1);
    };

    const updateRoleFilter = (value) => {
        setRoleFilter(value);
        tableState.goToPage(1);
    };

    const resetFilters = () => {
        tableState.resetTableState();
        setBanState('');
        setRoleFilter('');
    };

    const banUser = async ({ userId, reason, isPermanent, days }) => {
        const payload = isPermanent ? { reason, is_permanent: true } : { reason, is_permanent: false, days };
        await banMutation.mutateAsync({ userId, payload });
    };

    const unbanUser = async ({ userId }) => {
        await unbanMutation.mutateAsync({ userId });
    };

    const value = useMemo(
        () => ({
            users, summary, total, currentPage, lastPage, canGoPrev, canGoNext,
            searchInput: tableState.searchInput,
            banState, roleFilter,
            perPage: tableState.perPage,
            sortBy: tableState.sortBy,
            sortDirection: tableState.sortDirection,
            isLoadingUsers: usersQuery.isLoading,
            isUsersError: usersQuery.isError,
            usersError: usersQuery.error,
            isFetchingUsers: usersQuery.isFetching,
            isMutating,
            updateSearchInput: tableState.updateSearchInput,
            updateBanState, updateRoleFilter,
            updatePerPage: tableState.updatePerPage,
            requestSort: tableState.requestSort,
            resetFilters,
            applyFilters: tableState.applySearch,
            goToFirstPage: () => tableState.goToPage(1),
            goToPreviousPage: () => tableState.goToPage(Math.max(1, currentPage - 1)),
            goToNextPage: () => tableState.goToPage(Math.min(lastPage, currentPage + 1)),
            goToLastPage: () => tableState.goToPage(lastPage),
            banUser, unbanUser,
        }),
        [
            users, summary, total, currentPage, lastPage, canGoPrev, canGoNext,
            tableState, banState, roleFilter, usersQuery.isLoading, usersQuery.isError,
            usersQuery.error, usersQuery.isFetching, isMutating,
        ],
    );

    return <AdminUsersContext.Provider value={value}>{children}</AdminUsersContext.Provider>;
};