import axiosClient from '../../lib/axiosClient';
import { ITEM_KEYS } from '../items/useItemQueries.js';
import { useBaseMutation } from '../common/useBaseMutation';

export const useAdminHideComment = () => {
  return useBaseMutation({
    mutationFn: ({ id, reason }) => axiosClient.patch(`/admin/comments/${id}/hide`, { reason }),
    invalidateKeys: [ITEM_KEYS.all],
    successMessage: 'Comentario ocultado',
  });
};
export const useAdminUnhideComment = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.patch(`/admin/comments/${id}/unhide`),
    invalidateKeys: [ITEM_KEYS.all],
    successMessage: 'Comentario restaurado',
  });
};
