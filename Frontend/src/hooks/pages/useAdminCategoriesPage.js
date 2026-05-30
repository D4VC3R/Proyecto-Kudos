import { useMemo } from 'react';
import { useFilters } from '../common/useFilters';
import { useCategories } from '../categories/useCategoryQueries';
import { useAdminCategoryActions } from './../admin/useAdminActions';

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