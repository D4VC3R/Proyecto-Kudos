import React from 'react';
import { Link } from 'react-router-dom';
import Card from './Card.jsx';

// Acceso a una acción de la categoría: una tarjeta sobria que es toda un enlace (una sola parada de Tab).
const ActionButton = ({ title, description, icon: Icon, to }) => (
  <Card as={Link} to={to} variant="interactive" className="flex h-full items-start gap-3">
    <Icon size={22} className="mt-0.5 shrink-0 text-cat-icon" aria-hidden="true" />
    <span className="flex min-w-0 flex-col gap-1">
      <span className="font-semibold text-ink">{title}</span>
      <span className="text-small text-ink-muted">{description}</span>
    </span>
  </Card>
);

export default ActionButton;
