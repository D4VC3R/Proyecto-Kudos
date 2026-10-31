import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useSessionStore } from '../../store/useSessionStore.js';
import { NOTIFICATIONS_PER_PAGE, NOTIFICATIONS_REFETCH_MS } from '../../lib/notifications.js';

// Notificaciones dentro de la app (F3-05). La clave lleva el usuario para no mezclar sesiones.
export const NOTIFICATION_KEYS = {
  all: ['notifications'],
  list: (userId) => ['notifications', 'list', userId ?? 'guest'],
};

// Sin websockets: sondeo con refetchInterval. React Query lo pausa con la pestaña oculta
// (refetchIntervalInBackground: false) y vuelve a pedir al recuperar el foco.
export const useNotifications = ({ enabled = true } = {}) => {
  const userId = useSessionStore((state) => state.user?.id ?? null);
  return useQuery({
    queryKey: NOTIFICATION_KEYS.list(userId),
    queryFn: () => axiosClient.get('/notifications', { params: { per_page: NOTIFICATIONS_PER_PAGE } }),
    enabled,
    refetchInterval: NOTIFICATIONS_REFETCH_MS,
    refetchIntervalInBackground: false,
    refetchOnWindowFocus: true,
    staleTime: 30 * 1000,
    retry: false,
  });
};
