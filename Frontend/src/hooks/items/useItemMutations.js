import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { ITEM_KEYS } from './useItemQueries';




export const useCreateItemComment = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ itemId, content }) => axiosClient.post(`/items/${itemId}/comments`, { content }),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
      toast.success(response.message || 'Comentario publicado');
    },
    onError: (error) => toast.error(error.response?.data?.message || error.message || 'Error al publicar'),
  });
};

