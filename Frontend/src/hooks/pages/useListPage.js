import { useNavigate, useParams } from 'react-router-dom';
import { useSessionStore, selectIsAdmin, selectIsVerified } from '../../store/useSessionStore.js';
import { useList } from '../lists/useListQueries';
import { buildItemUrl } from '../../lib/search.js';

/**
 * Hook para la página pública de una lista (/listas/:slug, F2-09).
 *
 * @returns {object} `state` con la lista, sus entradas y `canReport`, y `actions` con `handleItemClick` y `handleGoHome`.
 */
export const useListPage = () => {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { data: list, isLoading, isError } = useList(slug);
  const isVerified = useSessionStore(selectIsVerified);
  const isAdmin = useSessionStore(selectIsAdmin);

  const handleItemClick = (item) => {
    const url = buildItemUrl(item);
    if (url) navigate(url);
  };

  const handleGoHome = () => navigate('/');

  return {
    state: {
      list,
      entries: list?.items ?? [],
      isLoading,
      isError,
      // "⋯ → Reportar" (F3-04): solo usuarios verificados que no sean admin.
      canReport: isVerified && !isAdmin,
    },
    actions: { handleItemClick, handleGoHome },
  };
};
