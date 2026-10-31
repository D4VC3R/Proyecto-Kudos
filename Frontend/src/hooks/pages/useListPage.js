import { useNavigate, useParams } from 'react-router-dom';
import { useList } from '../lists/useListQueries';
import { buildItemUrl } from '../../lib/search.js';

/**
 * Hook para la página pública de una lista (/listas/:slug, F2-09).
 *
 * @returns {object} `state` con la lista y sus entradas y `actions` con `handleItemClick` y `handleGoHome`.
 */
export const useListPage = () => {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { data: list, isLoading, isError } = useList(slug);

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
    },
    actions: { handleItemClick, handleGoHome },
  };
};
