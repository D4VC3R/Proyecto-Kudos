import { useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useFilters } from '../common/useFilters.js';
import { useInfiniteItems } from '../items/useItemQueries.js';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';

/**
 * Hook personalizado para manejar la lógica de la página de exploración.
 *
 * @return {object} Objeto con el estado, acciones y refs necesarios para la página de exploración.
 * - `state`: Contiene el estado actual de la página, incluyendo el slug de la categoría, input de búsqueda, ordenamiento, estado de carga y error, items a mostrar, nombre de la categoría para mostrar y si se están cargando más items.
 * - `actions`: Funciones para manejar cambios en el input de búsqueda, ordenamiento y clics en los items.
 * - `refs`: Referencias necesarias para implementar el scroll infinito.
 * */
export const useExplorePage = () => {
  const { categorySlug } = useParams();
  const navigate = useNavigate();

  const {
    searchInput,
    debouncedSearch,
    handleSearchChange,
    sortValue,
    handleSortChange,
    sortBy,
    sortOrder,
  } = useFilters({ initialSort: 'vote_avg|desc' });

  const {
    data,
    isLoading,
    isError,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage
  } = useInfiniteItems(
    {
      category_slug: categorySlug,
      search: debouncedSearch,
      sort_by: sortBy,
      sort_order: sortOrder,
      per_page: 20
    },
    { enabled: !!categorySlug }
  );

  const observerRef = useInfiniteScroll({ isFetchingNextPage, hasNextPage, fetchNextPage });

  const flattenedItems = useMemo(() => {
    return data?.pages.flatMap((page) => page.data) || [];
  }, [data]);

  const displayCategoryName = useMemo(() => {
    if (flattenedItems.length > 0 && flattenedItems[0].category) {
      return flattenedItems[0].category.name;
    }
    return categorySlug ? categorySlug.charAt(0).toUpperCase() + categorySlug.slice(1).replace(/-/g, ' ') : '';
  }, [flattenedItems, categorySlug]);

  const handleItemClick = (item) => {
    navigate(`/${categorySlug}/item/${item.id}`);
  };

  return {
    state: {
      categorySlug,
      searchInput,
      sortValue,
      isLoading: isLoading && !data,
      isError,
      flattenedItems,
      displayCategoryName,
      isFetchingNextPage,
    },
    actions: {
      handleSearchChange,
      handleSortChange,
      handleItemClick,
    },
    refs: {
      observerRef,
    }
  };
};