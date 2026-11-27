import React, { useId } from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { FIELD_ERROR, FIELD_LABEL, fieldControlClass } from './fieldStyles.js';

// Área de texto con el mismo estilo y API que Input.
const Textarea = ({
  label,
  hideLabel = false,
  labelEnd,
  icon: Icon,
  rows = 4,
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
          <div className="pointer-events-none absolute left-0 top-3 flex pl-3 text-ink-muted">
            <Icon size={18} />
          </div>
        )}
        <textarea
          id={fieldId}
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={error ? errorId : undefined}
          rows={rows}
          disabled={disabled}
          placeholder={placeholder}
          {...(registration || {})}
          {...props}
          className={fieldControlClass({ hasIcon: !!Icon, hasError: !!error, className: clsx('py-3 resize-y', inputClassName) })}
        />
      </div>
      {error && <span id={errorId} className={FIELD_ERROR}>{error.message || error}</span>}
    </div>
  );
};

export default Textarea;
