import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore } from '../../store/useSessionStore.js';

export const USER_KEYS = {
  profile: ['profile'],
  minimalProfile: ['profile', 'minimal'],
  ranking: ['users', 'ranking'],
  statistics: ['profile', 'statistics'],
  publicProfile: (username, viewerId) => ['users', 'public', username, viewerId ?? 'guest'],
};

export const useProfile = () => {
  return useQuery({
    queryKey: USER_KEYS.profile,
    queryFn: () => axiosClient.get('/profile'),
    select: (response) => response.data,
    staleTime: 1000 * 60 * 15,
  });
};

export const useMinimalProfile = () => {
  return useQuery({
    queryKey: USER_KEYS.minimalProfile,
    queryFn: () => axiosClient.get('/profile/minimal'),
    select: (response) => response.data,
    staleTime: 1000 * 60 * 5, // 5 minutos, ya que esta info cambia menos frecuentemente
  });
};

export const useProfileStatistics = () => {
  return useQuery({
    queryKey: USER_KEYS.statistics,
    queryFn: () => axiosClient.get('/profile/statistics'),
    select: (response) => response.data,
    staleTime: 1000 * 60 * 5,
  });
};

export const useUserRanking = (page = 1) => {
  return useQuery({
    queryKey: [...USER_KEYS.ranking, page],
    queryFn: () => axiosClient.get('/users/ranking', { params: { page } }),
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 5,
  });
};

// Perfil público (F3-03). La clave lleva el usuario: is_owner y user_vote dependen de la sesión. 404 = privado o inexistente.
export const usePublicProfile = (username) => {
  const viewerId = useSessionStore((state) => state.user?.id ?? null);
  return useQuery({
    queryKey: USER_KEYS.publicProfile(username, viewerId),
    queryFn: () => axiosClient.get(`/users/${encodeURIComponent(username)}`),
    select: (response) => response.data,
    enabled: !!username,
    retry: false,
  });
};
