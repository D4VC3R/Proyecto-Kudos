import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { useSessionStore } from '../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';

export const useLogin = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: (credentials) => axiosClient.post('/login', credentials),
    successMessage: 'Sesión iniciada correctamente',
    onSuccessExtra: (response) => {
      const { access_token, user } = response.data;
      setSession({ token: access_token, user });
      queryClient.clear();
    }
  });
};

export const useLogout = () => {
  const clearSession = useSessionStore((state) => state.clearSession);
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: () => axiosClient.post('/logout'),
    successMessage: 'Sesión cerrada',
    onSuccessExtra: () => {
        clearSession();
        queryClient.clear();
    }
  });
};

export const useRegister = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/register', data),
    successMessage: 'Cuenta creada. Revisa tu email para confirmar y empezar a jugar.',
    onSuccessExtra: () => {
      queryClient.clear();
    }
  });
};
