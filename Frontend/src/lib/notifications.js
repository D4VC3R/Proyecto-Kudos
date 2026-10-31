// Notificaciones dentro de la app (F3-05).
export const NOTIFICATIONS_PER_PAGE = 10;           // las que caben en el desplegable de la campana
export const NOTIFICATIONS_REFETCH_MS = 60 * 1000;  // sondeo cada 60 s, solo con la pestaña visible

// Texto del contador: '' sin no leídas, '1'..'9' y '9+'.
export const formatUnreadCount = (count) => {
  if (!count || count < 1) return '';
  return count > 9 ? '9+' : String(count);
};
