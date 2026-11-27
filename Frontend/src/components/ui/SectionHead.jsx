import React from 'react';
import { Link } from 'react-router-dom';
import Button from './Button.jsx';

// Cabecera de una sección dentro de una página, con un enlace opcional a "Ver todo".
const SectionHead = ({ id, title, as: Heading = 'h2', moreTo, moreLabel = 'Ver todo', children }) => (
  <div className="flex items-baseline justify-between gap-3 border-t border-rule pt-3">
    <Heading id={id} className="font-display text-h2 text-ink">{title}</Heading>
    {(moreTo || children) && (
      <div className="flex items-center gap-2">
        {moreTo && (
          <Button as={Link} to={moreTo} variant="link">
            {moreLabel}
            <span className="sr-only">: {title}</span>
          </Button>
        )}
        {children}
      </div>
    )}
  </div>
);

export default SectionHead;
