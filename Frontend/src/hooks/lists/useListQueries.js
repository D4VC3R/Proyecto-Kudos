import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore, selectIsAuthenticated } from '../../store/useSessionStore.js';

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

// Listas propias (F3-01). Raíz distinta de LIST_KEYS: un slug como "mine" chocaría con ['lists', slug].
export const MY_LIST_KEYS = {
  all: ['my-lists'],
  index: (viewerId, itemId) => ['my-lists', viewerId ?? 'guest', 'index', itemId ?? 'all'],
  detail: (viewerId, id) => ['my-lists', viewerId ?? 'guest', 'detail', id],
};

// Devuelve la respuesta completa ({ data, meta }): meta trae total, max_lists y max_items.
export const useMyLists = ({ itemId = null, enabled = true } = {}) => {
  const viewerId = useSessionStore((state) => state.user?.id ?? null);
  const hasSession = useSessionStore(selectIsAuthenticated);
  return useQuery({
    queryKey: MY_LIST_KEYS.index(viewerId, itemId),
    queryFn: () => axiosClient.get('/profile/lists', { params: itemId ? { item_id: itemId } : {} }),
    enabled: enabled && hasSession,
  });
};

export const useMyList = (id) => {
  const viewerId = useSessionStore((state) => state.user?.id ?? null);
  const hasSession = useSessionStore(selectIsAuthenticated);
  return useQuery({
    queryKey: MY_LIST_KEYS.detail(viewerId, id),
    queryFn: () => axiosClient.get(`/profile/lists/${id}`),
    select: (response) => response.data,
    enabled: !!id && hasSession,
    retry: false,
  });
};
