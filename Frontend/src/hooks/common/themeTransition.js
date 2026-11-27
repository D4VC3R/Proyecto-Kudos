import { flushSync } from 'react-dom';

/** Centro del rectángulo de un elemento, en coordenadas de la ventana: el origen del círculo del cambio de tema. */
export const centerOf = (el) => {
  const rect = el.getBoundingClientRect();
  return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
};

/**
 * Envuelve un cambio de tema o de paleta en una transición de vista (`kind`: `'theme'` o `'palette'`; el CSS de
 * `index.css` anima cada una). Si el navegador no la admite o se pide menos movimiento, el cambio es instantáneo.
 * `origin` (`{ x, y }`) es el punto desde el que se abre el círculo del cambio de modo.
 */
export const runThemeTransition = (kind, update, origin) => {
  const root = document.documentElement;
  const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  if (!document.startViewTransition || reduceMotion) {
    update();
    return;
  }

  root.dataset.vt = kind;
  if (origin) {
    root.style.setProperty('--vt-x', `${origin.x}px`);
    root.style.setProperty('--vt-y', `${origin.y}px`);
  }
  // `flushSync` pinta en el acto el estado nuevo de React: si no, la captura de "después" se haría con la interfaz vieja.
  document.startViewTransition(() => flushSync(update)).finished.finally(() => {
    delete root.dataset.vt;
  });
};
