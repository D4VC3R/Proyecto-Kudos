import { useNavigate } from 'react-router-dom';
import { useHome } from '../home/useHomeQueries';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore.js';
import { buildItemUrl } from '../../lib/search.js';

/**
 * Hook para la portada (F2-09): "Para ti" (o populares sin sesión), novedades, listas destacadas,
 * mejor valorados y accesos a las categorías.
 *
 * @returns {object} `state` con las secciones ya preparadas y `actions` con `handleItemClick`.
 */
export const useHomePage = () => {
  const navigate = useNavigate();
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const { data, isLoading, isError, isFetching } = useHome();

  const categories = data?.categories ?? [];
  const newReleases = data?.new_releases ?? [];
  const editorialLists = data?.editorial_lists ?? [];
  const topRated = data?.top_rated ?? [];

  // Una fila por grupo; con "because" se titula "Porque te gustó X".
  const forYouRows = (data?.for_you?.groups ?? [])
    .filter((group) => group.items?.length)
    .map((group, index) => ({
      key: group.because?.id ?? `popular-${index}`,
      label: group.because ? `Porque te gustó ${group.because.name}` : null,
      items: group.items,
    }));

  const isEmpty = !forYouRows.length && !newReleases.length && !editorialLists.length && !topRated.length;

  const handleItemClick = (item) => {
    const url = buildItemUrl(item);
    if (url) navigate(url);
  };

  return {
    state: {
      categories,
      newReleases,
      editorialLists,
      topRated,
      forYouTitle: isAuthenticated ? 'Para ti' : 'Populares',
      forYouRows,
      isLoading,
      isError,
      isBackgroundUpdating: isFetching && !isLoading,
      isEmpty,
    },
    actions: { handleItemClick },
  };
};
