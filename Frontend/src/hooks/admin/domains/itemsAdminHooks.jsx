import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes, useAdminMutation } from '../adminQueryBase';
import { useAdminPaginatedQuery } from '../useAdminPaginatedQuery';

export const useAdminItemsQuery = (params) => {
  return useAdminPaginatedQuery({
    queryKey: adminQueryKeys.items(params),
    endpoint: '/admin/items',
    params: {
      page: params.page,
      per_page: params.perPage,
      sort_by: params.sortBy,
      sort_direction: params.sortDirection,
      search: params.search,
      status: params.status,
    },
  });
};

export const useAdminModerateItemMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ itemId, status, reason }) => {
      const payload = reason ? { status, reason } : { status };
      const response = await apiClient.patch(`/admin/items/${itemId}/moderate`, payload);
      return response.data;
    },
    defaultSuccessMessage: 'Estado del item actualizado por administracion.',
    invalidateQueryKeys: [adminQueryScopes.items],
  });
};