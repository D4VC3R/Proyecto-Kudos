import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { Check } from 'lucide-react';
import { FIELD_ERROR } from './fieldStyles.js';

// Base común de Checkbox y Radio: control nativo (accesible con teclado y lectores) sin su aspecto, con la
// marca dibujada encima. `type` es `checkbox` o `radio`.
const ChoiceField = ({ type, label, description, registration, error, className, ...props }) => {
  const isRadio = type === 'radio';

  return (
    <div className={twMerge(clsx(className))}>
      <label className="flex cursor-pointer items-start gap-2 text-small text-ink">
        <span className="relative mt-0.5 inline-flex h-4 w-4 shrink-0">
          <input
            type={type}
            aria-invalid={error ? 'true' : undefined}
            {...(registration || {})}
            {...props}
            className={clsx(
              'peer h-4 w-4 appearance-none border border-line-strong bg-surface checked:border-accent checked:bg-accent',
              'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
              'disabled:cursor-not-allowed disabled:opacity-50',
              isRadio ? 'rounded-full' : 'rounded',
            )}
          />
          {isRadio ? (
            <span aria-hidden="true" className="pointer-events-none absolute inset-0 m-auto hidden h-1.5 w-1.5 rounded-full bg-on-accent peer-checked:block" />
          ) : (
            <Check aria-hidden="true" size={12} className="pointer-events-none absolute inset-0 m-auto hidden text-on-accent peer-checked:block" />
          )}
        </span>
        {description ? (
          <span className="flex flex-col">
            <span className="font-semibold">{label}</span>
            <span className="text-ink-muted">{description}</span>
          </span>
        ) : (
          label
        )}
      </label>
      {error && <span className={FIELD_ERROR}>{error.message || error}</span>}
    </div>
  );
};

export default ChoiceField;
