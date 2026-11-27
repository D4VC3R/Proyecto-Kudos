import React, { useId } from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { FIELD_ERROR, FIELD_LABEL, fieldControlClass } from './fieldStyles.js';

// Desplegable con el mismo estilo que Input. `className` es del contenedor y `selectClassName` del control.
// Con `placeholder` se añade la opción vacía (`value=""`).
const Select = ({
  label,
  hideLabel = false,
  options = [],
  placeholder,
  icon: Icon,
  registration,
  error,
  className,
  selectClassName,
  ...props
}) => {
  const generatedId = useId();
  const fieldId = props.id ?? generatedId;
  const errorId = `${fieldId}-error`;

  return (
    <div className={twMerge(clsx(className))}>
      {label && (
        <label htmlFor={fieldId} className={hideLabel ? 'sr-only' : clsx('mb-2 block', FIELD_LABEL)}>{label}</label>
      )}
      <div className="relative">
        {Icon && (
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
            <Icon size={18} />
          </div>
        )}
        <select
          id={fieldId}
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={error ? errorId : undefined}
          {...(registration || {})}
          {...props}
          className={fieldControlClass({ hasIcon: !!Icon, hasError: !!error, className: clsx('h-10 pr-8', selectClassName) })}
        >
          {placeholder !== undefined && <option value="">{placeholder}</option>}
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>
      {error && <span id={errorId} className={FIELD_ERROR}>{error.message || error}</span>}
    </div>
  );
};

export default Select;
