import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import toast from 'react-hot-toast';
import { VOTE_KEYS } from './useVoteQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
import { USER_KEYS } from '../users/useUserQueries';
import { useSessionStore } from '../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateVote = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: (voteData) => axiosClient.post('/votes', voteData),
    invalidateKeys: [VOTE_KEYS.myVotesList(), ['categories'], ITEM_KEYS.all],
    onSuccessExtra: (response) => {
      // 1. Sincronizar Kudos en Zustand
      const tk = response.meta?.total_kudos;
      if (tk !== undefined) {
        const { token, user, setSession } = useSessionStore.getState();
        if (user) {
          setSession({ token, user: { ...user, total_kudos: tk } });
        }

        queryClient.setQueryData(USER_KEYS.minimalProfile, (old) => {
          if (!old) return old;
          return {
            ...old,
            data: {
              ...old.data,
              total_kudos: tk
            }
          };
        });
      }

      // Opcional: Mostrar toast solo si no fue un "skip" o si realmente gan puntos
      if (response.meta?.vote_type === 'vote' && response.message) {
        toast.success(response.message);
      }
    }
  });
};

export const useUpdateVote = () => {
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/votes/${id}`, data),
    invalidateKeys: [VOTE_KEYS.myVotesList(), ITEM_KEYS.all, ['categories']],
    successMessage: 'Voto actualizado',
  });
};

export const useDeleteVote = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/votes/${id}`),
    invalidateKeys: [VOTE_KEYS.myVotesList(), ITEM_KEYS.all, ['categories']],
    successMessage: 'Voto eliminado',
  });
};