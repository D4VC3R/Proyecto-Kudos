import React from 'react';
import { ArrowLeft } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import VoteStepTitle from './VoteStepTitle.jsx';
import { KBD } from './voteStyles.js';
import { VOTE_LEVELS } from '../../lib/constants.js';

// Paso 2a de la votación (F2-04): cinco niveles de valoración. Reutilizable fuera de la página de votar (F2-05):
// `showKeys` oculta las teclas donde no hay atajos y `stacked` mantiene la columna también en escritorio (p. ej. dentro de un modal).
const VoteScoreLevels = ({ onRate, onBack, isPending, showKeys = true, stacked = false, autoFocus = false }) => {
  return (
    <div className="animate-rise">
      <VoteStepTitle autoFocus={autoFocus}>¿Qué te ha parecido?</VoteStepTitle>

      <div className={clsx('flex flex-col-reverse gap-2', !stacked && 'lg:grid lg:grid-cols-5')}>
        {VOTE_LEVELS.map((level, index) => (
          <Button
            key={level.score}
            onClick={() => onRate(level.score)}
            disabled={isPending}
            variant="secondary"
            size="lg"
            className={clsx(
              'justify-between',
              !stacked && 'lg:min-h-24 lg:flex-col lg:justify-center lg:gap-2 lg:px-2 lg:py-3',
            )}
          >
            <span aria-hidden="true" className="inline-flex gap-1">
              {[0, 1, 2, 3, 4].map((d) => (
                <i
                  key={d}
                  className={clsx('h-2 w-2 rounded-full border border-current', d <= index && 'bg-current')}
                />
              ))}
            </span>
            <span className={clsx('flex-1 text-left', !stacked && 'lg:flex-none lg:text-center')}>{level.label}</span>
            {showKeys && <kbd className={clsx(KBD, 'hidden sm:inline-block')}>{level.key}</kbd>}
          </Button>
        ))}
      </div>

      {onBack && (
        <Button onClick={onBack} variant="ghost" icon={ArrowLeft} className="mt-3">
          Volver
          {showKeys && <kbd className={clsx(KBD, 'hidden sm:inline-block')}>Esc</kbd>}
        </Button>
      )}
    </div>
  );
};

export default VoteScoreLevels;
