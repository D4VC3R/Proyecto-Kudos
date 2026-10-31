import { useNavigate, useParams } from 'react-router-dom';
import { useFilters } from '../common/useFilters';
import { useAdminItems, useAdminList } from '../admin/useAdminQueries';
import { useAdminAddListItem, useAdminRemoveListItem, useAdminReorderListItems } from '../admin/useAdminMutations';

/**
 * Hook para la gestión de los ítems de una lista editorial (F2-09): buscar y añadir, quitar y reordenar.
 *
 * @returns {object} `state` (lista, entradas, resultados de búsqueda) y `actions`.
 */
export const useAdminListDetailPage = () => {
  const { listId } = useParams();
  const navigate = useNavigate();

  const { data: list, isLoading, isError } = useAdminList(listId);
  const entries = list?.items ?? [];

  const { searchInput, debouncedSearch, handleSearchChange } = useFilters();
  const { data: searchResponse, isFetching: isSearching } = useAdminItems(
    { search: debouncedSearch, status: 'active', per_page: 8 },
    { enabled: Boolean(debouncedSearch) },
  );
  const searchResults = debouncedSearch ? (searchResponse?.data ?? []) : [];

  const { mutate: addItem, isPending: isAdding } = useAdminAddListItem();
  const { mutate: removeItem, isPending: isRemoving } = useAdminRemoveListItem();
  const { mutate: reorderItems, isPending: isReordering } = useAdminReorderListItems();

  const isInList = (itemId) => entries.some((entry) => entry.item.id === itemId);

  const handleAdd = (item) => addItem({ listId, itemId: item.id });

  const handleRemove = (itemId) => removeItem({ listId, itemId });

  // direction: -1 sube, +1 baja. Se envían todos los ids en el nuevo orden.
  const handleMove = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= entries.length) return;

    const ids = entries.map((entry) => entry.item.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];
    reorderItems({ listId, itemIds: ids });
  };

  const handleGoBack = () => navigate('/admin/lists');

  return {
    state: {
      list,
      entries,
      isLoading,
      isError,
      searchInput,
      searchResults,
      hasSearch: Boolean(debouncedSearch),
      isSearching,
      isBusy: isAdding || isRemoving || isReordering,
    },
    actions: {
      handleSearchChange,
      handleAdd,
      handleRemove,
      handleMove,
      handleGoBack,
      isInList,
    },
  };
};
