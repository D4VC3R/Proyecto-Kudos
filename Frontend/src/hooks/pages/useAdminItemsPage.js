import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useCategories } from '../categories/useCategoryQueries';
import { useAdminItems } from './../admin/useAdminQueries';
import { useAdminItemActions } from './../admin/useAdminActions';

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