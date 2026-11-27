import { useEffect } from 'react';

// Sin los de `tabindex="-1"`: no entran por Tab (p. ej. los radios no activos de un grupo), así que no pueden ser los extremos.
const FOCUSABLE = 'a[href]:not([tabindex="-1"]), button:not([disabled]):not([tabindex="-1"]), input:not([disabled]):not([tabindex="-1"]), [tabindex]:not([tabindex="-1"])';

// Mantiene el Tab dentro de `containerRef` mientras `isOpen`: del último enfocable salta al primero y, con Shift,
// del primero al último. No mueve el foco al abrir: quien abre el contenedor (el ☰) forma parte del ciclo.
export const useFocusTrap = ({ isOpen, containerRef }) => {
  useEffect(() => {
    if (!isOpen) return undefined;

    const handleKeyDown = (event) => {
      if (event.key !== 'Tab' || !containerRef.current) return;

      const focusables = [...containerRef.current.querySelectorAll(FOCUSABLE)].filter((el) => el.getClientRects().length > 0);
      if (focusables.length === 0) return;

      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      const active = document.activeElement;

      if (!containerRef.current.contains(active)) {
        event.preventDefault();
        first.focus();
      } else if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, containerRef]);
};
