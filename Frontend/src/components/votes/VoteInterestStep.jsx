import React from 'react';
import { ArrowLeft, BookmarkPlus, EyeOff } from 'lucide-react';
import Button from '../ui/Button.jsx';

// Paso 2b de la votación (F2-04): si no lo conoces, ¿te interesa? (wishlist o "no me interesa").
const VoteInterestStep = ({ onWant, onNotInterested, onBack, isPending }) => {
  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        ¿Te interesa?
      </h4>

      <div className="grid grid-cols-2 gap-3 w-full max-w-md">
        <Button
          onClick={onWant}
          disabled={isPending}
          variant="solid"
          color="primary"
          size="lg"
          icon={BookmarkPlus}
          className="py-4 text-lg"
        >
          Me interesa
          <kbd className="hidden sm:inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-xs font-bold">1</kbd>
        </Button>
        <Button
          onClick={onNotInterested}
          disabled={isPending}
          variant="ghost"
          color="neutral"
          size="lg"
          icon={EyeOff}
          className="py-4 text-lg"
        >
          No me interesa
          <kbd className="hidden sm:inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-xs font-bold">2</kbd>
        </Button>
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

export default VoteInterestStep;
