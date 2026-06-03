import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useNextCategoryItem } from '../categories/useCategoryQueries.js';
import { useCreateVote } from '../votes/useVoteMutations.js';

/**
 * Hook personalizado para manejar la lógica de la página de votación.
 * Encapsula la obtención del siguiente ítem a votar, el manejo de votos y skips, y la lógica de mostrar/ocultar comentarios.
 *
 * @returns {object} Objeto con el estado y funciones necesarias para la página de votación.
 * - state: Contiene el slug de la categoría, el ítem actual a votar, si se están mostrando los comentarios, y estados de carga.
 * - actions: Funciones para manejar la votación, el skip y el toggle de comentarios.
 * */
export const useVotePage = () => {
  const { categorySlug } = useParams();
  const [showComments, setShowComments] = useState(false);

  // Peticiones de red
  const { data, isLoading, isFetching } = useNextCategoryItem(categorySlug);
  const { mutate: submitVote, isPending } = useCreateVote();

  const item = data?.data;
  const isBackgroundUpdating = isFetching && !isLoading;


  useEffect(() => {
    setShowComments(false);
  }, [item?.id]);

  const handleVote = (score) => {
    submitVote({ item_id: item.id, type: 'vote', score, categorySlug });
  };

  const handleSkip = () => {
    submitVote({ item_id: item.id, type: 'skip', categorySlug });
  };

  const toggleComments = () => {
    setShowComments((prev) => !prev);
  };

  return {
    state: {
      categorySlug,
      item,
      showComments,
      isLoading,
      isPending,
      isBackgroundUpdating,
    },
    actions: {
      handleVote,
      handleSkip,
      toggleComments,
    }
  };
};