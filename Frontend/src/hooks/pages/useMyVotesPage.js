import { useState, useMemo } from 'react';
import { useFilters } from '../common/useFilters.js';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';
import { useInfiniteMyVotes } from '../votes/useVoteQueries.js';
import { useDeleteVote, useUpdateVote } from '../votes/useVoteMutations.js';

/**
 * Hook personalizado para manejar la lógica de la página "Mis Votos".
 *
 * @return {object} Objeto con el estado, acciones y refs necesarios para la página de "Mis Votos".
 * - `state`: Contiene el estado actual de la página, incluyendo el tipo de vista, categoría seleccionada, lista de votos, meta información, estados de carga y eliminación/actualización.
 * - `actions`: Funciones para actualizar los filtros, manejar la apertura/cierre del modal de eliminación y confirmar la eliminación de un voto.
 * - `refs`: Referencias necesarias para implementar el scroll infinito.
 * */
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
    if (!response?.pages) return [];

    return response.pages
      .flatMap(page => page.data)
      .filter(vote => vote && vote.item !== null);
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