import {keepPreviousData, useQuery} from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
export const ADMIN_ITEM_KEYS = {
  all: ['admin-items'],
  lists: () => [...ADMIN_ITEM_KEYS.all, 'list'],
  list: (filters) => [...ADMIN_ITEM_KEYS.lists(), { filters }],
};
export const useAdminItems = (filters = {}) => {
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );

  return useQuery({
    queryKey: ADMIN_ITEM_KEYS.list(cleanFilters),
    queryFn: () => axiosClient.get('/admin/items', { params: cleanFilters }),
    placeholderData: keepPreviousData,
  });
};
