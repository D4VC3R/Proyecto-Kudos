import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { COMMENT_KEYS } from './useCommentQueries';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ itemId, data }) => axiosClient.post(`/items/${itemId}/comments`, data),
    successMessage: 'Comentario creado',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({
        queryKey: ['comments', 'item', variables.itemId]
      });
    }
  });
};

export const useUpdateComment = () => {
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/comments/${id}`, data),
    invalidateKeys: [COMMENT_KEYS.all],
    successMessage: 'Comentario actualizado',
  });
};

export const useDeleteComment = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/comments/${id}`),
    invalidateKeys: [COMMENT_KEYS.all],
    successMessage: 'Comentario eliminado',
  });
};
