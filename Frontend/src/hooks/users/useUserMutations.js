import axiosClient from './../../lib/axiosClient';
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