import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore } from '../../store/useSessionStore.js';

// Listas públicas (F2-09). La clave lleva el usuario: user_vote depende de la sesión.
export const LIST_KEYS = {
  all: ['lists'],
  detail: (slug, viewerId) => ['lists', slug, viewerId ?? 'guest'],
};

export const useList = (slug) => {
  const viewerId = useSessionStore((state) => state.user?.id ?? null);
  return useQuery({
    queryKey: LIST_KEYS.detail(slug, viewerId),
    queryFn: () => axiosClient.get(`/lists/${slug}`),
    select: (response) => response.data,
    enabled: !!slug,
    retry: false,
  });
};
