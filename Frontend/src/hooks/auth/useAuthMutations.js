import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore } from '../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';
/** Hooks personalizados para manejar las mutaciones relacionadas con la autenticación.
 *
 * Cada hook utiliza `useBaseMutation` para manejar la lógica común de las mutaciones, como mostrar mensajes de éxito y manejar errores.
 * Algunos hooks interactúan con el estado global de la sesión y el cliente de React Query para mantener los datos sincronizados.
 */

/**
 * useLogin: Maneja el proceso de inicio de sesión. Envía las credenciales al backend, actualiza el estado de la sesión con el token
 * y la información del usuario, y limpia el caché de consultas para reflejar el nuevo estado autenticado.
 * */
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

/** useLogout: Maneja el proceso de cierre de sesión.
 * Envía una solicitud al backend para cerrar la sesión, limpia el estado de la sesión y el caché de consultas.
 *
 */
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

/** useRegister: Maneja el proceso de registro de nuevos usuarios.
 * Al finalizar, limpia el caché de consultas para reflejar cualquier cambio relacionado con la autenticación.
 */
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

/** useForgotPassword: Maneja el proceso de solicitud de restablecimiento de contraseña.
 * Envía el correo electrónico al backend y muestra un mensaje genérico para evitar revelar si el correo existe o no.
 */
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