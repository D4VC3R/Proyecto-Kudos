import axiosClient from '../../core/axiosClient.js';
import { USER_KEYS } from './useUserQueries';
import { useSessionStore } from './../../store/useSessionStore';
import { useBaseMutation } from '../common/useBaseMutation';

export const useUpdateProfile = () => {
  const setSession = useSessionStore((state) => state.setSession);
  const token = useSessionStore((state) => state.token);

  return useBaseMutation({
    mutationFn: (data) => axiosClient.put('/profile', data),
    invalidateKeys: [USER_KEYS.profile],
    successMessage: 'Perfil actualizado correctamente',
    onSuccessExtra: (response) => {
      if (response.data) {
        const currentUser = useSessionStore.getState().user;
        setSession({ token, user: { ...currentUser, avatar: response.data.avatar } });
      }
    }
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
