import { useEffect, useRef } from 'react';

const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

// Foco de un diálogo modal: al abrirlo, si el contenido no ha tomado ya el foco (p. ej. con `autoFocus`),
// pasa al diálogo; Tab y Shift+Tab no salen de él; Escape lo cierra; y al cerrarlo o desmontarlo el foco
// vuelve al elemento que lo abrió (o a `returnFocusRef`, si ese elemento ya no existe al cerrar).
// El disparador se lee durante el render, al pasar de cerrado a abierto, porque un `autoFocus` del contenido
// mueve el foco antes de que se ejecute cualquier efecto y `document.activeElement` ya sería otro.
export const useDialogFocus = ({ isOpen, onClose, returnFocusRef }) => {
  const dialogRef = useRef(null);
  const triggerRef = useRef(null);
  const wasOpenRef = useRef(false);

  if (isOpen && !wasOpenRef.current) triggerRef.current = document.activeElement;
  wasOpenRef.current = isOpen;

  useEffect(() => {
    if (!isOpen) return undefined;

    const dialog = dialogRef.current;
    if (dialog && !dialog.contains(document.activeElement)) dialog.focus();

    return () => {
      const target = returnFocusRef?.current ?? triggerRef.current;
      if (target?.isConnected) target.focus();
    };
    // `returnFocusRef` es un ref estable: no debe reabrir el efecto.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen]);

  const handleKeyDown = (event) => {
    if (event.key === 'Escape') {
      // Evita que el Escape llegue a oyentes globales de la página (p. ej. "volver" en /votar).
      event.stopPropagation();
      onClose();
      return;
    }

    if (event.key !== 'Tab') return;

    const dialog = dialogRef.current;
    if (!dialog) return;

    const focusables = [...dialog.querySelectorAll(FOCUSABLE_SELECTOR)].filter(
      (element) => element.getClientRects().length > 0,
    );
    if (focusables.length === 0) {
      event.preventDefault();
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || active === dialog)) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  };

  return { dialogRef, handleKeyDown };
};
