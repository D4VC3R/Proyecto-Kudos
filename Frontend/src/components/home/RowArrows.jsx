import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { ICON_BUTTON } from '../layout/headerStyles.js';

const ARROW = `${ICON_BUTTON} disabled:cursor-default disabled:border-transparent disabled:text-line-strong`;

// Flechas de una fila horizontal; solo desde `md`, porque en móvil se desliza con el dedo.
const RowArrows = ({ controls, atStart, atEnd, scrollByPage }) => (
  <div data-row-arrows className="hidden gap-1 md:flex">
    <button type="button" aria-label="Anteriores" aria-controls={controls} disabled={atStart} onClick={() => scrollByPage(-1)} className={ARROW}>
      <ChevronLeft size={20} aria-hidden="true" />
    </button>
    <button type="button" aria-label="Siguientes" aria-controls={controls} disabled={atEnd} onClick={() => scrollByPage(1)} className={ARROW}>
      <ChevronRight size={20} aria-hidden="true" />
    </button>
  </div>
);

export default RowArrows;
