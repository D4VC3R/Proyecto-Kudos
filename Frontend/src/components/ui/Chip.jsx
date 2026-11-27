import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

// Etiqueta de solo lectura (atributos de un ítem: género, año, país…).
const Chip = ({ className, children, ...props }) => (
  <span
    className={twMerge(clsx(
      'inline-flex items-center whitespace-nowrap rounded-full border border-line px-2 py-0.5 text-caption text-ink-muted',
      className,
    ))}
    {...props}
  >
    {children}
  </span>
);

export default Chip;
