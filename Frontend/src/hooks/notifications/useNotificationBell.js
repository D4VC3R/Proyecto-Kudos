import { useCallback, useId, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSessionStore, selectIsVerified } from '../../store/useSessionStore.js';
import { formatUnreadCount } from '../../lib/notifications.js';
import { useDismiss } from '../common/useDismiss.js';
import { useNotifications } from './useNotificationQueries.js';
import { useMarkNotificationRead, useMarkAllNotificationsRead } from './useNotificationMutations.js';

// Lógica de la campana de la cabecera (F3-05): desplegable con las últimas notificaciones.
export const useNotificationBell = () => {
  const navigate = useNavigate();
  const isVerified = useSessionStore(selectIsVerified);
  const { data, isLoading, isError } = useNotifications({ enabled: isVerified });
  const markRead = useMarkNotificationRead();
  const markAll = useMarkAllNotificationsRead();

  const [isOpen, setIsOpen] = useState(false);
  const panelId = useId();
  const containerRef = useRef(null);
  const buttonRef = useRef(null);

  const notifications = data?.data ?? [];
  const unreadCount = data?.meta?.unread_count ?? 0;

  const toggle = useCallback(() => setIsOpen((open) => !open), []);
  const close = useCallback(() => setIsOpen(false), []);

  const openNotification = (notification) => {
    if (!notification.is_read) markRead.mutate(notification.id);
    if (notification.data?.url) navigate(notification.data.url);
    close();
  };

  const markAllAsRead = () => markAll.mutate();

  // Mientras está abierto: clic fuera cierra; Escape cierra y devuelve el foco a la campana.
  useDismiss({ isOpen, containerRef, onDismiss: close, returnFocusRef: buttonRef });

  return {
    state: {
      isOpen,
      notifications,
      unreadCount,
      badgeLabel: formatUnreadCount(unreadCount),
      isLoading,
      isError,
      isMarkingAll: markAll.isPending,
      panelId,
    },
    actions: { toggle, close, openNotification, markAllAsRead },
    refs: { containerRef, buttonRef },
  };
};
