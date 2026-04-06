import { createContext, useMemo, useState } from 'react';
import { useAdminProposalsQuery, useAdminReviewProposalMutation } from '../hooks/admin/domains/proposalsAdminHooks';
import { useTableState } from '../hooks/useTableState';

export const AdminProposalsContext = createContext(null);

export const AdminProposalsProvider = ({ children }) => {
    const tableState = useTableState(15);
    const [statusFilter, setStatusFilter] = useState('');

    const proposalsQuery = useAdminProposalsQuery({
        page: tableState.page,
        perPage: tableState.perPage,
        search: tableState.search,
        status: statusFilter,
    });

    const reviewMutation = useAdminReviewProposalMutation();

    const proposals = proposalsQuery.data?.data ?? [];
    const meta = proposalsQuery.data?.meta ?? null;

    const currentPage = tableState.page;
    const lastPage = meta?.last_page ?? 1;
    const total = meta?.total ?? proposals.length;
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

    const reviewProposal = async ({ proposalId, status, adminNotes }) => {
        await reviewMutation.mutateAsync({ proposalId, status, adminNotes });
    };

    const value = useMemo(
        () => ({
            proposals, total, currentPage, lastPage, canGoPrev, canGoNext,
            searchInput: tableState.searchInput,
            statusFilter, perPage: tableState.perPage,
            isLoadingProposals: proposalsQuery.isLoading,
            isProposalsError: proposalsQuery.isError,
            proposalsError: proposalsQuery.error,
            isFetchingProposals: proposalsQuery.isFetching,
            isMutatingProposals: reviewMutation.isPending,
            updateSearchInput: tableState.updateSearchInput,
            updateStatusFilter,
            updatePerPage: tableState.updatePerPage,
            applyFilters: tableState.applySearch,
            resetFilters,
            goToFirstPage: () => tableState.goToPage(1),
            goToPreviousPage: () => tableState.goToPage(Math.max(1, currentPage - 1)),
            goToNextPage: () => tableState.goToPage(Math.min(lastPage, currentPage + 1)),
            goToLastPage: () => tableState.goToPage(lastPage),
            reviewProposal,
        }),
        [
            proposals, total, currentPage, lastPage, canGoPrev, canGoNext,
            tableState, statusFilter, proposalsQuery.isLoading, proposalsQuery.isError,
            proposalsQuery.error, proposalsQuery.isFetching, reviewMutation.isPending,
        ],
    );

    return <AdminProposalsContext.Provider value={value}>{children}</AdminProposalsContext.Provider>;
};