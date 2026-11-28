import React from 'react';
import { Check } from 'lucide-react';
import KudosBadge from '../ui/KudosBadge.jsx';
import VoteStepTitle from './VoteStepTitle.jsx';

// Panel que sustituye a la pregunta cuando el voto ya se ha guardado, hasta que llega el siguiente ítem.
const VoteDoneStep = ({ kudosAwarded, autoFocus = false }) => {
  return (
    <div className="flex min-h-28 items-center gap-3.5 animate-rise">
      <span className="grid h-12 w-12 shrink-0 place-items-center rounded-full border-2 border-success text-success">
        <Check size={24} aria-hidden="true" className="animate-draw" />
      </span>
      <div>
        <VoteStepTitle autoFocus={autoFocus} className="mb-1">¡Hecho!</VoteStepTitle>
        <p className="text-small text-ink-muted">
          {kudosAwarded > 0 ? (
            <>Tu voto suma <KudosBadge value={kudosAwarded}>+{kudosAwarded}</KudosBadge></>
          ) : (
            'Tu respuesta se ha guardado.'
          )}
        </p>
      </div>
    </div>
  );
};

export default VoteDoneStep;
