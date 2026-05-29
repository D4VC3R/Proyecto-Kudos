import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useFilters } from '../common/useFilters.js';
import { useMyProposals } from '../proposals/useProposalQueries.js';
import { useDeleteProposal } from '../proposals/useProposalMutations.js';

export const useMyProposalsPage = () => {
  const navigate = useNavigate();

  const { data: response, isLoading, isFetching } = useMyProposals();
  const { mutate: deleteProposal, isPending: isDeleting } = useDeleteProposal();

  const [proposalToDelete, setProposalToDelete] = useState(null);
  const { filters, setFilters } = useFilters({
    initialFilters: { status: '', search: '' }
  });

  const proposals = response?.data || [];
  const meta = response?.meta || {};

  const filteredProposals = useMemo(() => {
    return proposals.filter((proposal) => {
      const matchesSearch = filters.search
        ? proposal.name?.toLowerCase().includes(filters.search.toLowerCase())
        : true;
      const matchesStatus = filters.status
        ? proposal.status === filters.status
        : true;
      return matchesSearch && matchesStatus;
    });
  }, [proposals, filters]);


  const showSkeletons = isLoading || (isFetching && proposals.length === 0);
  const isBackgroundUpdating = isFetching && !showSkeletons;


  const updateParams = (updates) => setFilters((prev) => ({ ...prev, ...updates }));
  const handleOpenDeleteModal = (proposal) => setProposalToDelete(proposal);
  const handleCloseDeleteModal = () => setProposalToDelete(null);

  const handleConfirmDelete = () => {
    if (proposalToDelete) {
      deleteProposal(proposalToDelete.id, {
        onSuccess: () => handleCloseDeleteModal()
      });
    }
  };

  const handleEditClick = (proposal) => {
    const categorySlug = proposal.category?.slug || proposal.categorySlug;
    navigate(`/${categorySlug}/proposals/${proposal.id}/edit`, {
      state: { proposalToEdit: proposal }
    });
  };

  return {
    state: {
      proposals,
      filteredProposals,
      meta,
      filters,
      showSkeletons,
      isBackgroundUpdating,
      proposalToDelete,
      isDeleting,
    },
    actions: {
      updateParams,
      handleOpenDeleteModal,
      handleCloseDeleteModal,
      handleConfirmDelete,
      handleEditClick,
    }
  };
};
