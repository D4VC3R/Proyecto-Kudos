import { useState, useMemo } from 'react';
import { useFilters } from '../common/useFilters.js';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';
import { useInfiniteMyVotes } from '../votes/useVoteQueries.js';
import { useDeleteVote, useUpdateVote } from '../votes/useVoteMutations.js';

export const useMyVotesPage = () => {
  const { filters, setFilters } = useFilters({
    initialFilters: { type: 'all', category_slug: '' }
  });

  const [voteToDelete, setVoteToDelete] = useState(null);
  const currentView = filters.type || 'all';
  const currentCategory = filters.category_slug;

  const finalFilters = useMemo(() => ({
    type: currentView === 'all' ? undefined : currentView,
    category_slug: currentCategory || undefined
  }), [currentView, currentCategory]);

  const {
    data: response,
    isLoading,
    isFetching,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  } = useInfiniteMyVotes(finalFilters, 15);

  const { mutate: deleteVote, isPending: isDeleting } = useDeleteVote();
  const { mutate: updateVote, isPending: isUpdating } = useUpdateVote();

  const lastElementRef = useInfiniteScroll({
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  });

  const allVotes = useMemo(() => {
    return response?.pages.flatMap(page => page.data) || [];
  }, [response]);

  const meta = useMemo(() => {
    return response?.pages[0]?.meta || {};
  }, [response]);

  const showSkeletons = isLoading;
  const isBackgroundUpdating = isFetching && !isFetchingNextPage && !showSkeletons;

  const updateParams = (updates) => {
    setFilters((prev) => ({ ...prev, ...updates }));
  };

  const handleOpenDeleteModal = (vote) => setVoteToDelete(vote);
  const handleCloseDeleteModal = () => setVoteToDelete(null);

  const handleConfirmDelete = () => {
    if (voteToDelete) {
      deleteVote(voteToDelete.id, {
        onSuccess: () => handleCloseDeleteModal()
      });
    }
  };

  return {
    state: {
      currentView,
      currentCategory,
      allVotes,
      meta,
      showSkeletons,
      isBackgroundUpdating,
      isFetchingNextPage,
      hasNextPage,
      voteToDelete,
      isDeleting,
      isUpdating,
    },
    actions: {
      updateParams,
      handleOpenDeleteModal,
      handleCloseDeleteModal,
      handleConfirmDelete,
      updateVote,
    },
    refs: {
      lastElementRef,
    }
  };
};