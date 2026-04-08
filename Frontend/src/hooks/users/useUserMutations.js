import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from './../../lib/axiosClient';
import toast from 'react-hot-toast';
import { USER_KEYS } from './useUserQueries';
import { useSessionStore } from './../../store/useSessionStore';

export const useUpdateProfile = () => {
  const queryClient = useQueryClient();
  const setSession = useSessionStore((state) => state.setSession);
  const token = useSessionStore((state) => state.token);

  return useMutation({
    mutationFn: (data) => axiosClient.put('/profile', data),
    onSuccess: (response) => {
      // 1. Actualizamos la caché de React Query
      queryClient.invalidateQueries({ queryKey: USER_KEYS.profile });

      // 2. Sincronizamos Zustand para que la barra de navegación (nombre/avatar) se actualice al instante
      if (response.data) {
        setSession({ token, user: response.data });
      }

      toast.success(response.message || 'Perfil actualizado correctamente');
    },
    onError: (error) => toast.error(error.message),
  });
};