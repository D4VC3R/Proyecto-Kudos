import { createContext, useMemo, useState } from 'react';
import { useAdminProposalsQuery, useAdminReviewProposalMutation } from '../hooks/admin/index.js';

export const AdminProposalsContext = createContext(null);

export const AdminProposalsProvider = ({ children }) => {
  const [page, setPage] = useState(1);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [perPage, setPerPage] = useState(15);

  const proposalsQuery = useAdminProposalsQuery({ page, search, status: statusFilter, perPage });
  const reviewMutation = useAdminReviewProposalMutation();

  const proposals = proposalsQuery.data?.proposals ?? [];
  const meta = proposalsQuery.data?.meta ?? null;

  const currentPage = meta?.current_page ?? page;
  const lastPage = meta?.last_page ?? 1;
  const total = meta?.total ?? proposals.length;
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
    const safePerPage = Number(value) || 15;
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
    setPerPage(15);
    setPage(1);
  };

  const goToFirstPage = () => setPage(1);
  const goToPreviousPage = () => setPage((prevPage) => Math.max(1, prevPage - 1));
  const goToNextPage = () => setPage((prevPage) => Math.min(lastPage, prevPage + 1));
  const goToLastPage = () => setPage(lastPage);

  const reviewProposal = async ({ proposalId, status, adminNotes }) => {
    await reviewMutation.mutateAsync({ proposalId, status, adminNotes });
  };

  const value = useMemo(
    () => ({
      proposals,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      statusFilter,
      perPage,
      isLoadingProposals: proposalsQuery.isLoading,
      isProposalsError: proposalsQuery.isError,
      proposalsError: proposalsQuery.error,
      isFetchingProposals: proposalsQuery.isFetching,
      isMutatingProposals: reviewMutation.isPending,
      updateSearchInput,
      updateStatusFilter,
      updatePerPage,
      applyFilters,
      resetFilters,
      goToFirstPage,
      goToPreviousPage,
      goToNextPage,
      goToLastPage,
      reviewProposal,
    }),
    [
      proposals,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      searchInput,
      statusFilter,
      perPage,
      proposalsQuery.isLoading,
      proposalsQuery.isError,
      proposalsQuery.error,
      proposalsQuery.isFetching,
      reviewMutation.isPending,
    ],
  );

  return <AdminProposalsContext.Provider value={value}>{children}</AdminProposalsContext.Provider>;
};

