import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

// Estilo único de los campos de formulario (Input, Textarea y Select): etiqueta, error y control.
export const FIELD_LABEL = 'text-small font-semibold text-ink';
export const FIELD_ERROR = 'mt-1 block text-caption text-danger';

export const fieldControlClass = ({ hasIcon = false, hasError = false, className } = {}) => twMerge(clsx(
  'block w-full rounded-sm border border-line-strong bg-surface pr-3 text-body text-ink placeholder:text-ink-muted transition-colors disabled:cursor-not-allowed disabled:opacity-50',
  // Foco común de todos los controles.
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
  hasIcon ? 'pl-9' : 'pl-3',
  hasError && 'border-danger',
  className,
));
