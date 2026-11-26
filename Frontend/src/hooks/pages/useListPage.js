import { useNavigate, useParams } from 'react-router-dom';
import { useSessionStore, selectIsAdmin, selectIsAuthenticated, selectIsVerified } from '../../store/useSessionStore.js';
import { useList } from '../lists/useListQueries';
import { useLikeList, useUnlikeList } from '../lists/useListMutations';

/**
 * Hook para la página pública de una lista (/listas/:slug, F2-09; "me gusta" y dueño, F3-01).
 *
 * @returns {object} `state` con la lista, sus entradas, `canReport` y los datos del "me gusta", y `actions`
 * con `handleGoHome` y `handleToggleLike`.
 */
export const useListPage = () => {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { data: list, isLoading, isError } = useList(slug);
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);
  const isAdmin = useSessionStore(selectIsAdmin);
  const { mutate: likeList, isPending: isLiking } = useLikeList();
  const { mutate: unlikeList, isPending: isUnliking } = useUnlikeList();

  const isOwner = Boolean(list?.can_edit);
  const liked = Boolean(list?.liked_by_me);

  const handleGoHome = () => navigate('/');

  // Sin sesión lleva a /login; sin verificar o siendo el dueño, el botón va deshabilitado.
  const handleToggleLike = () => {
    if (!isAuthenticated) {
      navigate('/login');
      return;
    }
    if (!isVerified || isOwner || !list) return;
    if (liked) unlikeList(list.id);
    else likeList(list.id);
  };

  return {
    state: {
      list,
      entries: list?.items ?? [],
      isLoading,
      isError,
      // "⋯ → Reportar" (F3-04): solo usuarios verificados que no sean admin ni el dueño de la lista.
      canReport: isVerified && !isAdmin && !isOwner,
      isOwner,
      liked,
      likesCount: list?.likes_count ?? 0,
      isLiking: isLiking || isUnliking,
      // Con sesión verificada y siendo otra persona se puede dar "me gusta"; sin sesión el botón lleva a /login.
      likeDisabled: isAuthenticated && (!isVerified || isOwner),
    },
    actions: { handleGoHome, handleToggleLike },
  };
};
