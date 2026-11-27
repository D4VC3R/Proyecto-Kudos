// Estilos que comparten los controles de la cabecera, el menú de usuario, el menú móvil y el pie.
export const FOCUS_RING = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

// Botón cuadrado de solo icono.
export const ICON_BUTTON = `relative inline-grid h-10 w-10 place-items-center rounded-sm border border-transparent text-ink hover:border-line-strong ${FOCUS_RING}`;

// Elemento de un menú desplegable.
export const MENU_ITEM = `flex w-full items-center gap-2.5 rounded-sm p-2.5 text-left text-small text-ink hover:bg-line/60 ${FOCUS_RING}`;

// Enlace de navegación de la página actual.
export const NAV_ACTIVE = 'text-ink underline decoration-accent decoration-2 underline-offset-8';
