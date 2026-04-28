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
      const { access_token, user } = response.data;

      setSession({ token: access_token, user });

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
      clearSession();
      queryClient.clear();
      toast.success('Sesión cerrada');
    }
  });
};

export const useRegister = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data) => axiosClient.post('/register', data),
    onSuccess: () => {
      queryClient.clear();
      toast.success('Cuenta creada. Revisa tu email para confirmar y empezar a jugar.');
    },
    onError: (error) => {
      const details = error.response?.data?.error?.details;
      if (details) {
        Object.values(details).forEach(err => toast.error(err[0]));
      } else {
        toast.error(error.message || 'Error al registrarte');
      }
    }
  });
};
