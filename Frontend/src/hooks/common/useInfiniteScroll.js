import { useCallback, useRef } from 'react';

/**
 * Hook reutilizable para implementar Scroll Infinito basado en IntersectionObserver.
 *
 * @param {boolean} isFetchingNextPage - Estado de carga actual.
 * @param {boolean} hasNextPage - Indica si hay más páginas disponibles.
 * @param {function} fetchNextPage - Función para cargar la siguiente página.
 * @param {number} threshold - Umbral de intersección (por defecto 0.1).
 * @returns {function} Callback Ref para adjuntar al elemento centinela del final de la lista.
 */
export const useInfiniteScroll = ({ isFetchingNextPage, hasNextPage, fetchNextPage, threshold = 0.1 }) => {
  const observer = useRef(null);

  return useCallback(
    (node) => {
      if (isFetchingNextPage) return;

      if (observer.current) observer.current.disconnect();

      observer.current = new IntersectionObserver(
        (entries) => {
          if (entries[0].isIntersecting && hasNextPage) {
            fetchNextPage();
          }
        },
        { threshold }
      );

      if (node) observer.current.observe(node);
    },
    [isFetchingNextPage, hasNextPage, fetchNextPage, threshold]
  );
};
