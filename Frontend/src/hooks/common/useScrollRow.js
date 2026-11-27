import { useCallback, useEffect, useRef, useState } from 'react';

// Margen de tolerancia, en píxeles, para dar por hecho que la fila está en un extremo (los decimales del zoom).
const EDGE_TOLERANCE = 2;

/**
 * Estado y control de una fila horizontal desplazable: si está al principio o al final y cuánto avanzar por página.
 * Sirve para activar o desactivar las flechas de la fila.
 *
 * @param {number} itemCount Número de elementos de la fila; al cambiar se vuelve a medir.
 * @returns {{ ref: object, atStart: boolean, atEnd: boolean, scrollByPage: (dir: number) => void }}
 */
export const useScrollRow = (itemCount) => {
  const ref = useRef(null);
  const [edges, setEdges] = useState({ atStart: true, atEnd: true });

  const update = useCallback(() => {
    const el = ref.current;
    if (!el) return;
    const atStart = el.scrollLeft <= EDGE_TOLERANCE;
    const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - EDGE_TOLERANCE;
    setEdges((prev) => (prev.atStart === atStart && prev.atEnd === atEnd ? prev : { atStart, atEnd }));
  }, []);

  useEffect(() => {
    const el = ref.current;
    if (!el) return undefined;

    update();
    el.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    const observer = new ResizeObserver(update);
    observer.observe(el);

    return () => {
      el.removeEventListener('scroll', update);
      window.removeEventListener('resize', update);
      observer.disconnect();
    };
  }, [update, itemCount]);

  const scrollByPage = useCallback((dir) => {
    const el = ref.current;
    if (!el) return;
    // El CSS global de `prefers-reduced-motion` no frena un `smooth` pedido por código.
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.scrollBy({ left: dir * el.clientWidth * 0.9, behavior: reduce ? 'auto' : 'smooth' });
  }, []);

  return { ref, atStart: edges.atStart, atEnd: edges.atEnd, scrollByPage };
};
