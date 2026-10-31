import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore } from '../../store/useSessionStore.js';

// Portada (F2-09). La clave lleva el usuario: "Para ti" y user_vote dependen de la sesión.
export const HOME_KEYS = {
  all: ['home'],
  feed: (viewerId) => ['home', viewerId ?? 'guest'],
};

export const useHome = () => {
  const viewerId = useSessionStore((state) => state.user?.id ?? null);
  return useQuery({
    queryKey: HOME_KEYS.feed(viewerId),
    queryFn: () => axiosClient.get('/home'),
    select: (response) => response.data,
    staleTime: 1000 * 60 * 5,
  });
};
