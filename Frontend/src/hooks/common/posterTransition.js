// Máximo que se espera a que la ficha pinte su póster antes de cerrar la transición.
const POSTER_WAIT_MS = 800;

/** Vacía el nombre de transición de los pósters de las tarjetas: dos elementos con el mismo nombre anulan la transición. */
const clearCardPosterNames = () => {
  document.querySelectorAll('[data-card-poster]').forEach((el) => {
    el.style.viewTransitionName = '';
  });
};

/** Resuelve cuando aparece un póster de ficha distinto de `previous`, o al pasar `timeout` ms si no llega. */
const waitForNewPoster = (previous, timeout) => new Promise((resolve) => {
  const isReady = () => {
    const poster = document.querySelector('[data-detail-poster]');
    return !!poster && poster !== previous;
  };
  if (isReady()) {
    resolve();
    return;
  }

  // `setTimeout` y no `requestAnimationFrame`: mientras dura la actualización de una transición no se pinta.
  let timer;
  const observer = new MutationObserver(() => {
    if (!isReady()) return;
    clearTimeout(timer);
    observer.disconnect();
    resolve();
  });
  observer.observe(document.body, { childList: true, subtree: true });
  timer = setTimeout(() => {
    observer.disconnect();
    resolve();
  }, timeout);
});

/**
 * Clic en una tarjeta: el póster pulsado viaja hasta el de la ficha con una transición de vista. Solo el póster
 * pulsado lleva el nombre `poster`, y la ficha lo recibe como el mismo elemento. `BrowserRouter` aplaza la navegación
 * y `Link viewTransition` solo actúa con un router de datos, así que la transición se abre a mano; como la ficha
 * carga sus datos al llegar, la actualización espera a que pinte su póster (con un máximo) para que la captura de
 * "después" lo incluya. Con otro botón o teclas modificadoras, sin soporte o con menos movimiento, el enlace navega como siempre.
 */
export const startPosterTransition = (event, navigate, to) => {
  clearCardPosterNames();
  if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  event.currentTarget.querySelector('[data-card-poster]')?.style.setProperty('view-transition-name', 'poster');
  const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  if (!document.startViewTransition || reduceMotion) return; // el Link navega como siempre
  event.preventDefault();
  const previous = document.querySelector('[data-detail-poster]');
  previous?.style.setProperty('view-transition-name', 'none'); // desde Similares habría dos «poster»
  const root = document.documentElement;
  root.dataset.vt = 'screen';
  const transition = document.startViewTransition(async () => {
    navigate(to);
    await waitForNewPoster(previous, POSTER_WAIT_MS);
    clearCardPosterNames();
  });
  transition.ready.catch(() => {});
  transition.finished.finally(() => { delete root.dataset.vt; });
};
