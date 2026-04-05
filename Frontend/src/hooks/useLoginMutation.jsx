import { useMutation } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { apiClient } from '../lib/apiClient';
import { useApiErrorHandler } from './useApiErrorHandler';
import { useSessionStore } from '../store/useSessionStore';

const extractLoginPayload = (responseData) => {
  const data = responseData?.data ?? {};

  const token = data.access_token ?? responseData?.access_token ?? data.token ?? responseData?.token ?? null;
  const user = data.user ?? responseData?.user ?? null;
  const message = responseData?.message ?? 'Sesion iniciada correctamente.';

  if (!token || !user) {
    throw new Error('La respuesta de login no incluye token o usuario.');
  }

  return { token, user, message };
};

export const useLoginMutation = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const { handleApiError } = useApiErrorHandler();

  return useMutation({
    mutationFn: async (payload) => {
      const response = await apiClient.post('/login', payload, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      return extractLoginPayload(response.data);
    },
    onSuccess: ({ token, user, message }) => {
      setSession({ token, user });
      toast.success(message);
    },
    onError: (error) => {
      handleApiError({ error, notify: (message) => toast.error(message) });
    },
  });
};
