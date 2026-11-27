import React, { useId } from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { FIELD_ERROR, FIELD_LABEL, fieldControlClass } from './fieldStyles.js';

// Campo de texto con etiqueta, icono opcional y mensaje de error. `className` es del contenedor e
// `inputClassName` del control. Con `hideLabel` la etiqueta solo la leen los lectores de pantalla.
const Input = ({
  label,
  hideLabel = false,
  labelEnd,
  icon: Icon,
  type = 'text',
  placeholder,
  registration,
  error,
  disabled,
  className,
  inputClassName,
  ...props
}) => {
  const generatedId = useId();
  const fieldId = props.id ?? generatedId;
  const errorId = `${fieldId}-error`;
  const showLabel = label && !hideLabel;

  return (
    <div className={twMerge(clsx(className))}>
      {label && hideLabel && <label htmlFor={fieldId} className="sr-only">{label}</label>}
      {(showLabel || labelEnd) && (
        <div className="mb-2 flex items-center justify-between">
          {showLabel ? <label htmlFor={fieldId} className={FIELD_LABEL}>{label}</label> : <span />}
          {labelEnd}
        </div>
      )}
      <div className="relative">
        {Icon && (
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
            <Icon size={18} />
          </div>
        )}
        <input
          id={fieldId}
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={error ? errorId : undefined}
          type={type}
          disabled={disabled}
          placeholder={placeholder}
          {...(registration || {})}
          {...props}
          className={fieldControlClass({ hasIcon: !!Icon, hasError: !!error, className: clsx('h-10', inputClassName) })}
        />
      </div>
      {error && <span id={errorId} className={FIELD_ERROR}>{error.message || error}</span>}
    </div>
  );
};

export default Input;
