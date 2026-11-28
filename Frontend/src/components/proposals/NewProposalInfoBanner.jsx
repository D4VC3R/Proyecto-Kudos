import React from 'react';
import { Star } from 'lucide-react';
import Card from '../ui/Card.jsx';

const NewProposalInfoBanner = () => {
  return (
    <Card className="mt-8">
      <h2 className="mb-1 flex items-center gap-2 font-display text-h3 text-ink">
        <Star size={18} aria-hidden="true" className="text-kudos" />
        ¡Gana kudos extra!
      </h2>
      <p className="text-small text-ink-muted">
        Por cada propuesta que un administrador acepte, se sumarán kudos a tu contador.
      </p>
    </Card>
  );
};

export default NewProposalInfoBanner;
