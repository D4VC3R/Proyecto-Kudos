import React from 'react';
import { ArrowLeft, BookmarkPlus, EyeOff } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import VoteStepTitle from './VoteStepTitle.jsx';
import { KBD } from './voteStyles.js';

// Paso 2b de la votación (F2-04): si no lo conoces, ¿te interesa? (wishlist o "no me interesa").
const VoteInterestStep = ({ onWant, onNotInterested, onBack, isPending, autoFocus = false }) => {
  return (
    <div className="animate-rise">
      <VoteStepTitle autoFocus={autoFocus}>¿Te interesa?</VoteStepTitle>

      <div className="grid grid-cols-2 gap-2.5">
        <Button onClick={onWant} disabled={isPending} variant="primary" size="lg" icon={BookmarkPlus}>
          Me interesa
          <kbd className={clsx(KBD, 'hidden sm:inline-block')}>1</kbd>
        </Button>
        <Button onClick={onNotInterested} disabled={isPending} variant="secondary" size="lg" icon={EyeOff}>
          No me interesa
          <kbd className={clsx(KBD, 'hidden sm:inline-block')}>2</kbd>
        </Button>
      </div>

      {onBack && (
        <Button onClick={onBack} variant="ghost" icon={ArrowLeft} className="mt-3">
          Volver
          <kbd className={clsx(KBD, 'hidden sm:inline-block')}>Esc</kbd>
        </Button>
      )}
    </div>
  );
};

export default VoteInterestStep;
