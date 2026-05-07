import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { COMMENT_KEYS } from './useCommentQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ itemId, data }) => axiosClient.post(`/items/${itemId}/comments`, data),
    successMessage: 'Comentario creado',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({
        queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments']
      });
    }
  });
};

export const useUpdateComment = () => {
  return useBaseMutation({
    mutationFn: ({ id, content }) => axiosClient.put(`/comments/${id}`, { content }),
    invalidateKeys: [ITEM_KEYS.all, COMMENT_KEYS.all],
    successMessage: 'Comentario actualizado',
  });
};

export const useDeleteComment = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/comments/${id}`),
    invalidateKeys: [ITEM_KEYS.all, COMMENT_KEYS.all],
    successMessage: 'Comentario eliminado',
  });
};
