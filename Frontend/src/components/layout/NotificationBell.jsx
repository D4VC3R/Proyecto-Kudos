import React from 'react';
import { Bell } from 'lucide-react';
import clsx from 'clsx';
import { VARIANTS, SIZES, RADII } from '../../lib/buttonStyles.js';
import { formatDate } from '../../lib/formatters.js';
import { useNotificationBell } from '../../hooks/notifications/useNotificationBell.js';

// Campana de notificaciones de la cabecera (F3-05): contador de no leídas y desplegable con las últimas.
const NotificationBell = () => {
  const { state, actions, refs } = useNotificationBell();
  const { isOpen, notifications, unreadCount, badgeLabel, isLoading, isError, isMarkingAll, panelId } = state;

  const ariaLabel = unreadCount > 0 ? `Notificaciones, ${unreadCount} sin leer` : 'Notificaciones';
  const emptyMessageStyles = 'px-4 py-3 text-sm font-medium text-text-normal';

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
        className={clsx(
          'relative inline-flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200',
          VARIANTS.ghost.neutral,
          SIZES.iconMd,
          RADII.full,
        )}
      >
        <Bell size={18} aria-hidden="true" />
        {badgeLabel && (
          <span
            aria-hidden="true"
            className="absolute -right-1 -top-1 min-w-5 h-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-black leading-5 text-white"
          >
            {badgeLabel}
          </span>
        )}
      </button>

      {isOpen && (
        <div
          id={panelId}
          className="fixed inset-x-4 top-20 z-50 mt-2 overflow-hidden rounded-2xl bg-surface shadow-xl ring-1 ring-border sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:w-96"
        >
          <div className="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
            <h2 className="font-black text-text-highlight">Notificaciones</h2>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={actions.markAllAsRead}
                disabled={isMarkingAll}
                className="text-sm font-bold text-primary hover:underline disabled:opacity-50"
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
            <ul className="max-h-96 overflow-y-auto divide-y divide-border">
              {notifications.map((notification) => (
                <li key={notification.id} className={clsx(!notification.is_read && 'bg-blue-50')}>
                  <button
                    type="button"
                    onClick={() => actions.openNotification(notification)}
                    className="block w-full px-4 py-3 text-left hover:bg-slate-50 focus-visible:bg-slate-50 focus-visible:outline-none"
                  >
                    <div className="flex items-start gap-2">
                      {!notification.is_read && (
                        <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary">
                          <span className="sr-only">Sin leer</span>
                        </span>
                      )}
                      <div className="min-w-0">
                        <p className="text-sm font-bold text-text-highlight">{notification.data?.title}</p>
                        <p className="text-sm text-text-normal">{notification.data?.message}</p>
                        <p className="text-xs font-medium text-text-normal">{formatDate(notification.created_at)}</p>
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
