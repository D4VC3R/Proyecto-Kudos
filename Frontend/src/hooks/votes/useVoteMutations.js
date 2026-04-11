import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { VOTE_KEYS } from './useVoteQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
// Necesitamos actualizar los puntos del usuario en vivo
import { useSessionStore } from '../../store/useSessionStore';

export const useCreateVote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (voteData) => axiosClient.post('/votes', voteData),
    onSuccess: (response) => {
      // 1. Sincronizar Kudos en Zustand (si el backend nos los manda en los metadatos)
      if (response.meta?.total_kudos !== undefined) {
        const { token, user, setSession } = useSessionStore.getState();
        if (user) {
          setSession({ token, user: { ...user, total_kudos: response.meta.total_kudos } });
        }
      }

      // 2. Invalidar cachés relacionadas para que la UI se repinte sola
      // Invalidamos mis votos
      queryClient.invalidateQueries({ queryKey: VOTE_KEYS.myVotesList() });

      // Invalidamos la cola del "next-item" para que pase al siguiente inmediatamente
      // (Usa la clave global de categorías para limpiar cualquier next-item guardado)
      queryClient.invalidateQueries({ queryKey: ['categories'] });

      // Invalidamos ítems (por si estamos viendo el detalle o el listado de este ítem)
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });

      // Opcional: Mostrar toast solo si no fue un "skip" o si realmente ganó puntos
      if (!response.meta?.idempotent_hit) {
        toast.success(response.message);
      }
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useUpdateVote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/votes/${id}`, data),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: VOTE_KEYS.myVotesList() });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all }); // Refrescar promedios
      queryClient.invalidateQueries({ queryKey: ['categories'] }); // Refrescar rankings
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useDeleteVote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => axiosClient.delete(`/votes/${id}`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: VOTE_KEYS.myVotesList() });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};