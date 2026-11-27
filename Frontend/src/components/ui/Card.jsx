import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

// Contenedor con borde. `interactive` es para tarjetas enteras que son un enlace o un botón (`as={Link}`).
const VARIANTS = {
  outline: 'rounded-md border border-line bg-surface p-4',
  interactive: 'rounded-md border border-line bg-surface p-4 block transition-colors hover:border-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
};

const Card = ({ as: Component = 'div', variant = 'outline', className, ...props }) => (
  <Component className={twMerge(clsx(VARIANTS[variant] || VARIANTS.outline, className))} {...props} />
);

export default Card;
