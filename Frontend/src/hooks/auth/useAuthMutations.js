import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { useSessionStore } from '../../store/useSessionStore';
import toast from 'react-hot-toast';

export const useLogin = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (credentials) => axiosClient.post('/login', credentials),
    onSuccess: (response) => {
      // Según tu AuthenticatedSessionController, la data viene estructurada así:
      const { access_token, user } = response.data;

      setSession({ token: access_token, user });

      // Limpiamos cualquier caché previa de React Query por seguridad
      queryClient.clear();
      toast.success('Sesión iniciada correctamente');
    },
    onError: (error) => {
      toast.error(error.message);
    }
  });
};

export const useLogout = () => {
  const clearSession = useSessionStore((state) => state.clearSession);
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => axiosClient.post('/logout'),
    onSettled: () => {
      // Se ejecuta sin importar si hubo error o éxito en el servidor
      clearSession();
      queryClient.clear();
      toast.success('Sesión cerrada');
    }
  });
};

export const useRegister = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data) => axiosClient.post('/register', data),
    onSuccess: (response) => {
      const { access_token, user } = response.data;
      
      setSession({ token: access_token, user });

      queryClient.clear();
      toast.success('Cuenta creada correctamente');
    },
    onError: (error) => {
      // Check if we get a specific error message, otherwise use generic
      const details = error.response?.data?.error?.details;
      if (details) {
        Object.values(details).forEach(err => toast.error(err[0]));
      } else {
        toast.error(error.message || 'Error al registrarte');
      }
    }
  });
};
