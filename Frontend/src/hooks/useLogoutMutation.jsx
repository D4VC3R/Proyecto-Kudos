import { useMutation } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { apiClient } from '../lib/apiClient';
import { useApiErrorHandler } from './useApiErrorHandler';
import { useSessionStore } from '../store/useSessionStore';

export const useLogoutMutation = () => {
  const { handleApiError } = useApiErrorHandler();
  const clearSession = useSessionStore((state) => state.clearSession);

  return useMutation({
    mutationFn: async () => {
      const response = await apiClient.post('/logout');
      return response.data;
    },
    onSuccess: (responseData) => {
      toast.success(responseData?.message ?? 'Sesion cerrada correctamente.');
    },
    onError: (error) => {
      handleApiError({ error, notify: (message) => toast.error(message) });
    },
    onSettled: () => {
      // Pase lo que pase (éxito o error de red), limpiamos la sesión en el cliente
      clearSession();
    },
  });
};