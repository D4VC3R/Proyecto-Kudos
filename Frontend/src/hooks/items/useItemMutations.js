import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { ITEM_KEYS } from './useItemQueries';
import {VOTE_KEYS} from "../votes/useVoteQueries.js";
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ itemId, content }) => axiosClient.post(`/items/${itemId}/comments`, { content }),
    successMessage: 'Comentario publicado',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
    }
  });
};

export const useUpdateComment = () => {
  return useBaseMutation({
    mutationFn: ({ id, content }) => axiosClient.put(`/comments/${id}`, { content }),
    invalidateKeys: [ITEM_KEYS.all][VOTE_KEYS.infiniteMyVotes()],
    successMessage: 'Comentario actualizado',
  });
};

export const useDeleteComment = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/comments/${id}`),
    invalidateKeys: [ITEM_KEYS.all],
    successMessage: 'Comentario eliminado',
  });
};

