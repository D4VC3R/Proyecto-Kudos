import React from 'react';
import { Bell } from 'lucide-react';
import clsx from 'clsx';
import { FOCUS_RING, ICON_BUTTON } from './headerStyles.js';
import { formatDate } from '../../lib/formatters.js';
import { useNotificationBell } from '../../hooks/notifications/useNotificationBell.js';

// Campana de notificaciones de la cabecera (F3-05): contador de no leídas y desplegable con las últimas.
const NotificationBell = () => {
  const { state, actions, refs } = useNotificationBell();
  const { isOpen, notifications, unreadCount, badgeLabel, isLoading, isError, isMarkingAll, panelId } = state;

  const ariaLabel = unreadCount > 0 ? `Notificaciones, ${unreadCount} sin leer` : 'Notificaciones';
  const emptyMessageStyles = 'px-4 py-3 text-small text-ink-muted';

  return (
    <div ref={refs.containerRef} className="relative">
      <button
        ref={refs.buttonRef}
        type="button"
        onClick={actions.toggle}
        aria-label={ariaLabel}
        aria-expanded={isOpen}
        aria-controls={panelId}
        title="Notificaciones"
        className={ICON_BUTTON}
      >
        <Bell size={18} aria-hidden="true" />
        {badgeLabel && (
          <span
            aria-hidden="true"
            className="absolute -right-1 -top-1 h-5 min-w-5 rounded-full border-2 border-canvas bg-accent px-1 text-center text-caption font-bold leading-4 text-on-accent"
          >
            {badgeLabel}
          </span>
        )}
      </button>

      {isOpen && (
        <div
          id={panelId}
          className="fixed inset-x-4 top-16 z-50 mt-2 overflow-hidden rounded-md border border-line bg-surface shadow-raised sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:w-96"
        >
          <div className="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
            <h2 className="font-display text-h3 text-ink">Notificaciones</h2>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={actions.markAllAsRead}
                disabled={isMarkingAll}
                className={`rounded-sm text-small font-semibold text-accent hover:underline disabled:opacity-50 ${FOCUS_RING}`}
              >
                Marcar todas como leídas
              </button>
            )}
          </div>

          {isLoading && <p className={emptyMessageStyles}>Cargando…</p>}
          {isError && <p className={emptyMessageStyles}>No se han podido cargar las notificaciones.</p>}
          {!isLoading && !isError && notifications.length === 0 && (
            <p className={emptyMessageStyles}>No tienes notificaciones.</p>
          )}

          {notifications.length > 0 && (
            <ul className="max-h-96 overflow-y-auto divide-y divide-line">
              {notifications.map((notification) => (
                <li key={notification.id} className={clsx(!notification.is_read && 'bg-accent/10')}>
                  <button
                    type="button"
                    onClick={() => actions.openNotification(notification)}
                    className="block w-full px-4 py-3 text-left hover:bg-line/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset"
                  >
                    <div className="flex items-start gap-2">
                      {!notification.is_read && (
                        <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-accent">
                          <span className="sr-only">Sin leer</span>
                        </span>
                      )}
                      <div className="min-w-0">
                        <p className="text-small font-semibold text-ink">{notification.data?.title}</p>
                        <p className="text-small text-ink-muted">{notification.data?.message}</p>
                        <p className="text-caption text-ink-muted">{formatDate(notification.created_at)}</p>
                      </div>
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
