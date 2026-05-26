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
    onSettledExtra: () => {
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

export const useForgotPassword = () => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/forgot-password', data),
    successMessage: 'Si el correo existe, te hemos enviado un enlace.',
  });
};

export const useResetPassword = () => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/reset-password', data),
    successMessage: 'Contraseña actualizada correctamente.',
  });
};

export const useResendVerificationEmail = () => {
  return useBaseMutation({
    mutationFn: () => axiosClient.post('/email/verification-notification'),
    successMessage: 'Se ha reenviado el enlace de verificación a tu correo.',
  });
};