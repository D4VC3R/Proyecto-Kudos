import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useCategories } from '../categories/useCategoryQueries';
import { useAdminItems } from './../admin/useAdminQueries';
import { useAdminItemActions } from './../admin/useAdminActions';

/**
 * Hook para la página de administración de ítems, que maneja la lógica de filtros, búsqueda y acciones sobre los ítems.
 *
 * @returns {object} Un objeto con el estado y las acciones necesarias para la página de administración de ítems.
 * - `state`: Contiene el estado actual de la página, incluyendo la página actual, el input de búsqueda, los filtros aplicados, la respuesta de los ítems, el estado de carga y error, las opciones de categoría y las acciones del modal.
 * - `actions`: Contiene las funciones para manejar la paginación, el cambio en el input de búsqueda, el cambio en los filtros y las acciones del modal.
 * */
export const useAdminItemsPage = () => {
  const { page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange } =
    useFilters({ initialFilters: { status: '', category_id: '' } });

  const { data: categoriesData } = useCategories();

  const { data: itemsResponse, isLoading, isError } = useAdminItems({
    page,
    search: debouncedSearch,
    status: filters.status,
    category_id: filters.category_id,
    per_page: 9
  });

  const modalActions = useAdminItemActions();

  const categoryOptions = useMemo(() => {
    return categoriesData?.map(cat => ({ value: cat.id, label: cat.name })) || [];
  }, [categoriesData]);

  return {
    state: {
      page,
      searchInput,
      filters,
      itemsResponse,
      isLoading,
      isError,
      categoryOptions,
      ...modalActions,
    },
    actions: {
      setPage,
      handleSearchChange,
      handleFilterChange,
      ...modalActions,
    }
  };
};