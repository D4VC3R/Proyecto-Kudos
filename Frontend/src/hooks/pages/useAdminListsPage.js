import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useAdminLists } from '../admin/useAdminQueries';
import { useAdminListActions } from '../admin/useAdminActions';

/**
 * Hook para la página de administración de listas editoriales (F2-09).
 * Búsqueda local por título y acciones de crear, editar y borrar.
 *
 * @returns {object} `state` (búsqueda, listas filtradas, carga y modal) y `actions`.
 */
export const useAdminListsPage = () => {
  const { searchInput, debouncedSearch, handleSearchChange } = useFilters();
  const { data: lists, isLoading, isError } = useAdminLists();
  const modalActions = useAdminListActions();

  const filteredLists = useMemo(() => {
    if (!lists) return [];
    if (!debouncedSearch) return lists;

    const lowerSearch = debouncedSearch.toLowerCase();
    return lists.filter((list) => list.title.toLowerCase().includes(lowerSearch));
  }, [lists, debouncedSearch]);

  return {
    state: {
      searchInput,
      filteredLists,
      isLoading,
      isError,
      ...modalActions,
    },
    actions: {
      handleSearchChange,
      ...modalActions,
    },
  };
};
