import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { ITEM_KEYS } from './useItemQueries';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateItemComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ itemId, content }) => axiosClient.post(`/items/${itemId}/comments`, { content }),
    successMessage: 'Comentario publicado',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
    }
  });
};
