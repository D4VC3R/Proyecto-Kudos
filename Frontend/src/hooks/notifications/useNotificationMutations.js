import axiosClient from '../../core/axiosClient.js';
import { useBaseMutation } from '../common/useBaseMutation.js';
import { NOTIFICATION_KEYS } from './useNotificationQueries.js';

// Sin toast de éxito: el contador y el estado de la lista ya muestran el cambio.
export const useMarkNotificationRead = () =>
  useBaseMutation({
    mutationFn: (id) => axiosClient.post(`/notifications/${id}/read`),
    invalidateKeys: [NOTIFICATION_KEYS.all],
  });

export const useMarkAllNotificationsRead = () =>
  useBaseMutation({
    mutationFn: () => axiosClient.post('/notifications/read-all'),
    invalidateKeys: [NOTIFICATION_KEYS.all],
  });
