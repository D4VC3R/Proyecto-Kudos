import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes } from '../adminQueryKeys';
import { useAdminMutation } from '../useAdminMutation';
import { useAdminQuery } from '../useAdminQuery';

export const adminCommentsQueryKey = adminQueryKeys.comments;

const normalizeCommentsData = (responseData) => {
  const rawData = responseData?.data;

  if (Array.isArray(rawData)) {
    return rawData;
  }

  if (rawData && Array.isArray(rawData.data)) {
    return rawData.data;
  }

  return [];
};

export const useAdminCommentsQuery = ({ itemId, page, perPage }) => {
  return useAdminQuery({
    queryKey: adminCommentsQueryKey({ itemId, page, perPage }),
    enabled: Boolean(itemId),
    queryFn: async () => {
      const response = await apiClient.get(`/items/${itemId}/comments`, {
        params: {
          page,
          per_page: perPage,
        },
      });

      const responseData = response.data;

      return {
        comments: normalizeCommentsData(responseData),
        meta: responseData?.meta ?? null,
      };
    },
  });
};

export const useAdminHideCommentMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ commentId, reason }) => {
      const payload = {};
      if (reason) {
        payload.reason = reason;
      }

      const response = await apiClient.patch(`/admin/comments/${commentId}/hide`, payload, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      return response.data;
    },
    defaultSuccessMessage: 'Comentario ocultado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.comments],
  });
};

export const useAdminUnhideCommentMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ commentId }) => {
      const response = await apiClient.patch(`/admin/comments/${commentId}/unhide`);
      return response.data;
    },
    defaultSuccessMessage: 'Comentario restaurado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.comments],
  });
};

