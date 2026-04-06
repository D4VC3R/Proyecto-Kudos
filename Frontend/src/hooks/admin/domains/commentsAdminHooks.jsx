import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes, useAdminMutation } from '../adminQueryBase';
import { useAdminPaginatedQuery } from '../useAdminPaginatedQuery';

export const useAdminCommentsQuery = ({ itemId, page, perPage }) => {
  return useAdminPaginatedQuery({
    queryKey: adminQueryKeys.comments({ itemId, page, perPage }),
    endpoint: `/items/${itemId}/comments`,
    enabled: Boolean(itemId),
    params: {
      page,
      per_page: perPage
    },
  });
};

export const useAdminHideCommentMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ commentId, reason }) => {
      const payload = reason ? { reason } : {};
      const response = await apiClient.patch(`/admin/comments/${commentId}/hide`, payload);
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