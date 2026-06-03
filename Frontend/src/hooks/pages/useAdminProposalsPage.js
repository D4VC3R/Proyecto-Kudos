import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useCategories } from '../categories/useCategoryQueries';
import { useAdminProposals } from './../admin/useAdminQueries';
import { useAdminProposalActions } from './../admin/useAdminActions';

/**
 * Maneja la lógica de filtros, búsqueda, paginación y acciones sobre propuestas.
 *
 * @returns Un objeto con el estado y acciones necesarias para la página de administración de propuestas.
 * - `state`: Contiene el estado actual de la página, incluyendo filtros, resultados y opciones de categoría.
 * - `actions`: Contiene funciones para actualizar el estado, manejar cambios en los filtros y ejecutar acciones sobre las propuestas.
 * */
export const useAdminProposalsPage = () => {
  const { page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange } =
    useFilters({ initialFilters: { status: 'pending', category_id: '' } });

  const { data: categoriesData } = useCategories();

  const { data: proposalsResponse, isLoading, isError } = useAdminProposals({
    page,
    search: debouncedSearch,
    status: filters.status,
    category_id: filters.category_id,
    per_page: 9
  });

  const modalActions = useAdminProposalActions();

  const categoryOptions = useMemo(() => {
    return categoriesData?.map(cat => ({ value: cat.id, label: cat.name })) || [];
  }, [categoriesData]);

  return {
    state: {
      page,
      searchInput,
      filters,
      proposalsResponse,
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