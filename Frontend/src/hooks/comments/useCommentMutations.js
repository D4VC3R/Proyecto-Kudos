import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { COMMENT_KEYS } from './useCommentQueries';

export const useCreateComment = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ itemId, data }) => axiosClient.post(`/items/${itemId}/comments`, data),
    onSuccess: (response, variables) => {
      // Invalidamos solo los comentarios del ítem afectado
      queryClient.invalidateQueries({
        queryKey: ['comments', 'item', variables.itemId]
      });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useUpdateComment = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/comments/${id}`, data),
    onSuccess: (response) => {
      // Invalidamos toda la rama de comentarios de forma segura
      queryClient.invalidateQueries({ queryKey: COMMENT_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useDeleteComment = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => axiosClient.delete(`/comments/${id}`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: COMMENT_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};


