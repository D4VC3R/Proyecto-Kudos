import { flushSync } from 'react-dom';

// El círculo dura 0,5 s con la curva de salida de la transición del póster: responde nada más pulsar y frena al final.
// Con 0,6 s y una curva de entrada lenta, la parte visible acababa antes de tiempo y el arranque se notaba pesado.
const REVEAL_MS = 500;
const REVEAL_EASING = 'cubic-bezier(.2, .7, .2, 1)';

// Transición en curso: si una anterior se salta, su limpieza no debe quitar los atributos de la que está viva.
let active = null;

/** Centro del rectángulo de un elemento, en coordenadas de la ventana: el origen del círculo del cambio de tema. */
export const centerOf = (el) => {
  const rect = el.getBoundingClientRect();
  return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
};

/**
 * Envuelve un cambio de tema o de paleta en una transición de vista (`kind`: `'theme'` o `'palette'`; el CSS de
 * `index.css` anima cada una). Si el navegador no la admite o se pide menos movimiento, el cambio es instantáneo.
 * `origin` (`{ x, y }`) es el punto desde el que se abre el círculo del cambio de modo: se anima con la API de
 * animaciones web hasta la esquina más lejana de la ventana, sin pasar por variables CSS (una propiedad heredable
 * en la raíz recalcularía el estilo de todo el documento antes de la captura).
 */
export const runThemeTransition = (kind, update, origin) => {
  const root = document.documentElement;
  const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  if (!document.startViewTransition || reduceMotion) {
    update();
    return;
  }

  root.dataset.vt = kind; // solo afecta a los pseudoelementos y a la cabecera: no recalcula todo el documento
  const transition = document.startViewTransition(() => {
    // Sin transiciones CSS mientras se aplica el tema: la vista nueva sale ya con sus colores finales y no hay que
    // repintarla en cada fotograma.
    root.dataset.themeSwitch = '';
    // `flushSync` pinta en el acto el estado nuevo de React: si no, la captura de "después" se haría con la interfaz vieja.
    flushSync(update);
  });
  active = transition;

  if (origin) {
    const radius = Math.hypot(Math.max(origin.x, window.innerWidth - origin.x), Math.max(origin.y, window.innerHeight - origin.y));
    const at = `at ${origin.x}px ${origin.y}px`;
    transition.ready.then(() => {
      root.animate(
        { clipPath: [`circle(0px ${at})`, `circle(${Math.ceil(radius)}px ${at})`] },
        { duration: REVEAL_MS, easing: REVEAL_EASING, pseudoElement: '::view-transition-new(root)' },
      );
    }).catch(() => {});
  } else {
    transition.ready.catch(() => {});
  }

  transition.finished.finally(() => {
    if (active !== transition) return;
    active = null;
    delete root.dataset.vt;
    delete root.dataset.themeSwitch;
  });
};
