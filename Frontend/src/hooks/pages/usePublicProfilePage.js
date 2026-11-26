import { useNavigate, useParams } from 'react-router-dom';
import { useSessionStore, selectIsAdmin, selectIsVerified } from '../../store/useSessionStore.js';
import { usePublicProfile } from '../users/useUserQueries';

/**
 * Hook para la página del perfil público (/u/:username, F3-03).
 *
 * @returns {object} `state` con el perfil, sus secciones (`topByCategory`, `lists`, `reviews`), `isOwner`,
 * `canReport` e `isEmpty`, y `actions` con `handleGoHome`.
 */
export const usePublicProfilePage = () => {
  const { username } = useParams();
  const navigate = useNavigate();
  const { data: profile, isLoading, isError } = usePublicProfile(username);
  const isVerified = useSessionStore(selectIsVerified);
  const isAdmin = useSessionStore(selectIsAdmin);

  const topByCategory = profile?.top_by_category ?? [];
  const lists = profile?.lists ?? [];
  const reviews = profile?.reviews ?? [];

  const handleGoHome = () => navigate('/');

  return {
    state: {
      profile,
      isLoading,
      isError,
      isOwner: Boolean(profile?.is_owner),
      // "⋯ → Reportar" (F3-04/F3-03): solo usuarios verificados que no sean admin ni el dueño.
      canReport: isVerified && !isAdmin && !profile?.is_owner,
      topByCategory,
      lists,
      reviews,
      isEmpty: topByCategory.length === 0 && lists.length === 0 && reviews.length === 0,
    },
    actions: { handleGoHome },
  };
};
