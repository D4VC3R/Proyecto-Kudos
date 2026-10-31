import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import toast from 'react-hot-toast';
import { VOTE_KEYS } from './useVoteQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
import { USER_KEYS } from '../users/useUserQueries';
import { HOME_KEYS } from '../home/useHomeQueries';
import { LIST_KEYS } from '../lists/useListQueries';
import { useSessionStore } from '../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';

// Cachés que cambian al votar (F2-04): la cola y las categorías, "Mis votos" y la wishlist (my-votes?type=want),
// las fichas, el ranking de usuarios, el perfil (incluye las estadísticas y el perfil mínimo), la portada y las listas (F2-09).
const VOTE_INVALIDATE_KEYS = [VOTE_KEYS.myVotesList(), ['categories'], ITEM_KEYS.all, USER_KEYS.ranking, USER_KEYS.profile, HOME_KEYS.all, LIST_KEYS.all];

// showSuccessToast = false lo usa el speed-rating del onboarding (F2-07), que vota de seguido sin avisos.
export const useCreateVote = ({ showSuccessToast = true } = {}) => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: (voteData) => axiosClient.post('/votes', voteData),
    invalidateKeys: VOTE_INVALIDATE_KEYS,
    onSuccessExtra: (response) => {
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

      if (showSuccessToast && ['vote', 'want'].includes(response.meta?.vote_type) && response.message) {
        toast.success(response.message);
      }
    }
  });
};

export const useUpdateVote = () => {
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/votes/${id}`, data),
    invalidateKeys: VOTE_INVALIDATE_KEYS,
    // El backend devuelve un mensaje distinto al valorar y al marcar "no me interesa" (F2-05).
    successMessage: (response) => response?.message || 'Voto actualizado',
  });
};

export const useDeleteVote = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/votes/${id}`),
    invalidateKeys: VOTE_INVALIDATE_KEYS,
    successMessage: 'Voto eliminado',
  });
};