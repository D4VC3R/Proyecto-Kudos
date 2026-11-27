import { useEffect } from 'react';

// Cierre de un desplegable abierto: un clic fuera de `containerRef` lo cierra; Escape lo cierra y
// devuelve el foco a `returnFocusRef` (el botón que lo abre), salvo que un elemento interior ya lo haya atendido (`preventDefault`). `onDismiss` debe ser estable (`useCallback`),
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
      // Un Escape ya usado dentro (p. ej. el buscador cerrando sus resultados) no cierra también el desplegable.
      if (event.key === 'Escape' && !event.defaultPrevented) {
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
