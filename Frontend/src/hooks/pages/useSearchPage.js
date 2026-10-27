import { useMemo } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useInfiniteItems } from '../items/useItemQueries.js';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';
import { buildItemUrl, groupItemsByCategory, normalizeSearchTerm } from '../../lib/search.js';

/**
 * Hook personalizado para manejar la lógica de la página de resultados de búsqueda (`/buscar?q=…`).
 *
 * @return {object} Objeto con el estado, acciones y refs necesarios para la página.
 * - `state`: término buscado (`term`, `undefined` si no llega al mínimo), si había texto en la URL, carga, error,
 *   resultados agrupados por categoría (`groups`) y si se están cargando más.
 * - `actions`: clic en un ítem (navega a su ficha).
 * - `refs`: referencia necesaria para implementar el scroll infinito.
 * */
export const useSearchPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const rawQuery = searchParams.get('q') ?? '';
  const term = normalizeSearchTerm(rawQuery);

  const {
    data,
    isLoading,
    isError,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage
  } = useInfiniteItems(
    { search: term, per_page: 24 },
    { enabled: !!term }
  );

  const observerRef = useInfiniteScroll({ isFetchingNextPage, hasNextPage, fetchNextPage });

  const items = useMemo(() => data?.pages.flatMap((page) => page.data) || [], [data]);
  const groups = useMemo(() => groupItemsByCategory(items), [items]);

  const handleItemClick = (item) => {
    const url = buildItemUrl(item);
    if (url) navigate(url);
  };

  return {
    state: {
      term,
      hasQuery: rawQuery.trim() !== '',
      isLoading: !!term && isLoading && !data,
      isError,
      groups,
      isFetchingNextPage,
    },
    actions: {
      handleItemClick,
    },
    refs: {
      observerRef,
    }
  };
};
