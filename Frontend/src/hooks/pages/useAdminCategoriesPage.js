import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useCategories } from '../categories/useCategoryQueries';
import { useAdminCategoryActions } from './../admin/useAdminActions';

/**
 * Hook para la página de administración de categorías.
 * Maneja la lógica de búsqueda, filtrado y acciones CRUD para categorías.
 *
 * @returns {object} Objeto con el estado y funciones necesarias para la página de administración de categorías.
 * - `searchInput`: Valor del input de búsqueda en tiempo real.
 * - `filteredCategories`: Lista de categorías filtrada según el término de búsqueda.
 * - `isLoading`: Indica si las categorías están siendo cargadas.
 * - `isError`: Indica si ocurrió un error al cargar las categorías.
 * - Funciones para manejar cambios en el input de búsqueda y acciones CRUD (abrir modales, ejecutar mutaciones).
 * */
export const useAdminCategoriesPage = () => {

  const { searchInput, debouncedSearch, handleSearchChange } = useFilters();

  const { data: categories, isLoading, isError } = useCategories();

  const modalActions = useAdminCategoryActions();

  const filteredCategories = useMemo(() => {
    if (!categories) return [];
    if (!debouncedSearch) return categories;

    const lowerSearch = debouncedSearch.toLowerCase();
    return categories.filter(cat =>
      cat.name.toLowerCase().includes(lowerSearch) ||
      (cat.description && cat.description.toLowerCase().includes(lowerSearch))
    );
  }, [categories, debouncedSearch]);

  return {
    state: {
      searchInput,
      filteredCategories,
      isLoading,
      isError,
      ...modalActions,
    },
    actions: {
      handleSearchChange,
      ...modalActions,
    }
  };
};