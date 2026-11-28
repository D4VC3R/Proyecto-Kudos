import React from 'react';
import { Link } from 'react-router-dom';
import { Star } from 'lucide-react';
import Button from '../ui/Button.jsx';

const EmptyVoteState = () => {
  return (
    <div className="mx-auto flex min-h-state max-w-reading flex-col items-center justify-center text-center animate-rise">
      <div className="mb-6 grid h-20 w-20 place-items-center rounded-full border-2 border-kudos text-kudos">
        <Star size={40} aria-hidden="true" />
      </div>
      <h1 className="mb-2 font-display text-h1 text-ink">¡Todo al día!</h1>
      <p className="mb-8 text-ink-muted">No tenemos nada más que ofrecerte aquí, ¿Por qué no exploras otra categoría?.</p>
      <Button as={Link} to="/" variant="primary">
        Seleccionar nueva categoría
      </Button>
    </div>
  );
};

export default EmptyVoteState;
