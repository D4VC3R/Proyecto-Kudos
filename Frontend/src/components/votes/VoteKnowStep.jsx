import React from 'react';
import { Check, X } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import VoteStepTitle from './VoteStepTitle.jsx';
import { KBD } from './voteStyles.js';

// Paso 1 de la votación (F2-04): "¿Lo conoces?". Sí lleva a valorar; No, a preguntar por el interés.
const VoteKnowStep = ({ onYes, onNo, isPending, autoFocus = false }) => {
  return (
    <div className="animate-rise">
      <VoteStepTitle autoFocus={autoFocus}>¿Lo conoces?</VoteStepTitle>

      <div className="grid grid-cols-2 gap-2.5">
        <Button onClick={onNo} disabled={isPending} variant="secondary" size="lg" icon={X}>
          No
          <kbd className={clsx(KBD, 'hidden sm:inline-block')}>N</kbd>
        </Button>
        <Button onClick={onYes} disabled={isPending} variant="primary" size="lg" icon={Check}>
          Sí
          <kbd className={clsx(KBD, 'hidden sm:inline-block')}>S</kbd>
        </Button>
      </div>

      <p className="mt-3 text-caption text-ink-muted sm:hidden">Desliza la tarjeta: a la derecha, Sí; a la izquierda, No</p>
      <p className="mt-3 hidden text-caption text-ink-muted sm:block">Atajos: S = Sí, N = No</p>
    </div>
  );
};

export default VoteKnowStep;
