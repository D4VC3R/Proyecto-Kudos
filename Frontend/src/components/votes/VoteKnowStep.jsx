import React from 'react';
import { Check, X } from 'lucide-react';
import Button from '../ui/Button.jsx';

// Paso 1 de la votación (F2-04): "¿Lo conoces?". Sí lleva a valorar; No, a preguntar por el interés.
const VoteKnowStep = ({ onYes, onNo, isPending }) => {
  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        ¿Lo conoces?
      </h4>

      <div className="grid grid-cols-2 gap-3 w-full max-w-md">
        <Button
          onClick={onNo}
          disabled={isPending}
          variant="ghost"
          color="neutral"
          size="lg"
          icon={X}
          className="py-4 text-lg"
        >
          No
          <kbd className="hidden sm:inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-xs font-bold">N</kbd>
        </Button>
        <Button
          onClick={onYes}
          disabled={isPending}
          variant="solid"
          color="primary"
          size="lg"
          icon={Check}
          className="py-4 text-lg"
        >
          Sí
          <kbd className="hidden sm:inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-xs font-bold">S</kbd>
        </Button>
      </div>

      <p className="text-xs text-slate-400 mt-4 font-medium sm:hidden">Desliza la tarjeta: a la derecha, Sí; a la izquierda, No</p>
      <p className="hidden sm:block text-xs text-slate-400 mt-4 font-medium">Atajos: S = Sí, N = No</p>
    </div>
  );
};

export default VoteKnowStep;
