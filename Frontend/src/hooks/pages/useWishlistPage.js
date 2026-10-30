import { useMemo, useState } from 'react';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';
import { useInfiniteMyVotes } from '../votes/useVoteQueries.js';
import { useUpdateVote } from '../votes/useVoteMutations.js';
import { splitWishlistByRelease } from '../../lib/wishlist.js';

// La wishlist son los votos de tipo `want`. La clave de caché cuelga de VOTE_KEYS.myVotesList(),
// que se invalida al crear, cambiar o borrar un voto.
const WISHLIST_FILTERS = { type: 'want' };
const WISHLIST_PAGE_SIZE = 50;

/**
 * Hook personalizado para manejar la lógica de la página "Wishlist" (F2-05).
 *
 * @return {object} Objeto con el estado, acciones y refs necesarios para la página.
 * - `state`: ítems agrupados en `available` ("Ya disponible") y `upcoming` ("Próximamente"), total, estados de carga y de actualización, y el voto que se está valorando.
 * - `actions`: abrir y cerrar el modal de valorar, valorar ("Ya lo conozco") y quitar (marcar "no me interesa").
 * - `refs`: referencia para el scroll infinito.
 * */
export const useWishlistPage = () => {
  const {
    data: response,
    isLoading,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  } = useInfiniteMyVotes(WISHLIST_FILTERS, WISHLIST_PAGE_SIZE);

  const { mutate: updateVote, isPending: isUpdating, variables } = useUpdateVote();

  const lastElementRef = useInfiniteScroll({
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  });

  const [voteToRate, setVoteToRate] = useState(null);

  // Los ítems en la papelera llegan con `item.id === null`: no se pintan.
  const wishlist = useMemo(() => {
    if (!response?.pages) return [];
    return response.pages.flatMap((page) => page.data).filter((vote) => vote?.item?.id);
  }, [response]);

  const { available, upcoming } = useMemo(() => splitWishlistByRelease(wishlist), [wishlist]);

  const handleOpenRate = (vote) => setVoteToRate(vote);
  const handleCloseRate = () => setVoteToRate(null);

  const handleRate = (score) => {
    if (!voteToRate) return;
    updateVote(
      { id: voteToRate.id, data: { type: 'vote', score } },
      { onSuccess: () => setVoteToRate(null) }
    );
  };

  // Sin modal: es reversible desde "Mis votos" → "No me interesan".
  const handleRemove = (vote) => {
    updateVote({ id: vote.id, data: { type: 'not_interested' } });
  };

  const removingId = isUpdating && variables?.data?.type === 'not_interested' ? variables.id : null;

  return {
    state: {
      available,
      upcoming,
      total: response?.pages[0]?.meta?.total ?? 0,
      isEmpty: !isLoading && wishlist.length === 0,
      showSkeletons: isLoading,
      isFetchingNextPage,
      hasNextPage,
      voteToRate,
      isUpdating,
      removingId,
    },
    actions: {
      handleOpenRate,
      handleCloseRate,
      handleRate,
      handleRemove,
    },
    refs: {
      lastElementRef,
    }
  };
};
