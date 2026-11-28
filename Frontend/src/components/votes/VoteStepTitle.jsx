import React, { useEffect, useRef } from 'react';
import { twMerge } from '../../lib/twMerge.js';

// Pregunta de cada paso de la votación. El botón pulsado desaparece al cambiar de paso: con `autoFocus` el foco pasa
// a la pregunta, así se anuncia y el teclado sigue dentro de la caja.
const VoteStepTitle = ({ children, autoFocus = false, className }) => {
  const ref = useRef(null);

  useEffect(() => {
    const title = ref.current;
    if (!autoFocus || !title) return;
    const active = document.activeElement;
    const box = title.closest('[data-vote-question]');
    if (active === document.body || (box && box.contains(active))) {
      title.focus({ preventScroll: true });
    }
  }, [autoFocus]);

  return (
    <h2
      ref={ref}
      tabIndex={-1}
      className={twMerge('mb-4 font-display text-2xl font-semibold leading-tight text-ink focus:outline-none', className)}
    >
      {children}
    </h2>
  );
};

export default VoteStepTitle;
