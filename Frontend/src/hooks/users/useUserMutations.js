import { useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import axiosClient from '../../core/axiosClient.js';
import { downloadJson } from '../../lib/download.js';
import { USER_KEYS } from './useUserQueries';
import { useSessionStore } from './../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';

// Acepta `{setError}` (el de react-hook-form) para marcar en el formulario los campos que el backend rechaza con un 422.
export const useUpdateProfile = ({ setError } = {}) => {
  const setSession = useSessionStore((state) => state.setSession);
  const token = useSessionStore((state) => state.token);

  return useBaseMutation({
    mutationFn: (data) => axiosClient.put('/profile', data),
    setError,
    invalidateKeys: [USER_KEYS.profile, ['users', 'public']],
    successMessage: 'Perfil actualizado correctamente',
    onSuccessExtra: (response) => {
      if (response.data) {
        const currentUser = useSessionStore.getState().user;
        setSession({ token, user: { ...currentUser, avatar: response.data.avatar } });
      }
    }
  });
};

// Guarda los intereses del onboarding (F2-07) sin toast y los copia a la sesión.
export const useUpdateInterests = () => {
  return useBaseMutation({
    mutationFn: (interests) => axiosClient.put('/profile', { interests }),
    invalidateKeys: [USER_KEYS.profile],
    onSuccessExtra: (response) => {
      const { token, user, setSession } = useSessionStore.getState();
      if (user) {
        setSession({ token, user: { ...user, interests: response.data?.interests ?? [] } });
      }
    },
  });
};

export const useClaimDailyReward = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const token = useSessionStore((state) => state.token);

  return useBaseMutation({
    mutationFn: () => axiosClient.post('/profile/daily-claim'),
    onSuccessExtra: (response) => {
      const data = response.data;
      const meta = response.meta;
      const currentUser = useSessionStore.getState().user;

      if (data?.status === 'claimed') {
        const newUser = {
          ...currentUser,
          total_kudos: data.kudos,
          login_streak_count: data.streak,
          last_login_streak_date: meta.server_date,
        };
        setSession({ token, user: newUser });
      } else if (data?.status === 'already_claimed') {
        const newUser = {
          ...currentUser,
          last_login_streak_date: meta.server_date,
        };
        setSession({ token, user: newUser });
      }
    }
  });
};

// Exportación de datos (F3-08): GET /profile/export y descarga del JSON.
export const useExportAccountData = () => {
  return useBaseMutation({
    mutationFn: () => axiosClient.get('/profile/export'),
    successMessage: 'Tus datos se han descargado.',
    onSuccessExtra: (response) => {
      downloadJson(response.data, `kudos-mis-datos-${new Date().toISOString().slice(0, 10)}.json`);
    },
  });
};

// Borrado de la cuenta (F3-08): DELETE /profile con la contraseña. Al terminar se sale de /profile y se cierra la sesión.
export const useDeleteAccount = () => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  return useBaseMutation({
    mutationFn: (password) => axiosClient.delete('/profile', { data: { password } }),
    successMessage: 'Tu cuenta se ha borrado.',
    onSuccessExtra: () => {
      // Primero se navega: si se limpiara antes la sesión, RequireAuth llevaría a /login. React Router aplica la
      // navegación en una transición, así que la sesión se limpia un instante después, ya fuera de /profile.
      navigate('/', { replace: true });
      setTimeout(() => {
        useSessionStore.getState().clearSession();
        queryClient.clear();
      }, 100);
    },
  });
};
