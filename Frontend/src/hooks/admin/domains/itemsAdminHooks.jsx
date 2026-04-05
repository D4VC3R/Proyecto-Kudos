import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes } from '../adminQueryKeys';
import { useAdminMutation } from '../useAdminMutation';
import { useAdminQuery } from '../useAdminQuery';

const normalizeItemsData = (responseData) => {
  const rawData = responseData?.data;

  if (Array.isArray(rawData)) {
    return rawData;
  }

  if (rawData && Array.isArray(rawData.data)) {
    return rawData.data;
  }

  return [];
};

export const adminItemsQueryKey = adminQueryKeys.items;

export const useAdminItemsQuery = ({ page, search, status, sortBy, sortDirection, perPage }) => {
  return useAdminQuery({
    queryKey: adminItemsQueryKey({ page, search, status, sortBy, sortDirection, perPage }),
    queryFn: async () => {
      const params = {
        page,
        per_page: perPage,
        sort_by: sortBy,
        sort_direction: sortDirection,
      };

      if (search) {
        params.search = search;
      }

      if (status) {
        params.status = status;
      }

      const response = await apiClient.get('/admin/items', { params });
      const responseData = response.data;

      return {
        items: normalizeItemsData(responseData),
        meta: responseData?.meta ?? null,
      };
    },
    placeholderData: (previousData) => previousData,
  });
};

export const useAdminModerateItemMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ itemId, status, reason }) => {
      const payload = {
        status,
      };

      if (reason) {
        payload.reason = reason;
      }

      const response = await apiClient.patch(`/admin/items/${itemId}/moderate`, payload, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      return response.data;
    },
    defaultSuccessMessage: 'Estado del item actualizado por administracion.',
    invalidateQueryKeys: [adminQueryScopes.items],
  });
};

