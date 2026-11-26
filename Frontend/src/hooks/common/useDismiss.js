import { useEffect } from 'react';

// Cierre de un desplegable abierto: un clic fuera de `containerRef` lo cierra; Escape lo cierra y
// devuelve el foco a `returnFocusRef` (el botón que lo abre). `onDismiss` debe ser estable (`useCallback`),
// o el efecto volvería a suscribirse en cada render.
export const useDismiss = ({ isOpen, containerRef, onDismiss, returnFocusRef }) => {
  useEffect(() => {
    if (!isOpen) return undefined;

    const handleMouseDown = (event) => {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        onDismiss();
      }
    };
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        onDismiss();
        returnFocusRef?.current?.focus();
      }
    };

    document.addEventListener('mousedown', handleMouseDown);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleMouseDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, containerRef, onDismiss, returnFocusRef]);
};
