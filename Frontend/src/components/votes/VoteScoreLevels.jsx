import React from 'react';
import { ArrowLeft } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import { VOTE_LEVELS } from '../../lib/constants.js';

// Paso 2a de la votación (F2-04): cinco niveles de valoración. Reutilizable fuera de la página de votar (F2-05):
// `showKeys` oculta las teclas donde no hay atajos y `stacked` mantiene la columna también en escritorio (p. ej. dentro de un modal).
const VoteScoreLevels = ({ onRate, onBack, isPending, showKeys = true, stacked = false }) => {
  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        ¿Qué te ha parecido?
      </h4>

      <div className={clsx('flex flex-col-reverse gap-2 w-full', !stacked && 'sm:grid sm:grid-cols-5')}>
        {VOTE_LEVELS.map((level) => (
          <Button
            key={level.score}
            onClick={() => onRate(level.score)}
            disabled={isPending}
            variant="ghost"
            color="primary"
            size="lg"
            className="py-4 text-base"
          >
            {level.label}
            {showKeys && (
              <kbd className="hidden sm:inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-xs font-bold">{level.key}</kbd>
            )}
          </Button>
        ))}
      </div>

      {onBack && (
        <Button
          onClick={onBack}
          title="Volver (Esc)"
          variant="ghost"
          color="neutral"
          size="sm"
          icon={ArrowLeft}
          className="mt-3"
        >
          Volver
        </Button>
      )}
    </div>
  );
};

export default VoteScoreLevels;
